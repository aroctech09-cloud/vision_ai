<?php
// SEGURIDAD: Iniciar sesión y verificar autenticación
session_start();
if (!isset($_SESSION['autenticado'])) {
    header("Location: login.php");
    exit();
}

$rol_usuario = $_SESSION['rol'] ?? 'Colaborador';
$num_empleado = $_SESSION['usuario'] ?? '0';
$nombre_usuario = $_SESSION['identificador'] ?? 'Usuario';

// 1. CONFIGURACIÓN DE CONEXIÓN
$conn = new mysqli("localhost", "root", "", "db_vision_ai");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// 2. CONSULTA PARA EL RANKING TOP 10 (Histórico)
$query_ranking = "SELECT devName, COUNT(*) as total 
                  FROM tb_ideas 
                  GROUP BY devName 
                  ORDER BY total DESC 
                  LIMIT 10";
$res_ranking = $conn->query($query_ranking);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="Logo_Vision.png">
    <title>Ranking - Vision AI</title>
    <style>
        :root {
            --primary-blue: #2563eb;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-light); margin: 0; color: var(--text-dark); }
        
        /* NAVBAR (Igual al Dashboard) */
        .navbar { background: var(--white); padding: 12px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
        .nav-container { max-width: 1300px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .logo { font-size: 22px; font-weight: bold; color: var(--primary-blue); text-decoration: none; }
        .logo span { color: #d4a017; }
        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-item { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; }
        .nav-item.active { color: var(--primary-blue); font-weight: 600; }

        .btn-ai-nav {
            background: linear-gradient(135deg, #2563eb, #d4a017);
            color: white !important; padding: 8px 16px; border-radius: 8px; font-weight: bold;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }

        .main-content { max-width: 800px; margin: 40px auto; padding: 0 20px; }

        /* ESTILOS DEL RANKING */
        .ranking-card {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        
        .ranking-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .ranking-table { width: 100%; border-collapse: collapse; }
        .ranking-table th { 
            text-align: left; 
            padding: 15px; 
            color: var(--text-muted); 
            font-size: 11px; 
            text-transform: uppercase; 
            border-bottom: 2px solid #f1f5f9; 
        }
        .ranking-table td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }

        .badge-rank { font-size: 22px; width: 40px; display: inline-block; text-align: center; }
        .user-me { background: #eff6ff; border-left: 4px solid var(--primary-blue); }
        .total-count { font-weight: 800; color: var(--primary-blue); text-align: right; }
        
        .rank-number {
            font-weight: bold;
            color: #94a3b8;
            font-size: 14px;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <div style="display: flex; align-items: center; gap: 25px;">
            <a href="dashboard.php" class="logo">Vision <span>AI</span></a>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-item">Ideas</a>
                <a href="ranking.php" class="nav-item active">Ranking</a>
                <?php if ($rol_usuario === 'Champion'): ?>
                    <a href="ahorro.php" class="nav-item">Ahorro</a>
                    <a href="indicadores.php" class="nav-item">Indicadores</a>
                <?php endif; ?>
                <a href="http://localhost:3000/?user=<?php echo urlencode($nombre_usuario); ?>&id=<?php echo urlencode($num_empleado); ?>" class="nav-item btn-ai-nav">✨ Generar con IA</a>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 15px;">
            <span style="font-size: 12px; color: var(--text-muted);">Hola, <b><?php echo htmlspecialchars($nombre_usuario); ?></b></span>
            <a href="logout.php" class="nav-item" style="color: #ef4444; font-weight: 600;">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="main-content">
    <div class="ranking-card">
        <div class="ranking-header">
            <h1 style="margin:0; color: var(--text-dark);">🏆 Cuadro de Honor</h1>
            <p style="color: var(--text-muted); margin-top: 10px;">Top 10 colaboradores con más ideas aportadas</p>
        </div>

        <table class="ranking-table">
            <thead>
                <tr>
                    <th>Puesto</th>
                    <th>Colaborador</th>
                    <th style="text-align: right;">Total Ideas</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $pos = 1;
                while($row = $res_ranking->fetch_assoc()): 
                    $es_usuario_actual = ($row['devName'] == $nombre_usuario);
                    
                    $medal = "";
                    if($pos == 1) $medal = "🥇";
                    elseif($pos == 2) $medal = "🥈";
                    elseif($pos == 3) $medal = "🥉";
                    else $medal = "<span class='rank-number'>#$pos</span>";
                ?>
                <tr class="<?php echo $es_usuario_actual ? 'user-me' : ''; ?>">
                    <td><span class="badge-rank"><?php echo $medal; ?></span></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['devName']); ?></strong>
                        <?php if($es_usuario_actual) echo " <small style='color:var(--primary-blue); font-weight:bold;'>(Tú)</small>"; ?>
                    </td>
                    <td class="total-count"><?php echo $row['total']; ?> ideas</td>
                </tr>
                <?php $pos++; endwhile; ?>
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