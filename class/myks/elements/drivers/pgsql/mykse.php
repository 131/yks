<?

  /**	Myks_gen by 131 for Exyks distributed under GPL V2
	this class export the basic field SQL definition from a myks_xml structure
  */

class mykse extends mykse_base {

  function int_node(){

        $sizes=array(
		'mini'=>'smallint',
		'small'=>'smallint',
		'int'=>'integer',
		'big'=>'integer',
		'giga'=>'bigint',
		'float'=>'double precision',
		'decimal'=>'float(10,5)',
	);$type=$sizes[(string)$this->mykse_xml['size']];
	if($this->birth)
            $this->field_def["Default"]="auto_increment('{$this->type}','{$this->table->uname}')";
	$this->field_def["Type"]=$type;
  }

  function enum_node(){
        $set=((string)$this->mykse_xml['set'])=='set';
        $length=0;foreach(vals($this->mykse_xml) as $val)$length=max($length,strlen($val));
	$type=$set?'set':'enum';
        if($set)$length=255;
	$this->field_def["Type"]="varchar($length)";

  }

  function bool_node(){
	$this->field_def["Type"]="boolean";

  }


  function linearize(){
	$str="`{$this->field_def['Field']}` {$this->field_def['Type']}";
	return $str;
  }

}
