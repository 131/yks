<?php



class smtp_lite {
  public static $trace = false;

  private static function server_sync($sock,$response){
    while($sock && !preg_match("#^[0-9]{3}(?=\s)#",$tmp=fgets($sock,256),$out) );
    if($out[0]!=$response)
        throw new Exception("Sync {$out[0]}!={$response} : $tmp");
  }


  public static function mail($body, $headers){
    if($body === true && PHP_SAPI == 'cli') 
      $body = stream_get_contents(STDIN);
  

    $dests  = array_merge(
            $headers['To'] ?$headers['To'] : array(),
            $headers['Cc'] ?$headers['Cc'] : array());

    if(!$dests)
      throw new Exception("No valid dests");

    $headers["To"] = join(',', $headers["To"]);
    if(isset($headers['Cc']))
     $headers['Cc'] = join(',', $headers['Cc']);

    $contents = "";
    foreach($headers as $header_name => $header_value)
      $contents .= sprintf("%s: %s\r\n", $header_name, rfc_2047::header_encode($header_value));

    $contents.= CRLF.$body;

    return self::smtpsend($contents, $dests);
  }



  public static function smtpmail($to, $subject, $body, $headers = TYPE_TEXT){
    if($body === true && PHP_SAPI == 'cli') 
      $body = stream_get_contents(STDIN);
  
    $to = is_array($to) ? $to : array($to);

    $contents = $headers.CRLF;
    $contents.= "Subject: ".rfc_2047::header_encode($subject).CRLF;
    $contents.= "From: ".rfc_2047::header_encode("[".SITE_DOMAIN."] <webmaster@".SITE_DOMAIN.">").CRLF;
    $contents.= "To: ".rfc_2047::header_encode(join(',', $to)).CRLF;
    $contents.= CRLF.CRLF.$body;

    $name_mask = "#<\s*([^<]*)\s*>#";
    foreach($to as &$dest) {
      $dest = preg_match($name_mask, $dest, $out) ? $out[1] : $dest;
    }
    return self::smtpsend($contents, $to);
  }

/**
* $dests is ALL recipients list (TO/CC/CCi)
*/
  public static function smtpsend($contents, $dests){

    if(self::$trace) {
      rbx::error(print_r($dests,1));
      rbx::error(specialchars_encode($contents));
      return;
    }

    foreach(yks::$get->config->apis->iterate("smtp") as $smtp_config ) {
        try {
            $success = self::host_smtpsend($smtp_config, $contents, $dests);
            break;
        } catch(Exception $e){
            error_log("Smtp host : {$smtp_config['host']} failure ($e), continue");
            $success = false;
        }
    }

    if(!$success)
        throw new Exception("Unable to send mail, general smtp failure");

    return true;
  }

/**
* $dests is ALL recipients list (TO/CC/CCi)
*/
  private static function host_smtpsend($smtp_config , $contents, $dests){

    $smtp_sender = $smtp_config['sender'];
    $smtp_port   = pick($smtp_config['port'], 25);

    $sock=fsockopen($smtp_config['host'], $smtp_port, $errno, $errstr, 20);
    self::server_sync($sock, "220");
    fputs($sock, "EHLO ".$smtp_config['host'].CRLF);
    self::server_sync($sock, "250");

    if(bool($smtp_config['tls'])){
      fputs($sock, "STARTTLS".CRLF);
      self::server_sync($sock, "220");
      if(!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
        throw new Exception("Could not start tls negociation");

      fputs($sock, "EHLO ".$smtp_config['host'].CRLF);
      self::server_sync($sock, "250");
    }


    if($smtp_config['login'] ) {
        fputs($sock, "AUTH LOGIN".CRLF);
        self::server_sync($sock, "334");

        fputs($sock, base64_encode($smtp_config['login']).CRLF);
        self::server_sync($sock, "334");

        fputs($sock, base64_encode($smtp_config['pass']).CRLF);
        self::server_sync($sock, "235");
    }

    fputs($sock, "MAIL FROM: <$smtp_sender>".CRLF);
    self::server_sync($sock, "250");


    $errors = array();
    foreach($dests as $mail_to){
      try {
        fputs($sock, "RCPT TO: <$mail_to>".CRLF);
        self::server_sync($sock, "250");
      } catch(Exception $e){
        error_log($e);
        $errors[] = $mail_to;
      }
    }
    if($errors)
        rbx::error("Not all recipient are valid (".join(', ',$errors).")");


    fputs($sock, "DATA".CRLF);
    self::server_sync($sock, "354");

   $message = $contents;
   $message.= CRLF.".".CRLF;

        //die($message);
    fputs($sock, $message);

    self::server_sync($sock, "250");
    fputs($sock, "QUIT\r\n");
    fclose($sock);
    return true;
  }



}



