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

// 2. LÓGICA DE ACTUALIZACIÓN DE ESTADO Y AHORRO
if (isset($_POST['update_status']) && in_array($rol_usuario, ['Champion', 'Administrativo'])) {
    $id = $_POST['idea_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    $ahorro = $_POST['ahorro_real'] ?? 0;

    $stmt = $conn->prepare("UPDATE tb_ideas SET estado = ?, ahorro_real = ? WHERE id = ?");
    $stmt->bind_param("sdi", $nuevo_estado, $ahorro, $id);
    $stmt->execute();
}

// 3. CAPTURAR FILTROS
$buscar = $_GET['buscar'] ?? '';
$fecha_inicio = $_GET['desde'] ?? '';
$fecha_fin = $_GET['hasta'] ?? '';
$filtro_estado = $_GET['filtro_estado'] ?? '';

// 4. CONSTRUCCIÓN DE LA CONSULTA SQL
$condiciones = [];
if ($rol_usuario === 'Colaborador') {
    $condiciones[] = "empNumber = '" . $conn->real_escape_string($num_empleado) . "'";
}
if (!empty($filtro_estado)) {
    $condiciones[] = "estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $condiciones[] = "fecha BETWEEN '" . $conn->real_escape_string($fecha_inicio) . "' AND '" . $conn->real_escape_string($fecha_fin) . "'";
}
if (!empty($buscar)) {
    $b = $conn->real_escape_string($buscar);
    $condiciones[] = "(devName LIKE '%$b%' OR empNumber LIKE '%$b%' OR titulo LIKE '%$b%' OR id = '$b')";
}

$query = "SELECT * FROM tb_ideas";
if (count($condiciones) > 0) {
    $query .= " WHERE " . implode(" AND ", $condiciones);
}
$query .= " ORDER BY id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="Logo_Vision.png">
    <title>Dashboard - Vision AI</title>
    <style>
        :root {
            --primary-blue: #2563eb;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --bg-analisis: #fef9c3; --txt-analisis: #854d0e; --brd-analisis: #fde047;
            --bg-rechazada: #fee2e2; --txt-rechazada: #991b1b; --brd-rechazada: #fecaca;
            --bg-aprobada: #dcfce7; --txt-aprobada: #14532d; --brd-aprobada: #bbf7d0;
            --bg-implementacion: #e0f2fe; --txt-implementacion: #075985; --brd-implementacion: #bae6fd;
            --bg-implementada: #f3e8ff; --txt-implementada: #6b21a8; --brd-implementada: #e9d5ff;
        }

        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-light); margin: 0; color: var(--text-dark); }
        
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

        /* CONTENEDOR PRINCIPAL QUE LIMITA EL ANCHO */
        .main-content { 
            max-width: 1000px; 
            margin: 40px auto; 
            padding: 0 20px; 
        }

        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
        .filter-group { background: var(--white); padding: 10px 18px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 12px; border: 1px solid #e2e8f0; }
        .filter-group label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .filter-input { border: 1px solid #e2e8f0; padding: 6px 10px; border-radius: 8px; font-size: 13px; outline: none; }
        
        .search-box { background: var(--white); padding: 8px 15px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; border: 1px solid #e2e8f0; flex-grow: 1; max-width: 250px; }
        .search-box input { border: none; outline: none; width: 100%; font-size: 14px; margin-left: 10px; }
        
        .btn-apply { background: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 13px; }
        .btn-reset { background: #f1f5f9; color: var(--text-muted); border: 1px solid #e2e8f0; padding: 10px 15px; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: 13px; }

        .card-container { background: var(--white); border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #fcfcfc; text-align: left; padding: 15px; color: var(--text-muted); font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }

        .status-pill { padding: 6px 12px; border-radius: 8px; font-size: 10px; font-weight: 800; text-transform: uppercase; border: 1px solid transparent; display: inline-block; }
        .status-en-analisis { background: var(--bg-analisis); color: var(--txt-analisis); border-color: var(--brd-analisis); }
        .status-aprobada { background: var(--bg-aprobada); color: var(--txt-aprobada); border-color: var(--brd-aprobada); }
        .status-rechazada { background: var(--bg-rechazada); color: var(--txt-rechazada); border-color: var(--brd-rechazada); }
        .status-en-implementacion { background: var(--bg-implementacion); color: var(--txt-implementacion); border-color: var(--brd-implementacion); }
        .status-implementada { background: var(--bg-implementada); color: var(--txt-implementada); border-color: var(--brd-implementada); }

        .ahorro-badge { background: #dcfce7; color: #14532d; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; margin-top: 5px; border: 1px solid #bbf7d0; }

        .action-container { background: #f8fafc; padding: 8px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 8px; }
        .input-ahorro { border: 1px solid #cbd5e1; padding: 5px; border-radius: 6px; font-size: 12px; width: 100%; }
        .modern-select { border: 1px solid #cbd5e1; background: white; padding: 5px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; }
        .btn-ok { background: var(--primary-blue); color: white; border: none; padding: 8px; border-radius: 6px; font-size: 10px; font-weight: 800; cursor: pointer; width: 100%; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .flash-card { background: white; width: 95%; max-width: 550px; border-radius: 24px; padding: 35px; position: relative; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .close-modal { position: absolute; top: 20px; right: 20px; cursor: pointer; font-size: 28px; color: var(--text-muted); }
        .modal-label { font-weight: 800; color: var(--primary-blue); display: block; font-size: 11px; text-transform: uppercase; margin-bottom: 6px; }
        .modal-content-text { color: #334155; font-size: 15px; line-height: 1.6; display: block; margin-bottom: 20px; }
        .logo { 
    font-size: 22px; 
    font-weight: bold; 
    color: var(--primary-blue); 
    text-decoration: none; 
    display: flex; 
    align-items: center; /* Centra la imagen con el texto verticalmente */
}

.logo img {
    transition: transform 0.3s ease;
}

.logo:hover img {
    transform: scale(1.1); /* Efecto sutil al pasar el mouse */
}
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <div style="display: flex; align-items: center; gap: 25px;">
            <a href="dashboard.php" class="logo" style="display: flex; align-items: center; gap: 10px;">
                <img src="Logo_Vision.png" alt="Logo" style="height: 32px; width: auto;">
                <div>Vision <span>AI</span></div>
            </a>
            
            <div class="nav-links">
                <a href="dashboard.php" class="nav-item active">Ideas</a>
                <a href="ranking.php" class="nav-item">Ranking</a>

                <?php if ($rol_usuario === 'Champion'): ?>
                    <a href="ahorro.php" class="nav-item">Ahorro</a>
                    <a href="indicadores.php" class="nav-item">Indicadores</a>
                    <a href="ajustes.php" class="nav-item">Usuarios</a>
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
    
    <form method="GET" action="dashboard.php" class="toolbar">
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div class="filter-group">
                <label>Periodo:</label>
                <input type="date" name="desde" value="<?php echo htmlspecialchars($fecha_inicio); ?>" class="filter-input">
                <label>a</label>
                <input type="date" name="hasta" value="<?php echo htmlspecialchars($fecha_fin); ?>" class="filter-input">
            </div>
            <div class="filter-group">
                <label>Estado:</label>
                <select name="filtro_estado" class="filter-input" style="border:none; font-weight:700; background:transparent;">
                    <option value="">TODOS</option>
                    <option value="En análisis" <?php if($filtro_estado == 'En análisis') echo 'selected'; ?>>ANÁLISIS</option>
                    <option value="Aprobada" <?php if($filtro_estado == 'Aprobada') echo 'selected'; ?>>APROBADAS</option>
                    <option value="Rechazada" <?php if($filtro_estado == 'Rechazada') echo 'selected'; ?>>RECHAZADAS</option>
                    <option value="En implementación" <?php if($filtro_estado == 'En implementación') echo 'selected'; ?>>EN IMPLEMENTACIÓN</option>
                    <option value="Implementada" <?php if($filtro_estado == 'Implementada') echo 'selected'; ?>>IMPLEMENTADAS</option>
                </select>
            </div>
            <div class="search-box">
                <span>🔍</span>
                <input type="text" name="buscar" placeholder="Buscar..." value="<?php echo htmlspecialchars($buscar); ?>">
            </div>
        </div>
        <div style="display: flex; gap: 10px; margin-left: auto;">
            <button type="submit" class="btn-apply">Aplicar Filtros</button>
            <a href="dashboard.php" class="btn-reset">✕ Limpiar</a>
        </div>
    </form>

    <div class="card-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th> 
                    <th>Empleado</th>
                    <th>Título de la Idea</th>
                    <th>Estado</th>
                    <th>Ver</th>
                    <?php if (in_array($rol_usuario, ['Champion', 'Administrativo'])): ?>
                        <th style="width: 180px;">Gestión</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $estado_raw = $row['estado'];
                        $status_class = str_replace(['á', 'é', 'í', 'ó', 'ú', ' '], ['a', 'e', 'i', 'o', 'u', '-'], strtolower($estado_raw));
                    ?>
                    <tr>
                        <td><span style="background:#f1f5f9; color:#64748b; padding:4px 8px; border-radius:6px; font-weight:700; font-size:12px;">#<?php echo $row['id']; ?></span></td>
                        <td style="color:var(--primary-blue); font-weight:700; font-size:13px;"><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                        <td>
                            <div style="display: flex; flex-direction: column;">
                                <strong><?php echo htmlspecialchars($row['devName']); ?></strong>
                                <span style="color:var(--text-muted); font-size:10px; font-weight:700;"><?php echo htmlspecialchars($row['empNumber']); ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($row['titulo']); ?></div>
                            <?php if ($row['ahorro_real'] > 0): ?>
                                <div class="ahorro-badge">💰 $<?php echo number_format($row['ahorro_real'], 2); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-pill status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($estado_raw); ?></span></td>
                        <td>
                            <button style="border:none; background:none; cursor:pointer; color:var(--primary-blue); font-size:18px;" onclick='showDetails(<?php echo json_encode($row); ?>)'>👁️</button>
                        </td>
                        
                        <?php if (in_array($rol_usuario, ['Champion', 'Administrativo'])): ?>
                        <td>
                            <form method="POST" class="action-container" style="background: none; border: none; padding: 0;">
                                <input type="hidden" name="idea_id" value="<?php echo $row['id']; ?>">
                                <div style="display:flex; gap:5px;">
                                    <select name="nuevo_estado" class="modern-select" style="flex:1; padding: 4px;">
                                        <option value="En análisis" <?php if($estado_raw == 'En análisis') echo 'selected'; ?>>Análisis</option>
                                        <option value="Aprobada" <?php if($estado_raw == 'Aprobada') echo 'selected'; ?>>Aprobar</option>
                                        <option value="Rechazada" <?php if($estado_raw == 'Rechazada') echo 'selected'; ?>>Rechazar</option>
                                        <option value="En implementación" <?php if($estado_raw == 'En implementación') echo 'selected'; ?>>Implementación</option>
                                        <option value="Implementada" <?php if($estado_raw == 'Implementada') echo 'selected'; ?>>Finalizada</option>
                                    </select>
                                    <input type="number" step="0.01" name="ahorro_real" class="input-ahorro" style="width: 70px;" placeholder="0.00" value="<?php echo $row['ahorro_real']; ?>">
                                </div>
                                <button type="submit" name="update_status" class="btn-ok" style="margin-top: 5px;">ACTUALIZAR</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; padding: 40px; color: var(--text-muted);">No se encontraron registros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="ideaModal" class="modal">
    <div class="flash-card">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div style="border-bottom: 1px solid #eee; margin-bottom: 20px;">
            <div id="modalStatus" class="status-pill" style="margin-bottom:10px;"></div>
            <h2 id="modalTitle" style="margin:0; color:#1e293b;"></h2>
            <p id="modalMeta" style="color:#64748b; font-size:13px;"></p>
        </div>
        <div class="card-body">
            <span class="modal-label">📍 Área</span><span id="modalArea" class="modal-content-text"></span>
            <span class="modal-label">📝 Descripción</span><span id="modalDesc" class="modal-content-text"></span>
            <span class="modal-label">💡 Beneficios</span><span id="modalBene" class="modal-content-text"></span>
            <div style="display:flex; gap:20px;">
                <div><span class="modal-label">⚙️ Viabilidad</span><span id="modalViai" class="modal-content-text"></span></div>
                <div><span class="modal-label">📊 Nivel</span><span id="modalNivel" class="modal-content-text"></span></div>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(data) {
    document.getElementById('modalTitle').innerText = data.titulo;
    document.getElementById('modalMeta').innerText = "Por " + data.devName + " | " + data.fecha + " | ID: #" + data.id;
    document.getElementById('modalArea').innerText = data.area;
    document.getElementById('modalDesc').innerText = data.descripcion;
    document.getElementById('modalBene').innerText = data.beneficios;
    document.getElementById('modalViai').innerText = data.viabilidad;
    document.getElementById('modalNivel').innerText = data.nivel;
    
    const statusPill = document.getElementById('modalStatus');
    statusPill.innerText = data.estado.toUpperCase();
    const safeStatus = data.estado.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/ /g, "-");
    statusPill.className = 'status-pill status-' + safeStatus;
    
    document.getElementById('ideaModal').style.display = 'flex';
}

function closeModal() { document.getElementById('ideaModal').style.display = 'none'; }
window.onclick = function(e) { if(e.target == document.getElementById('ideaModal')) closeModal(); }
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