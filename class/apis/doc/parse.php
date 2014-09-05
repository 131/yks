<?php

class doc_parser {



  static function parse($str){
    if(!$str) return false;
    $res = array();
      //unix style LF
    $str = preg_replace("#\r?\n#", "\n", $str);
    $str = strip_end(strip_start($str, "/**\n"), "*/");

    $args = array(); $doc = array();
    if(preg_match_all("#^\s*\* ?(.*?)$#m", $str, $out)) {

      foreach($out[1] as $line){
        if($arg = self::arg($line)) {
          $args[$arg[0]]['computed'] = $arg[1];
          $args[$arg[0]]['values'][] = $arg[1];
        } else $doc[] = $line;
      }
    }
    return compact('args', 'doc');

  }

  static function arg($str){
    if(!preg_match("#^@([a-z_][0-9a-z_-]+)(.*?)$#", $str, $out))
      return false;

    $key  = $out[1];
    $args = cli::parse_args($out[2]);

    return array($key, $args);
  }



}
