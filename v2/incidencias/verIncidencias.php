<?php
session_start();
if (!isset($_SESSION["idUsuario"])) {
  header("Location: ../login.php");
  exit;
}

function registrarLog(PDO $bd, string $accion, string $descripcion, string $usuario): void
{
  try {
    $stmt = $bd->prepare("
      INSERT INTO `Log` (`accion`, `descripcion`, `fecha`, `usuario`)
      VALUES (?, ?, NOW(), ?)
    ");
    $stmt->execute([$accion, $descripcion, $usuario]);
  } catch (Exception $e) {
    error_log("Fallo registrarLog(): " . $e->getMessage());
  }
}

try {
  $bd = new PDO(
    'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
    'Mapapli',
    '9R%d5cf62'
  );
  $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $query = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
  $query->execute([$_SESSION['idUsuario']]);
  $userRow = $query->fetch();
  $permiso = strtolower($userRow['permiso']);
  $nombreUsuario = $userRow['usuario'];
} catch (PDOException $e) {
  echo "Error: " . $e->getMessage();
  exit;
}

$allowedFields = [
  'numIncidencia' => 'Incidencias.numIncidencia',
  'fecha' => 'Incidencias.fecha',
  'nombre' => 'Incidencias.nombre',
  'numero' => 'Incidencias.numero',
  'ubicacion' => 'Equipos.ubicacion',
  'correo' => 'Incidencias.correo',
  'incidencia' => 'Incidencias.incidencia',
  'observaciones' => 'Incidencias.observaciones',
  'TDesplazamiento' => 'Incidencias.TDesplazamiento',
  'TIntervencion' => 'Incidencias.TIntervencion',
  'tecnicoAsignado' => 'Incidencias.tecnicoAsignado',
  'usuario' => 'Usuarios.usuario',
  'numEquipo' => 'Incidencias.numEquipo',
  'estado' => 'Incidencias.estado',
  'firma' => 'Incidencias.firma'
];

$filterField = isset($_GET['filter_field']) && isset($allowedFields[$_GET['filter_field']])
  ? $allowedFields[$_GET['filter_field']]
  : '';
$filterValue = trim($_GET['filter_value'] ?? '');
$filterClause = '';
$params = [];

if ($filterField && $filterValue !== '') {
    if ($_GET['filter_field'] === 'firma') {
        $fv = strtolower($filterValue);
        if (in_array($fv, ['true', 'firmado'])) {
            $filterClause = " AND Incidencias.firma IS NOT NULL AND Incidencias.firma <> ''";
        } elseif (in_array($fv, ['false', 'sin firmar'])) {
            $filterClause = " AND (Incidencias.firma IS NULL OR Incidencias.firma = '')";
        }
    } elseif ($_GET['filter_field'] === 'estado') {
        $fv = strtolower($filterValue);
        if ($fv === 'abierto' || $fv === '0') {
            $filterClause = " AND Incidencias.estado = ?";
            $params[] = 0;
        } elseif ($fv === 'cerrado' || $fv === '1') {
            $filterClause = " AND Incidencias.estado = ?";
            $params[] = 1;
        } else {
            // Valor inválido: ignorar filtro para evitar errores o devolver vacío
            $filterClause = '';
            $params = [];
        }
    } else {
        $filterClause = " AND $filterField = ?";
        $params[] = $filterValue;
    }
}

if ($filterField === 'Incidencias.estado' && !in_array($permiso, ['admin', 'jefetecnico', 'cliente', 'recepcion'])) {
  $filterClause = $filterField = '';
  $params = [];
}
if (in_array($permiso, ['admin', 'recepcion', 'jefetecnico'])) {
  $sql = "SELECT Incidencias.*, Usuarios.usuario, Equipos.ubicacion
            FROM Incidencias
            LEFT JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios
            LEFT JOIN Equipos ON Incidencias.numEquipo = Equipos.numEquipo
            WHERE 1=1 $filterClause";
} elseif ($permiso === 'cliente') {
  $sql = "SELECT Incidencias.*, Usuarios.usuario, Equipos.ubicacion
            FROM Incidencias
            LEFT JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios
            LEFT JOIN Equipos ON Incidencias.numEquipo = Equipos.numEquipo
            WHERE Incidencias.idUsuario = ?" . $filterClause;
  array_unshift($params, $_SESSION['idUsuario']);
} elseif ($permiso === 'tecnico') {
  $sql = "SELECT Incidencias.*, Usuarios.usuario, Equipos.ubicacion
            FROM Incidencias
            LEFT JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios
            LEFT JOIN Equipos ON Incidencias.numEquipo = Equipos.numEquipo
            WHERE Incidencias.estado = 0 AND Incidencias.tecnicoAsignado = ?" . $filterClause;
  array_unshift($params, $userRow['usuario']);
} else {
  header("Location: ../login.php");
  exit;
}

try {
  $stmt = $bd->prepare($sql);
  $stmt->execute($params);

  $descripcionLog = "El usuario '{$nombreUsuario}' con permiso '{$permiso}' ha accedido a verIncidencias";
  if ($filterField && $filterValue !== '') {
    $campoFiltro = array_search($filterField, $allowedFields);
    $descripcionLog .= " con filtro por $campoFiltro = '$filterValue'";
  }
  $totalIncidencias = $stmt->rowCount();
  $descripcionLog .= ". Se encontraron $totalIncidencias incidencias.";
  registrarLog($bd, 'ver incidencias', $descripcionLog, $nombreUsuario);

} catch (PDOException $e) {
  echo "Error en la consulta: " . $e->getMessage();
  registrarLog($bd, 'error en verIncidencias', "Error: " . $e->getMessage(), $nombreUsuario);
  exit;
}
$allowedFieldsCliente = [
  'numEquipo' => 'Incidencias.numEquipo',
  'estado' => 'Incidencias.estado'
];

if ($permiso === 'cliente') {
  $allowedFieldsToUse = $allowedFieldsCliente;
} else {
  $allowedFieldsToUse = $allowedFields;
}

$filterField = isset($_GET['filter_field']) && isset($allowedFieldsToUse[$_GET['filter_field']])
  ? $allowedFieldsToUse[$_GET['filter_field']]
  : '';

$filterValue = trim($_GET['filter_value'] ?? '');

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ver Incidencias</title>
  <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .nav {
      background-color: #00225a;
      padding: 0.8rem;
      text-align: center;
      width: 100%;
    }

    .nav .brand {
      color: white;
      font-size: 2rem;
      font-weight: bold;
    }

    .container {
      flex: 1;
      width: 95%;
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
    }

    h1 {
      color: #00225a;
      margin-bottom: 20px;
      border-bottom: 2px solid #2573fa;
      padding-bottom: 10px;
    }

    footer.footer {
      background-color: #000;
      color: white;
      padding: 1rem;
      text-align: center;
      width: 100%;
      margin-top: auto;
    }

    .footer-content {
      display: inline-block;
    }

    .footer a {
      color: white;
      text-decoration: none;
    }

    .filter-form {
      margin-bottom: 20px;
      padding: 15px;
      background-color: #eaf1ff;
      border-radius: 5px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      border: 1px solid #2573fa;
    }

    .filter-form label {
      margin-right: 5px;
    }

    select,
    input[type="text"] {
      padding: 8px;
      border-radius: 4px;
      border: 1px solid #ddd;
    }

    .btn {
      background-color: #00225a;
      color: white;
      border: none;
      padding: 8px 15px;
      cursor: pointer;
      border-radius: 4px;
      font-size: 14px;
      text-decoration: none;
      display: inline-block;
      transition: background-color 0.3s;
    }

    .btn:hover {
      background-color: #2573fa;
    }

    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background-color: #5a6268;
    }

    .table-container {
      width: 100%;
      overflow-x: auto;
      margin-bottom: 20px;
      border: 1px solid #2573fa;
      border-radius: 4px;
      background-color: #fff;
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
      background-color: #2573fa;
      color: white;
      position: sticky;
      top: 0;
    }

    tr:nth-child(even) {
      background-color: #f0f5ff;
    }

    tr:hover {
      background-color: #e5eeff;
    }

    .green {
      color: green;
      font-weight: bold;
    }

    .red {
      color: red;
      font-weight: bold;
    }

    .grey {
      color: grey;
      font-style: italic;
    }

    .action-links {
      margin-top: 20px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .action-links a {
      display: inline-block;
      padding: 8px 15px;
      background-color: #00225a;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      margin-bottom: 5px;
      font-weight: bold;
      transition: background-color 0.3s;
    }

    .action-links a:hover {
      background-color: #2573fa;
    }

    .card-view {
      display: none;
    }

    .card {
      border: 1px solid #2573fa;
      border-radius: 5px;
      padding: 15px;
      margin-bottom: 15px;
      background-color: #f8faff;
      box-shadow: 0 2px 8px rgba(37, 115, 250, 0.15);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px solid #2573fa;
      background-color: #eaf1ff;
      margin: -15px -15px 15px -15px;
      padding: 12px 15px;
      border-radius: 5px 5px 0 0;
    }

    .card-id {
      font-weight: bold;
      color: #00225a;
      font-size: 16px;
    }

    .card-status {
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: bold;
    }

    .card-status.complete {
      background-color: #d4edda;
      color: #155724;
    }

    .card-status.incomplete {
      background-color: #f8d7da;
      color: #721c24;
    }

    .card-row {
      display: flex;
      margin-bottom: 8px;
      padding-bottom: 6px;
      border-bottom: 1px solid #e1e8ff;
    }

    .card-label {
      flex: 0 0 40%;
      font-weight: bold;
      color: #00225a;
    }

    .card-value {
      flex: 0 0 60%;
      color: #333;
    }

    @media screen and (max-width: 1024px) {

      th,
      td {
        padding: 8px;
        font-size: 14px;
      }
    }

    @media screen and (max-width: 768px) {
      .nav .brand {
        font-size: 1.8rem;
      }

      .filter-form {
        flex-direction: column;
        align-items: flex-start;
      }

      .filter-form label,
      .filter-form select,
      .filter-form input[type="text"] {
        width: 100%;
        margin-bottom: 5px;
      }

      .filter-buttons {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        width: 100%;
      }

      .filter-buttons .btn {
        flex: 1;
        text-align: center;
      }

      h1 {
        color: #00225a;
        font-size: 1.8rem;
        border-bottom: 2px solid #2573fa;
        padding-bottom: 8px;
        margin-bottom: 20px;
      }
    }

    @media screen and (max-width: 576px) {
      .table-container {
        display: none;
      }

      .card-view {
        display: block;
      }

      .container {
        width: 100%;
        padding: 10px;
      }

      h1 {
        font-size: 1.5rem;
        margin-bottom: 15px;
        text-align: center;
      }

      .footer-content {
        font-size: 0.9rem;
      }
    }
  </style>
</head>

<body>
  <header>
    <nav class="nav">
      <span class="brand">Mapache Security</span>
    </nav>
  </header>

  <div class="container">
    <h1>Lista de Incidencias</h1>

    <form method="GET" action="" class="filter-form">
      <label for="filter_field">Filtrar por:</label>
      <select name="filter_field" id="filter_field">
        <option value="">-- Seleccione --</option>
        <?php foreach ($allowedFieldsToUse as $key => $col): ?>
          <option value="<?= $key; ?>" <?= (isset($_GET['filter_field']) && $_GET['filter_field'] === $key) ? 'selected' : ''; ?>>
            <?= $key === 'usuario' ? 'Cliente' : ucfirst($key); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="text" name="filter_value" placeholder="Valor" value="<?= htmlspecialchars($filterValue); ?>">

      <div class="filter-buttons">
        <input type="submit" value="Filtrar" class="btn">
        <a href="<?= $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Limpiar</a>
      </div>
    </form>

    <?php if ($stmt->rowCount()): ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Número Incidencia</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Nombre</th>
              <th>Número</th>
              <th>Correo</th>
              <th>Ubicación</th>
              <th>Incidencia</th>
              <th>Observaciones</th>
              <th>Tiempo Desplazamiento</th>
              <th>Tiempo Intervención</th>
              <th>Técnico</th>
              <th>Estado</th>
              <th>Nº Equipo</th>
              <th>Firma</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $stmt->execute($params);
            while ($r = $stmt->fetch()):
              ?>
              <tr>
                <td><?= htmlspecialchars($r['numIncidencia']); ?></td>
                <td><?= htmlspecialchars($r['fecha']); ?></td>
                <td><?= htmlspecialchars($r['usuario']); ?></td>
                <td><?= htmlspecialchars($r['nombre']); ?></td>
                <td><?= htmlspecialchars($r['numero']); ?></td>
                <td><?= htmlspecialchars($r['correo']); ?></td>
                <td><?= htmlspecialchars($r['ubicacion'] ?? 'Sin ubicación'); ?></td>
                <td><?= htmlspecialchars($r['incidencia']); ?></td>
                <td><?= htmlspecialchars($r['observaciones']); ?></td>
                <td>
                  <?php if (!empty($r['TDesplazamiento'])): ?>
                    <?= htmlspecialchars($r['TDesplazamiento']); ?> min
                  <?php else: ?><span class="grey">pendiente</span><?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($r['TIntervencion'])): ?>
                    <?= htmlspecialchars($r['TIntervencion']); ?> min
                  <?php else: ?><span class="grey">pendiente</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['tecnicoAsignado']); ?></td>
                <td><?= $r['estado'] ? 'Cerrado' : 'Abierto'; ?></td>
                <td><?= htmlspecialchars($r['numEquipo']); ?></td>
                <td>
                  <?php if (!empty($r['firma'])): ?>
                    <span class="green">Firmado</span>
                  <?php else: ?>
                    <span class="red">Sin firmar</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="card-view">
        <?php
        $stmt->execute($params);
        while ($r = $stmt->fetch()):
          ?>
          <div class="card">
            <div class="card-header">
              <div class="card-id">ID: <?= htmlspecialchars($r['numIncidencia']); ?></div>
              <div class="card-status <?= $r['estado'] ? 'cerrado' : 'abierto'; ?>">
                <?= $r['estado'] ? 'Cerrado' : 'Abierto'; ?>
              </div>
            </div>
            <div class="card-row">
              <div class="card-label">Fecha:</div>
              <div class="card-value"><?= htmlspecialchars($r['fecha']); ?></div>
            </div>
            <div class="card-row">
              <div class="card-label">Cliente:</div>
              <div class="card-value"><?= htmlspecialchars($r['usuario']); ?></div>
            </div>
            <div class="card-row">
              <div class="card-label">Nombre:</div>
              <div class="card-value"><?= htmlspecialchars($r['nombre']); ?></div>
            </div>
            <div class="card-row">
              <div class="card-label">Número:</div>
              <div class="card-value"><?= htmlspecialchars($r['numero']); ?></div>
            </div>
            <div class="card-row">
              <div class="card-label">Ubicación:</div>
              <div class="card-value"><?= htmlspecialchars($r['ubicacion'] ?? 'Sin ubicación'); ?></div>
            </div>
            <div class="card-row">
              <div class="card-label">Incidencia:</div>
              <div class="card-value"><?= htmlspecialchars($r['incidencia']); ?></div>
            </div>
            <div class="card-row">
              <div class="card-label">Técnico:</div>
              <div class="card-value"><?= htmlspecialchars($r['tecnicoAsignado']); ?></div>
            </div>
            <div class="card-row">
              <div class="card-label">T. Desplaz.:</div>
              <div class="card-value">
                <?php if (!empty($r['TDesplazamiento'])): ?>       <?= htmlspecialchars($r['TDesplazamiento']); ?>
                  min<?php else: ?><span class="grey">pendiente</span><?php endif; ?>
              </div>
            </div>
            <div class="card-row">
              <div class="card-label">T. Interv.:</div>
              <div class="card-value">
                <?php if (!empty($r['TIntervencion'])): ?>       <?= htmlspecialchars($r['TIntervencion']); ?>
                  min<?php else: ?><span class="grey">pendiente</span><?php endif; ?>
              </div>
            </div>
            <div class="card-row">
              <div class="card-label">Nº Equipo:</div>
              <div class="card-value"><?= htmlspecialchars($r['numEquipo']); ?></div>
            </div>

            <div class="card-row">
              <div class="card-label">Firma:</div>
              <div class="card-value">
                <?php if (!empty($r['firma'])): ?><span class="green">Firmado</span><?php else: ?><span class="red">Sin
                    firmar</span><?php endif; ?>
              </div>
            </div>
            <div class="card-row">
              <div class="card-label">Estado:</div>
              <div class="card-value">
                <?php if ($r['estado']): ?><span class="green">Completo</span><?php else: ?><span
                    class="red">Incompleto</span><?php endif; ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p>No hay incidencias disponibles.</p>
    <?php endif; ?>

    <div class="action-links">
      <a href="../home.php">Volver al inicio</a>
      <a href="crearIncidencias.php">Crear incidencia</a>
       <a href="pdf_incidencias.php?filter_field=<?= urlencode($_GET['filter_field'] ?? '') ?>&filter_value=<?= urlencode($_GET['filter_value'] ?? '') ?>" target="_blank" class="btn">
    <i class="bi bi-file-earmark-pdf-fill" style="margin-right: 6px;"></i>Descargar PDF
  </a>
    </div>
  </div>

  <footer class="footer">
    <div class="footer-content">
      <span>&copy; Copyright 2025</span>
    </div>
  </footer>

  <script>
    const sel = document.getElementById('filter_field');
    const inp = document.querySelector('input[name="filter_value"]');
    sel.addEventListener('change', () => {
      if (sel.value === 'firma') {
        inp.placeholder = 'true o firmado / false o sin firmar';
      } else {
        inp.placeholder = 'Valor';
      }
    });
    sel.dispatchEvent(new Event('change'));
  </script>
</body>

</html>