<?

class tpls {
 const REPLACE = 1;
 const ERASE = 2;
 const TOP = 3;

 static $nav=array();
 static $top=array();
 static $bottom=array();
 static $body=false; //if!tpls::$body

 static function top($href,$mode=0){
    if($mode==self::REPLACE)array_pop(self::$top);
    elseif($mode==self::ERASE) self::$top=array();
    elseif($mode==self::TOP) return array_unshift(self::$top, $href);
    array_push(self::$top,$href);
 }
 static function bottom($href,$mode=0){
    if($mode==self::REPLACE) array_shift(self::$top);
    elseif($mode==self::ERASE) self::$bottom=array();
    array_unshift(self::$bottom,$href);
 }
    
 static function body($href) {
    self::$body=$href;
 }
 static function page_def($subs_file){
    exyks::$page_def = $subs_file;
 }
 static function css_add($href,$media=false){
    $tmp=yks::$get->config->head->styles->addChild("css");
    if($media)$tmp['media']=$media;
    $tmp['href']=$href;
 }
 static function css_clean(){
    yks::$get->config->head->styles = null;
 }

 static function js_add($href,$defer=false){
    $tmp=yks::$get->config->head->scripts->addChild("js");
    if($defer)$tmp['defer']="true";
    $tmp['src']=$href;
 }

 static function call($page,$vars=array()){
    global $href_fold, $class_path, $action;
    $href=$page;
    extract($vars);
    include "subs/$page.php";
    include "tpls/$page.tpl";
 }
 static function sub($href){ return "subs/".ltrim($href,"/").".php"; }
 static function tpl($href){ return "tpls/".ltrim($href,"/").".tpl"; }


 static function nav($tree){
    self::$nav = array_merge(self::$nav, $tree);
 }
}