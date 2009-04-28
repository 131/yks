<?


class xml {
  private static $dtds_paths = array();

  static function load_file($file_path, $FPI){
    $fpi = self::$dtds_paths[$FPI];
    if(!$fpi) throw new Exception("Unknow fpi");

    $search_mask = '#<\!DOCTYPE\s+(%s)\s+PUBLIC\s+"%s"[^>]*>#';
    $search_mask = sprintf($search_mask, $fpi['root_mask'], $fpi['fpi_mask']);
    $replace = '<!DOCTYPE $1 SYSTEM "'.$fpi['dtd_path'].'">';

    $contents = file_get_contents($file_path);
    $contents = preg_replace( $search_mask, $replace, $contents);

    $doc      = new DomDocument("1.0", "UTF-8");
    $doc->loadXML($contents, LIBXML_MYKS);
    if(!$doc->validate()) throw new Exception("Invalid syntax");
    return $doc;
  }

  static function register_fpi($FPI, $dtd_path, $root_element=false) {
    self::$dtds_paths[$FPI] = array(
        'dtd_path'=>$dtd_path,
        'root_mask'=>$root_element?$root_element:"[a-z]+", //anonymous root preg
        'fpi_mask'=> preg_quote($FPI, '#')
    );
  }

}