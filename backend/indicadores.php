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

// 3. CAPTURAR FILTROS DE FECHA (Segmentadores)
$fecha_inicio = $_GET['desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['hasta'] ?? date('Y-m-d');

// 4. CONSULTA FILTRADA PARA GRÁFICAS Y TARJETAS
$query = "SELECT fecha, estado, COUNT(*) as total 
          FROM tb_ideas 
          WHERE fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'
          GROUP BY fecha, estado 
          ORDER BY fecha ASC";
$result = $conn->query($query);

$fechas_raw = [];
$datos_por_estado = [
    'Total' => [],
    'En análisis' => [],
    'Aprobada' => [],
    'Rechazada' => [],
    'En implementación' => [],
    'Implementada' => []
];

$totales_tarjetas = [
    'Total' => 0, 'En análisis' => 0, 'Aprobada' => 0, 
    'Rechazada' => 0, 'En implementación' => 0, 'Implementada' => 0
];

while($row = $result->fetch_assoc()){
    $f = $row['fecha'];
    $est = trim($row['estado']);
    $cant = (int)$row['total'];

    if(!isset($fechas_raw[$f])) $fechas_raw[$f] = true;
    
    if (array_key_exists($est, $datos_por_estado)) {
        $datos_por_estado[$est][$f] = $cant;
        $totales_tarjetas[$est] += $cant;
    }
    
    $datos_por_estado['Total'][$f] = ($datos_por_estado['Total'][$f] ?? 0) + $cant;
    $totales_tarjetas['Total'] += $cant;
}

// --- CONSULTA PARA PARTICIPACIÓN POR ÁREA ---
$query_areas = "SELECT area, COUNT(*) as total FROM tb_ideas 
                WHERE fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' 
                GROUP BY area ORDER BY total DESC";
$res_areas = $conn->query($query_areas);
$datos_areas = [];
$total_ideas_periodo = $totales_tarjetas['Total'] > 0 ? $totales_tarjetas['Total'] : 1;

while($ra = $res_areas->fetch_assoc()){
    $datos_areas[] = [
        'nombre' => $ra['area'],
        'cantidad' => $ra['total'],
        'porc' => round(($ra['total'] / $total_ideas_periodo) * 100, 1)
    ];
}

$labels_fechas = array_keys($fechas_raw);

function normalizar_datos($fechas, $datos_estado) {
    $salida = [];
    foreach($fechas as $f) { $salida[] = $datos_estado[$f] ?? 0; }
    return $salida;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicadores - Vision AI</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-blue: #2563eb;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --color-analisis: #fde047;
            --color-aprobada: #bbf7d0;
            --color-rechazada: #fecaca;
            --color-implementacion: #bae6fd;
            --color-implementada: #e9d5ff;
        }

        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-light); margin: 0; padding-bottom: 50px; }
        
        .navbar { background: var(--white); padding: 12px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; margin-bottom: 25px; }
        .nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .logo { font-size: 22px; font-weight: bold; color: var(--primary-blue); text-decoration: none; }
        .logo span { color: #d4a017; }
        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-item { text-decoration: none; color: #64748b; font-weight: 500; font-size: 15px; }
        .nav-item.active { color: var(--primary-blue); font-weight: 600; }

        .main-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }

        .filter-bar {
            background: var(--white); padding: 15px 25px; border-radius: 15px; margin-bottom: 25px;
            display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .filter-controls { display: flex; align-items: center; gap: 20px; }
        .filter-group { display: flex; align-items: center; gap: 10px; }
        .filter-group label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .filter-input { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; }
        
        .button-group { display: flex; gap: 10px; }
        .btn-filter { background: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 13px; }
        .btn-reset { background: #f1f5f9; color: var(--text-muted); border: 1px solid #e2e8f0; padding: 10px 15px; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: 13px; display: flex; align-items: center; }

        .flashcards-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        /* Flashcard como enlace */
        .flashcard { 
            background: var(--white); padding: 20px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            display: flex; align-items: center; gap: 20px; border: 1px solid rgba(0,0,0,0.02);
            text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;
        }
        .flashcard:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); }
        
        .fc-icon { width: 55px; height: 55px; background: #f0f7ff; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .fc-content h4 { margin: 0; font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .fc-content p { margin: 4px 0; font-size: 19px; font-weight: 800; color: var(--text-dark); }
        .fc-info { font-size: 12px; color: var(--text-muted); }

        .stats-grid { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 30px; }
        .stat-card {
            background: var(--white); flex: 1; padding: 15px 10px; border-radius: 18px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; border-bottom: 5px solid #e2e8f0;
        }
        .stat-card.total { border-bottom-color: var(--primary-blue); }
        .stat-card.analisis { border-bottom-color: var(--color-analisis); }
        .stat-card.aprobada { border-bottom-color: var(--color-aprobada); }
        .stat-card.rechazada { border-bottom-color: var(--color-rechazada); }
        .stat-card.implementacion { border-bottom-color: var(--color-implementacion); }
        .stat-card.implementada { border-bottom-color: var(--color-implementada); }

        .stat-number { display: block; font-size: 26px; font-weight: 800; color: var(--text-dark); }
        .stat-label { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; white-space: nowrap; }

        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
        .chart-card { background: var(--white); padding: 20px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .chart-card h3 { font-size: 12px; color: var(--text-dark); text-transform: uppercase; border-left: 4px solid var(--primary-blue); padding-left: 10px; margin-bottom: 20px; }

        .nav-container { max-width: 100%; padding: 0 40px; }
        .btn-ai { background: linear-gradient(135deg, #4461f2 0%, #d4a017 100%); color: white; text-decoration: none; padding: 8px 20px; border-radius: 10px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; margin-left: 15px; }
        .user-menu { display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .logout-link { color: #ef4444; text-decoration: none; font-weight: 500; }
        /* Busca esta sección y reemplázala */
.flashcards-container { 
    display: grid; 
    grid-template-columns: 1fr; /* Cambiado de 1fr 1fr a 1fr para ocupar todo el ancho */
    gap: 20px; 
    margin-bottom: 25px; 
}

.flashcard { 
    background: var(--white); 
    padding: 25px 40px; /* Aumentamos el padding lateral para que luzca mejor a lo ancho */
    border-radius: 20px; 
    box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
    display: flex; 
    align-items: center; 
    gap: 30px; /* Más espacio entre el icono y el texto */
    border: 1px solid rgba(0,0,0,0.02);
    text-decoration: none; 
    transition: transform 0.2s, box-shadow 0.2s; 
    cursor: pointer;
}
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

<div class="main-container">
    <form method="GET" action="indicadores.php" class="filter-bar">
        <div class="filter-controls">
            <div class="filter-group">
                <label>Desde:</label>
                <input type="date" name="desde" value="<?php echo $fecha_inicio; ?>" class="filter-input">
            </div>
            <div class="filter-group">
                <label>Hasta:</label>
                <input type="date" name="hasta" value="<?php echo $fecha_fin; ?>" class="filter-input">
            </div>
        </div>
        <div class="button-group">
            <button type="submit" class="btn-filter">Filtrar Datos</button>
            <a href="indicadores.php" class="btn-reset">✕ Limpiar</a>
        </div>
    </form>

    <div class="flashcards-container">
    <a href="indicadores_areas.php?desde=<?php echo $fecha_inicio; ?>&hasta=<?php echo $fecha_fin; ?>" class="flashcard">
        <div class="fc-icon" style="width: 70px; height: 70px; font-size: 32px;">📊</div>
        <div class="fc-content" style="flex-grow: 1;">
            <h4 style="font-size: 14px;">Porcentajes de Participación por Área</h4>
            <p style="font-size: 24px;"><?php echo count($datos_areas); ?> Áreas Activas en el Sistema</p>
            <div class="fc-info" style="font-size: 14px;">
                Departamento con mayor actividad: <strong><?php echo $datos_areas[0]['nombre'] ?? 'N/A'; ?></strong>
            </div>
        </div>
        <div style="color: var(--primary-blue); font-weight: 800; font-size: 20px;">
            Ver detalles →
        </div>
    </a>
</div>

    <div class="stats-grid">
        <div class="stat-card total"><span class="stat-number"><?php echo $totales_tarjetas['Total']; ?></span><span class="stat-label">Total Ideas</span></div>
        <div class="stat-card analisis"><span class="stat-number"><?php echo $totales_tarjetas['En análisis']; ?></span><span class="stat-label">En Análisis</span></div>
        <div class="stat-card aprobada"><span class="stat-number"><?php echo $totales_tarjetas['Aprobada']; ?></span><span class="stat-label">Aprobadas</span></div>
        <div class="stat-card rechazada"><span class="stat-number"><?php echo $totales_tarjetas['Rechazada']; ?></span><span class="stat-label">Rechazadas</span></div>
        <div class="stat-card implementacion"><span class="stat-number"><?php echo $totales_tarjetas['En implementación']; ?></span><span class="stat-label">En Implementación</span></div>
        <div class="stat-card implementada"><span class="stat-number"><?php echo $totales_tarjetas['Implementada']; ?></span><span class="stat-label">Implementadas</span></div>
    </div>

    <div class="dashboard-grid">
        <?php 
        $config = [
            "Total" => ["Tendencia General", "#2563eb"],
            "En análisis" => ["Evolución Análisis", "#eab308"],
            "Aprobada" => ["Evolución Aprobadas", "#22c55e"],
            "Rechazada" => ["Evolución Rechazadas", "#ef4444"],
            "En implementación" => ["Evolución Implementación", "#0ea5e9"],
            "Implementada" => ["Evolución Finalizadas", "#a855f7"]
        ];
        foreach($config as $key => $info): ?>
            <div class="chart-card">
                <h3>📈 <?php echo $info[0]; ?></h3>
                <canvas id="chart-<?php echo str_replace(' ', '-', $key); ?>"></canvas>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const labels = <?php echo json_encode($labels_fechas); ?>;
const dataSets = {
    Total: <?php echo json_encode(normalizar_datos($labels_fechas, $datos_por_estado['Total'])); ?>,
    Analisis: <?php echo json_encode(normalizar_datos($labels_fechas, $datos_por_estado['En análisis'])); ?>,
    Aprobada: <?php echo json_encode(normalizar_datos($labels_fechas, $datos_por_estado['Aprobada'])); ?>,
    Rechazada: <?php echo json_encode(normalizar_datos($labels_fechas, $datos_por_estado['Rechazada'])); ?>,
    Implementacion: <?php echo json_encode(normalizar_datos($labels_fechas, $datos_por_estado['En implementación'])); ?>,
    Implementada: <?php echo json_encode(normalizar_datos($labels_fechas, $datos_por_estado['Implementada'])); ?>
};

function createLineChart(canvasId, label, data, color) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: label, data: data, borderColor: color, backgroundColor: color + '15',
                fill: true, tension: 0.4, borderWidth: 3, pointRadius: 4, pointBackgroundColor: color
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, color: '#94a3b8' }, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
            }
        }
    });
}

createLineChart('chart-Total', 'Total', dataSets.Total, '#2563eb');
createLineChart('chart-En-análisis', 'Análisis', dataSets.Analisis, '#eab308');
createLineChart('chart-Aprobada', 'Aprobadas', dataSets.Aprobada, '#22c55e');
createLineChart('chart-Rechazada', 'Rechazadas', dataSets.Rechazada, '#ef4444');
createLineChart('chart-En-implementación', 'Implementación', dataSets.Implementacion, '#0ea5e9');
createLineChart('chart-Implementada', 'Finalizadas', dataSets.Implementada, '#a855f7');
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