<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Configuracion del Servidor
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'angeloyercam09@gmail.com'; //
    $mail->Password   = 'cebhjnpplzbjpoya';         // Tu clave de 16 letras
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Destinatarios
    $mail->setFrom('angeloyercam09@gmail.com', 'Prueba Vision AI'); //
    $mail->addAddress('angeloyercam09@gmail.com');                  //
    
    // Contenido
    $mail->isHTML(true);
    $mail->Subject = "Correo de Prueba Exitoso";
    $mail->Body    = "<h1>¡Funciona!</h1><p>Este es un correo enviado desde PHP usando PHPMailer y Gmail.</p>";

    $mail->send();
    echo 'El mensaje ha sido enviado correctamente';

} catch (Exception $e) {
    echo "El mensaje no pudo enviarse. Error de Mailer: {$mail->ErrorInfo}";
}