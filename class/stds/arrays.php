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

 function array_restrict($items, $verif = array()){
    $out = array();
    foreach($items as $item_key => $item) {
      $success = true;
      foreach($verif as $k => $v)
        $success &= $item[$k] == $v;
      if($success)
        $out[$item_key] = $item;
    }
    return $out;
  }



function trimmed_mean($X, $k = 0.1) {
  $n = count($X);
  sort($X, SORT_NUMERIC); 
  for($i=$k; $i < $n-$k; $i++) 
    $sum += $X[$i];
  $mean = $sum / ($n - 2 * $k);
  return $mean;
}


function array_avg($data){
  return array_sum($data) / count($data);
}

function array_avg_trimmed($data, $pad = 0.1) {
  $avg = array_avg($data);
  foreach($data as $k=>$v)
      if(abs(1 - ($v / $avg)) > $pad) unset($data[$k]);
  return $avg = array_avg($data);
}

function array_median($data) {
    sort($data);
    $count = count($data);
    $middleval = floor(($count-1)/2);
    if($count % 2) {
        $median = $data[$middleval];
    } else { 
        $low = $data[$middleval];
        $high = $data[$middleval+1];
        $median = (($low+$high)/2);
    }
    return $median;
}

function pick_between($i, $min, $max) { return  min(max($min, (int) $i), $max); }

function array_next_val($array,$val){ return array_step($array, $val, 1, false); }
function array_step($array, $val, $way=1, $loop=true){
    $tmp = array_search($val, $array) + $way;
    return $array[$loop?(($tmp+count($array))%count($array)):$tmp];
}

  //this is array_sort_key
function array_sort($array, $keys){
    $keys = is_array($keys)?$keys:array_slice(func_get_args(),1);
    if(is_object($array)) {
        $tmp = array(); foreach($keys as $k) if(isset($array[$k]))$tmp[$k] = $array[$k];
        return $tmp;
    }
    $keys = array_flip($keys);
    return array_intersect_key(array_merge_numeric($keys, $array), $keys, $array);
}

function array_sort_values($array, $values){
  return array_unique(array_intersect(array_merge($values, $array), $values, $array));
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

function array_get($array,$col){return $col?$array[$col]:$array; }

function array_merge_numeric($a,$b, $depth="array_merge"){
    $args = func_get_args();
    $res  = array_shift($args);
    $depth = is_string(end($args)) ? array_pop($args) : "array_merge";


    for($i=0;$i<count($args);$i++) {
      foreach($args[$i] as $k=>$v) {
        $res[$k] = (is_array($v) && isset($res[$k]) && is_array($res[$k]) )? $depth($res[$k], $v) : $v;
      }
    }

    return $res;
}

function attributes_to_assoc($x, $ns=null, $prefix = false){
    $r = array(); //php 5.3 grrrr
    if(!$x || gettype($x) != "object" || get_class($x) != "SimpleXMLElement") return $r;
    foreach($x->attributes($ns, $prefix) as $k=>$v)$r[$k]=(string)$v;
    return $r;
}


function array_unique_multidimensional($input) {
    $serialized = array_map('serialize', $input);
    $unique = array_unique($serialized);
    return array_intersect_key($input, $unique);
}



function array_sublinearize($a,$c){$ret=array();foreach($a as $k=>$val)$ret[$k]=$val[$c];return $ret;}


//thx cagret
//$mirrors_paths = array_msort($mirrors_paths, array("path_root"=>SORT_ASC) );

function array_msort($array, $cols) {
    $colarr = array();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array();
        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
    }
    $params = array();
    foreach ($cols as $col => $order) {
        $params[] =& $colarr[$col];
        $params = array_merge($params, (array)$order);
    }
    call_user_func_array('array_multisort', $params);
    $ret = array();
    $keys = array();
    $first = true;
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            if ($first) { $keys[$k] = substr($k,1); }
            $k = $keys[$k];
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
        $first = false;
    }
    return $ret;
}


function linearize_tree($tree,$depth=0){
    $ret=array();
    foreach($tree as $cat_id=>$children){
        $ret[$cat_id]=array('id'=>$cat_id,'depth'=>$depth);
        if($children)$ret+=linearize_tree($children,$depth+1);
    }return $ret;
}

//you might want to look at array_msort ^^
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
        $array0[$k] = is_array($v) ? array_merge_deep(isset($array0[$k])?$array0[$k]:array(), $v) : $v;
    }

    return $array0;

}

  // Find documentation in the manual
function array_reindex($src, $cols=array(), $body = null){
    $res = array();
    if(!is_array($cols)) $cols = array($cols);

    foreach($src as $item){
      $tmp = &$res;
      foreach($cols as $col)
        $tmp = &$tmp[$item[$col]]; // \õ_ (autocreate entries)
      $tmp = is_null($body) ? $item : $item [$body];
    } return $res;
}

function array_filter_criteria($list, $criteria){
    $result = array();
    if(!$criteria) return $result;
    foreach($list as $k=>$v) {
        $match = true;
        foreach($criteria as $criteria_name=>$value) {
            if(is_array($v[$criteria_name]) && !is_array($value)) $match &= in_array($value, $v[$criteria_name]);
            elseif(is_array($value) && !is_array($v[$criteria_name])) $match &= in_array($v[$criteria_name], $value);
            else $match &= $v[$criteria_name] == $value;
        }
        if($match) $result[$k] = $v;
    }
    return $result;
}


function xml_to_dict($xml, $pfx){
    if(is_string($xml))
        $xml = simplexml_load_file($xml);
    $ret  = array();
    $name = strtoupper($xml->getName());
    if($pfx) $name = $pfx.$name;

    if(!count($xml->children())) //! != $xml->children()
        $ret[$name] = (string)$xml;
    foreach($xml->attributes() as $k=>$v)
        $ret["{$name}_".strtoupper($k)] = (string)$v;

    foreach($xml->children() as $child)
        $ret = array_merge($ret, xml_to_dict($child, $name.'_'));
    return $ret;
}

function xml_to_constants($xml, $pfx, $set = false){
  $ret = xml_to_dict($xml, $pfx);
   if($set)
      foreach($ret as $k=>$v) define($k, $v);
    return $ret;
}

