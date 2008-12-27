<?



function server_sync($sock,$response){
    while(!preg_match("#^[0-9]{3}(?=\s)#",$tmp=fgets($sock,256),$out) );
    if($out[0]!=$response) throw rbx::error("Sync {$out[0]}!={$response} : $tmp");
}

function smtpmail($to, $subject, $body, $headers = TYPE_TEXT){
    $contents = $headers.CRLF."Subject: $subject".CRLF;
    $contents.= "From: [".SITE_DOMAIN."] <webmaster@".SITE_DOMAIN.">".CRLF;
    $contents.= "To: $to".CRLF;
    $contents.= CRLF.CRLF.$body;

    $name_mask = "#<\s*([^<]*)\s*>#";
    $to = preg_match($name_mask, $to,$out)?$out[1]:$to;
    return smtpsend($contents, array($to));
}

function smtpsend($contents,$dests){
    $smtp_config = yks::$get->config->apis->smtp;
    $smtp_sender = $smtp_config['sender'];

    $sock=fsockopen($smtp_config['host'], 25, $errno, $errstr, 20);
    server_sync($sock, "220");
    fputs($sock, "EHLO ".$smtp_config['host'].CRLF);
    server_sync($sock, "250");

    fputs($sock, "AUTH LOGIN".CRLF);
    server_sync($sock, "334");

    fputs($sock, base64_encode($smtp_config['login']).CRLF);
    server_sync($sock, "334");

    fputs($sock, base64_encode($smtp_config['pass']).CRLF);
    server_sync($sock, "235");

    fputs($sock, "MAIL FROM: <$smtp_sender>".CRLF);
    server_sync($sock, "250");

    foreach($dests as $mail_to){
        fputs($sock, "RCPT TO: <$mail_to>".CRLF);
        server_sync($sock, "250");
    }
        
    fputs($sock, "DATA".CRLF);
    server_sync($sock, "354");

   $message = $contents;
   $message.= CRLF.".".CRLF;

        //die($message);
    fputs($sock, $message);

    server_sync($sock, "250");
    fputs($sock, "QUIT\r\n");
    fclose($sock);
    return true;
}


