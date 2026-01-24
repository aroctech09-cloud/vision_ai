<?php
session_start();
if (!isset($_SESSION['autenticado'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "db_vision_ai");
$conn->set_charset("utf8");

// FILTROS DE FECHA
$fecha_inicio = $_GET['desde'] ?? '';
$fecha_fin = $_GET['hasta'] ?? '';

$departamentos = [
    "Producción / Operaciones", "Mantenimiento", "Calidad (Aseguramiento y Control)",
    "Logística / Cadena de Suministro", "Almacén / Inventarios", "Ingeniería de Procesos",
    "Investigación y Desarrollo (I+D)", "Comercial (Ventas y Marketing)",
    "Compras / Adquisiciones", "Finanzas y Contabilidad", "Recursos Humanos (RR.HH.)",
    "EHS (Medio Ambiente, Seguridad y Salud)", "Sistemas / Tecnologías de la Información (TI)"
];

$datos_por_area = [];
foreach ($departamentos as $depto) {
    $sql = "SELECT fecha, SUM(ahorro_real) as total FROM tb_ideas WHERE area = '$depto' AND ahorro_real > 0";
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $sql .= " AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
    $sql .= " GROUP BY fecha ORDER BY fecha ASC";
    
    $res = $conn->query($sql);
    $fechas = [];
    $totales = [];
    while ($r = $res->fetch_assoc()) {
        $fechas[] = date('d/m', strtotime($r['fecha']));
        $totales[] = (float)$r['total'];
    }
    $datos_por_area[$depto] = ['fechas' => $fechas, 'totales' => $totales];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="Logo_Vision.png">
    <title>Indicadores por Área - Vision AI</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #2563eb; --bg: #f8fafc; --text-main: #1e293b; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; color: var(--text-main); }
        
        /* NAVBAR ESTILO PROFESIONAL (image_9c8e72.png) */
        .navbar {
            background: white;
            padding: 0 40px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid #f1f5f9;
        }
        .nav-left { display: flex; align-items: center; gap: 40px; }
        .nav-logo { font-size: 24px; font-weight: 800; color: #2563eb; text-decoration: none; }
        .nav-logo span { color: #d4a017; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a { text-decoration: none; color: #64748b; font-size: 15px; font-weight: 500; }
        .nav-links a.active { color: #2563eb; font-weight: 700; }
        
        .nav-right { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #64748b; }
        .btn-logout { color: #ef4444; text-decoration: none; font-weight: 600; }

        .container { max-width: 1450px; margin: 20px auto; padding: 0 25px; }

        /* BARRA DE FILTROS (image_9cfaae.png) */
        .filter-bar {
            background: white; padding: 12px 25px; border-radius: 15px;
            display: flex; align-items: center; gap: 12px; margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #f1f5f9;
        }
        .filter-bar input { padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; }
        .btn-filter { background: #2563eb; color: white; border: none; padding: 10px 25px; border-radius: 10px; font-weight: 700; cursor: pointer; }

        /* GRID DE ÁREAS (image_9c9270.png) */
        .areas-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; }
        .area-card { 
            background: white; border-radius: 24px; padding: 25px; 
            border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .area-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; font-weight: 700; font-size: 15px; color: #1e293b; }
        .dot { width: 8px; height: 8px; background: #d4a017; border-radius: 50%; }
        .chart-wrapper { height: 180px; margin-bottom: 10px; }
        .total-label { text-align: right; font-size: 24px; font-weight: 800; color: #16a34a; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <a href="ahorro.php" class="nav-logo">Vision <span>AI</span></a>
        <div class="nav-links">
            <a href="ideas.php">Ideas</a>
            <a href="ahorro.php">Ahorro</a>
            <a href="#" class="active">Indicadores</a>
            <a href="usuarios.php">Usuarios</a>
        </div>
    </div>
    <div class="nav-right">
        <span>Hola, <b><?php echo $_SESSION['identificador']; ?></b></span>
        <span style="color: #cbd5e1;">|</span>
        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
    </div>
</nav>

<div class="container">
    <form method="GET" class="filter-bar">
        <input type="date" name="desde" value="<?php echo $fecha_inicio; ?>">
        <input type="date" name="hasta" value="<?php echo $fecha_fin; ?>">
        <button type="submit" class="btn-filter">Filtrar</button>
        <a href="indicadores_areas.php" style="font-size: 13px; color: #94a3b8; text-decoration:none;">✕ Limpiar</a>
        <a href="ahorro.php" style="margin-left: auto; color: #64748b; text-decoration:none; font-size: 14px;">← Volver a General</a>
    </form>

    <div style="margin-bottom: 25px;">
        <h2 style="margin:0; font-weight: 800;">Rendimiento por Áreas</h2>
        <p style="color: #64748b; margin: 5px 0 0;">Histórico de ahorros generados por cada departamento</p>
    </div>

    <div class="areas-grid">
        <?php foreach ($datos_por_area as $nombre => $datos): ?>
        <div class="area-card">
            <div class="area-header">
                <div class="dot"></div>
                <?php echo $nombre; ?>
            </div>
            <div class="chart-wrapper">
                <canvas id="chart_<?php echo md5($nombre); ?>"></canvas>
            </div>
            <div class="total-label">
                $<?php echo number_format(array_sum($datos['totales']), 2); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#cbd5e1', font: { size: 10 } } },
        y: { beginAtZero: true, grid: { color: '#f8fafc' }, ticks: { display: false } }
    },
    elements: { line: { tension: 0.4 }, point: { radius: 0 } }
};

<?php foreach ($datos_por_area as $nombre => $datos): ?>
new Chart(document.getElementById('chart_<?php echo md5($nombre); ?>').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($datos['fechas']); ?>,
        datasets: [{
            data: <?php echo json_encode($datos['totales']); ?>,
            borderColor: '#2563eb',
            borderWidth: 2,
            fill: true,
            backgroundColor: (context) => {
                const ctx = context.chart.ctx;
                const gradient = ctx.createLinearGradient(0, 0, 0, 180);
                gradient.addColorStop(0, 'rgba(37, 99, 235, 0.1)');
                gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');
                return gradient;
            }
        }]
    },
    options: chartOptions
});
<?php endforeach; ?>
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