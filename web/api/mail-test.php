<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

$mail = new PHPMailer();
$mail->isSMTP();
$mail->SMTPAuth = true;
$mail->SMTPSecure = 'tls';
$mail->Host = 'smtp.office365.com';
$mail->Port = '587';
$mail->isHTML();
$mail->Username = 'edhec@edhec.com';
$mail->Password = 'fsddsfs!';
$mail->SetFrom('anwer.awledbelhedi@aurone.com', 'Your Name To Be Displayed');
$mail->Subject = "Your email 98989898989";
$mail->Body = 'Any HTML content';

$mail->AddAddress('belhedi.anwer@gmail.com');

$result = $mail->Send();

if ($result == 1) {
    echo "OK Message";
} else {
    echo "Sorry. Failure Message";
}
