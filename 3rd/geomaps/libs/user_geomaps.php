<?php



class user_geomaps extends geomaps {
  private $area_user   = array();
  private $users_colors = array();

  static $toggle_user_callback;

  function __construct($map_id, $users_colors = false){
    parent::__construct($map_id);
    $verif_map = compact('map_id');

    sql::select("ks_users_geomaps_area", $verif_map);
    $this->area_user = sql::brute_fetch("area_id", "user_id"); //one area per user in this one

    if($users_colors === false) {
        $users_list  = array_unique(array_filter(array_values($this->area_user)));
        $users_colors = array();
        foreach($users_list as $user_id)
            $users_colors[$user_id] = self::user_color($user_id);
    }
    $this->users_colors = $users_colors;
  }

  public static function filter_zipcode($areas_list, $addr_zipcode){
    $success = array();

    foreach($areas_list as $area_id){
        if(starts_with($area_id, "fra-dep-")
           && starts_with($addr_zipcode, strip_start($area_id, "fra-dep-")) )
        $success ["fra-dep"]= $area_id;

        if(starts_with($area_id, "fra-75-")
           && starts_with($addr_zipcode, "750".strip_start($area_id, "fra-75-")) )
        $success ["fra-75"]= $area_id;
    }

        //prefer paris district
    return array_sort($success, array('fra-75', 'fra-dep'));
  }



  static function user_color($user, $hex = false){
    $user_id = is_numeric($user)?$user:$user['user_id'];
    $dec = hexdec(substr(md5($user_id),0,6));
    return $hex ? substr("000000".dechex($dec),-6) : $dec;
  }


  function __get($key){
    if(method_exists($this, $getter = "get_$key"))
        return $this->$getter();

    return $this->data[$key];
  }

  function get_root_user(){
    return $this->data['user_id'];
  }

  function get_area_user(){
    return $this->area_user;
  }

  function toggle_user_at($x,$y, $user_id){

    $area_id = $this->png_map->hash_key_at($x, $y);
    if(!$area_id)
        return;

    $map_id = $this->data['map_id'];

    $verif_area = compact('area_id', 'map_id');

    $previous_user = sql::value('ks_users_geomaps_area', $verif_area, 'user_id');

    if(self::$toggle_user_callback)
      call_user_func(self::$toggle_user_callback, $map_id, $area_id, $user_id, $previous_user);

    if(isset($this->area_user[$area_id]))
        sql::delete("ks_users_geomaps_area", $verif_area);

    $data = compact('area_id', 'user_id');
    $data['map_id'] = $map_id;
    sql::insert("ks_users_geomaps_area", $data);
    $this->area_user[$area_id] = $user_id;
  }

  function render($default_color = imgs::COLOR_WHITE) {
    $this->area_colors = array();
    foreach($this->area_user as $area=>$user_id) {
        if(!$this->users_colors[$user_id]) continue;
        $this->area_colors[$area] = $this->users_colors[$user_id];
    }
    parent::render($default_color);
  }
}
