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

// CONSULTA PARA LA GRÁFICA
$res_grafica = $conn->query("SELECT fecha, SUM(ahorro_real) as diario FROM tb_ideas $whereSQL GROUP BY fecha ORDER BY fecha ASC");
$fechas_grafica = [];
$datos_grafica = [];
while($row_g = $res_grafica->fetch_assoc()){
    $fechas_grafica[] = date('d/m/Y', strtotime($row_g['fecha']));
    $datos_grafica[] = $row_g['diario'];
}

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
    <title>Ahorro - Vision AI</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-blue: #2563eb;
            --bg-light: #f8fafc;
            --dark-card: #1e293b;
            --green-success: #22c55e;
            --text-muted: #64748b;
        }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-light); margin: 0; color: #1e293b; }

        /* NAVBAR (image_9cf72b.png) */
        .navbar {
            background: white; padding: 10px 40px; display: flex;
            align-items: center; justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000;
        }
        .nav-logo { font-size: 22px; font-weight: bold; color: var(--primary-blue); text-decoration: none; }
        .nav-logo span { color: #d4a017; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { text-decoration: none; color: #64748b; font-weight: 500; font-size: 15px; }
        .nav-links a.active { color: var(--primary-blue); font-weight: 700; }
        .btn-ia {
            background: linear-gradient(135deg, #4f46e5, #d4a017);
            color: white; border: none; padding: 8px 18px; border-radius: 10px;
            font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px;
        }

        .main-content { max-width: 1400px; margin: 0 auto; padding: 20px; }

        /* TOOLBAR FILTROS (image_9d6b14.png) */
        .toolbar { 
            background: white; padding: 15px 25px; border-radius: 16px; 
            display: flex; gap: 20px; align-items: center; margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #edf2f7;
        }
        .filter-group { display: flex; align-items: center; gap: 10px; }
        .filter-label { font-size: 11px; font-weight: 800; color: var(--primary-blue); text-transform: uppercase; }
        .toolbar input, .toolbar select { 
            padding: 10px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 14px;
        }

        /* FLASHCARDS (image_9cfe56.png) */
        .dashboard-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .card-stat {
            background: white; padding: 20px; border-radius: 20px;
            display: flex; align-items: center; gap: 15px;
            text-decoration: none; color: inherit; border: 1px solid #f1f5f9;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04);
        }
        .card-dark { background: var(--dark-card); color: white; border-left: 6px solid var(--green-success); }
        .card-dark .value { color: var(--green-success); font-size: 28px; font-weight: 800; }

        /* TABLA */
        .table-container { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 15px; font-size: 11px; text-transform: uppercase; color: var(--text-muted); }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; }
    </style>
</head>
<body>

<nav class="navbar">
    <div style="display: flex; align-items: center; gap: 40px;">
        <a href="#" class="nav-logo">Vision <span>AI</span></a>
        <div class="nav-links">
            <a href="dashboard.php">Ideas</a>
            <a href="ahorro.php" class="active">Ahorro</a>
            <a href="indicadores.php">Indicadores</a>
            <a href="usuarios.php">Usuarios</a>
            <a href="http://localhost:3001/?user=<?php echo urlencode($nombre_usuario); ?>&id=<?php echo urlencode($num_empleado); ?>" class="nav-item btn-ai-nav">✨ Generar con IA</a>
        </div>
    </div>
    <div style="display: flex; align-items: center; gap: 20px;">
        
        <div style="font-size: 13px; color: #64748b;">Hola, <b><?php echo $nombre_usuario; ?></b> | <a href="logout.php" style="color:#ef4444; text-decoration:none;">Cerrar Sesión</a></div>
    </div>
</nav>

<div class="main-content">
    
    <form method="GET" class="toolbar">
        <div class="filter-group">
            <span class="filter-label">Periodo:</span>
            <input type="date" name="desde" value="<?php echo $fecha_inicio; ?>">
            <span style="color: #cbd5e1; font-weight: bold;">A</span>
            <input type="date" name="hasta" value="<?php echo $fecha_fin; ?>">
        </div>

        <div class="filter-group">
            <span class="filter-label">Estado:</span>
            <select name="estado">
                <option value="">TODOS</option>
                <option value="Implementada" <?php echo $estado == 'Implementada' ? 'selected' : ''; ?>>IMPLEMENTADA</option>
            </select>
        </div>

        <div class="filter-group" style="flex-grow: 1;">
            <input type="text" name="buscar" placeholder="🔍 Buscar por título o empleado..." style="width: 100%;" value="<?php echo htmlspecialchars($buscar); ?>">
        </div>

        <button type="submit" style="background: var(--primary-blue); color:white; border:none; padding:10px 25px; border-radius:12px; font-weight:bold; cursor:pointer;">Aplicar Filtros</button>
        <a href="ahorro.php" style="color: var(--text-muted); text-decoration:none; font-size: 13px;">✕ Limpiar</a>
    </form>

    <div class="dashboard-grid">
        <div class="card-stat card-dark">
            <div style="font-size: 24px;">💰</div>
            <div>
                <div style="font-size: 11px; text-transform: uppercase; opacity: 0.8;">Ahorro Total Validado</div>
                <div class="value">$<?php echo number_format($ahorro_global, 2); ?> <small style="font-size:12px; opacity:0.6;">MXN</small></div>
            </div>
        </div>

      <a href="ahorro.php" class="card-stat">
    <div style="background: #eff6ff; padding: 10px; border-radius: 12px;">📈</div>
    <div>
        <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted);">Tendencia Temporal</div>
        <div style="font-size: 18px; font-weight: 700;">Volver a Ahorros</div>
    </div>
    <div style="margin-left: auto; color: #cbd5e1;">⬅️</div>
</a>

        <a href="indicadores_areas.php" class="card-stat">
            <div style="background: #fefce8; padding: 10px; border-radius: 12px;">🏢</div>
            <div>
                <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted);">Ahorro por Áreas</div>
                <div style="font-size: 18px; font-weight: 700;">Top Departamentos</div>
            </div>
            <div style="margin-left: auto; color: #cbd5e1;">➜</div>
        </a>
    </div>

    <div style="background: white; padding: 30px; border-radius: 24px; border: 1px solid #edf2f7; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 30px;">
        <h3 style="margin: 0; color: var(--dark-card);">Evolución de Ahorros por Fecha</h3>
        <p style="margin: 5px 0 25px; font-size: 14px; color: var(--text-muted);">Visualización de cuándo se generaron los mayores impactos económicos.</p>
        <div style="height: 350px;">
            <canvas id="savingsChart"></canvas>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Empleado</th>
                    <th>Título de la Idea</th>
                    <th>Estado</th>
                    <th>Ahorro Validado</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight: bold; color: var(--primary-blue);"><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                    <td><b><?php echo $row['devName']; ?></b><br><small>N. Empleado: <?php echo $row['empNumber']; ?></small></td>
                    <td><?php echo $row['titulo']; ?></td>
                    <td><span style="background: #f0fdf4; color: #16a34a; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;"><?php echo strtoupper($row['estado']); ?></span></td>
                    <td style="font-size: 16px; font-weight: 800; color: var(--green-success);">$<?php echo number_format($row['ahorro_real'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ctx = document.getElementById('savingsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($fechas_grafica); ?>,
        datasets: [{
            label: 'Ahorro',
            data: <?php echo json_encode($datos_grafica); ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.05)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString() } },
            x: { grid: { display: false } }
        }
    }
});
</script>

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