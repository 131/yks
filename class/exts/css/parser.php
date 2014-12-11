<?php
/** http://doc.exyks.org/wiki/Source:ext/css **/

class css_parser {

  const pad = " \t\n\r";
  const STRING = "(?:\"([^\"]*)\"|'([^']*)')";
  const URI    = "url\(\s*(?:\"(?P<dq>[^\"]*)\"|'(?P<sq>[^']*)\'|(?P<nq>[^)]*))\s*\)";
  const COMMENTS = "/\*.*?\*/";
  const FUNC    = '(?P<func>[a-z:.-]+)\(';
  const KEYWORD = "(?P<keyword>[!]?[\#a-z0-9-]+)";
  const css_fpi = "-//YKS//CSS";

  private static $entities = array();

  //raw keywords, direct direct declaration, no block
  private static $raw_at_keywords = array("font-face", "page");

  public static function init(){

    $dir = dirname(__FILE__);
    classes::register_class_paths(array(
      "ibase"                  => "$dir/ibase.php",
      "at_rule"                => "$dir/at_rule.php",
      "css_block"              => "$dir/block.php",
      "css_ruleset"            => "$dir/ruleset.php",
      "css_declarations"       => "$dir/declarations.php",
      "css_declaration"        => "$dir/declaration.php",
    ));

  }
  public static function register_entities($entities){
    self::$entities = $entities;
  }

  public static function load_file($file_path){
    $str = file_get_contents($file_path);
    $str = strtr($str, self::$entities);
    return self::load_string($str, $file_path);
  }

  public static function load_string($str, $file_path = "path://public") {
    try {
      $i = 0;
      $str = strtr($str, self::$entities);
      $str = self::strip_comments($str);
      return self::parse_block($str, $i, $file_path);
    } catch(Exception $e){
      $msg = __METHOD__. " parsing failure (#$i) on $file_path";
      syslog(LOG_INFO, $e->getMessage());
      throw new Exception($msg);
    }
  }

  //comments are semanticaly equals to whitespaces
  private static function strip_comments($str){
    $mask = "#".self::COMMENTS."#s";
    $str = preg_replace($mask, " ", $str);
    return $str;
  }

  private static function parse_block($str, &$i, $file_path = false){

    $i += strspn($str, self::pad, $i); $start = $i;
    $embraced = $str{$i} == "{";
    $block = new css_block($embraced);
    if($file_path)
      $block->set_path($file_path);

    if($embraced)
      $i++;

    $len = strlen($str);
    do {
      $statement = self::walk($str, $i);
      if(!$statement)
        break;
      $block->stack_statement($statement);
    } while($i < $len && $str[$i+1] != "}");

    if($embraced && $str{$i++}!="}")
      throw new Exception("Invalid block end start at $start ".substr($str, $i-2));

    return $block;
  }

  private static function parse_declaration($str, &$i){

    $i += strspn($str, self::pad.';', $i);
    if(in_array($str{$i}, array(';', '}', '')))
      return;

    $mask = "#^([^:\r\n{]*?):#";
    if(!preg_match($mask, substr($str, $i), $out))
      throw new Exception("Invalid property declaration ".substr($str,$i));

    list(, $property_name) = $out; $i+= strlen($out[0]);
    $declaration = new css_declaration($property_name);

    $gid = 0; //groupId
    do {
      $value = self::parse_value($str, $i);
      if(is_null($value))
        break;
      ///die("THIS IS $value");

      $declaration->stack_value($value, $gid);

      if($str{$i}==',') {
        $i++; $gid++;
      }

    } while($str{$i}!=';' && $str{$i}!='}' && $str{$i}!="");

    $i += strspn($str, self::pad.';', $i);

    return $declaration;
  }

  public static function split_values($str, &$i){
    $values = array();
    while(!is_null($tmp = self::parse_value($str, $i)))
      $values []= $tmp;
    return $values;
  }

  public static function split_string($str){

    $all = array(
      self::URI,                    //URI, check first
      self::FUNC,                   //other function call
      self::STRING,                 //string
      "(?P<color>\#[0-9A-F]+)",              //hexacolor
        "(?P<unit_val>-?[0-9.]+)(%|[a-z]{1,3})",  //unit value
        "(?P<number>-?[0-9.]+)",                //simple number
        self::KEYWORD,                //keyword
      ); $mask = "#^(?:".join('|', $all).")#i";

    if(!preg_match($mask, $str, $out))
      return null; //throw new Exception("Invalid property value=".substr($str, $i));

    if(array_get($out, 'func'))
      $out[0] = self::split_func($str);

    $uri = pick($out['nq'], $out['dq'], $out['sq']); //double, simple, no quote
    return array('full' => $out[0], 'uri' => $uri );
  }

  public static function split_func($str){

    preg_match("#".self::FUNC."#i", $str, $out);

    $func_name = $out['func'];
    $i = strlen($out[0]);
    $args = array();
    $arg_key = null;
    $value = null;

    do {
      $values = self::split_values($str, $i);

      $token = $str{$i++};
      if(!in_array($token, array(',', '=', ')')))
        throw new Exception("Invalid function end '$token'");

      if($token == '=') { //matching Ms filers (e.g. endColorstr='#ff0077b3')
        if(count($values) != 1)
          throw new Exception("Func hash arg name " .print_r($values,1));
        $arg_key = $values[1];
        continue;
      }

      $args[pick($arg_key, count($args))] = $value;
      $arg_key = null;

    } while($token != ')');

    if($arg_key)
      throw new Exception("Invalid hash definition $arg_key");

    return substr($str, 0, $i);
  }

  private static function parse_value($str, &$i){
    $i += strspn($str, self::pad, $i);

    $infos = self::split_string(substr($str, $i));
    if(is_null($infos)) return null;

    $value = $infos['full']; //until more is needed
    $i += strlen($value);
    $i += strspn($str, self::pad, $i);

    //rbx::ok("parsevalue $value");
    return $value;
  }

  private static function walk($str, &$i){
    $i += strspn($str, self::pad, $i);
    $step = $str{$i};

    //rbx::ok("Walk on step $step");
    $value = null;
    if($step == '}')
      return $value;
    elseif($step == '@')
      $value = self::parse_at($str, $i);
    elseif($step == '{')
      $value = self::parse_block($str, $i);
    elseif($step != "")
      $value = self::parse_ruleset($str, $i);

    $i += strspn($str, self::pad, $i);
    return $value;
  }

  private static function parse_ruleset($str, &$i){
    //rbx::ok("parserulset");
    //$selector = self::parse_selector($str, $i);

    $mask = "#^(.*?)\s*\{#s";
    if(!preg_match($mask, substr($str,$i), $out))
      throw new Exception("Invalid ruleset" . substr($str, $i));

    $selector = preg_replace('#[\r\n]#', '', $out[1]);

    $i += strlen($out[0])-1;

    $ruleset = new css_ruleset($selector);
    $ruleset->set_declarations(self::parse_declarations($str, $i));

    return $ruleset;
  }

  private static function parse_declarations($str, &$i){
    $declarations  = new css_declarations();
    //declarations block
    if($str{$i++} != "{")
      throw new Exception("Invalid declarations block".substr($str, $i));

    do {
      $declaration = self::parse_declaration($str, $i);
      if(!$declaration)
        break;
      $declarations->stack_declaration($declaration);
    } while($str{$i}!="}" && $str{$i}!="");

    if($str{$i}=="}")
      $i++;

    $i += strspn($str, self::pad, $i);

    return $declarations;
  }

  private static function parse_string($str, &$i){
    //rbx::ok("parsestring");
    $i += strspn($str, self::pad, $i);

    //([!#$%&*-~]|{nonascii}|{escape})*{w} unspecaped
    //{string1}|{string2}
    $mask = "#(?:".self::STRING.'|'.self::KEYWORD.")#";
    if(!preg_match($mask, substr($str,$i), $out))
      throw new Exception("Invalid string $mask");

    $i += strlen($out[0]);
    return pick($out[1], $out[2], $out[3]);
  }

  private static function parse_at($str, &$i){
    //rbx::ok("parseat");

    if($str{$i++} != '@')
      throw new Exception("Invalid at rule entry");

    $rule_keyword = self::parse_string($str, $i);
    $rule = new at_rule($rule_keyword);

    do {
      $value = self::parse_value($str, $i);
      if(!$value)
        break;
      //rbx::ok("THIS IS $value --".$str{$i+1}."--");
      ///die("THIS IS $value");
      $rule->stack_expression($value);
    } while($str{$i}!=';' && $str{$i}!='{' && $str{$i}!="");

    //inline rule
    if($str{$i} == ";") {
      $i += strspn($str, self::pad.';', $i);
      return $rule;
    }

    if(in_array($rule_keyword,  self::$raw_at_keywords) ) {
      $declarations = self::parse_declarations($str, $i);
      $rule->set_declarations($declarations);
    } else {
      $block = self::parse_block($str, $i);
      $rule->set_block($block);
    }

    return $rule;
  }

  /******** XML ***********/

  public static function from_xml($str){
    $tree = simplexml_load_string($str);
    return self::parse_block_XML($tree);
  }

  private static function parse_declaration_XML($xml){
    //echo $xml->asXML();die;
    $gid = 0; $tmp = new css_declaration((string)$xml['name']);
    foreach($xml->valuegroup as $group) {
      foreach($group->val as $value)
        $tmp->stack_value((string)$value, $gid);
      $gid ++;
    }
    return $tmp;
  }

  private static function parse_ruleset_XML($xml){
    $tmp = new css_ruleset((string)$xml['selector']);
    $tmp->set_declarations(self::parse_declarations_XML($xml->declarations));
    return $tmp;
  }

  private static function parse_declarations_XML($xml){
    $tmp = new css_declarations();
    foreach($xml->rule as $rule)
      $tmp->stack_declaration(self::parse_declaration_XML($rule));
    return $tmp;
  }

  private static function parse_at_XML($xml){
    $tmp = new at_rule((string)$xml['keyword']);
    foreach(self::split_values((string)$xml->expression, 0) as $value)
      $tmp->stack_expression($value);

    if($xml->style)
      $tmp->set_block(self::parse_block_XML($xml->style));
    if($xml->declarations)
      $tmp->set_declarations(self::parse_declarations_XML($xml->declarations));
    return $tmp;
  }

  private static function parse_block_XML($xml){
    $embraced = $xml['exposed']=='exposed';
    $path     = (string)$xml['file_path'];

    $tmp = new css_block($embraced);
    if($path)
      $tmp->set_path($path);

    foreach($xml->children() as $child) {
      if($child->getName() == "style")
        $tmp->stack_statement(self::parse_block_XML($child));
      elseif($child->getName() == "ruleset")
        $tmp->stack_statement(self::parse_ruleset_XML($child));
      elseif($child->getName() == "atblock")
        $tmp->stack_statement(self::parse_at_XML($child));
    }
    return $tmp;
  }
}
