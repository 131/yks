<?

class cookiejar {

  private $cookies_list; //domain indexed cookie list

  function __construct(){

    $this->cookies_list = array();
  }

  function retrieve($url){
    $cookies = array();
    foreach($this->cookies_list as $cookie) {
        if(!$cookie->match($url)) continue;
        $cookies[] = $cookie;
    }

    return $cookies;

  }

  function store($cookie){
    $old_cookie = $this->find($cookie);
    if($old_cookie) $this->delete($old_cookie);
    if(!$cookie->is_valid()) return;
    $this->cookies_list[] = $cookie;
    
  }
  function delete($cookie){
    $key = array_search($cookie, $this->cookies_list);
    if( $key === false) return false;
    unset($this->cookies_list[$key]);
    return true;
  }

  function find($search_cookie){
    $search_id = $search_cookie->hash;
    foreach($this->cookies_list as $cookie)
        if($cookie->hash == $search_id) return $cookie;
  }




  private function cookie_stack($old, $new){
    die("jere");
    if(!$old) return $new; if(!$new) return $old;
    
//    if(strlen($old->sub)==strlen($new->sub))
  }

}