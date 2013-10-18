<?php


/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");

/**
 * Mail Class, FlexiStore Framework
 * 
 * Mailing suport for in-app alerts and notifications
 * Right now is working with phpmailler (hard-coded) 
 * @TODO: remove the phpmailer deep-integration and include support for conencting other mailing libraries
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 18.10.2008
 */
class Mail
{
	/**
	 * Trimite un mail automat, cu textul preluat din tabela lifecarenew.auto_emails
	 * @param string - to whom the email is send
	 * @param string - mail subject
	 * @param string - mail body
	 * @param string - sender
	 *
	 * @return boolean - sending status
	 */
	static function send($toEmail, $subject, $message, $fromEmail = null)
	{
		if (empty($fromEmail))
			$fromEmail = Settings::get("emails.from");

		if (empty($fromEmail))
			$fromEmail = Settings::get("emails.default");



		/*if ($_SERVER["REMOTE_ADDR"] == "127.0.0.1")
		{
			log::debug("<h1>$subject</h1>$message");
			$status = true;
		}
		else*/
			$status = self::sendHTMLMail($toEmail, $fromEmail, $subject, $message);
		
		//SQL::getSelf()->query("insert into email_log set date='". date("Y-m-d H:i:s"). "', email='$toEmail', subject='$subject', user='". (Auth::isLogged() ? Auth::getUserId() : ""). "'");
		
		return $status;
	}
	
	
	
	static function sendPlainMail($to_email, $from_email, $subject, $message)
	{
		mail($to_email, $subject, $message, "From: ". $from_email. "\r\n");
	}
	
	
	
	static function sendHTMLMail($toEmail, $fromEmail, $subject, $message, $fileNameList = null)
	{
		list($toEmail, $toName) = self::parseEmailAddress($toEmail);
		list($fromEmail, $fromName) = self::parseEmailAddress($fromEmail);
		
		$mail = new PHPMailer();
		
		$mail->IsSMTP();
		$mail->Host = "smtp.mandrillapp.com";
		//$mail->SMTPSecure = "ssl";
		$mail->Port = 587;
		$mail->SMTPAuth = true;
		$mail->Username = "andrei.firoiu@neti.ro";
		$mail->Password = "bB_dTFuVtDerfeCszxsCdA";
		
		
		$mail->From = $fromEmail;
		$mail->FromName = $fromName;
		
		$mail->Subject = $subject;
		$mail->MsgHTML($message);
		
		$mail->AddAddress($toEmail, $toName);
		$mail->IsHTML(true);
		
		$mail->AltBody = strip_tags($message);
		
		if (!$mail->Send())
		{
			log::error("Mail error: ". $mail->ErrorInfo);
			return false;
		}
		
		return true;
	}
	
	
	static function parseEmailAddress($email)
	{
		$elems = explode("<", $email);
		if (count($elems) > 1)
			return array(substr($elems[1], 0, strlen($elems[1]) - 1), trim($elems[0]));
		
		return array($email, "");
	}
}

?>