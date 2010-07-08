<?
/** http://doc.exyks.org/wiki/Source:ext/css **/


class css_ruleset extends ibase  {
  private $selector; //string for now
  private $declarations;

  function __construct($selector) {
    $this->selector = $selector;
    $this->declarations = array();
  }

  function get_rules(){
    return $this->declarations;
  }

  function stack_declaration($declaration){
    $declaration->set_parent($this);
    $this->declarations[] = $declaration;
  }

  function get_selector(){
    return $this->selector;
  }
  
  function output(){
    $str = "";
    $str .= $this->selector;

    //declarations
    $str .= '{';
    end($this->declarations);
    $last = key($this->declarations);

    foreach($this->declarations as $i=>$declaration) {
        $is_last = $last == $i;
        $tmp = $declaration->output();
        $str .= $is_last ? substr($tmp, 0, -1) : $tmp;
    }
    $str .= '}';
    return $str;
  }

  function outputXML(){
    $selector = specialchars_encode($this->selector);
    $str = "<ruleset {$this->uuid} selector=\"$selector\">";
    foreach($this->declarations as $declaration)
        $str .= $declaration->outputXML();
    $str .= "</ruleset>";
    return $str;    
  }
}