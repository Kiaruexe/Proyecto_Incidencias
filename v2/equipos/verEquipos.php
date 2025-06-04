<?php
session_start();

function leerTiposEquipo()
{
  $json = __DIR__ . '/../configuracion/tiposEquipo.json';
  $php = __DIR__ . '/../configuracion/cofTiposEquipo.php';
  if (file_exists($json)) {
    $d = json_decode(file_get_contents($json), true);
    if ($d !== null)
      return $d;
  }
  if (file_exists($php)) {
    include_once($php);
    if (isset($tiposEquipo))
      return $tiposEquipo;
  }
  return [
    'pc' => ['label' => 'PC', 'campos' => []],
    'portatil' => ['label' => 'Portátil', 'campos' => []],
    'impresora' => ['label' => 'Impresora', 'campos' => []],
    'monitor' => ['label' => 'Monitor', 'campos' => []],
    'otro' => ['label' => 'Otro', 'campos' => []],
    'teclado' => ['label' => 'Teclado', 'campos' => []],
    'raton' => ['label' => 'Ratón', 'campos' => []],
    'router' => ['label' => 'Router', 'campos' => []],
    'sw' => ['label' => 'Switch', 'campos' => []],
    'sai' => ['label' => 'SAI', 'campos' => []],
  ];
}

function leerTiposMantenimiento()
{
  $json = __DIR__ . '/../configuracion/tiposMantenimiento.json';
  $php = __DIR__ . '/../configuracion/tiposMantenimiento.php';
  if (file_exists($json)) {
    $d = json_decode(file_get_contents($json), true);
    if ($d !== null)
      return $d;
  }
  if (file_exists($php)) {
    include_once($php);
    if (isset($tiposMantenimiento))
      return $tiposMantenimiento;
  }
  return [
    'mantenimientoCompleto' => ['label' => 'Completo', 'descripcion' => 'Incluye mano de obra y materiales'],
    'mantenimientoManoObra' => ['label' => 'Mano de Obra', 'descripcion' => 'Solo mano de obra'],
    'mantenimientoFacturable' => ['label' => 'Facturable', 'descripcion' => 'Facturable a terceros'],
    'mantenimientoGarantia' => ['label' => 'Garantía', 'descripcion' => 'Cubierto por garantía'],
  ];
}

$tiposEquipo = leerTiposEquipo();
$tiposMantenimiento = leerTiposMantenimiento();

try {
  $bd = new PDO(
    'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
    'Mapapli',
    '9R%d5cf62'
  );
  $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $bd->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  $bd->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

  $u = $bd->prepare("SELECT permiso, usuario FROM Usuarios WHERE idUsuarios = ?");
  $u->execute([$_SESSION['idUsuario']]);
  $user = $u->fetch();
  $permiso = strtolower($user['permiso']);
  $nombreUsuario = $user['usuario'];

  $fTipo = $_GET['tipoEquipo'] ?? 'todos';
  $fMaint = $_GET['tipoMantenimiento'] ?? '';
  
  // Filtros específicos según el tipo de usuario
  if ($permiso === 'cliente') {
    // Para clientes: solo filtro de ubicación
    $fUbicacion = $_GET['ubicacion'] ?? '';
  } else {
    // Para otros usuarios: filtros de ubicación separados + ubicación general
    $fCP = $_GET['cp'] ?? '';
    $fProv = $_GET['provincia'] ?? '';
    $fLocal = $_GET['localidad'] ?? '';
    $fUbicacion = $_GET['ubicacion'] ?? '';
    $fUser = $_GET['usuario'] ?? '';
  }
  
  $orderBy = $_GET['ordenarPor'] ?? 'numEquipo';
  $orderDir = (($_GET['orden'] ?? 'ASC') === 'DESC') ? 'DESC' : 'ASC';
  $validCols = ['numEquipo', 'fechaAlta', 'fechaCompra', 'usuario', 'costo'];
  if (!in_array($orderBy, $validCols))
    $orderBy = 'numEquipo';

  $orderBySql = ($orderBy === 'usuario') ? 'u.usuario' : "e.$orderBy";

  $sql = "SELECT e.*, u.usuario, e.costo + 0.0 as costo_decimal
          FROM Equipos e
          LEFT JOIN Usuarios u ON e.idUsuario = u.idUsuarios
          WHERE 1=1";
  $params = [];

  if ($permiso === 'cliente') {
    $sql .= " AND e.idUsuario = ?";
    $params[] = $_SESSION['idUsuario'];
  }
  if ($fTipo !== 'todos') {
    $sql .= " AND e.tipoEquipo = ?";
    $params[] = $fTipo;
  }
  if ($fMaint !== '') {
    $sql .= " AND e.tipoMantenimiento = ?";
    $params[] = $fMaint;
  }

  // Filtros de ubicación según el tipo de usuario
  if ($permiso === 'cliente') {
    // Para clientes: buscar en ubicación o en la concatenación de cp, provincia, localidad
    if ($fUbicacion !== '') {
      $sql .= " AND (e.ubicacion LIKE ? OR CONCAT_WS(', ', NULLIF(e.cp, ''), NULLIF(e.provincia, ''), NULLIF(e.localidad, '')) LIKE ?)";
      $params[] = "%$fUbicacion%";
      $params[] = "%$fUbicacion%";
    }
  } else {
    // Para otros usuarios: filtros separados + ubicación general
    foreach (['cp' => $fCP, 'provincia' => $fProv, 'localidad' => $fLocal] as $col => $val) {
      if ($val !== '') {
        $sql .= " AND e.$col LIKE ?";
        $params[] = "%$val%";
      }
    }

    if ($fUbicacion !== '') {
      $sql .= " AND (e.ubicacion LIKE ? OR CONCAT_WS(', ', NULLIF(e.cp, ''), NULLIF(e.provincia, ''), NULLIF(e.localidad, '')) LIKE ?)";
      $params[] = "%$fUbicacion%";
      $params[] = "%$fUbicacion%";
    }

    if ($fUser !== '') {
      $sql .= " AND u.usuario LIKE ?";
      $params[] = "%$fUser%";
    }
  }

  $sql .= " ORDER BY $orderBySql $orderDir";

  $stmt = $bd->prepare($sql);
  $stmt->execute($params);
  $resultados = $stmt->fetchAll();

} catch (Exception $e) {
  exit("Error: " . htmlspecialchars($e->getMessage()));
}

function fmtMantenimiento($k)
{
  global $tiposMantenimiento;
  return $tiposMantenimiento[$k]['label'] ?? $k;
}

function formatUbicacion($cp, $provincia, $localidad, $ubicacion = '') {
  // Si hay ubicación específica, mostrarla
  if (!empty($ubicacion)) {
    return $ubicacion;
  }
  // Si no, mostrar la concatenación de cp, provincia, localidad
  $ubicacionTradicional = array_filter([$cp, $provincia, $localidad]);
  return implode(', ', $ubicacionTradicional) ?: '-';
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Ver Equipos</title>
  <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: Arial, sans-serif;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background: #f5f7fa;
    }

    .nav {
      background: #00225a;
      padding: 1rem;
      text-align: center;
    }

    .nav .brand {
      color: #fff;
      font-size: 1.8rem;
      font-weight: bold;
    }

    .container {
      flex: 1;
      width: 95%;
      max-width: 1200px;
      margin: 20px auto;
    }

    h1 {
      color: #00225a;
      margin-bottom: 20px;
      border-bottom: 2px solid #2573fa;
      padding-bottom: 8px;
    }

    .button-container {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .button {
      padding: 10px 15px;
      background: #4caf50;
      color: #fff;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      cursor: pointer;
      font-weight: bold;
      transition: background .3s;
    }

    .button:hover {
      background: #45a049;
    }

    .home-button {
      background: #2196f3;
    }

    .home-button:hover {
      background: #0b7dda;
    }

    .filter-form {
      margin-bottom: 20px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      background: #eaf1ff;
      padding: 15px;
      border: 1px solid #2573fa;
      border-radius: 5px;
    }

    .filter-form label {
      color: #00225a;
      font-weight: 500;
    }

    .filter-form select,
    .filter-form input,
    .filter-form button {
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    .filter-form button {
      background: #00225a;
      color: #fff;
      cursor: pointer;
      transition: background .3s;
    }

    .filter-form button:hover {
      background: #2573fa;
    }

    .table-container {
      overflow-x: auto;
      background: #fff;
      border: 1px solid #2573fa;
      border-radius: 5px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      padding: 10px;
      border: 1px solid #c5d5ff;
      text-align: left;
    }

    th {
      background: #2573fa;
      color: #fff;
      position: sticky;
      top: 0;
    }

    tr:nth-child(even) {
      background: #f0f5ff;
    }

    tr:hover {
      background: #e5eeff;
    }

    .card-view {
      display: none;
    }

    .card {
      background: #fff;
      border: 1px solid #c5d5ff;
      border-radius: 8px;
      margin-bottom: 15px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .card-header {
      background: #eaf1ff;
      border-bottom: 2px solid #2573fa;
      padding: 12px 15px;
      font-size: 1.1rem;
      font-weight: bold;
      color: #00225a;
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
    }

    .card-body {
      padding: 10px 15px;
    }

    .card-row {
      display: flex;
      margin-bottom: 8px;
    }

    .card-label {
      flex: 0 0 45%;
      font-weight: bold;
      color: #2573fa;
    }

    .card-value {
      flex: 1;
      color: #333;
    }

    @media(max-width:768px) {
      .filter-form {
        flex-direction: column;
        align-items: flex-start;
      }

      .filter-form label,
      .filter-form select,
      .filter-form input,
      .filter-form button {
        width: 100%;
      }
    }

    @media(max-width:576px) {
      .table-container {
        display: none;
      }

      .card-view {
        display: block;
      }
    }

    footer {
      background: #000;
      color: #fff;
      text-align: center;
      padding: 10px;
      margin-top: auto;
    }
  </style>
</head>

<body>
  <nav class="nav"><span class="brand">Mapache Security</span></nav>
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <h1>Lista de Equipos</h1>
      <a href="../home.php" class="button home-button">Volver al inicio</a>
    </div>
    <form method="get" class="filter-form">
      <label>Tipo:</label>
      <select name="tipoEquipo">
        <option value="todos" <?= $fTipo === 'todos' ? 'selected' : ''; ?>>Todos</option>
        <?php foreach ($tiposEquipo as $k => $v): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= $fTipo === $k ? 'selected' : ''; ?>>
            <?= htmlspecialchars($v['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Mantenimiento:</label>
      <select name="tipoMantenimiento">
        <option value="" <?= $fMaint === '' ? 'selected' : ''; ?>>Todos</option>
        <?php foreach ($tiposMantenimiento as $k => $info): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= $fMaint === $k ? 'selected' : ''; ?>>
            <?= htmlspecialchars($info['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <?php if ($permiso === 'cliente'): ?>
        <!-- Para clientes: solo campo de ubicación -->
        <label>Ubicación:</label><input type="text" name="ubicacion" value="<?= htmlspecialchars($fUbicacion) ?>" placeholder="Buscar por ubicación">
      <?php else: ?>
        <!-- Para otros usuarios: campos separados + ubicación -->
        <label>CP:</label><input type="text" name="cp" value="<?= htmlspecialchars($fCP) ?>">
        <label>Provincia:</label><input type="text" name="provincia" value="<?= htmlspecialchars($fProv) ?>">
        <label>Localidad:</label><input type="text" name="localidad" value="<?= htmlspecialchars($fLocal) ?>">
        <label>Ubicación:</label><input type="text" name="ubicacion" value="<?= htmlspecialchars($fUbicacion) ?>" placeholder="Buscar por ubicación">
        <label>Cliente:</label><input type="text" name="usuario" value="<?= htmlspecialchars($fUser) ?>">
      <?php endif; ?>

      <label>Ordenar por:</label>
      <select name="ordenarPor">
        <?php 
        $orderOptions = ['numEquipo' => 'Equipo', 'fechaAlta' => 'Alta', 'fechaCompra' => 'Compra', 'costo' => 'Costo'];
        if ($permiso !== 'cliente') {
          $orderOptions['usuario'] = 'Usuario';
        }
        foreach ($orderOptions as $col => $lbl): 
        ?>
          <option value="<?= $col ?>" <?= $orderBy === $col ? 'selected' : ''; ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
      <select name="orden">
        <option value="ASC" <?= $orderDir === 'ASC' ? 'selected' : ''; ?>>Asc</option>
        <option value="DESC" <?= $orderDir === 'DESC' ? 'selected' : ''; ?>>Desc</option>
      </select>

      <button type="submit">Filtrar</button>
      <a href="<?= $_SERVER['PHP_SELF'] ?>" class="button" style="background:#6c757d">Limpiar</a>
    </form>

    <?php if (count($resultados)): ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Equipo</th>
              <th>Alta</th>
              <th>Compra</th>
              <th>Tipo</th>
              <th>Maint.</th>
              <th>Ubicación</th>
              <?php if ($permiso !== 'cliente'): ?>
                <th>Cliente</th>
              <?php endif; ?>
              <th>Marca</th>
              <th>Modelo</th>
              <th>Serie</th>
              <th>Obs.</th>
              <th>Costo</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($resultados as $r): ?>
              <tr>
                <td><?= $r['numEquipo'] ?: '<span class="empty">-</span>' ?></td>
                <td><?= $r['fechaAlta'] ?: '<span class="empty">-</span>' ?></td>
                <td><?= $r['fechaCompra'] ?: '<span class="empty">-</span>' ?></td>
                <td><?= htmlspecialchars($tiposEquipo[$r['tipoEquipo']]['label'] ?? '-') ?></td>
                <td><?= htmlspecialchars(fmtMantenimiento($r['tipoMantenimiento']) ?? '-') ?></td>
                <td><?= formatUbicacion($r['cp'], $r['provincia'], $r['localidad'], $r['ubicacion'] ?? '') ?></td>
                <?php if ($permiso !== 'cliente'): ?>
                  <td><?= $r['usuario'] ?: '<span class="empty">-</span>' ?></td>
                <?php endif; ?>
                <td><?= $r['marca'] ?: '<span class="empty">-</span>' ?></td>
                <td><?= $r['modelo'] ?: '<span class="empty">-</span>' ?></td>
                <td><?= $r['serie'] ?: '<span class="empty">-</span>' ?></td>
                <td><?= $r['observaciones'] ?: '<span class="empty">Sin obs.</span>' ?></td>
                <td>€<?= number_format($r['costo_decimal'] ?: 0, 2, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="card-view">
        <?php foreach ($resultados as $r): ?>
          <div class="card">
            <div class="card-header">Equipo <?= htmlspecialchars($r['numEquipo'] ?: '–') ?></div>
            <div class="card-body">
              <div class="card-row">
                <div class="card-label">Alta:</div>
                <div class="card-value"><?= $r['fechaAlta'] ?: '–' ?></div>
              </div>
              <div class="card-row">
                <div class="card-label">Compra:</div>
                <div class="card-value"><?= $r['fechaCompra'] ?: '–' ?></div>
              </div>
              <div class="card-row">
                <div class="card-label">Tipo:</div>
                <div class="card-value"><?= htmlspecialchars($tiposEquipo[$r['tipoEquipo']]['label'] ?? '-') ?></div>
              </div>
              <div class="card-row">
                <div class="card-label">Maint.:</div>
                <div class="card-value"><?= htmlspecialchars(fmtMantenimiento($r['tipoMantenimiento']) ?? '-') ?></div>
              </div>
              <div class="card-row">
                <div class="card-label">Ubicación:</div>
                <div class="card-value"><?= formatUbicacion($r['cp'], $r['provincia'], $r['localidad'], $r['ubicacion'] ?? '') ?></div>
              </div>
              <?php if ($permiso !== 'cliente'): ?>
                <div class="card-row">
                  <div class="card-label">Cliente:</div>
                  <div class="card-value"><?= $r['usuario'] ?: '-' ?></div>
                </div>
              <?php endif; ?>
              <div class="card-row">
                <div class="card-label">Marca/Modelo:</div>
                <div class="card-value"><?= ($r['marca'] ?: '-') . ' / ' . ($r['modelo'] ?: '-') ?></div>
              </div>
              <div class="card-row">
                <div class="card-label">Serie:</div>
                <div class="card-value"><?= $r['serie'] ?: '-' ?></div>
              </div>
              <div class="card-row">
                <div class="card-label">Obs:</div>
                <div class="card-value"><?= $r['observaciones'] ?: 'Sin obs.' ?></div>
              </div>
              <div class="card-row">
                <div class="card-label">Costo:</div>
                <div class="card-value">€<?= number_format($r['costo_decimal'] ?: 0, 2, ',', '.') ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No hay equipos para mostrar.</p>
    <?php endif; ?>

  </div>
  <footer>&copy;  <?php echo date('Y'); ?> Mapache Security</footer>
</body>

</html>