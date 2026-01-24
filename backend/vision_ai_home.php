<?php
session_start();

// Protección: Si no hay sesión iniciada, mandarlo al login
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: login.php");
    exit();
}

/**
 * LÓGICA DE IDENTIDAD
 * Priorizamos los datos de la SESSION (identificador / usuario) 
 * Si no existen, probamos con POST (casos donde React envíe datos)
 */

// Recuperar Nombre del Colaborador
$desarrollador = "Sin Nombre";
if (isset($_SESSION['identificador'])) {
    $desarrollador = $_SESSION['identificador'];
} elseif (!empty($_POST['txtdesarrollador'])) {
    $desarrollador = $_POST['txtdesarrollador'];
}

// Recuperar ID/Número de Empleado
$no_empleado = "00000";
if (isset($_SESSION['usuario'])) {
    $no_empleado = $_SESSION['usuario'];
} elseif (!empty($_POST['txtno_empleado'])) {
    $no_empleado = $_POST['txtno_empleado'];
}

// Variables de la Idea (vienen de la IA en la pantalla anterior)
$area_seleccionada = $_POST['cboarea'] ?? '';
$titulo            = $_POST['txt_titulo'] ?? '';
$descripcion       = $_POST['txt_descripcion'] ?? '';
$beneficios        = $_POST['txt_beneficios'] ?? '';
$viabilidad        = $_POST['txt_viabilidad'] ?? 'V';
$complejidad       = $_POST['txt_complejidad'] ?? 'B';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vision AI - Confirmación</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: linear-gradient(180deg, #f6f9f7, #eef2f0); margin: 0; padding: 30px 15px; }
        .header { max-width: 640px; margin: 0 auto 20px; background: white; border-radius: 16px; padding: 18px 22px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .vision-title { margin: 0; font-size: 32px; font-weight: 800; letter-spacing: 0.5px; }
        .vision { background: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .ai { color: #f5b400; margin-left: 6px; }
        .responsive-form { max-width: 640px; margin: auto; background: white; border-radius: 18px; padding: 22px; box-shadow: 0 15px 40px rgba(0,0,0,0.1); }
        form { display: flex; flex-direction: column; gap: 14px; }
        label { font-size: 13px; font-weight: 600; color: #444; }
        input[type=text], select, textarea { width: 100%; border: 1px solid #dcdcdc; border-radius: 12px; padding: 10px 14px; font-size: 14px; background: #fafafa; transition: all 0.2s ease; }
        textarea { min-height: 95px; resize: vertical; }
        
        .user-info-box { background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 5px; }
        .user-info-label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px; display: block; }
        .user-info-data { font-size: 15px; color: #1e293b; font-weight: 600; }

        .btn-generate { margin-top: 12px; border: none; border-radius: 16px; padding: 14px 18px; font-size: 15px; font-weight: 600; cursor: pointer; color: white; width: 100%; background: linear-gradient(90deg, #2563eb 0%, #5b7fdc 45%, #f5b400 100%); box-shadow: 0 10px 22px rgba(0,0,0,0.25); transition: all 0.25s ease; }
        .btn-generate:hover { transform: translateY(-2px); box-shadow: 0 14px 28px rgba(0,0,0,0.3); }
    </style>
</head>
<body>

<div class="header">
  <h2 class="vision-title">
    <span class="vision">Vision</span>
    <span class="ai">AI</span>
  </h2>
</div>

<div class="responsive-form">
<form method="post" action="generar_cookies.php">

  <div class="user-info-box">
      <span class="user-info-label">Colaborador Registrando:</span>
      <div class="user-info-data">
          👤 <?php echo htmlspecialchars($desarrollador); ?> 
          <span style="font-weight: normal; color: #94a3b8; margin-left: 10px; font-family: monospace;">ID: <?php echo htmlspecialchars($no_empleado); ?></span>
      </div>
  </div>

  <input type="hidden" name="txtdesarrollador" value="<?php echo htmlspecialchars($desarrollador); ?>">
  <input type="hidden" name="txtno_empleado" value="<?php echo htmlspecialchars($no_empleado); ?>">

  <label for="cboarea">Área Destino</label>
  <select id="cboarea" name="cboarea">
    <?php
    $areas = [
        "1" => "Producción / Operaciones", "2" => "Mantenimiento", "3" => "Calidad (Aseguramiento y Control)",
        "4" => "Logística / Cadena de Suministro", "5" => "Almacén / Inventarios", "6" => "Ingeniería de Procesos",
        "7" => "Investigación y Desarrollo (I+D)", "8" => "Comercial (Ventas y Marketing)", "9" => "Compras / Adquisiciones",
        "10" => "Finanzas y Contabilidad", "11" => "Recursos Humanos (RR.HH.)", "12" => "EHS (Medio Ambiente, Seguridad y Salud)",
        "13" => "Sistemas / Tecnologías de la Información (TI)"
    ];
    foreach ($areas as $val => $texto) {
        $selected = ($area_seleccionada == $texto || $area_seleccionada == $val) ? 'selected' : '';
        echo "<option value='$val' $selected>$texto</option>";
    }
    ?>
  </select>

  <label for="txt_titulo">Título de la Idea</label>
  <input type="text" id="txt_titulo" name="txt_titulo" value="<?php echo htmlspecialchars($titulo); ?>" required>

  <label for="txt_descripcion">Descripción Industrial</label>
  <textarea id="txt_descripcion" name="txt_descripcion" required><?php echo htmlspecialchars($descripcion); ?></textarea>

  <label for="txt_beneficios">Beneficios</label>
  <textarea id="txt_beneficios" name="txt_beneficios" required><?php echo htmlspecialchars($beneficios); ?></textarea>

  <div style="display: flex; gap: 10px;">
      <div style="flex: 1;">
          <label>Viabilidad</label>
          <select disabled style="background-color: #f1f5f9; color: #1e293b; opacity: 1;">
            <option value="V" <?php echo ($viabilidad == 'V' || $viabilidad == 'Viable') ? 'selected' : ''; ?>>Viable</option>
            <option value="N" <?php echo ($viabilidad == 'N' || $viabilidad == 'No viable') ? 'selected' : ''; ?>>No viable</option>
          </select>
          <input type="hidden" name="txt_viabilidad" value="<?php echo (in_array($viabilidad, ['N', 'No viable'])) ? 'N' : 'V'; ?>">
      </div>
      <div style="flex: 1;">
          <label>Complejidad</label>
          <select disabled style="background-color: #f1f5f9; color: #1e293b; opacity: 1;">
            <option value="B" <?php echo ($complejidad == 'B' || $complejidad == 'Bajo') ? 'selected' : ''; ?>>Bajo</option>
            <option value="M" <?php echo ($complejidad == 'M' || $complejidad == 'Medio') ? 'selected' : ''; ?>>Medio</option>
            <option value="A" <?php echo ($complejidad == 'A' || $complejidad == 'Alto') ? 'selected' : ''; ?>>Alto</option>
          </select>
          <input type="hidden" name="txt_complejidad" value="<?php 
            if(in_array($complejidad, ['A', 'Alto'])) echo 'A'; 
            elseif(in_array($complejidad, ['M', 'Medio'])) echo 'M'; 
            else echo 'B'; 
          ?>">
      </div>
  </div>

  <button type="submit" class="btn-generate">
    ⚡ Enviar Idea Final
  </button>

</form>
</div>

</body>
</html>