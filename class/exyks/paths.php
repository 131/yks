<?php


class exyks_paths {

  static private $paths = array();

  public static function init(){
    if(!classes::init_need(__CLASS__)) return;

    self::register("yks", YKS_PATH, "yks");
    self::register("here", ROOT_PATH, "yks");

  }

  public static function register($key, $dest, $ns){
    //"ns/key" index prevents double declaration (could have been [] as key is irrevelant
    self::$paths["$ns/$key"] = compact('key', 'dest', 'ns');
  }



  public static function resolve($path, $ns = false){

        //namespace list resolution order
    if(!$ns) $ns_list = array_values(array_extract(self::$paths, "ns", true));
    elseif(!is_array($ns)) $ns_list = array($ns);
    else $ns_list = $ns;

    $replaces = array();
    foreach($ns_list as $ns) {
      foreach(self::$paths as $path_infos){
        if($path_infos['ns'] != $ns) continue;
        $replaces[$path_infos['key']] = $path_infos['dest'];
    }}

        //resolve
    $mask = '#^path://('.join('|',array_keys($replaces)).')(.*?)(/|$)#ie'; //cooool

    $repl = '$replaces["$1"]."$2"';

    if(preg_match($mask, $path, $out)) {
        $path = preg_replace($mask, "$repl.'/'", $path);
        return $path;

    }

die("Resolving $path failed in $mask");
    $repl = 'self::$paths_ns["$1"]."$2"';

    $sub_path = paths_merge(ROOT_PATH, preg_replace($mask, "$repl.'/subs/'", $path));
    $tpl_path = paths_merge(ROOT_PATH, preg_replace($mask, "$repl.'/tpls/'", $path));



    self::$paths['subs'][$path_key]   = $sub_path;
    tpls::add_resolver($path_key, $tpl_path);


    

    echo "resolving $path";
    print_r(self::$paths_ns);die;
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

  static function parse($url){

    $href_ks = htmlspecialchars(strtok(urldecode($url), "|"),ENT_QUOTES,'UTF-8');
    preg_match_all("#/([^/]+)(?://([^/]*))?#", $href_ks, $url_tree, PREG_SET_ORDER);

    if(!$url_tree) //FALLBACK si url = '/'
         $url_tree= array(array(1=>ucfirst(SITE_CODE)));
        //rajout systematique d'une feuille pour ne pas finir sur un noeud
    $url_tree[] = array();

    $result_path = array();

    $subs_path = ROOT_PATH."/subs";
    $subs_fold = "";
    $href_fold = "";
    $href_base = "";

    $zero_args = array(substr($href_ks, 0,strcspn($href_ks, "/"))); //should be expode ;
    $result_path[] = array($subs_path, $subs_fold, "main", $zero_args);

    $value = strtok("|");

    foreach($url_tree as $tmp){
        list($node_name, $args_str) = array($tmp[1],$tmp[2]); 
        $args = self::parse_args($args_str);

            //sanitize all malicious attempts '/Admin/../config...'
        if(preg_match("#[.]#",$node_name) || $node_name=="main")
            return self::parse('/');

        $href_key  = "$subs_fold/$node_name";

        $tmp_path = self::resolve($subs_fold, $node_name, $subs_path);

            //on s'arrete si l'on est plus dans un noeud
        if(!$node_name || !is_dir($tmp_path)) 
            break;

            //on ne modifie le path que si l'on est dans un noeud
        $subs_path = $tmp_path;


        $subs_fold .= "/$node_name";
        if($href_base) $href_fold = $href_base; 
        $href_fold.="/$node_name";

        $result_path[] = array(
                $subs_path,
                $subs_fold,
                "main",
                $args,
                $href_fold,
                $href_base = $args_str?"$href_fold//$args_str":$href_fold //cosmetique
        );
    }


    $href = "$href_base/$node_name";
        //si on a pas fini sur une feuille, utilisation de feuille par default
    if(!$node_name)
        $node_name = &exyks::$page_def;

    $result_path[] = array($subs_path, $subs_fold, &$node_name, $args, $href_fold, $href_base);


        /* $result_path, $href, $href_ks, $context_depths */
    $res = array($result_path, $href, $href_ks, count($result_path) - 1, $value);
    return $res;
  }

  static function parse_args($args_str){
     return explode(';', "$args_str;;;;");
  }






}
