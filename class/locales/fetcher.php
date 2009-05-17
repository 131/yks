<?

// xmlns:pf="locales_prefix" pf:here="./lang"
class locales_fetcher {

  const prefix_ns = "locales_prefix";
  static public $locale_paths;
  
  static private $locales_ns = array();
  static private function default_ns($base_ns){
    $languages  = exyks::retrieve('LANGUAGES');

    $base = array_merge($base_ns, array(
            'yks'   => EXYKS_PATH."/lang",
            'here'  => './lang'
    ));

    foreach($languages as $lang_key)
        foreach($base as $ns=>$path)
            $paths[$lang_key][$ns] = "$path/$lang_key";

    return $paths;
  }

  function resolve_path($ns, $path){
    $mask = '#^locale://('.join('|',array_keys($ns)).')#e';
    $repl = '$ns["$1"]';
    $path = preg_replace($mask, $repl, $path);;
    return paths_merge(ROOT_PATH, $path);
  }

    //this function only purpose is to be called as data::reload("entities");
  public static function retrieve(){
    self::fetch_all();
    return false; // data::reload will fetch it, finally
  }

  public static function fetch_all($trace=false){
    $locales          = config::retrieve("locales");
    $languages        = exyks::retrieve('LANGUAGES');

    self::$locales_ns = self::default_ns(attributes_to_assoc($locales, self::prefix_ns));
    $languages_order  = preg_split(VAL_SPLITTER, yks::$get->config->languages['order']);
    $languages_order  = array_intersect($languages_order, $languages);

    $tmp_paths = array();
    if($locales->locales_paths->path) foreach($locales->locales_paths->path as $path){
        $lang_keys = $path['lang_key']?array((string)$path['lang_key']):$languages;
        foreach($lang_keys as $lang_key)
            $tmp_paths[$lang_key][] = self::resolve_path(self::$locales_ns[$lang_key], $path['path']);

    } self::$locale_paths = $tmp_paths;

    $done = array();
    if(!$languages_order) return $done;

    $entities = array();
    foreach($languages_order as $lang_key){
        $flag_full = "entities_$lang_key";
        $entities = self::load_entities((string)$lang_key, $entities);
        data::store($flag_full, $entities);
        $done[] = array($lang_key, count($entities));
    } return $done;
  }

  private static function load_entities($lang_key, $entities=array()){ 
 
    $config = yks::$get->config;

    $dyn_entities = array();
    if($config->dyn_entities)
      foreach($config->dyn_entities->children() as $entity_def){
        if(strpos($entity_def['options'],"cachable")===false)continue;
        $dyn_entities = array_merge($dyn_entities,
            locales_renderer::render($entity_def->getName(), false, $lang_key));
    }
    $entities = array_merge($dyn_entities, $entities);

    $files = array();
    if(self::$locale_paths[$lang_key]) foreach(self::$locale_paths[$lang_key] as $path)
        $files = array_merge($files, files::find($path,'\.ent$',files::FIND_FOLLOWLINK));

    foreach($files as $dtd_file)
        $entities = array_merge($entities, dtd::ent_get($dtd_file));

    return $entities;
  }

}