<?php


class exyks_paths {

  static private $paths  = array();
  static private $consts_cache = array();
  const default_ns = 'default';

  public static function init(){

    if(!classes::init_need(__CLASS__)) return;

    self::register("yks", YKS_PATH);

    self::register("skin", RSRCS_PATH."/themes/Yks");
    self::register("skin.js", RSRCS_PATH."/js");
    self::register("here", ROOT_PATH);
    self::register("public", PUBLIC_PATH);

    foreach(yks::$get->config->paths->iterate("ns") as $ns)
        self::register($ns['name'], self::resolve($ns['path']));


    stream_wrapper_register("path", "ExyksPathsResolver");

    self::$consts_cache = retrieve_constants();
  }

  public static function register($key, $dest, $ns = self::default_ns){
    //"ns/key" index prevents double declaration (could have been [] as key is irrevelant
    self::$paths["$ns/$key"] = compact('key', 'dest', 'ns');
  }


  public static function expose($path){

    $full = self::resolve($path);
    $hash = crpt("$path/$full", FLAG_FILE);
    return "/?/Yks/Scripts/Contents//$hash|$path";  //absolute here, might be a pref ?

  }

  public static function merge($path0, $path1){
    if(starts_with($path1, "path://")) return $path1;

    $info = parse_url($path0);
    
    $path0 = '/'.strip_start($path0, "path://");
    $path = files::paths_merge($info['path'], $path1);
    return "path://{$info['host']}/".ltrim($path, '/');
  }

  public static function resolve($path, $ns = false){

    $path  = strtr($path, self::$consts_cache);
    
        //namespace list resolution order
    if(!$ns) $ns_list = array_values(array_extract(self::$paths, "ns", true));
    elseif(!is_array($ns)) $ns_list = array($ns);
    else $ns_list = $ns;
    $ns_list[] = self::default_ns;

    $replaces = array();
    foreach($ns_list as $ns) {
      foreach(self::$paths as $path_infos){
        if($path_infos['ns'] != $ns) continue;
        if(isset($replaces[$path_infos['key']])) continue;
        $replaces[$path_infos['key']] = $path_infos['dest'];
    }}

        //resolve
    $mask = '#^path://('.join('|',array_keys($replaces)).')(?:/(.*?)|$)#iem'; //cooool

    $repl = '$replaces["$1"]."$2"';

    if(starts_with($path, "path://")) {
      if(preg_match($mask, $path, $out)) {
        $path = preg_replace($mask, "$repl.'/'", $path);
        return $path;
      }
      throw new Exception("Unresolved path : '$path'");
    }

    $str = files::paths_merge(ROOT_PATH."/", files::rp($path)); //ROOT_PATH is a directory
    return $str;
  }


}





class ExyksPathsResolver { //implements streamWrapper
    private $file_path;
    private $fp;
    function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->file_path = exyks_paths::resolve($path);
        if(!is_file($this->file_path)) {
             trigger_error ("Invalid file path resolution {$this->file_path}", E_USER_WARNING);
             return false;
        }
        $this->fp        = fopen($this->file_path, $mode);
        $this->position = 0;
        return true;
    }

    function stream_read($count) {  return fread($this->fp, $count); }
    function stream_write($data) {  return fwrite($this->fp, $data); }
    function stream_stat()       {  return stat($this->file_path); }
    function stream_tell()       {  return ftell($this->fp); }
    function stream_eof()        {  return feof($this->fp); }
    function stream_seek($offset, $whence) { return fseek($this->fp, $offset, $whence); }
}



