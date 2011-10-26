<?php

class exyks_ws {

  private static $classes = array();

  static public function init(){
    exyks::init();

    $wsdls = yks::$get->config->wsdls;

    $default_use_sess = bool($wsdls['use_sess']);

    foreach($wsdls->iterate("class") as $class) {
        $class_name = $class['name']; $aliases = array();
        $use_sess = isset($class['use_sess']) && bool($class['use_sess']) ? (string) $class['use_sess'] : $default_use_sess;
        $wsdl_ns  = pick($class['ns'], "urn:".SITE_CODE);
        $data = compact('class_name', 'aliases', 'use_sess', 'wsdl_ns');
        foreach($class->iterate("alias") as $alias)
            $data['aliases'][] = $alias['name'];
        self::$classes[$class_name] = $data;
    }

  }


  // return tupple list($class_name, $wsdl_file)
  public static function resolve($class){

    if(isset(self::$classes[$class]))
        $class_name = $class;
    else foreach(self::$classes as $_class_name=>$class_infos)
      if(in_array($class, $class_infos['aliases'])) {
        $class_name = $_class_name;
        break;
    }
    
    $wsdl_infos = self::$classes[$class_name];
    if(!isset($wsdl_infos)) {
        if($_SERVER['HTTP_SOAPACTION'])
            throw new SoapFault("server", "No valid class selected");
        header(TYPE_TEXT);
        die("No valid class selected");
    }

    $wsdls_path = ROOT_PATH."/wsdls/".FLAG_DOMAIN;
    $wsdl_file = "$wsdls_path/$class_name.wsdl";
    return array($class_name, $wsdl_file, $wsdl_infos['use_sess'], $wsdl_infos['wsdl_ns']);
  }

  public static function serve() {
    header(TYPE_XML);
    set_time_limit(90);

    rbx::$output_mode = 0;

    list($class_name, $wsdl_file, $use_sess, $wsdl_ns) = self::resolve($_GET['class']);

    if($_SERVER['REQUEST_METHOD']=='GET') {
        readfile($wsdl_file);
        die;
    }

        //autodetect if current argument is session_id, init session if so
    $SOAP_SESSION_ID = null;
    if($use_sess == "auto") {
        $url_infos = parse_url(trim($_SERVER['HTTP_SOAPACTION'],'"'));
        parse_str($url_infos['query'], $soap_action);
        //$class_name = pick($soap_action['class'], $_GET['class'], $_POST['class']);
        $method     = pick($soap_action['method']);
        $query = stream_get_contents(fopen("php://input", "r"));
        //file_put_contents(TMP_PATH."/query", $query);
        $xml = simplexml_load_string($query);
        $xml->registerXPathNamespace("me", $wsdl_ns);
        $xml->registerXPathNamespace("env", "http://schemas.xmlsoap.org/soap/envelope/");
        $SOAP_SESSION_ID = (string) reset($xml->xpath("//env:Body/me:{$method}/*[1][name()='session_id']"));
    } define('SOAP_SESSION_ID', $SOAP_SESSION_ID);

    if($use_sess)
        sess::connect(SOAP_SESSION_ID);

    $options = array(
        'actor'      => SITE_CODE,
        'classmap'   => array(),
        'cache_wsdl' => WSDL_CACHE_NONE,
    );

    $server = new SoapServer($wsdl_file, $options);
    $server->setClass($class_name);
    $server->setPersistence(SOAP_PERSISTENCE_REQUEST);
      use_soap_error_handler(true);
    $server->handle();
  }

}
