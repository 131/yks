<?php


class ksql extends isql {


  static function connect(){
    $serv = ksql::$config->links->search(ksql::$link);
    $credentials = array();
    ksql::$links[ksql::$link] = mysql_connect($serv['host'], $serv['user'], $serv['pass']);
    if(!ksql::$links[ksql::$link])
      throw new Exception("Unable to load link #{".ksql::$link."} configuration");

    mysql_select_db($serv['db'], ksql::$links[ksql::$link]);
    //mysql_set_charset ( "UTF-8",  ksql::$links[ksql::$link]);
    mysql_query("SET NAMES utf8", ksql::$links[ksql::$link]);
    return ksql::$links[ksql::$link];
  }


  static function close($link = false){
    if(!$link) $link = ksql::$link;
    if(!($serv = ksql::$links[$link])) return;
    mysql_close($serv); unset(ksql::$links[$link]);
  }

  static function free(&$r=null){
    if($r=$r?$r:ksql::$result) mysql_free_result($r);
    return $r=null;
  }


  public static function query($query, $params=null, $arows=false){

    if(!$lnk = ksql::get_lnk()) return false;
    $query = ksql::unfix($query);
    $query = ksql::format_raw_query($query, $params, $lnk);

    ksql::$result = mysql_query($query, $lnk);

    if(ksql::$log) ksql::$queries[] = $query;

    if(ksql::$result===false) {
        $error = ksql::error(htmlspecialchars($query));
        return $error;
    }

    if($arows) {
        $arows = mysql_affected_rows($lnk);
        return $arows; 
    }
    return ksql::$result;
  }

  static function fetch($r=false){
    $tmp = mysql_fetch_assoc( pick($r, ksql::$result));
    return $tmp?$tmp:array();
  }
  

  static function fetch_all(){
    $res = array();
    while($l=mysql_fetch_row(ksql::$result)) $res[]=$l[0];
    return $res;
  }

  static function error($msg='') {
    $error = mysql_error(ksql::$links[ksql::$link]);
    $msg = "<b>".htmlspecialchars($error)."</b> in $msg";
    if(DEBUG && !ksql::$transaction) error_log($msg);
    return false;
  }

  static function clean($str){
    if(is_numeric($str)) return $str;
    if(!$lnk = ksql::get_lnk()) return false;
    return mysql_real_escape_string($str, $lnk);
  }


  static function rows($r=false){ return  mysql_num_rows(pick($r, ksql::$result)); }
  static function auto_indx(){
    return (int)mysql_insert_id(ksql::$links[ksql::$link]);
  }

  static function query_raw($query){
    if(!$lnk = ksql::get_lnk()) return false;
    return mysql_query($lnk, $query);
  }

//************** Extras ************
  static function limit_rows(){return ksql::qvalue("SELECT FOUND_ROWS()");}
}


class sql extends ksql {}
