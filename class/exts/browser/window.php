<?php

class window extends __native {
  private $browser;
  public $url;
  public $document;


  function __construct($browser){
    $this->browser = $browser;

  }

  function get_cookies($name = false) {
    return $this->browser->get_cookies($this->url, $name);
  }

  function extract_url($from){

    if($from instanceof domElementWrapper){
        $tagname = $from->getName();
        if($tagname == "img") {
            $url = new url((string)$from['src']);
        }
        //if(!$url->is_browsable)
        //    $url = $from->document->window->url->merge($url);

    } else {
        $url  = $from;
    }

    $url = url::from($url);

    if(!$url->is_browsable)
        $url = $this->url->merge($url);

    return $url;
  }

  function download($from, $out = false){
    $url = self::extract_url($from);

    $headers = array();
    if($this->referer)
      $headers["Referer"] = (string)$this->referer;

    return $this->browser->download($url, $out, $headers);

  }


    //return void /self so it can be chained
  function go($url, $method = 'GET', $data  = array(), $enctype =false) {

    $url = url::from($url);

    if($method == 'GET' && $data) {
        $url->set_query(http_build_query($data, null, '&'));
        $data = false;
    }

    $old_referer = null;

    if(is_null($this->url))
        $this->url = $url;
    else {
        $old_referer = $this->url;
        $this->url = $this->url->merge($url); //history HERE
    }
    if(!$this->url->is_browsable)
        throw new Exception("Invalid url");

    $this->referer = $this->url;
    $lnk = $this->browser->get_lnk($this->url);

    $headers = array();
    if($old_referer)
        $headers["Referer"] = (string)$old_referer;

    $query = new request($this->url, $method, $data, $enctype );
    $query->addHeaders($headers);
    $lnk->execute($query);

    $content_type = $lnk->response['headers']['Content-Type'];
        //could abort 

    if($lnk->url != $this->url)//has been redirected
        $this->url = $lnk->url;

    $out_stream = fopen('php://temp', 'w');
    $lnk->receive($out_stream);
    rewind($out_stream);
    $str = stream_get_contents($out_stream);


    if(strtolower($content_type->value)== "application/octet-stream") {
      $this->document = $str;
    } else {
      $charset = strtolower($content_type->extras['charset']);
      $document = new document($this, dom::simplexml_load_html($str, $charset) );
      $reloc = $document->reloc;
      if($reloc)
          return $this->go($reloc);
      $this->document = $document;
      if(!$charset)
        $this->get_charset();
    }

    return $this;

  }

  function get_charset(){
    return $this->document->charset;
  }

  function submit($element, $data=array()){

    $tag = $element->get("tag");
    if($tag == "input") {
        $form = $element->getParent("form");
        $data[(string)$element['name']] = (string)$element['value'];
    } else $form = $element;


    $enctype = (string)$form['enctype'];
    $data = array_merge($form->toQueryString(), $data);

    $action  = $form['action'] ? new url((string) $form['action']) : $this->url;
    $method  = pick_in(strtoupper($form['method']), "GET", array("GET", "POST"));

    $this->go($action, $method, $data, $enctype );
  }


}
