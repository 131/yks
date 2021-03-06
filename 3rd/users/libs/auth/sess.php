<?php

class sess  {
  static $sess = array();
  static $id = false;

  static $connected = false;
  static $renewed = false;

  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    if(sess::$id) return false;
    session_name(SESSION_NAME);
    session_set_cookie_params (0, "/", SESS_DOMAIN, false, true);
    self::$id = $_COOKIE[SESSION_NAME];
  }

    //need 5.3 late static binding self::_class
  static protected $_storage = array();
  static function store($key, $value){
    $key = SITE_CODE.$key;
    return self::$_storage[$key] = $value;
  }

  static function fetch($key){
    $key = SITE_CODE.$key;
    return self::$_storage[$key];
  }

    //deprecated
  static function retrieve($key){ return self::fetch($key); }

  static function flag_ks($connected = true){
    $id = $connected ? self::$id : $_COOKIE[SESSION_NAME];
    return crpt($id, FLAG_SESS, 10);
  }

  public static function cors_connect(){
    $session_id = null;
    $origin_host = null;
    
    if($_SERVER['HTTP_ORIGIN']){
      $parse_origin_url = parse_url($_SERVER['HTTP_ORIGIN']);
      $origin_host = $parse_origin_url['host'];
      if($parse_origin_url['host'] == SITE_DOMAIN)
        $session_id = $_POST['token'];
    }
        
    self::connect($session_id);
  }
  
  
  static function connect($session_id = null){
    if($session_id) session_id($session_id) ;

    // PHP 5.4
    if(function_exists('session_status') && session_status() !== PHP_SESSION_ACTIVE)
      session_start();

    self::$sess = &$_SESSION['user'];
    self::$id = session_id();
    self::$_storage = &$_SESSION['storage'];
    self::status_check();

    if($_POST)
      self::log($_POST);
  }

  private static function log($data, $key = 'data'){
    $user_id = self::$sess['user_id'];
    if(!$user_id) return;

    if(is_array($data)) unset($data['user_pswd']);

    $message = json_encode(array(
      'time'  => _NOW,
      'ip'    => $_SERVER['REMOTE_ADDR'],
      'agent' => $_SERVER['HTTP_USER_AGENT'],
      'uri'   => $_SERVER['REQUEST_URI'],
      $key    => $data,
    ));
    $log_dir  = sprintf('%s/users_sess/%s', sys_get_temp_dir(), SITE_DOMAIN);
    files::create_dir($log_dir);
    $log_file = "{$log_dir}/".$user_id;
    file_put_contents($log_file, $message.",".CRLF,  FILE_APPEND);
  }

  static function logout(){
    $_COOKIE['user_id'] = false;
    setcookie("user_pswd_".sess::$sess['user_id'], false);
    self::renew(); 
    rbx::ok("&auth_deco;");
  }

  static function renew(){
      //sess::renew is sufficient to open a session
    if(!self::$id) self::connect(); 

    setcookie('user_id', false, 0, "/", SESS_DOMAIN);
    sess::$sess     = new _ArrayObject();
    sess::$_storage = array();
    self::$renewed  = true;
    $sess_infos     = auth::valid_tree(exyks::retrieve('USERS_ROOT'));
    if($sess_infos) sess::$sess = $sess_infos;
  }

  static function close(){ return session_write_close(); }

  static function status_check(){
    $user_id = array_get(self::$sess, 'user_id');
    self::$connected = $user_id && $user_id != exyks::retrieve('USERS_ROOT');
  }

  static function update($user_id, $skip_auth = false){
    $sess_infos = auth::valid_tree($user_id, $skip_auth);
    if(!$sess_infos) return false;
    
    self::load($sess_infos['user_id'], $sess_infos['users_tree']);
    return true;
  }

  static function load($user_id, $users_tree){ //private ? - no, force tree
    sess::$sess     = user::instanciate($user_id, $users_tree);
    sess::$sess->sql_update(array('user_connect'=>_NOW), "ks_users_profile");
    sess::$_storage = array();

    self::log(sess::$sess, "log");

    $_SESSION['client_addr']   = exyks::$REMOTE_ADDR;
    $_SESSION['remote_addr']   = $_SERVER['REMOTE_ADDR'];
    $_SESSION['session_create'] = _NOW;
    self::status_check();
  }

  static function reload(){
    self::load(sess::$sess['user_id'], sess::$sess['users_tree']);
  }

}
