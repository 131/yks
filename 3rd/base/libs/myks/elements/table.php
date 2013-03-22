<?php



abstract class table_base  extends myks_installer {
  protected $sql_type = 'TABLE';

  protected $escape_char="`";

  protected $table_name;

  protected $keys_xml_def   = array();
  protected $fields_xml_def = array();

  protected $keys_sql_def   = array();
  protected $fields_sql_def = array();
  private $abstract;


  static $fk_actions_in = array('NO ACTION'=>'no_action', 'CASCADE'=> 'cascade', 'SET NULL'=>'set_null');
  static $fk_actions_out = array('no_action'=>'NO ACTION', 'cascade'=>'CASCADE','set_null'=> 'SET NULL');

  protected $key_mask=array("PRIMARY"=>"PRIMARY KEY","INDEX"=>"INDEX `%s`","UNIQUE"=>"UNIQUE `%s`");

  protected $key_update=array("PRIMARY"=>"PRIMARY KEY", "UNIQUE"=>"UNIQUE ");

  private $tmp_key;

  protected $keys_name = array(        // $this->table_name, $field, $type
    'PRIMARY'=>"%s_pkey",
    'UNIQUE'=>"%s_%s_%s",
    'FOREIGN'=>"%s_%s_%s",
  );

  public $comment_xml;

  function get_name(){
    return $this->table_name;
  }

  function delete_def(){
    return array(
        "DROP TABLE {$this->table_name['safe']}"
    );
  }

  function __construct($table_xml){
    $this->xml = $table_xml;
    $this->table_name = sql::resolve( (string) $table_xml['name']);

    $this->keys_def=array();

    if($this->xml->abstract) {
        $abstract = $this->xml->abstract;
        if($abstract['type'] == "tree_integral")
            $this->abstract = new tree_integral($this, $abstract);
        else $this->abstract = new materialized_view($this, $abstract);
    }

  }

  protected function table_where(){
    return array(
        'table_name'   => $this->table_name['name'],
        'table_schema' => $this->table_name['schema'],
    );
  }


  function alter_def(){

    if(in_array($this->table_name['hash'], myks_gen::$tables_ghosts_views)) {
        rbx::ok("-- Double sync from view {$this->table_name['hash']}, skipping");
        return array();
    }

    if(!$this->fields_sql_def)
        return $this->create();

    if(!$this->modified())
        return array();

    //print_r(array_show_diff($this->fields_sql_def, $this->fields_xml_def,"sql","xml"));die;
    //print_r(array_show_diff($this->keys_sql_def, $this->keys_xml_def,"sql","xml" ));die;
    //print_r($this->privileges);die;

    $todo = array_merge(
        $this->alter_fields(),
        $this->alter_keys()
    );

    if($this->abstract){
        $todo = array_merge($todo, $this->abstract->alter_def());
    }

    return $todo;

    if(!$todo)
        throw rbx::error("Error while looking for differences in {$this->table_name['name']}");
    $todo = array_map(array('sql', 'unfix'), $todo);
    return $todo;
  }


  function modified(){
    $modified = $this->fields_xml_def != $this->fields_sql_def;
    $modified |= $this->keys_xml_def != $this->keys_sql_def;
    $modified |= $this->comment_raw != $this->comment_new;

    if($this->abstract)
        $modified |= $this->abstract->modified();

    return $modified;
  }


/*
    populate fields_sql_def and keys_sql_def definition based on the SQL structure
    return (boolean) whereas this table already exists (alter mode) or not (create mode)
*/

  public function sql_infos(){

    if($this->fields_sql_def)
        return; //already processed

    $this->sql = sql::row("information_schema.tables", $this->table_where());
        //load comment
   $where = sql::where( array(
        "c.relkind" => "r",
        "c.relname" => $this->table_name['name'],
        "n.nspname" => $this->table_name['schema'],
    ));
    $query = "SELECT
        n.nspname             AS schema_name,
        c.relname             AS table_name,
        pg_get_viewdef(c.oid) AS compiled_definition,
        d.description         AS full_description
      FROM
        pg_class AS c
        LEFT JOIN pg_namespace AS n ON n.oid = c.relnamespace
        LEFT JOIN pg_description AS d ON c.relfilenode = d.objoid
      $where;
    ";
    $this->comment_raw = sql::qvalue($query, 'full_description');
    $this->comment_xml = @simplexml_load_string(XML_VERSION."<comment>{$this->comment_raw}</comment>");


    //$len=mb_strlen($str);$start=mb_strpos($str,"<body>")+6;$end=mb_strpos($str,"</body>");
//echo ;die;
    if(!$this->sql)
        return ;

    $this->fields_sql_def = $this->table_fields();
    $this->keys_sql_def   = $this->table_keys();

    if($this->abstract)
        $this->abstract->sql_infos();

  }

  protected function get_comment_new(){
    $str = strip_start($this->comment_xml->asXML(), XML_VERSION);
    $str = preg_reduce("#^<comment>(.*?)</comment>$#e", $str);
    return $str;
  }

  public function save_comment(){
    if($this->comment_raw == $this->comment_new) return; //ras
    $comment_sql = sprintf("COMMENT ON %s %s IS %s",
            $this->sql_type,
            $this->table_name['safe'],
            sprintf("E'%s'", addslashes($this->comment_new)));
    return array($comment_sql);
 }

/*
    populate fields_xml_def and keys_xml_def definition based on the xml structure
*/

  function xml_infos(){
    foreach($this->xml->fields->field as $field_xml){
        $mykse=new mykse($field_xml,$this);
        $this->fields_xml_def[$mykse->field_def['Field']] = $mykse->field_def;
    }
    if($this->abstract)
        $this->abstract->xml_infos();

  }

  function key_add($type, $field, $refs=array()){$TYPE=strtoupper($type);
    $key_name = sprintf($this->keys_name[$TYPE], $this->table_name['name'], $field, $type);
    $key_name = substr($key_name, 0, 63);

    if($TYPE=="PRIMARY"){
        $this->keys_xml_def[$key_name]['type'] = $TYPE;
        $this->keys_xml_def[$key_name]['members'][$field] = $field;
    } elseif($TYPE=="UNIQUE"){
        $this->keys_xml_def[$key_name]['type'] = $TYPE;

        $this->keys_xml_def[$key_name]['members'] = &$this->tmp_key[$field];
        $this->tmp_key[$field][$field] = $field;
    } elseif($TYPE == "FOREIGN" && SQL_DRIVER == "pgsql"){

        $this->keys_xml_def[$key_name]['type'] = $TYPE;
        $this->keys_xml_def[$key_name]['members'] = &$this->tmp_key[$key_name];
        $this->tmp_key[$key_name][$field] = $field;

        $this->keys_xml_def[$key_name]=array_merge($this->keys_xml_def[$key_name],$refs);
    } else {
        $this->tmp_key[$type][$field]=$field;
    }

  }


  function alter_fields() {
    $ec = $this->escape_char;

    $drop_columns = array();

    $table_alter = "ALTER TABLE {$this->table_name['safe']} ";
    $todo = array();
    //fields sync
    foreach($this->fields_xml_def as $field_name=>$field_xml){
        $field_sql = $this->fields_sql_def[$field_name];
        if($field_sql){
            unset($this->fields_sql_def[$field_name]);
            if($field_sql==$field_xml) continue;

            $diff = array_diff_assoc($field_xml,$field_sql);
            foreach($diff as $diff_type=>$new_value){
                if($diff_type=="Null"){
                    if(!$new_value && !is_null($field_xml['Default']) && sql::row($this->table_name['raw'], array($field_name=>null))  )
                        $todo[] = "UPDATE {$this->table_name['safe']} "
                            ."SET {$ec}$field_name{$ec}={$field_xml['Default']} WHERE {$ec}$field_name{$ec} IS NULL";
                    $todo[] = "$table_alter ALTER COLUMN {$ec}$field_name{$ec} "
                              .($new_value?"DROP NOT NULL":"SET NOT NULL");
                } elseif($diff_type == "Type"){
                    $todo[] = "$table_alter ALTER COLUMN {$ec}$field_name{$ec} TYPE $new_value";
                    $drop_columns[] = $field_name;
                } elseif($diff_type == "Default"){
                    $value="SET DEFAULT $new_value";
                    if(is_null($new_value))$value="DROP DEFAULT";
                    $todo[] = "$table_alter ALTER COLUMN {$ec}$field_name{$ec} $value";
                } else { rbx::error("-- UNKNOW type of diff : $diff_type"); }
            }
        } else { //ajout de colonne
            $todo[] = "$table_alter ADD COLUMN {$ec}$field_name{$ec} {$field_xml['Type']}";
            if(!is_null($field_xml['Default'])){
                $todo[] = "$table_alter ALTER COLUMN {$ec}$field_name{$ec} "
                          ." SET DEFAULT {$field_xml['Default']}";
                $todo[] = "UPDATE {$this->table_name['safe']} SET {$ec}$field_name{$ec}={$field_xml['Default']}";
            }
            $todo[] = "$table_alter ALTER COLUMN {$ec}$field_name{$ec} "
                .($field_xml['Null']?"DROP NOT NULL":"SET NOT NULL");
        }

    } foreach(array_keys($this->fields_sql_def) as $field_name) {
        $todo[]="$table_alter DROP {$ec}$field_name{$ec}";
        $drop_columns[] = $field_name;
    }

    $drop_views = $this->drop_views_from_altered_columns($drop_columns);
    $todo = array_merge( $drop_views, $todo);
    return $todo;
  }

  private function drop_views_from_altered_columns($columns){
    $where = $this->table_where();
    $where['column_name'] = $columns;
    sql::select("information_schema.view_column_usage", $where, "DISTINCT view_schema, view_name");
    $views_list = sql::brute_fetch();
    $ec = $this->escape_char;
    $drops  = array();
    foreach($views_list as $view) {
        $drops[] = "DROP VIEW IF EXISTS $ec{$view['view_schema']}$ec.$ec{$view['view_name']}$ec CASCADE";
    }
    return $drops;
  }

  function alter_keys(){
    $ec = $this->escape_char;
    $table_alter = "ALTER TABLE {$this->table_name['safe']} ";
    $todo = array();
    if($this->keys_xml_def == $this->keys_sql_def) return $todo;

    foreach($this->keys_sql_def as $key=>$def){
        if($this->keys_xml_def[$key] != $def)
            array_unshift($todo, $drop = "$table_alter DROP ".
                (($def['type']=="PRIMARY" || $def['type']=="FOREIGN"|| $def['type']=="UNIQUE")?
                    "CONSTRAINT {$ec}$key{$ec}"
                    :"INDEX {$ec}$key{$ec}") );
        else unset($this->keys_xml_def[$key]);
    }

    foreach($this->keys_xml_def as $key=>$def){
        $members=' ("'.join('","',$def['members']).'")';$type=$def['type'];
        $add = "ADD CONSTRAINT $key ".$this->key_mask[$type]." $members ";
        if($type=="INDEX") { $todo[]="CREATE INDEX $key ON {$this->table_name['safe']} $members";continue;}
        elseif($type=="FOREIGN"){
            $add.=" REFERENCES ".table::output_ref($def['refs'])." ";
            if($def['delete']) $add.=" ON DELETE ".table::$fk_actions_out[$def['delete']];
            if($def['update']) $add.=" ON UPDATE ".self::$fk_actions_out[$def['update']];
            if($def['defer']=='defer') $add.=" DEFERRABLE INITIALLY DEFERRED";
        } $todo[]="$table_alter $add";
    }
    return $todo;
  }


  protected function table_keys(){
    $where = $this->table_where();
    $cols = 'constraint_catalog, constraint_schema, constraint_name, table_schema, table_name, constraint_type';
    if(SQL_DRIVER=="pgsql") $cols.=",is_deferrable";
    sql::select("information_schema.table_constraints", $where, $cols);

    $keys = sql::brute_fetch('constraint_name');$table_keys=array();
    $keys = array_map('array_change_key_case', $keys);

    $usages=array(); $behavior=array();

    $where['constraint_name']   = array_keys($keys);


    if(SQL_DRIVER=="pgsql") $order ="ORDER BY position_in_unique_constraint ASC";
    sql::select("information_schema.key_column_usage", $where, "constraint_name,column_name", $order);
    while($l=sql::fetch())
        $table_keys[$l['constraint_name']]['members'][$l['column_name']]=$l['column_name'];
            //une clée est basé sur au moins UNE colonne ( élimine les checks )

    $verif_contraints = array(
        'constraint_name'   => array_keys($table_keys),
        'constraint_schema' => $this->table_name['schema'],
    );

    if(SQL_DRIVER=="pgsql"){ ///FOREIGN_KEYS
        sql::select("information_schema.constraint_column_usage", $verif_contraints );
        while($l=sql::fetch())
            $usages[$l['constraint_name']][$l['table_schema']][$l['table_name']][] = $l['column_name'];
                //="{$l['table_name']}({$l['column_name']})";
        sql::select("information_schema.referential_constraints", $verif_contraints );
        $behavior = sql::brute_fetch('constraint_name');
    }


    foreach($table_keys as $constraint_name=>&$constraint_infos){
        $key=$keys[$constraint_name];
        $types=array('PRIMARY KEY'=>'PRIMARY','FOREIGN KEY'=>'FOREIGN','UNIQUE'=>'UNIQUE','INDEX'=>'INDEX');

        $constraint_infos['type']=$type=$types[$key['constraint_type']];
        if($type=="FOREIGN") {

            list($usage_schema, $usage_fields) = each($usages[$constraint_name]);
            list($usage_table, $usage_fields)  = each($usage_fields);


            $constraint_infos['table']  = $usage_table;
            $constraint_infos['update'] = table::$fk_actions_in[$behavior[$constraint_name]['update_rule']];
            $constraint_infos['delete'] = table::$fk_actions_in[$behavior[$constraint_name]['delete_rule']];
            $constraint_infos['refs']   = table::build_ref($usage_schema, $usage_table, $usage_fields);
            $constraint_infos['defer']  = bool($key['is_deferrable'])&&bool($key['is_deferrable'])?'defer':'strict';

        }
    }
    return $table_keys;
 }

 public static function build_ref($table_schema, $table_name, $table_fields){
    return compact('table_schema', 'table_name', 'table_fields');
 }

 public static function output_ref($ref){
    return  sprintf('"%s"."%s"(%s)',
        $ref['table_schema'],
        $ref['table_name'],
        join(',',$ref['table_fields']) );
 }

}

