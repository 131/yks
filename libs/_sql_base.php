<?
    //extending ArrayObject should have worked, but it break session storage
abstract class _sql_base  implements ArrayAccess {

   protected $sql_table=false;
   protected $sql_key=false;
   private $data;

  function __construct($from){
    if(!($this->sql_table && $this->sql_key))
        throw "Invalid definition for _sql_base";

    if(is_array($from))
        $this->feed($from);
    else $this->from_id((int)$from);
  }

  function feed($data){
    $this->data = $data;
  }

  function __get($key){
    if(isset($this->data[$key]))
        return $this->data[$key];
    if(method_exists($this, $getter = "get_$key")
        || $this->manager && method_exists($this->manager, $getter))
        return $this->$getter();
  }

  function __sql_where(){
    $key = $this->sql_key;
    return array($key=> $this->$key);
  }

  function from_id($key_id){
    $verif_base = array($this->sql_key => $key_id);
    $data = sql::row($this->sql_table, $verif_base);
    $this->feed($data);
  }

    //proxies to manager's static functions
  function __call($method, $args){
    if(!($this->manager && method_exists($this->manager, $method))) return;
    array_unshift($args, $this);
    return call_user_func_array(array($this->manager, $method), $args);
  }


  static function from_where($class, $sql_table, $sql_key, $where) {//, optionals
    $args = array_slice(func_get_args(),4); //retrieve optionals args
    sql::select($sql_table, $where); $tmp = array();
    if(!$args) foreach(sql::brute_fetch($sql_key) as $key_id=>$key_infos)
        $tmp[$key_id] = new $class($key_infos);
    else {
        $class = new ReflectionClass($class);
        foreach(sql::brute_fetch($sql_key) as $key_id=>$key_infos){
            $args_tmp = array($key_infos); foreach($args as $arg) $args_tmp[] = $arg==PH?false:$arg[$key_id];
            $tmp[$key_id] = $class->newInstanceArgs($args_tmp);
        }
    } return $tmp;
  }

  static function extract_where($array){
    if(!$array) return array();
    return array(($key = current($array)->sql_key) => array_values(array_extract($array, $key)));
  }


  static function from_ids($class, $sql_table, $sql_key, $ids) {
    $results = self::from_where($class,  $sql_table, $sql_key, array($sql_key=>$ids));
    return array_sort($results, $ids);
  }

  function update($data, $table = false){
    $res = sql::update($table?$table:$this->sql_table, $data, $this);
    if($res) foreach($data as $k=>$v) $this->_set($k, $v); //array_walk wrong arg order :/
    return $res;
  }

  function sql_delete(){ return sql::delete($this->sql_table, $this); }

  function _set($key, $value){
        //fonction temporaire, à refactoriser par update une fois _user correctement integré
    $this->data[$key] = $value;
    return $this;
  }

  function offsetExists ($key){ return isset($this->data[$key])||isset($this->$key); }
  function offsetGet($key){ return $this->$key;}
  function offsetSet($offset,$value){}
  function offsetUnset($key){unset($this->data[$key]); }

}
