<?php

class table extends table_base {

  public $tmp_refs=array();


  function update(){
    return array_merge(
        $this->alter_fields(),
        $this->alter_keys()
    );
  }





 function table_fields(){
    //SELECT sql FROM sqlite_master WHERE type="table"
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
