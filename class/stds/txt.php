<?php

function unicode_decode($str){return preg_replace('#\\\u([0-9a-f]{4})#e',"unicode_value('\\1')",$str);}


function unicode_value($code) {
    if(($v=hexdec($code))<0x0080) return chr($v);
    elseif($v<0x0800) return chr((($v&0x07c0)>>6)|0xc0).chr(($v&0x3f)|0x80);
    else return chr((($v&0xf000)>>12)|0xe0).chr((($v&0x0fc0)>>6)|0x80).chr(($v&0x3f)|0x80);
}


function allentities_decode($str){
    $str = htmlspecialchars_decode($str,ENT_QUOTES);
    $str = html_entity_decode($str,ENT_QUOTES,'UTF-8');
    return $str;
}

function html_extract_text($str){
    $str = str_replace("<br/>", "\r\n", $str);
    $str = preg_replace("#<.*?>#","",$str);
    return trim($str);
}

function innerHTML($str){
    return preg_reduce("#^[^>]+>(.*?)<[^<^]+$#s", $str);
}

function pict_clean($str){ return strtr($str, '/', ' '); }



//5.4
if(!function_exists('hex2bin')) {
 function hex2bin($str) {
  $r='';
  if(is_array($str))
    foreach($str as $h) $r.=chr(hexdec($h));
  else  //   $str = str_split($str, 2); //is not a so good idea
    for ($a=0, $max = strlen($str); $a<$max; $a+=2)  $r.=chr(hexdec($str{$a}.$str{($a+1)}));
  return $r;
 }
}

function rte_clean($str){
  error_log("Deprecated direct call to rte_clean, please use txt::rte_clean");
  return txt::rte_clean($str);
}

class txt {

  function rte_clean($str){

    $str = htmlspecialchars_decode(trim($str), ENT_QUOTES); //!! <a href='#flow'>
    $str = preg_replace("#<!--.*?-->#s","", $str);
    $str=html_entity_decode($str,ENT_NOQUOTES,"UTF-8");
    if(stripos($str,"<body")){
        preg_match("#<body.*?>(.*?)</body>#is",$str,$out);
        $str=(string)$out[1];
    }

    if(mb_detect_encoding($str,'utf-8,iso-8859-1')!="UTF-8") $str=utf8_encode($str);

    $meta_charset = "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /></head>";
    $doc = new DOMDocument('1.0','UTF-8');
    @$doc->loadHTML("<html>$meta_charset<body>$str</body></html>");
    $str=$doc->saveXML();

    $str = html_entity_decode($str,ENT_NOQUOTES,"UTF-8");
    $str = mb_ereg_replace("&","&amp;",mb_ereg_replace("&amp;","&",$str));
    if(mb_strpos($str,"<body/>"))return "";


    $len=mb_strlen($str);$start=mb_strpos($str,"<body>")+6;$end=mb_strpos($str,"</body>");
    $str=mb_substr($str,$start,$end-$start);

    $replaces=array(
        '#<([a-z/]+[^<>]*?)>#s'    => '&ks_start;$1&ks_end;',
        '#<#'                      => '&lt;',
        '#>#'                      => '&gt;',
        '#&ks_start;#'             => '<',
        '#&ks_end;#'               => '>',
        "#[\r\n]#"                 => '',
        '#^(<br/>)+|(<br/>)+$#'    => '',
    );$str = preg_areplace($replaces, $str);

    while($tmp != $str && $tmp = $str)
        $str = preg_replace('#(<[^>]+)\s+[a-z0-9-]+:[a-z]*=".*?"#', "$1", $str);

    return $str;
  }


  static function truncate($str, $len=10){
    return preg_replace('#&[^;]*?$#m', '…', mb_strimwidth($str,0,$len,'…') );
  }

  static function truncate_words($str, $len=100){
    if(strlen($str) <= $len)
      return $str;
    $break = "-[]-";
    return substr($str, 0, strpos(wordwrap($str, $len, $break), $break)).'…';
  }


  static function cp_to_utf8($cp){
    if(!is_array($cp)) $cp = array($cp);
    $utf8 = "";
    foreach($cp as $cp)
      $utf8 .= self::_cps_to_utf8($cp);
    return $utf8;
  }

  private static function _cps_to_utf8($cp){
    if ($cp < 0x80)
      $utf8 = chr($cp);
    else if($cp<0x800)     // 2 bytes
      $utf8 = (chr(0xC0 | $cp>>6) . chr(0x80 | $cp & 0x3F));
      else if($cp<0x10000)   // 3 bytes
        $utf8 = (chr(0xE0 | $cp>>12) . chr(0x80 | $cp>>6 & 0x3F) . chr(0x80 | $cp & 0x3F));
        else if($cp<0x200000) // 4 bytes
          $utf8 = (chr(0xF0 | $cp>>18) . chr(0x80 | $cp>>12 & 0x3F) . chr(0x80 | $cp>>6 & 0x3F) . chr(0x80 | $cp & 0x3F));
          return $utf8;
  }

  //to unicode code point
  static function utf8_to_cp($str){
    $chars   = unpack('C*', $str);

    $results = array();
    $max     = count($chars);

    for ($i=1; $i<=$max; $i++) //unpack start at 1
      $results []= self::_utf8_to_cp($chars, $i);

    return $results;
  }

  private static function _utf8_to_cp($chars, &$id)
  {
    if( ($chars[$id]>=240)&&($chars[$id]<=255) )
      $cp =    (intval($chars[$id]-240)<<18)
      + (intval($chars[++$id]-128)<<12)
      + (intval($chars[++$id]-128)<<6)
      + (intval($chars[++$id]-128)<<0);
    elseif( ($chars[$id]>=224)&&($chars[$id]<=239) )
      $cp =   (intval($chars[$id]-224)<<12)
      + (intval($chars[++$id]-128)<<6)
      + (intval($chars[++$id]-128)<<0);
    elseif( ($chars[$id]>=192)&&($chars[$id]<=223) )
      $cp =   (intval($chars[$id]-192)<<6)
      +(intval($chars[++$id]-128)<<0);
    else
      $cp = $chars[$id];

    return $cp;
  }


  //cp950 specifics (windows DOS)
  public static function cp950_to_utf8($str){
    $out = "";
    foreach(unpack('C*', $str) as $char)
      $out.= self::_cps_to_utf8(charset_map::$cps['cp950'][$char]);
    return $out;
  }

  public static function utf8_to_cp950($str){
    $out = "";
    foreach(self::utf8_to_cp($str) as $cp)
      $out.= chr(charset_map::$cps['_cp950'][$cp]);
    return $out;
  }

  public static function strip_accents($str){

    return strtr($str, array('À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'Ç'=>'C', 'ç'=>'c', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ÿ'=>'y', 'Ñ'=>'N', 'ñ'=>'n'));
  }



  private static function parse_headers($headers){
    require_once "yks/3rd/mails/libs/rfc/822_mail/822.php";
    $head = preg_replace('#'.CRLF.'[\s]+#',' ',$headers); $message_headers=array();

    preg_match_all("#(.*?):\s*(.*)#",$head,$out,PREG_SET_ORDER);
    foreach($out as $data){
      $data[1]=ucfirst(preg_replace("#(-[a-z])#e",'strtoupper("$1")',$data[1]));
      if(strpos($data[2], ";") !== false){ //loading extras
        list($value,$params) = explode(';',$data[2],2);
        $data=array($data[1]=>$value,$data[1]."-Details"=>rfc_822::header_extras(";$params"));
      } else $data=array($data[1]=>rfc_822::header_decode($data[2]));
      $message_headers = array_merge_recursive($message_headers,$data);
    }
    return $message_headers;
  }


  public static function multipart_decode($buffer, $boundary){

    $data = array();
    $parts = explode(CRLF . '--' . $boundary, $buffer);
    foreach($parts as $part) {
      unset($part_headers);unset($part_body);
      list($part_headers, $part_body) = explode(CRLF.CRLF, $part,2);

      if(!$part_body) continue; //skipp pad
      $part_headers = self::parse_headers($part_headers);

      $data[]= array($part_headers, $part_body);
    }

    return $data;
  }

}


