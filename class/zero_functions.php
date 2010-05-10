<?php
// bases functions, i'm naked without

function crpt($msg,$flag,$len=40) {
    return substr($flag?sha1($msg.$flag.yks::$get->config->data['hash']):$msg,0,$len);
}

function paths_merge($path_root, $path, $default="."){
    if(!$path) $path = $default;
    if(substr($path,0,1)=="/") return $path;
    return realpath("$path_root/$path");
}

function soft_match($mask, $str, $full = true){
    $mask = strtr($mask, array('.'=> '\.', '*'=>'.*'));
    $mask = sprintf($full?'#^%s$#':'#%s#', $mask);
    return preg_match($mask, $str);
}

    //return the first non empty value
function pick(){ $args = func_get_args(); return reset(array_filter($args)); }



if(PHP_SAPI == 'cli') { class storage { //speed
  private static $data = array();
  static function store($k, $v, $ttl=0) { return self::$data[$k] = $v; }
  static function fetch($k)           {   return self::$data[$k]; }
  static function delete($k)          {   unset(self::$data[$k]); }
}} else { class storage { //on apc
  static function store($k, $v, $ttl=0) { return apc_store($k, $v, $ttl)?$v:false; }
  static function fetch($k)           { return apc_fetch($k); }
  static function delete($k)          { return apc_delete($k); }
}}