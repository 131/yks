<?php

class http {
  const LWSP='[\s]';
  static private $headers_multiple = array('Set-Cookie');
  static private $headers_onlyraw  = array('Location');

  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    classes::register_class_paths(array(
        'header'     => CLASS_PATH."/exts/http/header.php",
        'request'    => CLASS_PATH."/exts/http/request.php",
        'sock'       => CLASS_PATH."/exts/http/sock.php",
        'url'        => CLASS_PATH."/exts/http/url.php",
        'http_proxy' => CLASS_PATH."/exts/http/proxy.php",
        'urls'       => CLASS_PATH."/exts/http/urls.php",
        'tlds'       => CLASS_PATH."/exts/http/tlds.php",
    ));
  }

  static function parse_headers($headers_str){
    $headers_str = preg_replace('#'.CRLF.self::LWSP.'+#',' ',$headers_str);
    $headers = array();

    $liste = explode(CRLF, $headers_str);
    foreach($liste as $header_str) {
        $header = header::parse_string($header_str); $name = $header->name;
        if(!$header) continue;
        if(in_array($name, self::$headers_onlyraw)) $header->value = $header->value_raw;
        if(in_array($name, self::$headers_multiple)) $headers[$name][] = $header;
        else {
            $tmp = $headers[$name];
            $headers[$name] = $tmp?array_merge(is_array($tmp)?$tmp:array($tmp), array($header)):$header;
        }
    }
    return $headers;
  }


  //option might be an integer ; this is just the timeout
  public static function ping_url($url, $options = array()){
    if(is_numeric($options))
      $options = array('timeout'=>$options);

    if($options['proxy']) {
        $options['request_fulluri'] = true;
        $proxy = parse_url($options['proxy']);
        $proxy['port'] = pick($proxy['port'], 8080);
        if($proxy['scheme'] == 'http') {
          $options['proxy'] = "tcp://{$proxy['host']}:{$proxy['port']}";
          if($proxy['user'])
            $credentials = "Basic ".base64_encode("{$proxy['user']}:{$proxy['pass']}");
            $options['header'] .= "Proxy-Authorization: $credentials".CRLF;
        }
    }
    if(!$options['timeout'])
      $options['timeout'] = 3;
    $options['timeout']/=2; //php 5.1 on socket open/close (checked with sleep());
    $opts = array('http' => $options);

    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, null, $ctx);
    return $res;
  }

  
  public static function connection_aborted(){
    $client_port = $_SERVER['REMOTE_PORT'];
    $client_addr = $_SERVER['REMOTE_ADDR'];
    $cmd = "netstat -lan | grep $client_addr:$client_port";
    exec($cmd, $out);
    $status = end(preg_split("#\s#", join('', $out)));
    return ($status != "ESTABLISHED");
  }

  public static function head($src_url, $timeout = 3, $ip = false, $end = false){
         //timeout is unused yet

    $url = new url($src_url);

    $host_ip  = $ip ? $ip : $url->host;

    $port    = $url->is_ssl?443:80;
    $enctype = $url->is_ssl?'ssl://':'';

    $lnk = new sock($host_ip, $port, $enctype);
    $lnk->request($url->http_query, "HEAD");
    $response = $lnk->response; unset($lnk);
    $response['headers'] = self::parse_headers($response['raw']);
    return $response;
  }

  public static function chunked_deflate($str){

    $body=''; $i = 0;
     do {
        $chunk = substr($str, $i, strspn($str,"abcdef0123456789", $i)); $i+=strlen($chunk)+2;
        $chunk_size = hexdec($chunk);
        $body .= substr($str, $i, $chunk_size); $i+= $chunk_size+2;
    } while($chunk!=="0" && $chunk);
    return $body;
  }


}
