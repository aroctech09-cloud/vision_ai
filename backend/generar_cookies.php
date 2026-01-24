<?php
session_start(); 

// 1. Mapeo de nombres de áreas
$areas_map = [
    "1" => "Producción / Operaciones", "2" => "Mantenimiento", "3" => "Calidad (Aseguramiento y Control)",
    "4" => "Logística / Cadena de Suministro", "5" => "Almacén / Inventarios", "6" => "Ingeniería de Procesos",
    "7" => "Investigación y Desarrollo (I+D)", "8" => "Comercial (Ventas y Marketing)", "9" => "Compras / Adquisiciones",
    "10" => "Finanzas y Contabilidad", "11" => "Recursos Humanos (RR.HH.)", "12" => "EHS (Medio Ambiente, Seguridad y Salud)",
    "13" => "Sistemas / Tecnologías de la Información (TI)"
];

$id_area = $_POST['cboarea'] ?? '';
$nombre_area = $areas_map[$id_area] ?? 'No definida';

// 2. Guardamos en SESSION para que mysql_create_table.php los pueda leer
$_SESSION['propuesta'] = [
    "desarrollador" => $_POST['txtdesarrollador'] ?? '',
    "no_empleado"   => $_POST['txtno_empleado'] ?? '',
    "area"          => $nombre_area, 
    "titulo"        => $_POST['txt_titulo'] ?? '',
    "descripcion"   => $_POST['txt_descripcion'] ?? '',
    "beneficios"    => $_POST['txt_beneficios'] ?? '',
    "viabilidad"    => $_POST['txt_viabilidad'] ?? '',
    "complejidad"   => $_POST['txt_complejidad'] ?? ''
];

$datos = $_SESSION['propuesta'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Idea - Vision AI</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; padding: 20px; display: flex; justify-content: center; }
        .container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 700px; width: 100%; border-top: 6px solid #2563eb; }
        h2 { color: #1e40af; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .dato-box { background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .full-width { grid-column: span 2; }
        .label { font-weight: bold; font-size: 11px; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px; }
        .valor { font-size: 14px; color: #1e293b; white-space: pre-wrap; }
        .tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .tag-v { background: #dcfce7; color: #166534; }
        .tag-nv { background: #fee2e2; color: #991b1b; }
        .button-group { display: flex; gap: 12px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
        .btn { flex: 1; text-align: center; padding: 12px 20px; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 15px; transition: all 0.2s ease; }
        .btn-edit { background: #64748b; color: white; }
        .btn-save { background: #16a34a; color: white; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.2); }
    </style>
</head>
<body>

<div class="container">
    <h2>🚀 Resumen de Idea Registrada</h2>
    <div class="grid">
        <div class="dato-box"><span class="label">Desarrollador</span><span class="valor"><?= htmlspecialchars($datos['desarrollador']) ?></span></div>
        <div class="dato-box"><span class="label">No. Empleado</span><span class="valor"><?= htmlspecialchars($datos['no_empleado']) ?></span></div>
        <div class="dato-box full-width"><span class="label">Área</span><span class="valor"><?= htmlspecialchars($datos['area']) ?></span></div>
        <div class="dato-box full-width"><span class="label">Título</span><span class="valor"><?= htmlspecialchars($datos['titulo']) ?></span></div>
        <div class="dato-box full-width"><span class="label">Descripción</span><div class="valor"><?= nl2br(htmlspecialchars($datos['descripcion'])) ?></div></div>
        <div class="dato-box full-width"><span class="label">Beneficios</span><div class="valor"><?= nl2br(htmlspecialchars($datos['beneficios'])) ?></div></div>
        <div class="dato-box">
            <span class="label">Viabilidad</span>
            <span class="tag <?= $datos['viabilidad'] == 'V' ? 'tag-v' : 'tag-nv' ?>"><?= $datos['viabilidad'] == 'V' ? 'VIABLE' : 'NO VIABLE' ?></span>
        </div>
        <div class="dato-box">
            <span class="label">Complejidad</span>
            <span class="valor"><?php echo ($datos['complejidad'] == 'B') ? 'Bajo' : (($datos['complejidad'] == 'M') ? 'Medio' : 'Alto'); ?></span>
        </div>
    </div>

    <div class="button-group">
        <a href="javascript:history.back()" class="btn btn-edit">← Modificar</a>
        <a href="mysql_create_table.php" class="btn btn-save">Confirmar y Guardar ✔</a>
    </div>
</div>

</body>
</html>