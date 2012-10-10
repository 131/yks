<?php

/*	"Exyks display" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2007
*/

class dsp{


  static function field_value($field_type,  $field_value){
    if(locales_processor::register_mykse_renderer($field_type))
        return "&$field_type.$field_value;";
    else return $field_value;
  }

    //goto mykses, thx
  static function field_input($field_type, $field_name, $field_value=false, $extras = "", $batch_mode = false){
    static $classes_map = false;
    if($classes_map === false ) {
        $classes_map = array();
        foreach(yks::$get->config->myks->class_map->iterate("class") as $class) {
            $mykse     = $class['mykse'];
            $mykse_xml = yks::$get->types_xml->$mykse;
            $classes_map[$mykse] = array(
                'class' => $class['name'],
                'mykse' => $mykse,
                'table' => (string) $mykse_xml['birth'],
            );
        }
    }

    $type_xml = yks::$get->types_xml->$field_type;
    $birth_table_name = (string) $type_xml['birth'];
    if(!$birth_table_name)
        return "<field title='$field_name' type='$field_type' name='$field_name' value='$field_value'  $extras/>";

    $options = array('selected'=>$field_value, 'truncate'=>20);
    $options_str = $batch_mode?"size='10' multiple='multiple'":"";
    $default_str = $batch_mode?"":"&select.choose;";
    if($batch_mode) $field_name.="[]";

    $birth_xml = yks::$get->tables_xml->$birth_table_name;
    $birth_fields = fields($birth_xml);
      //look for a "_name" field in birth table
    $birth_name = reset(preg_split('#_id|_key$#', $field_type))."_name";
    $nbs = sql::lines($birth_table_name);

    if($birth_fields[$birth_name]) { //!!We have a birth field description

      sql::select($birth_table_name, true, "$birth_name, $field_type", "ORDER BY $birth_name ASC");
      $birth_description = sql::brute_fetch($field_type, $birth_name);
      $str = "<field title='$field_name' $extras><select name='$field_name' $options_str>$default_str"
          .dsp::dd($birth_description, $options)
          ."</select></field>";
      return $str;
    } elseif (isset($classes_map[$field_type])){
        $class_infos = $classes_map[$field_type];
        $options = array('selected'=>$field_value);
        $elemes = call_user_func(array($class_infos['class'], 'from_where'), true);
        $str = "<field title='$field_name' $extras><select name='$field_name' $options_str>$default_str"
            .dsp::dd($elemes, $options)
            ."</select></field>";
        return $str;
    } elseif($nbs<20) {
        sql::select($birth_table_name, true, $field_name, "ORDER BY $field_name ASC");

        $birth_description = sql::brute_fetch($field_name, $field_name);
        $str = "<field title='$field_name' $extras><select name='$field_name' $options_str>$default_str"
          .dsp::dd($birth_description, $options)
          ."</select></field>";
        return $str;
    }

    return "<field title='$field_name' type='$field_type' name='$field_name' value='$field_value'  $extras/>";
  }

  // Find documentation in the manual
  static function pages($max, $by, $page_id, $href, $target=false, $step=true){
    $str="";
    if($by) for($a=0; $a<$max; $a += $by){
        $b=$a/$by; $tmp=$b+1; $total=ceil($max/$by);
        if($b==$page_id) $tmp="[$tmp]";
        if($step && $b>4 && $b<$page_id-2){
            if($b==5) $str.="…"; continue;
        } elseif($step && $b>$page_id+2 && $b<$total-5){
            if($b==$page_id+3) $str.="…"; continue;
        } $str.="<a href='$href$b' ".($target?"target='$target'":'').">$tmp</a>&#160;";
    }
    $end = ($page_id*$by +1 )."-".min(($page_id+1)*$by+1, $max);
    return $str?$str." ($end/$max)":'';
  }

  static function radio($type,$actives=false,$mode="radio"){
    global $xml_types;$tmp="";
    if(!is_array($actives)) $actives=array($actives);
    foreach($xml_types->$type->enum->val as $elem){
      $selected=in_array("$elem",$actives)?" checked='checked'":'';
      $tmp.="<input type='$mode' name='$type' value='$elem' $selected/> &$type.$elem;<br/>";
    }return $tmp;
  }

  static function dds($data,$options=array()){
    $text=''; foreach($data as $k=>$v)
        $text.="<optgroup label=\"$k\">".self::dd($v,$options)."</optgroup>\n";
    return $text;
  }

  static function ccc($city,$name){
   if(is_numeric($city)||!$city){
    extract(sql::row("SELECT commune FROM `ks_geo_zipcode` WHERE insee='$city' LIMIT 1"));
    return "<select name='$name' id='$name'><option value='$city'>$commune</option></select>";
   } else return "<input type='text' name='$name' id='$name' value='$city'/>";
  }


  static function mailto($str, $subject=false, $contents = false){
    $to = specialchars_decode($str);
    $name = preg_match("#(.*?)\s*<.*?>#",$to,$out)?$out[1]:$to;
    $subject = $subject?"?subject=".mailto_escape($subject):'';
    if(!$contents) $contents = $name;
    return "<mailto secured=\"".base64_encode(mailto_escape($str).$subject)."\">$contents</mailto>";
  }

    //find doc in the manual
  static function element_truncate($str, $len, $element="span", $alternative=false){
    $empty = !((bool)trim($str)); $truncated = txt::truncate($str, $len);
    if($empty && $alternative) $truncated = $alternative;
    $element_name = preg_reduce('#([^\s]+)#', $element);
    $title = !$empty && $truncated!=$str?" title=\"$str\"":'';
    return "<{$element}{$title}>$truncated</$element_name>";
  }
  static function dd($data, $opts = false){
    if(!is_array($opts)) $opts = array('selected'=>array($opts));
    $selected = $opts['selected']; if(!is_array($selected)) $selected = array($selected);
    $mykse=$opts['mykse'];
    $col = pick ($opts['col'], "value");
    if(!$data) $data = array();
    if(!is_array($data)){
        $tmp = myks::resolve_base($data);
        $list = $tmp['type']!='enum'?array():vals($tmp);
        $list = array_combine($list, array_mask($list,"&$data.%s;"));
    } else $list = $data;


    $depth    = (int)$opts['depth'];
    $options  = "";
    $pad      = $opts['pad'] ? $opts['pad'] : "&#160;&#160;";
    $truncate = $opts['truncate'] ? $opts['truncate'] : 50;


    foreach($list as $k=>$v){

        if( $col == "value" && ( !is_array($v) || is_object($v) ) )
            $v = array("value"=>$v);

        if($opts['mask'])
            $str = str_evaluate($opts['mask'], $v);
        else $str = $v[$col];

        $options.="<option value='$k' "
        .((!$mykse) && $truncate&&mb_strlen($str)>$truncate?"title='$str' ":'')
        .($v['class']?"class='{$v['class']}' ":'')
        .($v['disabled']?"disabled='{$v['disabled']}' ":'')
        .($v['selected']||in_array($k,$selected)?'selected="selected" ':'')
        .">".str_repeat($pad,(int)$v['depth']+$depth)
            .($mykse?"&$mykse.$k;":txt::truncate($str,$truncate))
        ."</option>";
    } return $options;
  }

  static function addr($addr,$format,$extras=array()){
    if((!is_array($addr)) && $addr=(int)$addr)
        $addr=sql::row("SELECT * FROM `ks_addr` WHERE addr_id='$addr' LIMIT 1");
    if(!$addr) return ''; extract(array_merge($addr,$extras));
    if(is_numeric($addr_city) && function_exists('get_city_insee'))
        $addr_city=get_city_insee($addr_city);
    $format=preg_replace(array(FUNC_MASK,VAR_MASK),VAR_REPL,$format);
    $format=preg_replace('#<([a-z]+)>\s*</\\1>#','',$format);
    $format=join("<br/>",array_filter(preg_split('#(<br\s*/>\s*)#',$format)));
    $format=preg_replace("#&[a-z0-9_-]+\.;#","",$format);
    return $format;
  }

  static function file_size($size){
    $size=sprintf("%f",$size);
    return ($size>>30) ? round($size/(1<<30),2).' Go'
        : (($size>>20) ? round($size/(1<<20),2).' Mo'
           : (($size>>10) ? round($size/(1<<10),2).' Ko'
              :((int)$size)." octets"));
  }

  static function datef($date=_NOW,$format=DATE_MASK){
    return date::sprintfc($date, preg_replace("#[a-z]#i",'$$0', $format));
  }
  static function date($date=_NOW,$format=DATE_DAY,$format_rel=false){
    return date::sprintfc($date, $format, $format_rel);
  }

  // Find documentation in the manual
  static function nav($tree,$id=false, $depth=0){
    $ul="<ul ".($id?"id='$id'":'').">"; $str = '';
    foreach($tree as $link_key=>$link_infos){
        if($link_infos['access']
             && !auth::verif(key($link_infos['access']),current($link_infos['access'])))
                continue;
        $title=$link_infos['title'];
        $children=(bool)$link_infos['children'];
        $current=(substr(exyks::$href,0,strlen($link_key))==$link_key);
        $target=$link_infos['target']?"target=\"{$link_infos['target']}\"":'';
        $class=$children?"class='parent'":'';
        $id = isset($link_infos['id'])? "id='{$link_infos['id']}'":'';
        $str.="<li $class $id>";
        $href  = $link_infos['href']?"href='{$link_infos['href']}'":'';
        $class = $current?"class='current'":'';

        if($theme=$link_infos['theme']){
            if($current) $theme .= ":on";
            $element = $href ? "button" : "title";
            $effects = $link_infos['effects']?"effects=\"{$link_infos['effects']}\"":"";
            $str.="<$element $target $effects $href theme='{$theme}'>$title</$element>";
        } else $str.="<a $class $target $href>$title</a>";

        if($children) $str.=self::nav($link_infos['children'],false,$depth+1)."";
       $str.="</li>";
    }
    $ul.= $str;
    $ul.= "</ul>";
    return $str?$ul:"";
 }


  public static function indent_xml($simplexml) {
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;
    $domnode = dom_import_simplexml($simplexml);
    $domnode = $doc->importNode($domnode, true);
    $domnode = $doc->appendChild($domnode);
    return $doc->saveXML();
  }

}


