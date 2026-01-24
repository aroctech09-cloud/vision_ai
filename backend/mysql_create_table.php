<?php
session_start();

// 1. CONFIGURACIÓN INICIAL
date_default_timezone_set('America/Mexico_City'); 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// 2. CONEXIÓN A LA BASE DE DATOS
$conn = new mysqli("localhost", "root", "", "db_vision_ai");
if ($conn->connect_error) { 
    die("Error de conexión: " . $conn->connect_error); 
}

// 3. SEGURIDAD: EVITAR REGISTROS VACÍOS
if (!isset($_SESSION['propuesta']) || empty($_SESSION['propuesta']['titulo'])) {
    header("Location: vision_ai_home.php");
    exit();
}

$p = $_SESSION['propuesta'];
$fecha = date("Y-m-d H:i:s"); // Incluimos hora para mayor precisión en auditoría

// 4. MAPEO DE CORREOS POR ÁREA
$correos_areas = [
    "Producción / Operaciones" => "aroctech09@gmail.com",
    "Mantenimiento"            => "aroctech09@gmail.com",
    "Sistemas / Tecnologías de la Información (TI)" => "aroctech09@gmail.com"
];
$destinatario = $correos_areas[$p['area']] ?? "aroctech09@gmail.com";

// 5. PREPARAR E INSERTAR LA IDEA
$stmt = $conn->prepare("INSERT INTO tb_ideas (fecha, devName, empNumber, area, nivel, viabilidad, titulo, descripcion, beneficios) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssss", $fecha, $p['desarrollador'], $p['no_empleado'], $p['area'], $p['complejidad'], $p['viabilidad'], $p['titulo'], $p['descripcion'], $p['beneficios']);

$registro_exitoso = false;
$error_mail = "";

if ($stmt->execute()) {
    $registro_exitoso = true;
    $id_idea = $conn->insert_id; // Obtenemos el ID de la idea para la auditoría

    // --- BLOQUE DE BLINDAJE LEGAL (AUDITORÍA) ---
    $ip_usuario = $_SERVER['REMOTE_ADDR'];
    $usuario_audit = $p['desarrollador'] . " (ID: " . $p['no_empleado'] . ")";
    $accion_audit = "REGISTRO FINAL: El usuario aceptó T&C Industriales y envió la Idea #" . $id_idea;

    $stmt_audit = $conn->prepare("INSERT INTO tb_auditoria (usuario, accion, ip_address) VALUES (?, ?, ?)");
    $stmt_audit->bind_param("sss", $usuario_audit, $accion_audit, $ip_usuario);
    $stmt_audit->execute();
    $stmt_audit->close();
    // ---------------------------------------------

    // 6. ENVIAR CORREO CON PHPMAILER
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aroctech09@gmail.com';
        $mail->Password   = 'iwlaavhfjzweiuth';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('aroctech09@gmail.com', 'Sistema Vision AI');
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = "Nueva Idea Registrada: " . $p['titulo'];
        
        $mail->Body = "
            <div style='font-family: sans-serif; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #2563eb;'>🚀 Nueva Propuesta de Mejora</h2>
                <p><strong>De:</strong> {$p['desarrollador']} (Emp: {$p['no_empleado']})</p>
                <p><strong>Área:</strong> {$p['area']}</p>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p><strong>Título:</strong> {$p['titulo']}</p>
                <p><strong>Descripción:</strong><br>" . nl2br(htmlspecialchars($p['descripcion'])) . "</p>
                <p><strong>Beneficios:</strong><br>" . nl2br(htmlspecialchars($p['beneficios'])) . "</p>
                <p style='font-size: 11px; color: #777;'>Registro auditado bajo la IP: $ip_usuario</p>
            </div>";

        $mail->send();
    } catch (Exception $e) {
        $error_mail = "La idea se guardó y auditó, pero hubo un error de correo: {$mail->ErrorInfo}";
    }

    // 7. LIMPIAR SESIÓN
    unset($_SESSION['propuesta']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado del Registro - Vision AI</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f0f2f5; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; max-width: 450px; }
        .icon { font-size: 60px; margin-bottom: 20px; }
        .email-tag { font-weight: bold; color: #333; border-bottom: 3px solid #16a34a; display: inline-block; margin: 10px 0; }
        .btn { display: inline-block; margin-top: 25px; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 10px; font-weight: bold; }
        .audit-info { font-size: 10px; color: #94a3b8; margin-top: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($registro_exitoso): ?>
            <div class="icon">✅</div>
            <h1 style="color:#16a34a">¡Idea enviada con éxito!</h1>
            <p>La idea se guardó y <strong>auditó legalmente</strong> con éxito.</p>
            <p>Notificación enviada a:</p>
            <div class="email-tag"><?php echo htmlspecialchars($destinatario); ?></div>
            
            <div class="audit-info">
                ID Auditoría: REG-<?= $id_idea ?>-<?= date("Hms") ?> | IP: <?= $ip_usuario ?>
            </div>

            <?php if ($error_mail): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-top: 15px; font-size: 12px;">
                    <strong>Aviso:</strong> <?php echo $error_mail; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="icon">❌</div>
            <h1 style="color:#dc2626">Error de Sistema</h1>
            <p>No se pudo registrar la información.</p>
        <?php endif; ?>
        <br>
        <a href="dashboard.php" class="btn">Volver al Dashboard</a>
    </div>
</body>
</html>
<?php
if(isset($stmt)) $stmt->close();
$conn->close();
?>