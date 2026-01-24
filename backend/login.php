<?php
session_start();

// 1. Redirección automática si ya hay sesión activa
if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Configuración de conexión
$conn = new mysqli("localhost", "root", "", "db_vision_ai");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['usuario'];
    $pass = $_POST['password'];

    // Consulta preparada
    $stmt = $conn->prepare("SELECT id, identificador, rol FROM tb_usuarios WHERE identificador = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $datos = $result->fetch_assoc();
        
        // --- ASIGNACIÓN DE VARIABLES DE SESIÓN ---
        $_SESSION['autenticado']   = true;
        $_SESSION['rol']           = $datos['rol']; 
        $_SESSION['usuario']       = $datos['id']; 
        $_SESSION['identificador'] = $datos['identificador']; 
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Credenciales incorrectas o usuario inexistente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="Logo_Vision.png">
    <title>Login - Vision AI</title>
    <style>
        body { 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            background: #f1f5f9; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
        }
        
        .login-card { 
            background: white; 
            padding: 40px; 
            border-radius: 24px; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); 
            width: 360px; 
            text-align: center; 
        }

        /* Contenedor del Logo e Imagen */
        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
        }

        .login-logo-img {
            width: 85px; /* Tamaño imponente */
            height: auto;
            margin-bottom: 12px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        .logo-text { 
            font-size: 30px; 
            font-weight: 800; 
            color: #1e40af; 
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .logo-text span { 
            color: #f5b400; 
        }

        input { 
            width: 100%; 
            padding: 14px; 
            margin: 12px 0; 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
            box-sizing: border-box; 
            outline: none; 
            background: #f8fafc;
            transition: border-color 0.2s;
        }

        input:focus {
            border-color: #3b82f6;
            background: white;
        }

        button { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-weight: bold; 
            font-size: 16px;
            cursor: pointer; 
            margin-top: 15px; 
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.3);
        }

        .error { 
            color: #b91c1c; 
            font-size: 13px; 
            margin-top: 20px; 
            background: #fef2f2; 
            padding: 12px; 
            border-radius: 10px; 
            border: 1px solid #fee2e2; 
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-container">
            <img src="Logo_Vision.png" alt="Vision AI Logo" class="login-logo-img">
            <h1 class="logo-text">Vision <span>AI</span></h1>
        </div>

        <form method="POST">
            <input type="text" name="usuario" placeholder="Usuario o Nº de Empleado" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Entrar al Sistema</button>
            
            <?php if(isset($error)): ?>
                <div class='error'><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>