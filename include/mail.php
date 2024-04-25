<?php
require_once(BASE."include/config.php");

require_once('PEAR.php');
require_once('Mail.php');
require_once('Mail/mime.php');

function mail_appdb($sEmailList,$sSubject,$sMsg)
{
    // NOTE: For AppDB developers: If email is disabled return from this function
    //  immediately. See include/config.php.sample for information
    if(defined("DISABLE_EMAIL"))
        return;

    // make email list
    $aEmailList = explode(" ",$sEmailList);
    if (empty($aEmailList))
    {
        addmsg("Error while sending e-mail", "red");
        return false;
    }

    // Build message body
    $sMsg  = trim(str_replace("\r\n","\n",$sMsg));
    $sMsg  = $sSubject."\n-------------------------------------------------------\n".$sMsg."\n\n";
    $sMsg .= "Best regards.\n";
    $sMsg .= "The AppDB team\n";
    $sMsg .= APPDB_ROOT."\n";
    $sMsg .= "\n\nIf you don't want to receive any other e-mail, please change your preferences:\n";
    $sMsg .= APPDB_ROOT."preferences.php\n";

    /* Print the message to the screen instead of sending it, if the PRINT_EMAIL
       option is set.  Output in purple to distinguish it from other messages */
    if(defined("PRINT_EMAIL"))
    {
        $sMsg = str_replace("\n", "<br>", $sMsg);
        addmsg("This mail would have been sent<br><br>".
               "To: $sEmailList<br>".
               "Subject: $sSubject<br><br>".
               "Body:<br>$sMsg", "purple");
        return;
    }

    if (!class_exists('Mail') or !class_exists('Mail_mime'))
        trigger_error("Mail libs not installed!", E_USER_ERROR);

    $mime = new Mail_mime(array(
        'head_charset' => 'utf-8',
        "text_charset" => "utf-8",
        "html_charset" => "utf-8",
        "eol"          => "\n"
    ));
    $opts = array(
        'host'     => APPDB_MAIL_HOST,
        'port'     => APPDB_MAIL_PORT
    );
    if (defined(APPDB_MAIL_USER) and !empty(APPDB_MAIL_USER) and !empty(APPDB_MAIL_PASS)) {
        $opts['auth'] = true;
        $opts['username'] = APPDB_MAIL_USER;
        $opts['password'] = APPDB_MAIL_PASS;
        $opts['timeout'] = '5';
    }

    $bResult = true;
    try {
        $mail = Mail::factory('smtp', $opts);
        $headers = array(
            'To' => join(", ", $aEmailList),
            'From' => APPDB_SENDER_EMAIL,
            'Reply-To' => APPDB_SENDER_EMAIL,
            'Subject' => $sSubject,
            'X-Mailer' => APPDB_OWNER." mailer"
        );
        foreach ($headers as $name => $value)
        {
            $headers[$name] = $mime->encodeHeader($name, $value, "utf-8", "quoted-printable");
        }
        $to = array();
        foreach ($aEmailList as $sEmailItem)
        {
            $to[] = $mime->encodeHeader("to", $sEmailItem, "utf-8", "quoted-printable");
        }
        $sMsg = $mime->setTXTBody($sMsg);
        $sMsg = $mime->get();
        $ret = $mail->send($to, $headers, $sMsg);
        if (PEAR::isError($ret))
        {
            $errMsg = $ret->getMessage();
            addmsg($ret->getMessage(), "red");
            return false;
        }
    } catch (Exception $e) {
        $bResult = false;
    }

    if($bResult)
        addmsg("E-mail sent", "green");
    else 
        addmsg("Error while sending e-mail", "red");
    return $bResult;
}
?>
