<?


abstract class procedure_base {
  public $sql_def=array();
  public $xml_def=array();

  function __construct($proc_xml){
    $proc_name=(string)$proc_xml['name'];
    $this->proc_xml  = $proc_xml;
    $this->proc_name = sql::unquote($proc_name);
  }

  function check(){
    $this->xml_infos(); $this->sql_infos();  //xml need to be parsed at first

    $same = array_diff($this->xml_def,$this->sql_def);
    if(!$same) return false;


    //usefull for debug
    //rbx::ok(join(', ',array_keys($same)));
    //print_r($this->xml_def);print_r($this->sql_def);
    //print_r(sql::$queries);die;

    $args=array();
    foreach((array)$this->xml_def['params'] as $param)
        $args[]=$param['name'].' '.myks_gen::$type_resolver->convert($param['type'], 'out');
    $args=join(', ',$args);

    $out  = $this->xml_def['setof']
            .' '.myks_gen::$type_resolver->convert($this->xml_def['type'],'out');
    $ret="CREATE OR REPLACE FUNCTION \"public\".\"{$this->proc_name}\" ($args) RETURNS $out AS\n\$body\$\n";
    $ret.= sql::unfix($this->xml_def['def'])."\n\$body\$\n";
    $ret.="LANGUAGE 'plpgsql' VOLATILE CALLED ON NULL INPUT SECURITY INVOKER;\n\n";

    return $ret;
  }

  /**
      retourne le 'specific name' de la procedure dans les tables information shema en se basant sur une signature approximative ( pourrait être completé )
      Case coverage : 90%
  **/

  function specific_name(){
    $params_nb= count($this->xml_def['params']);
    $data_type = myks_gen::$type_resolver->convert($this->xml_def['type'],'search');
    $query = "SELECT specific_name
        FROM
          information_schema.routines
          LEFT JOIN information_schema.parameters USING(specific_name)
        WHERE
          routine_name='{$this->proc_name}'
            AND  information_schema.routines.data_type='$data_type'
        GROUP BY specific_name
        HAVING
            COUNT(information_schema.parameters.specific_name)=$params_nb
    "; return current(sql::qrow($query));

  }

  function sql_infos(){
    $specific_name = $this->specific_name();

    if(!$specific_name){
        rbx::ok("-- New procedure : $this->proc_name");
        return false;
    }

    $oid = end(split("_", $specific_name));
    $verif_proc=array(
        'routine_type'=>'FUNCTION',
        'routine_name'=>$this->proc_name,
        'specific_name'=>$specific_name,
        'routine_schema'=>'public'
    ); $cols = "specific_name, routine_name, data_type, routine_definition";
    $data = sql::row("information_schema.routines",$verif_proc, $cols);
    $extras = sql::qrow("SELECT IF(proretset,'setof','') as routine_setof
            FROM pg_catalog.pg_proc WHERE oid='$oid'");
    $data = array_merge($data, $extras);

    if(!$data) return false;

    $this->sql_def = array(
        'name'=>$data['routine_name'],
        'setof'=>$data['routine_setof'],
        'type'=>myks_gen::$type_resolver->convert($data['data_type'], 'in'),
        'def'=>myks_gen::sql_clean_def($data['routine_definition']),
    );
    $specific_name=$data['specific_name'];
    $verif_proc_inner=compact('specific_name');
    sql::select("information_schema.parameters",$verif_proc_inner,'*','ORDER BY ordinal_position');
    while($l=sql::fetch()){
        $this->sql_def['params'][]=array(
            'type'=>myks_gen::$type_resolver->convert($l['data_type'], 'in'),
            
            'name'=>$l['parameter_name'],
        );
    }
  }

  function xml_infos(){
    $data=array(
        'name'=>(string)  $this->proc_name,
        'type'=>(string)  $this->proc_xml['type'],
        'setof'=>(string) $this->proc_xml['setof'],

        'def'=>myks_gen::sql_clean_def($this->proc_xml->def),
    );

    foreach($this->proc_xml->param as $param_xml){
        $data['params'][]=array(
            'type'=>(string)$param_xml['type'],
            'name'=>isset($param_xml['name'])?(string)$param_xml['name']:null,
        );
    }

    $this->xml_def=$data;
  }
}

