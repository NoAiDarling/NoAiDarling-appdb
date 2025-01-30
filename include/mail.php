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
    $aEmailList = array_map('strtolower', $aEmailList);
    $aEmailList = array_unique($aEmailList);
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

    $sMsg = $mime->setTXTBody($sMsg);
    $sMsg = $mime->get();

    $bEmailFail = 0;
    foreach ($aEmailList as $sEmailItem)
    {
        $mail = Mail::factory('smtp', $opts);
        $headers = array(
            'To' => $sEmailItem,
            'From' => APPDB_SENDER_EMAIL,
            'Reply-To' => APPDB_SENDER_EMAIL,
            'Subject' => $sSubject,
            'X-Mailer' => APPDB_OWNER." mailer"
        );
        foreach ($headers as $name => $value)
        {
            $headers[$name] = $mime->encodeHeader($name, $value, "utf-8", "quoted-printable");
        }
        $to = $mime->encodeHeader("to", $sEmailItem, "utf-8", "quoted-printable");
        $ret = $mail->send($to, array_merge($headers, $mime->headers()), $sMsg);
        if (PEAR::isError($ret))
        {
            trigger_error($ret->getMessage(), E_USER_WARNING);
            $bEmailFail++;
        }
        unset($mail, $headers, $to, $ret);
    }

    /* we are only going to display a failure, when the number of errors matches
     the number of emails sent */
    if ($bEmailFail and $bEmailFail == count($aEmailList))
        addmsg("Error while sending e-mail", "red");
    else
        addmsg("E-mail sent", "green");
    return true;
}
?>
