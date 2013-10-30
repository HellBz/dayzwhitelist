<?php
require '../PHPMailer/PHPMailerAutoload.php';
$mail = new PHPMailer;
$mail->isSMTP();
$mail->Host = 'localhost';
$mail->SMTPAuth = true;
$mail->Username = 'user';
$mail->Password = 'pass';
$mail->From = 'whitelist@yourdomain.com';
$mail->FromName = 'Your Name';	
$mail->addReplyTo('whitelist@yourdomain.com', 'Your Name');
$mail->addBCC('copy@yourdomain.com');
$mail->Subject = 'Whitelist request';
$mail->Body = "Activation link: http://www.whitelist.com/?guid=$guid&activate=$activation_code\r\n".
			  "For questions please go to our website at $site_url";
?>
