<?php

class Element extends XHTMLElement {

  function getElements($expression){
    $local = array();
    return Selectors_Utils::search($this, $expression, $local);
  }

  function getElement($expression){
    return current($this->getElements($expression));
  }

  function match($selector) {
    if(!$selector || $selector == $this) return true;

    $tree = Selectors_Utils::tokenize($selector);  $selector = $tree[0][0]['members'];

    if( (($tag = $selector[0]['tag']) || ($id = $selector[0]['id']))
        && !(Selectors_Filters::ById($this, $id) && Selectors_Filters::byTag($this, $tag)))
            return false;

    $parsed = Selectors_Utils::parseSelector($selector);
    return $parsed ? Selectors_Utils::filter($this, $parsed, array() ):true;
  }

  function clean(){
    $c=0; $name = $this->getName();
    foreach ($this->getParent()->$name as $node) {
        if($node == $this) {
            unset($this->getParent()->{$name}[$c]);
            return $this;
        } $c++;
    }
  }

  function get($key, $opts = false){
    if($key=="tag")
        return strtolower($this->getName());
    if($key=="text") {
        $clean = (bool) $opts;
        $str = dom_import_simplexml($this)->textContent;
        if($clean) $str = preg_replace("#[\s\r\n]+#"," ", trim($str));
        return $str;
    } elseif($key=="html")
        return $this->asXML();
    elseif($key=="innerHTML")
        return innerHTML($this->asXML());

    return (string)$this[$key];
  }
}


