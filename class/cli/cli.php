<?

class cli {
  const OS_UNIX = 1;
  const OS_WINDOWS = 2;
  private static $OS = null;

  const pad = 70;

  public static function init(){
    if(!classes::init_need(__CLASS__)) return;

    $win = stripos($_SERVER['OS'],'windows')!==false;
    self::$OS = $win ? self::OS_WINDOWS : OS_LINUX;

      //transcoding UTF-8 to IBM codepage
    if(self::$OS & self::OS_WINDOWS)
      ob_start(array('cli', 'console_out'), 2);

  }


  static function console_out($str){
    return charset_map::Utf8StringDecode($str, charset_map::$_toUtfMap);
  }

  static function console_in($str){
    return charset_map::Utf8StringEncode($str, charset_map::$_toUtfMap);
  }

  public static function pad($title='', $pad = '─', $MODE = STR_PAD_BOTH, $mask = '%s', $pad_len = self::pad){
    $pad_len -= mb_strlen(sprintf($mask, $title));
    $left = ($MODE==STR_PAD_BOTH) ? floor($pad_len/2) : 0;
    return sprintf($mask, 
            str_repeat($pad, max($left,0)) . $title . str_repeat($pad, max($pad_len - $left,0)));
  }


  public static function box($title, $msg){
    $args = func_get_args(); $pad_len = self::pad;

    for($a=1;$a<count($args);$a+=2) {
      $msg= &$args[$a];
      if(!is_string($msg)) $msg = print_r($msg, 1);
      $msg = explode("\n", trim($msg));
      $pad_len = max($pad_len, max(array_map('strlen', $msg))+2); //2 chars enclosure
    }

    for($a=0; $a<count($args); $a+=2) {
      echo self::pad(" {$args[$a]} ", "═", STR_PAD_BOTH, $a?"╠%s╣":"╔%s╗", $pad_len).LF;
      foreach($args[$a+1] as $line)
          echo self::pad($line, " ", STR_PAD_RIGHT, "║%s║", $pad_len).LF;
    }

    echo self::pad('', "═", STR_PAD_BOTH, "╚%s╝", $pad_len).LF;
  }


  public static function password_prompt(){
    if(self::$OS & self::OS_WINDOWS) {
        $pwObj = new Com('ScriptPW.Password');
        $password = $pwObj->getPassword();
    } else {
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
    } return $password;
  }

  public static function text_prompt($prompt=false, &$args = null){
    if($prompt) echo "$prompt : ";

    $data_str = "";
    do {
        $line =  fread(STDIN, 1024);

        if(preg_match("#[\x01-\x1F]\r?\n$#", $line, $out)) {
            $control = ord($out[0]);
            $line = substr($line, 0, -strlen($out[0]));
        } else $control = false;

        if(self::$OS & self::OS_WINDOWS) 
            $line = self::console_in($line);

        $data_str .= $line;
        $args = self::parse_args(trim($data_str), $complete);
    } while( ! ($complete || in_array($control, array(26))) );

    if($control == 26) $args = array();
    return trim($data_str);
  }

/**
* @param int control return the control key (if present) of the last input
*/
  public static function parse_args($str, &$complete = null) {
    $mask      = "#(\s+)|([^\s\"']+)|\"([^\"]*)\"|'([^']*)'#s";

    $args = array(); $need_value = true; $digest = "";
    preg_match_all($mask, $str, $out, PREG_SET_ORDER);

    foreach($out as $part_id => $step){
        list($sep, $value) = array($step[1]!='', pick($step[2], $step[3], $step[4]));
        $digest .= $step[0];

        if($digest != substr($str,0, strlen($digest)) )
            break;

            //check "value"/separator alternance
        if($sep) { $need_value = true; continue; }
        if(!$need_value) break; $need_value = false;

        $args[] = $value;
    }
    $complete = ($digest == $str);
    return $args;
  }

  function exec($cmd){
    $WshShell = new COM("WScript.Shell");
    return $WshShell->Run($cmd);
  }
}