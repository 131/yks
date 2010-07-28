<?php

function make_tree($splat, $root=false, $inverted = false){
    $tree = array();
    if(!$inverted) { $_parent = "parent"; $_id = "id"; }
    else { $_parent = "id"; $_id = "parent"; }

    foreach($splat as $id=>$parent){
        if(!$tree[$$_id]) $tree[$$_id]=array();
        if($$_parent!=$$_id) $tree[$$_parent][$$_id] = &$tree[$$_id];
    } return $root!==false?array($root=>$tree[$root]):$tree;
}


    //return the first non empty valid value (last arg is a list of possibles values)
function pick_in(){
    $args = func_get_args();

    $possibles = array_pop($args);
    $value = reset(array_filter($args));
    return in_array($value, $possibles) ? $value : reset($possibles);
}

function pick_between($i, $min, $max) { return  min(max($min, (int) $i), $max); }

function array_next_val($array,$val){ return array_step($array, $val, 1, false); }
function array_step($array,$val,$way=1,$loop=true){
    $tmp=array_search($val,$array)+$way;
    return $array[$loop?(($tmp+count($array))%count($array)):$tmp];
}

function array_sort($array,$keys){
    $keys = array_flip(is_array($keys)?$keys:array_slice(func_get_args(),1));
    return array_intersect_key(array_merge_numeric($keys, $array),$keys,$array);
}



function array_key_map($callback, $array){
    return array_combine(array_map($callback, array_keys($array)), $array);
}


function mask_join($glue,$array,$mask){
    foreach($array as $k=>&$v) $v=sprintf($mask,$v,$k);
    return join($glue,$array);
}



function json_encode_lite($json){
    $json = json_encode($json);
    $json = preg_replace("#\\\/(?!script)#", "/", $json);

    $json = preg_replace("#([\"])([0-9]+)\\1#","$2",$json);//dequote ints
    $json = unicode_decode($json);
    $json = str_replace("&quot;","\\\"",$json);

    return $json;
}


function array_extract($array, $col, $clean=false){
    $ret=array();
    if(is_array($col)) foreach($array as $k=>$v) $ret[$k] = array_sort($v, $col);
    elseif($array instanceof simplexmlelement) foreach($array as $k=>$v) $ret[] = (string)$v[$col];
    else foreach($array as $k=>$v) $ret[$k]=$v[$col];
    return $clean?array_filter(array_unique($ret)):$ret;
}
function array_get($array,$col){return $col?$array[$col]:$array; }

function array_merge_numeric($a,$b, $depth="array_merge"){
    $args = func_get_args(); $res = array_shift($args);
    $depth = is_string(end($args)) ? array_pop($args) : "array_merge";

    for($i=0;$i<count($args);$i++)
      foreach($args[$i] as $k=>$v)
        $res[$k] = is_array($v) && is_array($res[$k]) ? $depth($res[$k], $v) : $v;
    return $res;
}

function attributes_to_assoc($x, $ns=null, $prefix = false){$r=array(); //php 5.3 grrrr
    if($x) foreach($x->attributes($ns, $prefix) as $k=>$v)$r[$k]=(string)$v;
    return $r;
}



function array_sublinearize($a,$c){$ret=array();foreach($a as $k=>$val)$ret[$k]=$val[$c];return $ret;}



function linearize_tree($tree,$depth=0){
    $ret=array();
    foreach($tree as $cat_id=>$children){
        $ret[$cat_id]=array('id'=>$cat_id,'depth'=>$depth);
        if($children)$ret+=linearize_tree($children,$depth+1);
    }return $ret;
}

function array_sort_deep($array,$sort_by,$order='asort'){ 
    $keys=array(); foreach($array as $k=>$v)$keys[$k]=$v[$sort_by]; $order($keys);
    return array_merge_numeric($keys,$array);
}
function array_filter_deep($array,$sort_by,$val){
    $keys=array(); foreach($array as $k=>$id)$keys[$k]=$id[$sort_by]; asort($keys);
    return array_merge($keys,$array);
}



function array_merge_deep($array0, $array1){
    foreach($array1 as $k=>$v){
        $array0[$k] = is_array($v) ? array_merge_deep($array0[$k], $v) : $v;

    }
    return $array0;

}

  // Find documentation in the manual
function array_reindex($array,$cols=array()){
    $res=array();if(!is_array($cols))$cols=array($cols);
    foreach($array as $v){
      $tmp=&$res;
      foreach($cols as $col) $tmp=&$tmp[$v[$col]];
      $tmp=$v;
    }return $res;
}
