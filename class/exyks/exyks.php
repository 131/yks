<?

/*	"Exyks controller" by Leurent F. (131)
    distributed under the terms of GNU General Public License - ? 2008
*/

class exyks {

  static public $href_ks;
  static public $page_def = 'home';
  static public $href;
  static public $is_script = false;

  static private $customs = array();

  static function bench($key) { return self::store("time_$key", microtime(true)); }
  static function tick($key,$now=false) {
    return ($now?self::bench($now):microtime(true)) - self::retrieve("time_$key");
  }


    //need 5.3 late static binding self::_class (extend storage)
  static protected $_storage = array();
  static function store($key, $value){ return self::$_storage[$key]=$value; }
  static function retrieve($key,$fallback=false){
    return isset(self::$_storage[$key])?self::$_storage[$key]:$fallback;
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



/*
  Prepare the passed URL to be used in the whole yks layer
    split & prepare args,  /Admin/Users//3123/Manage/Addrs//23/manage
    return context = array( array(
                $subs_fold  // path to the included file
                $file,      // current node/leaf name
                $args,      // args tuple for current node
                $href_fold, // recombined path to current node
                $href_base, // href_fold, with current args
      , ... ) used as tuples in index.php.
    )
    //DONT CORRECT THIS TO SUPPORT &#160;, send proper %C2%A0 instead
*/

  static function prepare($url){

    self::$href_ks = htmlspecialchars(strtok(urldecode($url), "|"),ENT_QUOTES,'UTF-8');
    self::$is_script = substr(self::$href_ks,0,13)=="/Yks/Scripts/"; //no args on scripts root
    preg_match_all("#/([^/]+)(?://([^/]*))?#", self::$href_ks, $url_tree, PREG_SET_ORDER);

    if(!$url_tree) //FALLBACK si url = '/'
         $url_tree= array(array(1=>ucfirst(SITE_CODE)));
        //rajout systematique d'une feuille pour ne pas finir sur un noeud
    $url_tree[] = array();

    $result_path = array( array("","main") );
    $subs_path = "subs";
    $href_fold = "";
    $href_base = "";
    $value = strtok("|");

    foreach($url_tree as $tmp){
        list($node_name, $args_str) = array($tmp[1],$tmp[2]); 
        $args = explode(';', "$args_str;;;;");

            //sanitize all malicious attempts '/Admin/../config...'
        if(preg_match("#[.]#",$node_name) || $node_name=="main")
            return self::prepare('/');
            //on s'arrete si l'on est plus dans un noeud
        if(!$node_name || !is_dir($subs_path.="/$node_name"))
            break;

        if($href_base)
            $href_fold = $href_base; 
        $result_path[] = array(
                $subs_fold=substr($subs_path,4),
                "main",
                $args,
                $href_fold.="/$node_name",
                $href_base = $args_str?"$href_fold//$args_str":$href_fold //cosmetique
        );
    }
    self::$href="$href_base/$node_name";
        //si on a pas fini sur une feuille, utilisation de feuille par default
    if(!$node_name)
        $node_name = &self::$page_def;
    $result_path[] = array($subs_fold, &$node_name, $args, $href_fold, $href_base);

        /* $context, $href, $href_ks, $context_depths */
    return array($result_path, self::$href, self::$href_ks, count($result_path) - 1, $value);
  }


    //Ferme les subs : close session, shut SQL link down & co
  static function context_end(){
    if(exyks::$is_script) die;
    sess::close();
    exyks::store('generation_time', exyks::tick('generation_start'));

    $str = ob_get_contents(); ob_end_clean(); //did subs provide contents ?
    if(DEBUG && $str){ header(TYPE_TEXT);die($str.print_r(sql::$queries,1)); }
    if(JSX && jsx::$rbx)jsx::end();
  }

  static function render_prepare($vars = array()){
    extract($vars); //!
    exyks::bench('display_start'); 
    ob_start();

    jsx::set("href","?".exyks::$href);
    jsx::set("href_ks","?".exyks::$href_ks);
    jsx::set("href_base","?$href_base");
    jsx::set("ks_flag",FLAG_KS);

    if(!JSX) {
        jsx::set("xsl_engine",XSL_ENGINE);
        jsx::set("site_code",SITE_CODE);
        jsx::set("cache_dir", CACHE_DIR);
        jsx::set("href_fold","?$href_fold");
        jsx::set("screen_id",10);

        $meta = yks::$get->config->head->addChild("meta");
        list($header, $value) = explode(':', TYPE_HTML);
        $meta->addAttribute("http-equiv", $header); $meta->addAttribute("content", $value);
    }

    if(!tpls::$body) tpls::body("$subs_file.tpl");

    if(JSX){
        tpls::top('Yks/'.MODE.'_top.tpl',TPLS_ERASE);
        tpls::bottom('Yks/'.MODE.'_bottom.tpl',TPLS_ERASE);
    }

    tpls::top('Yks/xml_head.tpl', TPLS_TOP);
  }

  static function render(){

    $str = ob_get_contents(); ob_end_clean();
    $str = jsx::translate($str);

    if(DEBUG)$str.=sys_end( exyks::retrieve('generation_time'), exyks::tick('display_start'));

    $doc = exyks::load_xml($str);
    exyks::parse($doc);

    $mode = self::retrieve('MODE', MODE);
    $headers= exyks::retrieve('HEADERS_MODE');
    header($headers[$mode]);
    header("Cache-Control: no-cache");

    if($mode=="xml" || $mode=="jsx")
        die($doc->saveXML());

    if($mode=="html"){
        $r = xsl::resolve($doc, XSL_SERVER_PATH);
        $content = $r->saveXML();
        $content = preg_replace('#\s+xmlns:[a-z]+=".*?"#',"",$content);
        if(IE6) echo strstr($content,"<!DOCTYPE");
        else echo $content;
    }


  }


}