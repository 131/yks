<?

class highlight_xml {
  private $parser;
  private $str;
  private $depth =0;
  private $empty_node=false;
  private $inline_node=null;

  private $no_pad = array("br", "img", "span", "b");


  public  static function highlight($str){
    $highlighter = new self();
    return $highlighter->parse($str);
  }

  function parse($str){
    xml_parse($this->parser, $str);
    return $this->contents;
  }

  function __construct(){
    $this->parser = xml_parser_create();

    xml_set_object($this->parser, $this);
    xml_set_element_handler($this->parser, "tag_open", "tag_close");
    xml_set_character_data_handler($this->parser, "cdata");
    xml_set_default_handler($this->parser, "std");
    xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
  }

  private function tag_open($parser, $name, $attribs) {
    $this->pos_pad('tag', $name);
    $this->depth++;

    $this->feed("&lt;%s", $this->format($name, 'xml_tag') );
    if ($attribs) foreach ($attribs as $k => $v)
        $this->feed(' %s="%s"', $this->format($k,"xml_attr"), $this->format($v, 'xml_val'));
    $this->feed("&gt;"); 
    $this->empty_node = true;
  }

  private function tag_close($parser, $name) {
    $this->depth--;
    if($this->empty_node) {
        $this->contents = preg_replace('/&gt;?$/', '',$this->contents);
        $this->feed("/&gt;");
    } else {
        $this->pos_pad( 'tag', $name);
        $this->feed("&lt;/%s&gt;", $this->format($name, 'xml_tag'));
    }
  }

  private function cdata($parser, $str) {
    if(!trim($str)) return;
    $this->pos_pad('cdata');
    $this->feed($str);
  }

  private function std($parser, $str) {
    $this->pos_pad('std');
    $entity = preg_match('#^&[^& ]+;$#', $str); 
    $class = $entity?"xml_entity":"xml_text";
    $this->feed("%s", $this->format(htmlspecialchars($str), $class));
  }

  private function feed($str){
    $this->empty_node = false; $args = func_get_args();
    $this->contents .= vsprintf($str, array_slice($args,1) );
  }

  private function format($str, $theme) { return "<span class='$theme'>$str</span>"; }

  private function pos_pad($type,  $name=false){
    $inline_now = in_array($name, $this->no_pad) || in_array($type, array('cdata', 'std')) ;
    $nl = ! ($inline_now && $this->inline_node || is_null($this->inline_node) );
    if($nl) $this->feed("<br/>".str_repeat("&#160;", $this->depth*2));
    $this->inline_node = $inline_now;
  }

}

