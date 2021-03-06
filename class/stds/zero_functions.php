<?php
// base functions, i'm naked without

include_once  dirname(__FILE__)."/../apis/legacy.php";

function crpt($msg,$flag,$len=40) {
  $msg = $flag?sha1($msg . $flag):$msg;
  return substr($msg, 0, $len);
}

//same thing as create_function, but returns a closure..( 5.4)
function create_closure($args, $body){
  return eval("return function($args){ {$body}  };");
}


function guid() {
    return strtolower(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
        mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535),
        mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535),
        mt_rand(0, 65535), mt_rand(0, 65535)));
}

function paths_merge($path_root, $path, $default="."){
    if(!$path) $path = $default;
    if( $path{0}==DIRECTORY_SEPARATOR
        || preg_match('#^[a-zA-Z]:\\\#', $path)
        || starts_with($path, "path://")) return $path;
    return realpath($path_root.DIRECTORY_SEPARATOR.$path);
}

function array_apply($array, $method) {
  $ret = array();
  foreach($array as $k=>$v)
      $ret[$k] = call_user_func(array($v, $method));
  return $ret;
}

function array_extract($array, $col, $clean=false){
    $ret = array();
    if(is_array($col)) foreach($array as $k=>$v) $ret[$k] = array_sort($v, $col);
    elseif($array instanceof simplexmlelement) foreach($array as $k=>$v) $ret[] = (string)$v[$col];
    elseif($array) foreach($array as $k=>$v) $ret[$k]=$v[$col];
    return $clean ? array_filter(array_unique($ret)):$ret;
}
    //return the first non empty value, or the last one
function pick(){
  $args = func_get_args();
  $val  = first(array_filter($args));
  return $val ? $val : end($args);
}


function array_set($array, $k, $v){
  foreach($array as &$element)  $element[$k] = $v;
}
//exclude an element from an array
function array_remove($array, $val){
  $vals = func_get_args();  $vals = array_shift($vals);
  return array_diff($array, array($val));
}

    //return the first non empty valid value (last arg is a list of possibles values)
function pick_in(){
    $args = func_get_args();

    $possibles = array_pop($args);
    $value = first(array_filter($args));
    return in_array($value, $possibles) ? $value : first($possibles);
}


function array_mask($array, $vmask = null, $kmask="%s"){
  $ret = array();
  foreach($array as $k=>$v)
    $ret[sprintf($kmask, $k, $v)] = is_null($vmask) ? $v : sprintf($vmask, $v, $k);
  return $ret;
}

    //paths merges
function array_key_map($callback, $array){
    if(!$array) return $array;
    return array_combine(array_map($callback, array_keys($array)), $array);
}

//function return a array from func_get_args
/*
* object a,b,c;
* f(a,b,c);   => [a,b,c]
* f([a,b,c]); => [a,b,c]
* f(a)        => [a]
*/
function aargs($args){
    if(!$args || count($args)>1 )
        return array($args, true);
    $arg = $args[0];
    if(is_array($arg))
        return array($arg, true);
    $key = is_object($arg)?$arg->hash_key:0;
    return array(array($key=>$arg), false);
}

//pad a list with empty array();
function alist($args){
    return array_fill_keys(array_keys($args), array());
}


    // return boolean state of a variable ( in string mode if asked )
function bool($val,$str=false){
    if(is_string($val)) {
        $val=strtolower($val);
        $val=$val && $val!="false" && $val !="no" && $val !="n" && $val !="f" && $val !="off";
    }else $val=(bool)$val;
    return $str?($val?"true":"false"):$val;
}


if(!function_exists("header_remove")) {
  function header_remove($header_name) { //5.3
    return php_legacy::header_remove($header_name);
  }
}

if(!function_exists("array_column")) {
  function array_column($array, $column_key = null, $index_key = null) { //5.5
    return php_legacy::array_column($array, $column_key, $index_key);
  }
}


if(!function_exists('stream_resolve_include_path')) { //5.3
  function stream_resolve_include_path($file_path) {
    return php_legacy::stream_resolve_include_path($file_path);
  }
}

if(!function_exists('quoted_printable_encode')) {
  function quoted_printable_encode($str, $len = 76) {
    return php_legacy::quoted_printable_encode_filter($str, $len);
  }
}



function ip2int($ip){return sprintf("%u",ip2long($ip));}
function vals($enum,$chld="val"){
    $tmp=array(); if($enum->$chld) foreach($enum->$chld as $v)$tmp[]="$v"; return $tmp;
}

  //recursive strtr
function str_set($str, $vals){
  $tmp = null;
  while($tmp != $str) {
    $tmp = $str;
    $str = strtr($str, $vals);
  }

  return $str;
}

function between($a, $min, $max) {
  return $a >= $min && $a <= $max;
}

function is_not_null($a){return !is_null($a);}

function preg_areplace($tmp, $str){ return preg_replace(array_keys($tmp),array_values($tmp),$str); }
function preg_clean($filter, $str, $rem = true){
    return preg_replace("#[".($rem?"^$filter":$filter)."]#i", '',$str);
}

function preg_list($mask, $str){ return preg_match($mask, $str, $out)?array_slice($out,1):array(); }
function preg_reduce($mask, $str){ return first(preg_list($mask, $str)); }



function input_deep($v){return is_array($v)?array_map(__FUNCTION__,$v):input_check($v);}
function input_check($v){return $v==null || $v=="\0"?null:$v;}

function specialchars_encode($v){ return htmlspecialchars($v,ENT_QUOTES,'utf-8'); }
function specialchars_decode($str){ return htmlspecialchars_decode($str,ENT_QUOTES); }
function specialchars_deep($v){return is_array($v)?array_map(__FUNCTION__,$v):specialchars_encode($v);}
function utf8_deep($v){return is_array($v)?array_map(__FUNCTION__,$v):utf8_encode($v);}
function mailto_escape($str){ return rawurlencode(utf8_decode(specialchars_decode($str)) ); }
function mail_valid($mail){ return (bool) filter_var($mail, FILTER_VALIDATE_EMAIL ); }




function html_entity_encode_numeric($str) {
    $res = "";
    for($i=0,$len=strlen($str);$i<$len;$i++) {
        $h = ord($str[$i]);
        if ($h <= 0x7F) {
            $res .= $str[$i];
        } elseif ($h < 0xC2) {
            $res .= $str[$i];
        } elseif ($h <= 0xDF) {
            $h = ($h & 0x1F) << 6
            | (ord($str[++$i]) & 0x3F);
            $res.= "&#$h;";
        } elseif ($h <= 0xEF) {
            $h = ($h & 0x0F) << 12
            | (ord($str[++$i]) & 0x3F) << 6
            | (ord($str[++$i]) & 0x3F);
            $res.= "&#$h;";
        } elseif ($h <= 0xF4) {
            $h = ($h & 0x0F) << 18
            | (ord($str[++$i]) & 0x3F) << 12
            | (ord($str[++$i]) & 0x3F) << 6
            | (ord($str[++$i]) & 0x3F);
            $res .= "&#$h;";
        }
    }
    return $res;
}

function strip_end($str, $end){
    return ends_with($str, $end) ? (string) substr($str, 0,-strlen($end)): $str;
}

function strip_start($str, $start){
    return  starts_with($str, $start) ? (string) substr($str, strlen($start)) : $str;
}
function starts_with($str, $start){
    return substr($str, 0, strlen($start)) == $start;
}
function ends_with($str, $end){
    return $end ? substr($str, -strlen($end)) == $end : true;
}



  // cf doc in the manual
  // Utilisation : this is my template {$distributor} where i can display {$distributor->addr->addr_zipcode}
function str_evaluate($str, $vars = array(), $replaces = array(FUNC_MASK,VAR_MASK) ){
    $vars = array_map("objectify", $vars);
    extract($vars);

    $mask = "#{\\$([a-z&_0-9;-]+)}#ie";
    $str = preg_replace($mask, '"$".specialchars_decode("$1")', $str);

    $str = preg_replace($replaces, VAR_REPL, $str);
    $str = preg_replace('#<([a-z]+)>\s*</\\1>#','', $str);
    $str = join("<br/>",array_filter(preg_split('#(<br\s*/>\s*)#', $str)));
    return $str;
}



class stdClassSerializable {
  private $string_value;
  private $value;
  function __construct($value){
    $this->string_value = is_object($value)
                ? ( method_exists($value, '__toString') ? (string) $value : "")
                : (string) $value;
    $this->value = $value;
  }

  function __get($name) {
    return $this->value->$name;
  }

  function __toString(){
    return $this->string_value;
  }
}

  // cf doc aussi!
function objectify($item) {
  if(is_scalar($item) || is_null($item))
    return $item;

  $out = new stdClassSerializable($item);
  foreach($item as $k=>$v)
    $out->$k = is_array($v) ? objectify($v) : $v;
  return $out;
}

function retrieve_constants($mask = "#.*?#", $format="{%s}", $useronly = true){
    $tmp = call_user_func_array("get_defined_constants", $useronly?array(true):array()); //!
    $tmp = $useronly?$tmp['user']:$tmp;  $constants = array();
    foreach($tmp as $name=>$val)
        if(preg_match($mask, $name)) $constants[sprintf($format, $name)] = $val;
    return $constants;
}

 /// Like sprintf but all parameters go through escapeshellarg.
function sprintfshell($mask){
  $args = func_get_args();
  $args = array_map('escapeshellarg', array_slice($args, 1));
  return call_user_func('vsprintf', $mask, $args);
}

 
function first($obj) {
  if(!is_array($obj)) {
    if(!is_a($obj, 'Traversable'))
      return null;
    foreach($obj as $tmp)
      return $tmp;
    return null;
  }

  $keys = array_keys($obj);
  if(!count($keys))
    return null;

  return $obj[$keys[0]];
}
