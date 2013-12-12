<?

class torrent implements ArrayAccess {
  const MIME_TYPE = "application/x-bittorrent";
  private $struct;  
  private $file_path;
  function __construct($file){
    $this->struct = bencode::decode($file);
    $this->trackers_cleanup();
  }

  static function from_file($file_path){
    $tor = new self(file_get_contents($file_path));
    $tor->file_path = $file_path;
    return $tor;
  }

  function bencode(){
    return bencode::encode($this->struct);
  }



  function save($file_path = null){
    $file_path = pick($file_path, $this->file_path);
    if(!$file_path) throw new Exception("Invalid file path");
    file_put_contents($file_path, $this->bencode());
  }

  function trackers_cleanup(){
    $announce = $this['announce'];
    if($announce)
      $this->tracker_add($announce);

    if($this['announce-list'])
    foreach($this['announce-list'] as $tid=>$tracker){
      if(! array_filter( $this->struct['announce-list'][$tid]) )
          unset($this->struct['announce-list'][$tid]);
    }

  }

  function tracker_exclude($domain){
    if($this['announce-list'])
    foreach($this['announce-list'] as $tid=>$tracker){
       foreach($tracker as $aid=>$announcestr) {
        $announce = @parse_url($announcestr);
        if(ends_with($announce['host'], $domain) || $announcestr== $domain)
          unset($this->struct['announce-list'][$tid][$aid]);
      }
    }

    $announce = parse_url($this['announce']);
    if(ends_with($announce['host'], $domain) || $this['announce'] == $domain) {
      if(!$this['announce-list'])
        throw new Exception("No tracker to fallback");
       $this->struct['announce'] = first(first($this['announce-list']));
    }

    $this->trackers_cleanup();
  }

  function tracker_add($tracker){
    $announce = $this['announce']; $this['announce'] = null;

    if(!in_array($tracker, $this->trackers))
      $this->struct['announce-list'][] = array($tracker);
    $this['announce'] = $announce;
  }

  function __toString(){
    $struct = $this->struct;
    unset($struct['info']['pieces']);
    return print_r($struct,1);
  }


  function offsetGet ($key){ return $this->struct[$key];}
  function offsetSet($key, $v) { return $this->struct[$key]= $v; }
  function offsetExists( $key){ }
  function offsetUnset($key){ unset($this->struct[$key]); }

  function __get($key){
    if(method_exists($this, $getter = "get_$key"))
      return $this->$getter();
    if(method_exists('torrents', $getter))
      return call_user_func(array('torrents', $getter), $this);
  }
}