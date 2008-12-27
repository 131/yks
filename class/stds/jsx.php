<?
//if( 0&& DEBUG && preg_match_all('#&[\#a-z0-9\._]*?[^;a-z0-9\._]#',$str,$out))

define('JSX_EVAL','jsx_eval');
define('JSX_PLACE','place');
define('JSX_MODAL','modal');
define('JSX_PARENT_RELOAD',"this.getBox().opener.reload();");
define('JSX_RELOAD',"this.getBox().reload();");
define('JSX_CLOSE',"this.getBox().close();");

class jsx {
  static private $customs = array();

  const MASK_INVALID_ENTITIES = "#&(?!lt;|gt;|\#[0-9]+;|quot;|amp;)#";

  static public $rbx=false; //only rbx mode
  static function end($var=false, $force_array=false){
    header(TYPE_JSON);
    die(($force_array && !$var)? "[]" : jsx::encode($var===false?rbx::$rbx:$var));
  }
  static function encode($var){
    if(!$var) return "{}";
    if($eval=$var[JSX_EVAL]){unset($var[JSX_EVAL]);$eval=JSX_EVAL.':function(jsx){'.$eval.'}';}

    $json=str_replace(array('<\/','\/>'),array('</','/>'),json_encode($var));
    if($eval){if($var)$json=substr($json,0,-1).",$eval}"; else $json='{'.$eval.'}';}

    $json=preg_replace("#([\"])([0-9]+)\\1#","$2",$json);//dequote ints
    $json=utf8_decode(html_entity_decode($json,ENT_NOQUOTES,"UTF-8"));
    $json=unicode_decode($json);
    $json=str_replace("&quot;","\\\"",$json);

    return jsx::translate($json,$_SESSION['lang']);
  }

  static function set($key,$val){ yks::$get->config->head->jsx[$key]=$val;  }
  static function export($key,$val){ rbx::$rbx['set'][$key]=$val; }
  static function js_eval($msg) { rbx::msg(JSX_EVAL,"$msg;"); }

  static function translate($str,$lang=USER_LANG){ if(!$lang) $lang = USER_LANG;
    $entities = yks::$get->get("entities",$lang);
    if($entities){while($tmp!=$str){ $tmp=$str; $str=strtr($str,$entities);} $str=$tmp;}

    if(strpos($str,"&")!==false)$str=entity_dynamics($str,$lang);



    if(preg_match(self::MASK_INVALID_ENTITIES, $str)) {
        error_log("There are invalid entities in your document");
        $str = preg_replace(self::MASK_INVALID_ENTITIES,'&amp;',$str);

        if(preg_match("#<!\[CDATA\[(?s:.*?)\]\]>#",$str,$out,PREG_OFFSET_CAPTURE)){
          $str=substr($str,0,$out[0][1])
            .str_replace("&amp;",'&',$out[0][0])
            .substr($str,$out[0][1]+strlen($out[0][0]));
        }
    }

    return $str;
  }

  static function load_xml($str){
    $doc = new DOMDocument('1.0','UTF-8');
    $doc->formatOutput = false;
    $doc->preserveWhiteSpace= false;
    $doc->loadXML($str, LIBXML_YKS);
    return $doc;
  }

  static function register($tagName, $callback){
    self::$customs[$tagName] = $callback;
  }

  static function parse($doc){
    if(!self::$customs) return;

    $xpath = new DOMXPath($doc);
    $query = mask_join("|",array_keys(self::$customs), "//%s");
    $entries = $xpath->query($query);
    if(!$entries->length) return;

    foreach ($entries as $entry) {
        $nodeName = $entry->nodeName;
        $callback = self::$customs[$nodeName];
        if($callback)
            call_user_func($callback, $doc, $entry);
    }
  }

}


