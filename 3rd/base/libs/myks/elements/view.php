<?php



abstract class view_base extends myks_base {
  public $sql_def = array();
  public $xml_def = array();

  private $update_cascade = false;
  protected $view_name;

  function __construct($view_xml){
    $this->view_xml  = $view_xml;
    $this->view_name = sql::resolve( (string) $view_xml['name'] );
  }

  function get_name(){
    return $this->view_name;
  }

  function check($force = false){
    $this->xml_infos();
    $this->sql_infos();

    if($force) $this->sql_def = array();
    if(!$this->modified())  return false;
    $todo = $this->alter_def();

    //print_r(array_show_diff($this->sql_def,  $this->xml_def, 'sql', 'xml'));die;
    if(!$todo)
        throw rbx::error("-- Unable to look for differences in $this");

    $todo = array_map(array('sql', 'unfix'), $todo);
    return array($todo, $this->update_cascade);
  }

  function modified(){
    //print_r(array_show_diff($this->sql_def,  $this->xml_def, 'sql', 'xml'));die;
    $res = $this->sql_def != $this->xml_def;
    return $res;
  }




  function delete_def(){
    return array(
        "DROP VIEW IF EXISTS {$this->view_name['safe']} CASCADE"
    );
  }

  function alter_def($force = false){
    $todo = array();
    if(!$force && ($this->sql_def == $this->xml_def)) return $todo;
    $this->update_cascade = true;

    if($force || $this->sql_def['compiled_definition'])
        $todo = array_merge($todo, $this->delete_def());

    $todo []= "CREATE OR REPLACE VIEW  {$this->view_name['safe']} AS ".CRLF
         . $this->xml_def['def'];


    $todo []= $this->sign("VIEW", $this->view_name['safe'], $this->xml_def['def'], $this->xml_def['signature'] );
    return $todo;
  }


  protected function calc_signature(){
        //self checked signature
    return $this->crpt(
            $this->sql_def['compiled_definition'],
            $this->xml_def['def']
    );
  }

  function sql_infos(){
    if($this->sql_def)
        return;

   $where = sql::where( array(
        "c.relkind" => "v",
        "c.relname" => $this->view_name['name'],
        "n.nspname" => $this->view_name['schema'],
    ));

    $query = "SELECT
        n.nspname             AS schema_name,
        c.relname             AS view_name,
        pg_get_viewdef(c.oid) AS compiled_definition,
        d.description         AS full_description
      FROM 
        pg_class AS c
        LEFT JOIN pg_namespace AS n ON n.oid = c.relnamespace
        LEFT JOIN pg_description AS d ON c.relfilenode = d.objoid
      $where;
    "; $data = sql::qrow($query);

    $sign = $this->parse_signature_contents($data['full_description']);

    $this->sql_def = array(
        'compiled_definition'=> $data['compiled_definition'],
        'def'=> $sign['base_definition'],
        'signature'=> $sign['signature'],
    );
  }


  function xml_infos() {
    $this->sql_infos();  //recursive signature, sql first

    $this->xml_def = array(
        'compiled_definition' => $this->sql_def['compiled_definition'],
        'def'=> myks_gen::sql_clean_def($this->view_xml->def),
     );
    $this->xml_def['signature'] = $this->calc_signature();

  }
}
