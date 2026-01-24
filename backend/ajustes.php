<?php
// 1. SEGURIDAD: Iniciar sesión y verificar autenticación
session_start();
// Si NO es Champion, denegar acceso
if ($_SESSION['rol'] !== 'Champion') {
    echo "<script>alert('Acceso denegado. Solo el Champion puede entrar aquí.'); window.location='dashboard.php';</script>";
    exit();
}

// 2. CONEXIÓN A LA BASE DE DATOS
$conn = new mysqli("localhost", "root", "", "db_vision_ai");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// 3. LÓGICA PARA CREAR USUARIO (Manejo de errores y duplicados)
if (isset($_POST['crear_usuario'])) {
    $user = $_POST['nuevo_usuario'];
    $pass = $_POST['nueva_password'];
    $rol = $_POST['rol_usuario']; 
    
    if(!empty($user) && !empty($pass)) {
        try {
            $stmt = $conn->prepare("INSERT INTO tb_usuarios (identificador, password, rol) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $user, $pass, $rol);
            
            if($stmt->execute()) {
                header("Location: ajustes.php?msg=creado");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                echo "<script>alert('Error: El usuario ya existe.'); window.location='ajustes.php';</script>";
                exit();
            } else {
                die("Error en la base de datos: " . $e->getMessage());
            }
        }
    }
}

// 4. LÓGICA PARA ELIMINAR USUARIO
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM tb_usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: ajustes.php?msg=eliminado");
    exit();
}

// 5. LÓGICA PARA CAMBIAR CONTRASEÑA
if (isset($_POST['cambiar_pass'])) {
    $id = $_POST['user_id'];
    $n_pass = $_POST['n_pass'];
    $stmt = $conn->prepare("UPDATE tb_usuarios SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $n_pass, $id);
    $stmt->execute();
    header("Location: ajustes.php?msg=actualizado");
    exit();
}

// LÓGICA DE BÚSQUEDA INTERNA (Corregida para evitar el error anterior)
$search_user = $_GET['search_user'] ?? '';
$res_count = $conn->query("SELECT COUNT(*) as total FROM tb_usuarios");
$total_usuarios = $res_count->fetch_assoc()['total'];

// Buscamos por identificador o por ID
$query_users = "SELECT * FROM tb_usuarios WHERE identificador LIKE ? OR id LIKE ? ORDER BY id DESC";
$stmt_u = $conn->prepare($query_users);
$term_u = "%$search_user%";
$stmt_u->bind_param("ss", $term_u, $term_u);
$stmt_u->execute();
$usuarios = $stmt_u->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="Logo_Vision.png">
    <title>Ajustes de Cuenta - Vision AI</title>
    <style>
        :root {
            --primary-blue: #2563eb;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body { font-family: 'Segoe UI', system-ui, sans-serif; background-color: var(--bg-light); margin: 0; color: var(--text-dark); }

        /* NAVBAR */
        .navbar { background: var(--white); padding: 12px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .logo { font-size: 22px; font-weight: bold; color: var(--primary-blue); text-decoration: none; }
        .logo span { color: #d4a017; }
        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-item { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 15px; }
        .nav-item.active { color: var(--primary-blue); font-weight: 600; }

        .main-content { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .card { background: var(--white); border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 25px; }
        
        h2 { margin-top: 0; color: var(--text-dark); border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }

        .table-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; gap: 20px; }
        .table-header h3 { font-size: 16px; color: var(--primary-blue); text-transform: uppercase; margin: 0; white-space: nowrap; }

        .user-search-form { flex-grow: 1; display: flex; background: #f1f5f9; padding: 8px 15px; border-radius: 10px; border: 1px solid #e2e8f0; max-width: 400px; }
        .user-search-form input { background: transparent; border: none; padding: 0; width: 100%; font-size: 14px; outline: none; color: var(--text-dark); }

        .user-counter { background: #f1f5f9; color: var(--primary-blue); padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 800; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 8px; white-space: nowrap; }

        .form-group { display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap; align-items: center; }
        .input-style { padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 14px; outline: none; }
        
        .btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: var(--primary-blue); color: white; }
        .btn-danger { background: #fee2e2; color: #ef4444; text-decoration: none; font-size: 13px; padding: 8px 12px; border-radius: 6px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; color: var(--text-muted); font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .user-badge { background: #f1f5f9; color: #1e293b; padding: 4px 8px; border-radius: 6px; font-weight: 700; }
        /* Badge para el ID */
        .id-badge { background: #f1f5f9; color: #64748b; padding: 4px 8px; border-radius: 6px; font-weight: 800; font-size: 11px; margin-right: 8px; border: 1px solid #e2e8f0; }
        
        .rol-badge { font-size: 11px; padding: 4px 10px; border-radius: 12px; font-weight: 800; text-transform: uppercase; }
        .rol-admin { background: #dcfce7; color: #166534; }
        .rol-colab { background: #e0f2fe; color: #075985; }
        .rol-champion { background: #fef3c7; color: #92400e; } 
        /* Ajustes para el contenedor del Nav para que use todo el ancho */
.nav-container {
    max-width: 100%; /* Permite que el saludo se vaya a la derecha */
    padding: 0 40px;
}

/* Botón Dorado "Generar con IA" */
.btn-ai {
    background: linear-gradient(135deg, #4461f2 0%, #d4a017 100%);
    color: white;
    text-decoration: none;
    padding: 8px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 15px;
    transition: transform 0.2s;
}

.btn-ai:hover { transform: scale(1.02); }

/* Menú de Usuario (Derecha) */
.user-menu {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.user-greeting { color: #64748b; }
.user-greeting strong { color: #1e293b; }

.divider { color: #e2e8f0; }

.logout-link {
    color: #ef4444;
    text-decoration: none;
    font-weight: 500;
}

.logout-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-links">
            <a href="dashboard.php" class="logo">Vision <span>AI</span></a>
            <a href="dashboard.php" class="nav-item">Ideas</a>
            <a href="ahorro.php" class="nav-item">Ahorro</a>
            <a href="indicadores.php" class="nav-item active">Indicadores</a>
            <a href="ajustes.php" class="nav-item">Usuarios</a>
            <a href="http://localhost:3001/?user=<?php echo urlencode($nombre_usuario); ?>&id=<?php echo urlencode($num_empleado); ?>" class="nav-item btn-ai-nav">✨ Generar con IA</a>
        </div>

        <div class="user-menu">
            <span class="user-greeting">Hola, <strong><?php echo $_SESSION['usuario'] ?? 'Usuario'; ?></strong></span>
            <span class="divider">|</span>
            <a href="logout.php" class="logout-link">Cerrar Sesión</a>
        </div>
    </div>
</nav>


<div class="main-content">
    <h2>⚙️ Gestión de Usuarios</h2>

    <div class="card">
        <h3>Crear Nuevo Acceso</h3>
        <form method="POST" class="form-group">
            <input type="text" name="nuevo_usuario" class="input-style" placeholder="Nombre de Usuario" required>
            <input type="password" name="nueva_password" class="input-style" placeholder="Contraseña" required>
            
            <select name="rol_usuario" class="input-style" required>
                <option value="Colaborador">Colaborador</option>
                <option value="Administrativo">Administrativo</option>
                <option value="Champion">Champion</option>
            </select>
            
            <button type="submit" name="crear_usuario" class="btn btn-primary">Dar de Alta</button>
        </form>
    </div>

    <div class="card">
        <div class="table-header">
            <h3>Usuarios con Acceso</h3>
            <form method="GET" class="user-search-form">
                <input type="text" name="search_user" placeholder="🔍 Buscar por usuario o ID..." value="<?php echo htmlspecialchars($search_user); ?>">
            </form>
            <div class="user-counter">
                <span>👥</span> Total: <?php echo $total_usuarios; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID / Identificador</th>
                    <th>Tipo / Rol</th>
                    <th>Cambiar Contraseña</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($usuarios->num_rows > 0): ?>
                    <?php while($user = $usuarios->fetch_assoc()): 
                        $rol_actual = !empty($user['rol']) ? $user['rol'] : 'Colaborador';
                        $rol_class = ($rol_actual == 'Administrativo') ? 'rol-admin' : (($rol_actual == 'Champion') ? 'rol-champion' : 'rol-colab');
                    ?>
                    <tr>
                        <td>
                            <span class="id-badge">ID: <?php echo $user['id']; ?></span>
                            <span class="user-badge"><?php echo htmlspecialchars($user['identificador']); ?></span>
                        </td>
                        <td>
                            <span class="rol-badge <?php echo $rol_class; ?>">
                                <?php echo htmlspecialchars($rol_actual); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: flex; gap: 8px;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="password" name="n_pass" placeholder="Nueva clave..." required style="min-width: 120px; font-size: 12px; padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
                                <button type="submit" name="cambiar_pass" class="btn" style="background: #f1f5f9; font-size: 11px; color: var(--text-dark); border: 1px solid #e2e8f0; padding: 5px 10px;">Actualizar</button>
                            </form>
                        </td>
                        <td>
                            <a href="?eliminar=<?php echo $user['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar acceso?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">No se encontraron usuarios.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
<script>
// --- CÓDIGO DE SEGURIDAD PARA EL DASHBOARD ---

const INACTIVITY_TIME = 5 * 60 * 1000; // 5 Minutos (300,000ms)
let inactivityTimer;

// 1. Función para Cerrar Sesión y "Limpiar" el historial de Google
function secureLogout() {
    console.log("Sesión expirada.");
    // 'replace' hace que el botón "Atrás" de Google no pueda volver al Dashboard
    window.location.replace("logout.php");
}

// 2. Reiniciar el reloj si el usuario se mueve o escribe
function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(secureLogout, INACTIVITY_TIME);
}

// 3. Bloqueo del botón "Regresar" de Google
function blockBackButton() {
    // Creamos una entrada falsa en el historial
    window.history.pushState(null, null, window.location.href);
    
    window.onpopstate = function() {
        // Si el usuario presiona "Atrás", lo obligamos a quedarse
        window.history.pushState(null, null, window.location.href);
        alert("Por seguridad, debe usar el botón 'Cerrar Sesión' para salir.");
    };
}

// --- ACTIVAR VIGILANCIA ---
const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];

activityEvents.forEach(event => {
    document.addEventListener(event, resetInactivityTimer);
});

// Iniciar procesos al entrar
blockBackButton();
resetInactivityTimer();
</script>

</body> </html>
</html>