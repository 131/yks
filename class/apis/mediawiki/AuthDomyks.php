<?php
 

/*
    Client Side
*/

class Auth_Domyks extends AuthPlugin {
  private $wsdl_url;
  private $host_key;
  private $ext_sess; //distant session
  private $ext_user; //distant user
  private $access_zone;
  private $session_id;
  private $groups = array();

  public function __construct($wsdl_url, $access_zone){
    $this->wsdl_url     = $wsdl_url;
    $this->access_zone  = $access_zone; 
    $tmp = parse_url($this->wsdl_url);
    $this->host_key = substr(md5($tmp['host']),0,5);
  }

  public function autoCreate(){ return true; }  // require pour que domyks prenne la main
  public function userExists( $user_name ) { return true;}  // stfu
  public function strict() { return true;}

  public function updateUser(&$user){
    //Save names
    $user->mRealName = $this->ext_user['user_name'];
    $user->mEmail    = $this->ext_user['user_mail'];
    
    //Apply groups
    foreach($this->groups as $group_name => $active){
      $in_db = in_array($group_name, $user->mGroups);
      if($active && !$in_db)
        $user->addGroup($group_name);
      if(!$active && $in_db)
        $user->removeGroup($group_name);        
    }
    
    $user->saveSettings();
    return true;
  }

  public function authenticate($user_login, $user_pswd){
    global $wgExtraNamespaces;

    try {
      $user_login = strtolower($user_login);
      $this->ext_sess = new  SoapClient($this->wsdl_url, array('cache_wsdl' => WSDL_CACHE_NONE));
      $this->session_id = $this->ext_sess->login($user_login, $user_pswd);
      $this->ext_user = unserialize($this->ext_sess->getUser($this->session_id));

      //Basic access right
      $auth = $this->ext_sess->verifAuth($this->session_id, $this->access_zone, "access");

      //Save groups status, based on namespaces
      $access = unserialize($this->ext_sess->getAccesses($this->session_id));
      foreach($wgExtraNamespaces as $ns_id => $ns_name){
        if($ns_id % 2 != 0) continue; // skip talk
        $access_right = "office:wiki_".strtolower($ns_name);
        $this->groups[$ns_name] = isset($access[$access_right]);
      }

      return $auth;
    } catch(Exception $e) {
      return false;
    }
  }

  public function initUser( &$user, $autocreate=false ) {
    //$user->mName     = sprintf("%s:%s", $this->host_key, strtolower($user->mName));
    $user->mRealName = $this->ext_user['user_name'];
    $user->mEmail    = $this->ext_user['user_mail'];
  }
  
  public function addNamespace($name, $base_id){
    global $wgExtraNamespaces;
    global $wgNamespacePermissionLockdown;
    global $wgContentNamespaces;
    global $wgGroupPermissions;

    //Create namespace & talk
    $wgExtraNamespaces[$base_id] = $name;
    $wgExtraNamespaces[$base_id+1] = "{$name}_talk";

    //Lockdown protection
    $wgGroupPermissions[$name] = array(); // No rights for ns group
    $wgNamespacePermissionLockdown[$base_id]['*'] = array($name);
    $wgNamespacePermissionLockdown[$base_id+1]['*'] = array($name);

    //Add namepace to content
    $wgContentNamespaces[] = $base_id;
  }
}
