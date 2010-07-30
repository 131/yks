<?php

class table extends table_base {

  public $tmp_refs=array();
  static $fk_actions_in = array('NO ACTION'=>'no_action', 'CASCADE'=> 'cascade', 'SET NULL'=>'set_null');
  static $fk_actions_out = array('no_action'=>'NO ACTION', 'cascade'=>'CASCADE','set_null'=> 'SET NULL');
  protected $key_mask=array("PRIMARY"=>"PRIMARY KEY","INDEX"=>"INDEX `%s`","UNIQUE"=>"UNIQUE `%s`");

  protected $key_update=array("PRIMARY"=>"PRIMARY KEY", "UNIQUE"=>"UNIQUE ");

  protected $keys_name = array(        // $field, $type
    'PRIMARY'=>"PRIMARY", 
    'UNIQUE'=>"%s_%s_%s",
  );

  function update(){
    return array_merge(
        $this->alter_fields(),
        $this->alter_keys()
    );
  }








 function table_fields(){

    sql::query("SHOW FULL COLUMNS FROM {$this->table_name['safe']}");
    $test = sql::brute_fetch('Field');
    $table_cols=array();

    foreach($test as $column_name=>$column){

        $data=array(
            'Extra'=>$column['Extra'],
            'Default'=>$column['Default']?"'{$column['Default']}'":$column['Default'],
            'Field'=>$column_name,
            'Type'=> $column['Type'],
            'Null'=>($column['Null']=="YES"),
        );
        if($data['Default']==='' || ($data['Type']=='text' && !$data['Null']) ){
            $type = reset(explode('(',$data['Type']));
            if($type=="enum" || $type=="set") $data['Default']=null;
            else  $data['Default']="''";
        }
        $table_cols[$column_name]=$data;

    } return $table_cols;
  }

  function create() {
    $todo   = array(); 
    $fields = array();

    foreach($this->fields_xml_def as $field_name=>$field_xml)
        $fields[] = mykse::linearize($field_xml);

    foreach($this->keys_xml_def as $key=>$def) {
        if(($type=$def['type'])!='PRIMARY') continue;
        $fields[]=$this->key_mask[$type]." (`".join('`,`',$def['members']).'`)';   
    }

    $todo[] = "CREATE TABLE {$this->table_name['safe']} (\n\t".join(",\n\t",$fields)."\n)";
    
    return $todo;
    die($query);

    $description=(string)$this->xml->description;
    if($description) $query.="\n\t COMMENT '".addslashes($description)."'";
    $query.=";\n";
    return $query;
  }


}
