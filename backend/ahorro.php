<?php
session_start();
if ($_SESSION['rol'] !== 'Champion') {
    echo "<script>alert('Acceso denegado.'); window.location='dashboard.php';</script>";
    exit();
}

$rol_usuario = $_SESSION['rol'] ?? 'Colaborador';
$nombre_usuario = $_SESSION['identificador'] ?? 'Usuario';

// CONEXIÓN
$conn = new mysqli("localhost", "root", "", "db_vision_ai");
$conn->set_charset("utf8");

// FILTROS
$fecha_inicio = $_GET['desde'] ?? '';
$fecha_fin = $_GET['hasta'] ?? '';
$buscar = $_GET['buscar'] ?? '';
$estado = $_GET['estado'] ?? '';

$condiciones = ["ahorro_real > 0"];
if (!empty($fecha_inicio) && !empty($fecha_fin)) { $condiciones[] = "fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'"; }
if (!empty($buscar)) {
    $b = $conn->real_escape_string($buscar);
    $condiciones[] = "(devName LIKE '%$b%' OR titulo LIKE '%$b%')";
}
if (!empty($estado)) {
    $e = $conn->real_escape_string($estado);
    $condiciones[] = "estado = '$e'";
}

$whereSQL = " WHERE " . implode(" AND ", $condiciones);

// CÁLCULO AHORRO TOTAL
$res_total = $conn->query("SELECT SUM(ahorro_real) as total FROM tb_ideas $whereSQL");
$ahorro_global = $res_total->fetch_assoc()['total'] ?? 0;

// CONSULTA TABLA
$query = "SELECT * FROM tb_ideas $whereSQL ORDER BY id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="Logo_Vision.png">
    <title>Ahorros - Vision AI</title>
    <style>
        :root {
            --primary-blue: #2563eb;
            --bg-light: #f8fafc;
            --dark-card: #1e293b;
            --green-success: #22c55e;
            --text-muted: #64748b;
            --accent-gold: #d4a017;
        }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-light); margin: 0; color: #1e293b; }

        /* NAVBAR */
        .navbar { background: #fff; padding: 12px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
        .nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .logo { font-size: 22px; font-weight: bold; color: var(--primary-blue); text-decoration: none; }
        .logo span { color: var(--accent-gold); }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-item { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; }
        .nav-item.active { color: var(--primary-blue); font-weight: 600; }
        .btn-ai-nav { background: linear-gradient(135deg, #2563eb, #d4a017); color: white !important; padding: 8px 16px; border-radius: 8px; font-weight: bold; }

        .main-content { max-width: 1400px; margin: 0 auto; padding: 20px; }

        /* GRID DE FLASHCARDS */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card-stat {
            padding: 20px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        /* Card Ahorro (Principal) */
        .card-main { background: var(--dark-card); color: white; border-left: 6px solid var(--green-success); }
        
        /* Cards de Navegación */
        .card-nav { background: white; border: 1px solid #e2e8f0; color: var(--dark-card); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-nav:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: var(--primary-blue); }

        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .bg-blue-soft { background: #eff6ff; color: var(--primary-blue); }
        .bg-gold-soft { background: #fefce8; color: var(--accent-gold); }

        .stat-info .label { font-size: 11px; font-weight: 800; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.5px; margin-bottom: 4px; }
        .stat-info .value { font-size: 24px; font-weight: 800; }
        .stat-info .sub-text { font-size: 12px; color: var(--text-muted); margin-top: 4px; }

        /* BARRA FILTROS MEJORADA */
        .toolbar { 
            background: #fff; padding: 15px 25px; border-radius: 16px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; 
            align-items: center; gap: 20px; margin-bottom: 25px; border: 1px solid #edf2f7; flex-wrap: wrap;
        }
        .filter-group { display: flex; align-items: center; gap: 10px; }
        .filter-group label { font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; }
        .toolbar input, .toolbar select { 
            padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; outline: none; 
        }
        .search-input { flex-grow: 1; min-width: 250px; }
        .btn-apply { background: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }

        /* TABLA */
        .table-container { background: #fff; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #fcfcfc; text-align: left; padding: 15px; color: var(--text-muted); font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .badge-ahorro { background: #dcfce7; color: #14532d; padding: 4px 8px; border-radius: 6px; font-weight: 800; font-size: 12px; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <div style="display: flex; align-items: center; gap: 30px;">
            <a href="dashboard.php" class="logo">Vision <span>AI</span></a>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-item">Ideas</a>
                <a href="ahorro.php" class="nav-item active">Ahorro</a>
                <a href="indicadores.php" class="nav-item">Indicadores</a>
                <a href="ajustes.php" class="nav-item">Usuarios</a>
                <a href="http://localhost:3001/?user=<?php echo urlencode($nombre_usuario); ?>&id=<?php echo urlencode($num_empleado); ?>" class="nav-item btn-ai-nav">✨ Generar con IA</a>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 15px;">
            <span style="font-size: 12px; color: var(--text-muted);">Hola, <b><?php echo htmlspecialchars($nombre_usuario); ?></b></span>
            <a href="logout.php" class="nav-item" style="color: #ef4444; font-weight: 600;">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="main-content">
    
    <div class="dashboard-grid">
        <div class="card-stat card-main">
            <div class="icon-box" style="background: rgba(34, 197, 94, 0.2); color: var(--green-success);">💰</div>
            <div class="stat-info">
                <div class="label">Ahorro Total Validado</div>
                <div class="value">$<?php echo number_format($ahorro_global, 2); ?> <small style="font-size: 12px; opacity:0.8;">MXN</small></div>
                <div class="sub-text">Cifra basada en filtros actuales</div>
            </div>
        </div>

        <a href="Chart.php" class="card-stat card-nav">
    <div class="icon-box bg-blue-soft">📈</div>
    <div class="stat-info">
        <div class="label">Histórico de Ahorros</div>
        <div class="value" style="font-size: 18px;">Ver Tendencias</div>
        <div class="sub-text">Gráfica global por línea de tiempo</div>
    </div>
    <div style="margin-left: auto; color: #cbd5e1;">➜</div>
</a>

        <a href="areas.php" class="card-stat card-nav">
            <div class="icon-box bg-gold-soft">🏢</div>
            <div class="stat-info">
                <div class="label">Ahorro por Áreas</div>
                <div class="value" style="font-size: 18px;">Top de Departamentos</div>
                <div class="sub-text">¿Qué área ahorra más dinero?</div>
            </div>
            <div style="margin-left: auto; color: #cbd5e1;">➜</div>
        </a>
    </div>

    <form method="GET" class="toolbar">
        <div class="filter-group">
            <label>Desde:</label>
            <input type="date" name="desde" value="<?php echo $fecha_inicio; ?>">
        </div>
        <div class="filter-group">
            <label>Hasta:</label>
            <input type="date" name="hasta" value="<?php echo $fecha_fin; ?>">
        </div>
       <button type="submit" class="btn-apply">Aplicar Filtros</button>
        <a href="ahorro.php" style="text-decoration:none; font-size:12px; color:var(--text-muted);">Limpiar</a>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Empleado</th>
                    <th>Título / Impacto</th>
                    <th>Estado</th>
                    <th>Ahorro Validado</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:700; color:var(--text-muted);">#<?php echo $row['id']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                    <td>
                        <div style="font-weight:700;"><?php echo htmlspecialchars($row['devName']); ?></div>
                        <div style="font-size:11px; color:var(--text-muted);">ID: <?php echo $row['empNumber']; ?></div>
                    </td>
                    <td>
                        <div style="margin-bottom:5px;"><?php echo htmlspecialchars($row['titulo']); ?></div>
                        <span class="badge-ahorro">💰 $<?php echo number_format($row['ahorro_real'], 2); ?></span>
                    </td>
                    <td>
                        <span style="background:#f1f5f9; padding:5px 10px; border-radius:6px; font-size:11px; font-weight:bold;">
                            <?php echo strtoupper($row['estado']); ?>
                        </span>
                    </td>
                    <td style="font-size:16px; font-weight:800; color:var(--green-success);">$<?php echo number_format($row['ahorro_real'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
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