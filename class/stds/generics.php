<?php


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

function sys_end($generation_time,$display_time=0){
    return sprintf("\n<!-- powerdé by exyks in - subs : %0-5Fs - tpls : %0-5Fs %s-->",
        $generation_time,$display_time,"");//,
    ;
}

    // return boolean state of a variable ( in string mode if asked )
function bool($val,$str=false){
    if(is_string($val)) {
        $val=strtolower($val);
        $val=$val && $val!="false" && $val !="no" && $val !="n" && $val !="f";
    }else $val=(bool)$val;
    return $str?($val?"true":"false"):$val;
}


if(!function_exists("header_remove")) {
  function header_remove($header_name) { //5.3
    header("$header_name:", true);
  }
}

function ip2int($ip){return sprintf("%u",ip2long($ip));}
function vals($enum,$chld="val"){
    $tmp=array(); if($enum->$chld) foreach($enum->$chld as $v)$tmp[]="$v"; return $tmp;
}


function fields($table, $key=false){
    $res=array();
    if($table->field) foreach($table->field as $field)
        if(!$key || $field['key']==$key)
        $res["$field"]=(string)($field['type']?$field['type']:$field);
    return $res;
}
 

function between($a,$min,$max){return $a>=$min && $a<=$max; }

function is_not_null($a){return !is_null($a);}

function preg_areplace($tmp, $str){ return preg_replace(array_keys($tmp),array_values($tmp),$str); }
function preg_clean($filter, $str, $rem = true){
    return preg_replace("#[".($rem?"^$filter":$filter)."]#i", '',$str);
}

function preg_list($mask, $str){ return preg_match($mask, $str, $out)?array_slice($out,1):array(); }
function preg_reduce($mask, $str){ return reset(preg_list($mask, $str)); }



function input_deep($v){return is_array($v)?array_map(__FUNCTION__,$v):input_check($v);}
function input_check($v){return $v==null || $v=="\0"?null:$v;}

function specialchars_encode($v){ return htmlspecialchars($v,ENT_QUOTES,'utf-8'); }
function specialchars_decode($str){ return htmlspecialchars_decode($str,ENT_QUOTES); }
function specialchars_deep($v){return is_array($v)?array_map(__FUNCTION__,$v):specialchars_encode($v);}
function mailto_escape($str){ return rawurlencode(utf8_decode(specialchars_decode($str))); }
function mail_valid($mail){ return (bool) filter_var($mail, FILTER_VALIDATE_EMAIL ); }


function strip_end($str, $end){
    return ends_with($str, $end) ? substr($str, 0,-strlen($end)): $str;
}

function strip_start($str, $start){
    return  starts_with($str, $start) ? substr($str, strlen($start)) : $str;
}
function starts_with($str, $start){
    return substr($str, 0, strlen($start)) == $start;
}
function ends_with($str, $end){
    return $end ? substr($str, -strlen($end)) == $end : true;
}

function reloc($url) {
    if(substr($url,0,1)=="/") $url=SITE_URL.'/'.ltrim($url,'/');
    if(class_exists('rbx') && rbx::$rbx) rbx::delay();
    if(JSX===true) {rbx::msg('go',$url);jsx::end();}
    header("Location: $url"); exit;
}

function abort($code) {
    $dest=ERROR_PAGE."//$code";
    if($code==404 && $dest==exyks::$href_ks) yks::fatality(yks::FATALITY_404);
    if(ERROR_PAGE==exyks::$href) return; //empeche les redirections en boucle

    $_SESSION[SESS_TRACK_ERR]="/?".exyks::$href_ks;

    if(JSX){if($code!=403)rbx::error($code);
        else jsx::js_eval("Jsx.open('/?$dest','error_box',this)");
        jsx::end();
    } reloc("?$dest");
}

    //cf doc in the manual
function str_evaluate($str, $vars = array(), $replaces = array(FUNC_MASK,VAR_MASK) ){
    extract($vars);

    $mask = "#{\\$([a-z&_0-9;-]+)}#ie";
    $str = preg_replace($mask, '"$".specialchars_decode("$1")', $str);

    $str = preg_replace($replaces, VAR_REPL, $str);
    $str = preg_replace('#<([a-z]+)>\s*</\\1>#','', $str);
    $str = join("<br/>",array_filter(preg_split('#(<br\s*/>\s*)#', $str)));
    return $str;
}

function retrieve_constants($mask = "#.*?#", $format="{%s}", $useronly = true){
    $tmp = call_user_func_array("get_defined_constants", $useronly?array(true):array()); //!
    $tmp = $useronly?$tmp['user']:$tmp;  $constants = array();
    foreach($tmp as $name=>$val)
        if(preg_match($mask, $name)) $constants[sprintf($format, $name)] = $val;
    return $constants;
}




