<?php

class http {
  const LWSP='[\s]';
  static $headers_multiple = array('Set-Cookie');


  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    classes::register_class_paths(array(
        'header'  => CLASS_PATH."/exts/http/header.php",
        'request' => CLASS_PATH."/exts/http/request.php",
        'sock'    => CLASS_PATH."/exts/http/sock.php",
        'url'     => CLASS_PATH."/exts/http/url.php",
        'urls'    => CLASS_PATH."/exts/http/urls.php",
    ));
  }

  static function parse_headers($headers_str){
    $headers_str = preg_replace('#'.CRLF.self::LWSP.'+#',' ',$headers_str);
    $headers = array();

    $liste = explode(CRLF, $headers_str);
    foreach($liste as $header_str) {
        $header = header::parse_string($header_str); $name = $header->name;
        if(!$header) continue;
        if(in_array($name, self::$headers_multiple)) $headers[$name][] = $header;
        else {
            $tmp=$headers[$name];
            $headers[$name] = $tmp?array_merge(is_array($tmp)?$tmp:array($tmp), array($header)):$header;
        }
    }
    return $headers;
  }



  public static function ping_url($url, $timeout = 3, $ip = false){
    $url_infos = parse_url($url);

    $host     = $url_infos['host'];
    $host_ip  = $ip ? $ip : $host;
    $path     = $url_infos['path'].'?'.$url_infos['query'];

    $fp = @fsockopen($host_ip, 80, $null, $null, $timeout);
    if (!$fp) throw new Exception("Unable to connect to host '$host'");
    $query = array("GET $path HTTP/1.0");
    $query []= "Host: $host";
    $query []= "Connection: close";
    $query []= "";
    $query []= "";
    $query  = join(CRLF, $query);

    fwrite($fp, $query);
    stream_set_timeout($fp, $timeout);
    list($headers, $contents) = explode(CRLF.CRLF, stream_get_contents($fp), 2);
    $info = stream_get_meta_data($fp);
    fclose($fp);

    if ($info['timed_out']) 
        throw new Exception("Request timed out");

    return $contents;
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

}
