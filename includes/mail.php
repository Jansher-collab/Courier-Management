<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/src/Exception.php';
require '../vendor/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/src/SMTP.php';

function send_mail($to, $subject, $body){
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jansherkhan385@gmail.com';    // Replace locally
        $mail->Password   = 'dzhtppggycilsaez';       // Replace locally
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('jansherkhan385@gmail.com', 'Courier Management'); // Replace locally
        $mail->addAddress($to);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->SMTPDebug = 0; 
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}
?>



