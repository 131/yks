<?

/* 131's
*  http://doc.exyks.org/wiki/Source:ext/http/proxy
*/

class http_aserver {

  private $ip;
  private $client_bind;
  private $port;  
  private $server;

  protected $trace        = true;
    //use binding on connect mode
  protected $bind_on_ssl  = false;

  function __construct($ip = '0.0.0.0', $port = '8080'){
    $bind = "tcp://$ip:$port";
  
    $this->server = stream_socket_server($bind, $errno, $errstr);
    if (!$this->server) 
      throw new Exception ("$errstr ($errno)");
  }

  function close(){
    fclose($this->server);
  }


  function run(){
    $this->wait_for_clients();
  }

  protected function wait_for_clients(){
    if($this->trace) echo "Waiting for clients \r\n";
    $serve_client = extension_loaded('pcntl') ? "fork_client" : "serve_client";
    //$serve_client = "serve_client";

    do {
      $client = @stream_socket_accept($this->server);
      if($client)
         $this->$serve_client($client);
    } while(true);
    $this->close();
  }


  private function fork_client($client){
    $pid = pcntl_fork();
    if ($pid == -1) {
         die('could not fork');
         return;
    } else if ($pid) {
        pcntl_wait($c_status,WNOHANG);
        fclose($client);
        return; //waitagain

    } else {
        $this->serve_client($client);
        die;
    }
  }

/****************************** ***********************/

  protected function serve_std($dest_stream, $client_stream){
      $this->stream_copy_to_stream($dest_stream, $client_stream);
  }

  protected function serve_client($client_stream){
    list($method, $url_infos, $request_str) = $this->read_request($client_stream);

    return array($method, $url_infos, $request_str);
  }


  protected function read_request($client_stream) {
    $request_str = $this->read_headers($client_stream);
    if($this->trace) echo $request_str;
    $mask = '#^([A-Z]+)\s*(.*?)\s*HTTP/[0-9]\.[0-9]\r\n#';
    if(!preg_match($mask, $request_str, $out))
        return null;

      //drop the request first line
    $request_str = substr($request_str, strlen($out[0]));

    list(, $method, $url, $version) = $out;
    $url_infos = parse_url($url);

    return array($method, $url_infos, $request_str);
  }

  /******** Utils : statics ***************************/

 protected function read_headers($lnk){
    $headers_str = "";
    do {
      $line = fgets($lnk);
      $headers_str .= $line;
    } while($line != CRLF && $lnk);
    return $headers_str;
   }


  protected function stream_copy_to_stream($src, $dest){
    while($tmp=fread($src,1024)) fwrite($dest, $tmp);
  }

  protected function stream_copy_to_stream_nb($src, $dst, $content_length){
    $sent_bytes = 0;
    stream_set_blocking($src, 0);
    while($sent_bytes< $content_length) {
      $read = array($src);
      $res = stream_select($read, $null, $null, null);
      if($res === false) break;
      if(feof($src) || feof($dst)) break;
      $sent_bytes += fwrite($dst, fread($src, 8193));
    }
  }

  protected function tunnel_streams($src, $dst){

    do {
      $read = array($src, $dst);
      $res = stream_select($read, $null, $null, NULL);
      if($res === false) break;
      //if($res === 0) continue; //should not append
      if(in_array($src, $read, true)) fwrite($dst, fread($src, 8193));
      if(in_array($dst, $read, true)) fwrite($src, fread($dst, 8193));

      if(feof($src) || feof($dst)) break;

    } while (true);
    fclose($src);
    fclose($dst);

  }



}