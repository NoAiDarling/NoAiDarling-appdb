<?php
require_once(BASE."include/config.php");

require_once '/usr/share/php/libphp-phpmailer/src/PHPMailer.php';
require_once '/usr/share/php/libphp-phpmailer/src/SMTP.php';
require_once '/usr/share/php/libphp-phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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

    $bEmailFail = 0;
    foreach ($aEmailList as $sEmailItem)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            if (defined('APPDB_DEBUG') and !empty(APPDB_DEBUG)) {
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function($str, $level) {
                    addmsg('smtp error: '.$str);
                };
            }
            $mail->Host = APPDB_MAIL_HOST;
            $mail->Sender = APPDB_SENDER_EMAIL;
            if (defined('APPDB_MAIL_USER') and !empty(APPDB_MAIL_USER) and !empty(APPDB_MAIL_PASS)) {
                $mail->SMTPAuth = true;
                $mail->Username = APPDB_MAIL_USER;
                $mail->Password = APPDB_MAIL_PASS;
                $mail->SMTPSecure = 'tls';
            }
            $mail->Port = APPDB_MAIL_PORT;
            $mail->setFrom(APPDB_SENDER_EMAIL, APPDB_OWNER);
            $mail->addAddress($sEmailItem);
            $mail->addCustomHeader("X-Mailer", APPDB_OWNER." Mailer");
            $mail->Subject = $sSubject;
            $mail->Body = $sMsg;
            $mail->send();
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            trigger_error($mail->ErrorInfo, E_USER_WARNING);
            $bEmailFail++;
        }
        unset($mail);
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
