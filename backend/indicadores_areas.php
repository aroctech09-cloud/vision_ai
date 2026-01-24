<?php
session_start();
if ($_SESSION['rol'] !== 'Champion') {
    echo "<script>alert('Acceso denegado.'); window.location='dashboard.php';</script>";
    exit();
}

$conn = new mysqli("localhost", "root", "", "db_vision_ai");
if ($conn->connect_error) { die("Error: " . $conn->connect_error); }

// 1. LISTA OFICIAL DE DEPARTAMENTOS
$departamentos = [
    "Producción / Operaciones", "Mantenimiento", "Calidad (Aseguramiento y Control)",
    "Logística / Cadena de Suministro", "Almacén / Inventarios", "Ingeniería de Procesos",
    "Investigación y Desarrollo (I+D)", "Comercial (Ventas y Marketing)",
    "Compras / Adquisiciones", "Finanzas y Contabilidad", "Recursos Humanos (RR.HH.)",
    "EHS (Medio Ambiente, Seguridad y Salud)", "Sistemas / Tecnologías de la Información (TI)"
];

// 2. CAPTURAR FILTROS
$fecha_inicio = $_GET['desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['hasta'] ?? date('Y-m-d');

// 3. OBTENER TOTAL GLOBAL PARA EL CÁLCULO DE PORCENTAJES
$res_total = $conn->query("SELECT COUNT(*) as total FROM tb_ideas WHERE fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'");
$row_total = $res_total->fetch_assoc();
$total_global = (int)$row_total['total'];

// 4. OBTENER DATOS DE GRÁFICAS POR ÁREA
$query_areas = "SELECT area, fecha, COUNT(*) as cantidad 
                FROM tb_ideas 
                WHERE fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' 
                GROUP BY area, fecha 
                ORDER BY fecha ASC";
$result = $conn->query($query_areas);

$datos_graficas = [];
$totales_por_area = array_fill_keys($departamentos, 0);
$fechas_labels = [];

while($row = $result->fetch_assoc()){
    $area = $row['area'];
    $f = $row['fecha'];
    $cant = (int)$row['cantidad'];
    $fechas_labels[$f] = true;
    if (in_array($area, $departamentos)) {
        $datos_graficas[$area][$f] = $cant;
        $totales_por_area[$area] += $cant;
    }
}
ksort($fechas_labels);
$labels_x = array_keys($fechas_labels);
if(empty($labels_x)) { $labels_x = [$fecha_inicio, $fecha_fin]; }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="Logo_Vision.png">
    <title>Indicadores por Área - Vision AI</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-blue: #2563eb;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --success-green: #22c55e;
        }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-light); margin: 0; padding-bottom: 50px; }

        /* NAVBAR */
        .navbar {
            background: var(--white);
            padding: 15px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            margin-bottom: 30px;
        }
        .nav-logo { font-size: 22px; font-weight: 800; color: var(--primary-blue); text-decoration: none; }
        .nav-logo span { color: #d4a017; }
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text-muted); font-weight: 600; font-size: 15px; }
        .nav-links a.active { color: var(--primary-blue); }
        .btn-ia {
            background: linear-gradient(135deg, #4461f2 0%, #d4a017 100%);
            color: white !important; padding: 8px 20px; border-radius: 10px;
            display: flex; align-items: center; gap: 8px;
        }
        .user-info { font-size: 14px; color: var(--text-muted); }
        .logout { color: #ef4444; text-decoration: none; font-weight: 600; margin-left: 10px; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }

        /* FILTROS */
        .filter-bar {
            background: var(--white); padding: 20px 30px; border-radius: 15px; margin-bottom: 35px;
            display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .filter-group { display: flex; align-items: center; gap: 15px; font-size: 11px; font-weight: 800; color: var(--text-muted); }
        .filter-group input { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; }
        .btn-filter { background: var(--primary-blue); color: white; border: none; padding: 10px 25px; border-radius: 10px; font-weight: 700; cursor: pointer; }
        .btn-clean { text-decoration: none; color: var(--text-muted); font-size: 13px; margin-left: 15px; }

        /* NUEVOS ESTILOS FLASHCARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: var(--white);
            padding: 18px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border-bottom: 4px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        .stat-card.active-stat { border-bottom-color: var(--primary-blue); }
        .stat-label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 8px;
            height: 25px;
            overflow: hidden;
        }
        .stat-value { font-size: 26px; font-weight: 800; color: var(--text-dark); }

        /* TITULO Y GRID */
        .section-title { margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-end; }
        .section-title h2 { font-size: 26px; color: var(--text-dark); margin: 0; }
        .section-title p { color: var(--text-muted); margin: 5px 0 0 0; font-size: 14px; }

        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; }
        .area-card { background: var(--white); border-radius: 20px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); transition: transform 0.2s; }
        .area-card:hover { transform: translateY(-5px); }
        
        .area-name { font-size: 15px; font-weight: 700; color: var(--text-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .area-name::before { content: ""; width: 8px; height: 8px; background: #fbbf24; border-radius: 50%; }
        
        .chart-container { height: 180px; margin-bottom: 10px; }
        
        .participation-footer { display: flex; justify-content: flex-end; align-items: baseline; border-top: 1px solid #f1f5f9; padding-top: 15px; }
        .value { font-size: 32px; font-weight: 800; color: var(--success-green); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div style="display: flex; align-items: center; gap: 40px;">
            <a href="dashboard.php" class="nav-logo">Vision <span>AI</span></a>
            <div class="nav-links">
                <a href="dashboard.php">Ideas</a>
                <a href="ahorro.php">Ahorro</a>
                <a href="indicadores.php" class="active">Indicadores</a>
                <a href="ajustes.php">Usuarios</a>
                <a href="#" class="nav-item btn-ai-nav">✨ Generar con IA</a>
            </div>
        </div>
        <div class="user-info">
            <a href="logout.php" class="logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        <form method="GET" class="filter-bar">
            <div class="filter-group">
                DESDE: <input type="date" name="desde" value="<?=$fecha_inicio?>">
                HASTA: <input type="date" name="hasta" value="<?=$fecha_fin?>">
            </div>
            <div>
                <button type="submit" class="btn-filter">Filtrar Datos</button>
                <a href="indicadores_areas.php" class="btn-clean">✕ Limpiar</a>
            </div>
        </form>

         <div class="section-title">
            <div>
                <h2>Cantidad de ideas por Áreas</h2>
                <p>Cantidad de ideas basado en el total de ideas generadas (Total Global: <strong><?=$total_global?></strong>).</p>
            </div>
            <a href="indicadores.php" style="color: var(--primary-blue); text-decoration: none; font-size: 14px; font-weight: 600;">← Volver a General</a>
        </div>

        <div class="stats-grid">
            <?php foreach($departamentos as $depto): 
                $total_depto = $totales_por_area[$depto];
                $card_class = ($total_depto > 0) ? "stat-card active-stat" : "stat-card";
            ?>
                <div class="<?php echo $card_class; ?>">
                    <span class="stat-label"><?php echo $depto; ?></span>
                    <div class="stat-value"><?php echo number_format($total_depto); ?></div>
                    <span style="font-size: 10px; color: #94a3b8; font-weight: 600;">IDEAS TOTALES</span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section-title">
            <div>
                <h2>% Participación por Áreas</h2>
                <p>Porcentaje de participación basado en el total de ideas generadas (Total Global: <strong><?=$total_global?></strong>).</p>
            </div>
            <a href="indicadores.php" style="color: var(--primary-blue); text-decoration: none; font-size: 14px; font-weight: 600;">← Volver a General</a>
        </div>

        <div class="charts-grid">
            <?php foreach($departamentos as $depto): 
                $total_depto = $totales_por_area[$depto];
                $porcentaje = ($total_global > 0) ? round(($total_depto / $total_global) * 100, 1) : 0;
            ?>
            <div class="area-card">
                <div class="area-name"><?=$depto?></div>
                <div class="chart-container">
                    <canvas id="chart-<?=md5($depto)?>"></canvas>
                </div>
                <div class="participation-footer">
                    <span style="font-size: 16px; color: #94a3b8; font-weight: 700; margin-right: 5px;">%</span>
                    <span class="value"><?=number_format($porcentaje, 1)?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

<script>
const labelsX = <?=json_encode($labels_x)?>;
function initChart(canvasId, dataPoints) {
    const canvas = document.getElementById(canvasId);
    if(!canvas) return;
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 180);
    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.15)');
    gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labelsX,
            datasets: [{
                data: dataPoints,
                borderColor: '#2563eb',
                borderWidth: 3,
                pointRadius: 0,
                fill: true,
                backgroundColor: gradient,
                tension: 0.4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { display: false, beginAtZero: true },
                x: { 
                    grid: { display: false }, 
                    ticks: { color: '#cbd5e1', font: { size: 10 }, maxTicksLimit: 3 } 
                }
            }
        }
    });
}

<?php foreach($departamentos as $depto): 
    $puntos = [];
    foreach($labels_x as $lx) { $puntos[] = $datos_graficas[$depto][$lx] ?? 0; }
?>
initChart('chart-<?=md5($depto)?>', <?=json_encode($puntos)?>);
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