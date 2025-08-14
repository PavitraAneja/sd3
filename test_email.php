<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('includes/PHPMailer/Exception.php');
include('includes/PHPMailer/PHPMailer.php');
include('includes/PHPMailer/SMTP.php');

$mail = new PHPMailer(true);

try {
    // SMTP setup
    $mail->isSMTP();
    $mail->Host       = 'mail.califorsale.org';           
    $mail->SMTPAuth   = true;
    $mail->Username   = 'alerts@califorsale.org';        
    $mail->Password   = 'Real_estate123$';  
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
    $mail->Port       = 465;

    // Email headers
    $mail->setFrom('alerts@blank.org', 'Real Estate Alerts');
    $mail->addAddress('anejapavitra@gmail.com', 'Vishruth'); 

    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test from alerts@califorsale.org';
    $mail->Body    = '<h2>This is a test email</h2>';

    $mail->send();
    echo 'Test email sent successfully!';
} catch (Exception $e) {
    echo "Email failed to send. Error: {$mail->ErrorInfo}";
}