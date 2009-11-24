<?php

class yks
{
  static public $get;
  const FATALITY_XSL_404    = "xsl_404";
  const FATALITY_XML_SYNTAX = "xml_syntax";
  const FATALITY_404        = "404";
  const FATALITY_CONFIG     = "config";
  const FATALITY_SITE_CLOSED     = "site_closed";

  static function init($load_config = true){
    classes::register_class_path("config", CLASS_PATH."/config.php");
    classes::register_class_path("exyks",  CLASS_PATH."/exyks/exyks.php");

    classes::call_init(true);
    classes::extend_include_path(LIBS_PATH, CLASS_PATH);
    classes::activate();
    if($load_config) self::load_config(SERVER_NAME);
  }

  public static function load_config($host = SERVER_NAME){

    if(preg_match("#[^a-z0-9_.-]#", $host)) die("Invalid hostname");

    $config_file = CONFIG_PATH."/$host.xml";

    if(!is_file($config_file))
        yks::fatality(yks::FATALITY_CONFIG, "$config_file not found");
    $GLOBALS['config'] = $config =  config::load($config_file);

    self::$get = new yks();
    $paths = array();
    if($config->include_path)
        foreach(explode(PATH_SEPARATOR, $config->include_path['paths']) as $path)
            $paths[] = paths_merge(ROOT_PATH, $path);
    $exts = (string) $config->include_path['exts'];
    $call_init = ((string)$config->include_path['call_init']) != 'false';

    classes::extend_include_path($paths);
    classes::activate($exts); //it's okay to activate again, autoload seems to be smart enough
    classes::call_init($call_init);
  }


  static function fatality($fatality, $details=false, $render_mode="html"){
    if($details) error_log("[FATALITY] $details");
    if(PHP_SAPI == "cli")die;
    header($render_mode=="jsx"?TYPE_XML:TYPE_HTML);
    $contents  = file_get_contents(RSRCS_PATH."/fatality/-top.html");
    if(DEBUG) $contents .= "\r\n<!-- ".strtr($details,array("-->"=>"--"))."-->\r\n";
    $contents .= file_get_contents(RSRCS_PATH."/fatality/$fatality.html");
    $contents .= file_get_contents(RSRCS_PATH."/fatality/-bottom.html");
    die($contents);//finish him
  }


  public function get($key, $args = false){ //dont use it as a static, use yks::$get->get(
    $flag = $args?"$key_$args":$key;
    if(isset($this->$flag)) return $this->$flag;
    if($key == "tables_xml")
        $this->$flag = data::load($key);

    if($key == "types_xml")
        $this->$flag = data::load($key);

    if($key == "config")
        $this->$flag = config::$config;

    if($key == "entities")
        $this->$flag = data::load($key, $args);

    return $this->$flag;
  }

  public function __get($key){ return $this->get($key);  }
}






