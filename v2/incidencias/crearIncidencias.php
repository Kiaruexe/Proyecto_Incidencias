<?php
session_start();

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

  $nombreUsuario = 'anónimo';
  if (isset($_SESSION["idUsuario"])) {
    $qU = $bd->prepare("SELECT usuario FROM Usuarios WHERE idUsuarios = ?");
    $qU->execute([$_SESSION['idUsuario']]);
    $nombreUsuario = $qU->fetchColumn() ?: 'anónimo';
  }

} catch (PDOException $e) {
  error_log("Error de conexión BD al iniciar: " . $e->getMessage());
  $bd = null;
  $nombreUsuario = 'anónimo';
}

set_exception_handler(function (Throwable $e) use ($bd, $nombreUsuario) {
  if (!$bd) {
    error_log("Excepción sin BD: " . $e->getMessage());
    http_response_code(500);
    echo "<h1>Lo sentimos, ha ocurrido un error.</h1>";
    exit;
  }

  $usuario = $_SESSION['idUsuario'] ?? $nombreUsuario;

  $detalle = sprintf(
    "Excepción no capturada en crearIncidencias.php: %s en %s:%d\nStack trace:\n%s",
    $e->getMessage(),
    $e->getFile(),
    $e->getLine(),
    $e->getTraceAsString()
  );

  registrarLog($bd, 'error en crear incidencias', $detalle, $usuario);

  http_response_code(500);
  echo "<h1>Lo sentimos, ha ocurrido un error.</h1>";
  exit;
});

if (!isset($_SESSION["idUsuario"])) {
  header("Location: ../login.php");
  exit;
}

if (isset($bd) && $bd) {
  try {
    $qU = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
    $qU->execute([$_SESSION['idUsuario']]);
    $userRow = $qU->fetch();

    if (!$userRow) {
      throw new Exception("Usuario no encontrado: {$_SESSION['idUsuario']}");
    }

    $permiso = strtolower($userRow['permiso']);
    $nombreUsuario = $userRow['usuario'];

    registrarLog(
      $bd,
      'acceso crear incidencias',
      "El usuario '{$nombreUsuario}' con permiso '{$permiso}' ha accedido a crear incidencias.",
      $nombreUsuario
    );
  } catch (PDOException $e) {
    error_log("Error al obtener datos de usuario: " . $e->getMessage());
  }
}

$incidenciaPrev = $_POST['incidencia'] ?? $_GET['incidenciaPrev'] ?? '';
$nombrePrev = $_POST['nombre'] ?? $_GET['nombrePrev'] ?? '';
$numeroPrev = $_POST['numero'] ?? $_GET['numeroPrev'] ?? '';

if (in_array($permiso, ['recepcion', 'admin', 'jefetecnico']) && isset($_POST['elegirCliente'])) {
  $clienteElegido = $_POST['cliente'] ?? '';
  if ($clienteElegido !== '') {
    try {
      $qCli = $bd->prepare("SELECT usuario FROM Usuarios WHERE idUsuarios = ?");
      $qCli->execute([$clienteElegido]);
      $nombreCliente = $qCli->fetchColumn() ?: 'desconocido';

      registrarLog(
        $bd,
        'selección de cliente',
        "El usuario '{$nombreUsuario}' ha seleccionado al cliente '{$nombreCliente}' (ID: {$clienteElegido}) para crear una incidencia.",
        $nombreUsuario
      );

      $qs = http_build_query([
        'clienteElegido' => $clienteElegido,
        'incidenciaPrev' => $incidenciaPrev,
        'nombrePrev' => $nombrePrev,
        'numeroPrev' => $numeroPrev,
      ]);
      header("Location: {$_SERVER['PHP_SELF']}?$qs");
      exit;
    } catch (PDOException $e) {
      registrarLog(
        $bd,
        'error al seleccionar cliente',
        "Error al seleccionar cliente ID {$clienteElegido}: " . $e->getMessage(),
        $nombreUsuario
      );
      die("<p style='color:red;'>Error al seleccionar cliente: " . $e->getMessage() . "</p>");
    }
  }
}

$tieneEquipos = false;
if ($permiso === 'cliente') {
  try {
    $qCnt = $bd->prepare("SELECT COUNT(*) FROM Equipos WHERE idUsuario = ?");
    $qCnt->execute([$_SESSION['idUsuario']]);
    $tieneEquipos = $qCnt->fetchColumn() > 0;

    if (!$tieneEquipos) {
      registrarLog(
        $bd,
        'verificación de equipos',
        "El cliente '{$nombreUsuario}' intentó crear una incidencia pero no tiene equipos asignados.",
        $nombreUsuario
      );
    }
  } catch (PDOException $e) {
    registrarLog(
      $bd,
      'error verificando equipos',
      "Error al verificar equipos del usuario {$nombreUsuario}: " . $e->getMessage(),
      $nombreUsuario
    );
    echo "<p style='color:red;'>Error verificando equipos.</p>";
  }
}

if (isset($_POST['crear']) && ($permiso !== 'cliente' || $tieneEquipos)) {
  function limpiar($v)
  {
    return !empty($v) ? $v : null;
  }

  $incidencia = $_POST['incidencia'] ?? '';
  $correo = limpiar($_POST['correo'] ?? '');
  $nombre = limpiar($_POST['nombre'] ?? '');
  $numero = limpiar($_POST['numero'] ?? '');
  $fecha = date('Y-m-d');
  $estado = 0;
  $obs = null;
  $td = null;
  $ti = null;
  $tech = "sin asignar";
  $cp = $_POST['cp'] ?? null;
  $prov = $_POST['provincia'] ?? null;
  $loc = $_POST['localidad'] ?? null;
  $dirDet = $_POST['direccionDetalle'] ?? null;

  $idU = null;
  $numEq = null;
  $tipoFin = null;

  if ($permiso === 'cliente') {
    $numEq = limpiar($_POST['equipo']);
    $idU = $_SESSION['idUsuario'];
    if ($numEq) {
      $qE = $bd->prepare("
        SELECT tipoMantenimiento, cp, provincia, localidad, direccion
        FROM Equipos WHERE numEquipo = ? AND idUsuario = ?
      ");
      $qE->execute([$numEq, $idU]);
      $d = $qE->fetch();
      $tipoFin = $d['tipoMantenimiento'] ?? null;
      $cp = $d['cp'] ?? $cp;
      $prov = $d['provincia'] ?? $prov;
      $loc = $d['localidad'] ?? $loc;
      $dirDet = $d['direccion'] ?? $dirDet;
    }
  } else {
    $idU = limpiar($_POST['cliente'] ?? '');
    $numEq = limpiar($_POST['equipo'] ?? '');
    if ($numEq) {
      $qE = $bd->prepare("
        SELECT tipoMantenimiento, cp, provincia, localidad, direccion
        FROM Equipos WHERE numEquipo = ? AND idUsuario = ?
      ");
      $qE->execute([$numEq, $idU]);
      $d = $qE->fetch();
      $tipoFin = $d['tipoMantenimiento'] ?? null;
      $cp = $d['cp'] ?? $cp;
      $prov = $d['provincia'] ?? $prov;
      $loc = $d['localidad'] ?? $loc;
      $dirDet = $d['direccion'] ?? $dirDet;
    }
    if (in_array($permiso, ['admin', 'jefetecnico'])) {
      $tech = limpiar($_POST['tecnico'] ?? '') ?: "sin asignar";
    }
  }

  try {
    $querySerie = $bd->prepare("SELECT valor FROM Contadores WHERE nombre = 'serie'");
    $querySerie->execute();
    $anio = $querySerie->fetchColumn();

    if (!$anio) {
      $anio = date('Y');
    }

    $queryCount = $bd->prepare("SELECT COUNT(*) FROM Incidencias WHERE numIncidencia LIKE ?");
    $queryCount->execute([$anio . '%']);
    $count = $queryCount->fetchColumn();

    $numIncidencia = $anio . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    registrarLog(
      $bd,
      'generación número incidencia',
      "Generado número de incidencia {$numIncidencia} para el usuario '{$nombreUsuario}'.",
      $nombreUsuario
    );
  } catch (PDOException $e) {
    registrarLog(
      $bd,
      'error al generar número incidencia',
      "Error al generar número de incidencia: " . $e->getMessage(),
      $nombreUsuario
    );
    die("<p style='color:red;'>Error al generar número de incidencia: " . $e->getMessage() . "</p>");
  }

  try {
    $sql = "INSERT INTO Incidencias (
              fecha, estado, tecnicoAsignado, observaciones,
              TDesplazamiento, TIntervencion, tipoFinanciacion,
              idUsuario, numEquipo, incidencia,
              cp, localidad, provincia, direccion, correo,
              nombre, numero, numIncidencia
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $st = $bd->prepare($sql);
    $st->execute([
      $fecha,
      $estado,
      $tech,
      $obs,
      $td,
      $ti,
      $tipoFin,
      $idU,
      $numEq,
      $incidencia,
      $cp,
      $loc,
      $prov,
      $dirDet,
      $correo,
      $nombre,
      $numero,
      $numIncidencia
    ]);

    $clienteInfo = "";
    if ($permiso !== 'cliente') {
      $qClienteInfo = $bd->prepare("SELECT usuario FROM Usuarios WHERE idUsuarios = ?");
      $qClienteInfo->execute([$idU]);
      $clienteNombre = $qClienteInfo->fetchColumn() ?: 'desconocido';
      $clienteInfo = " para el cliente '{$clienteNombre}' (ID: {$idU})";
    }

    $descripcionIncidencia = "El usuario '{$nombreUsuario}' ha creado la incidencia #{$numIncidencia}{$clienteInfo}. " .
      "Equipo: {$numEq}, Tipo: '{$incidencia}', Técnico asignado: '{$tech}'.";

    registrarLog(
      $bd,
      'creación de incidencia',
      $descripcionIncidencia,
      $nombreUsuario
    );

    header("Location: ../home.php");
    exit;
  } catch (PDOException $e) {
    registrarLog(
      $bd,
      'error al crear incidencia',
      "Error al registrar incidencia: " . $e->getMessage() . "\nDatos: " . json_encode([
        'fecha' => $fecha,
        'estado' => $estado,
        'tecnico' => $tech,
        'idUsuario' => $idU,
        'numEquipo' => $numEq,
        'incidencia' => $incidencia,
        'numIncidencia' => $numIncidencia
      ]),
      $nombreUsuario
    );
    die("<p style='color:red;'>Error al registrar incidencia: "
      . $e->getMessage() . "</p>");
  }
}

$emailCliente = '';
if ($permiso === 'cliente') {
  $emailCliente = $userRow['correo'] ?? '';
} elseif (isset($_GET['clienteElegido'])) {
  try {
    $qC = $bd->prepare("SELECT correo FROM Usuarios WHERE idUsuarios = ?");
    $qC->execute([$_GET['clienteElegido']]);
    $emailCliente = $qC->fetchColumn() ?: '';
  } catch (PDOException $e) {
    registrarLog(
      $bd,
      'error al obtener correo de cliente',
      "Error al obtener correo del cliente ID {$_GET['clienteElegido']}: " . $e->getMessage(),
      $nombreUsuario
    );
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Crear Incidencias</title>
  <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      padding: 20px;
      text-align: center;
    }

    form {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      max-width: 600px;
      margin: 0 auto;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      text-align: left;
    }

    h1 {
      color: #2c3e50;
      text-align: center;
      margin-bottom: 20px;
    }

    p {
      margin-bottom: 15px;
    }

    label {
      font-weight: bold;
    }

    select,
    textarea,
    input[type="text"],
    input[type="email"],
    input[type="number"] {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      margin-bottom: 10px;
    }

    input[type="submit"] {/* Logo Mapache Security centrado */
.logo-mapache {
  text-align: center;
  margin: 20px 0;
  font-size: 24px;
  font-weight: bold;
  color: #2c3e50;
}

.logo-mapache .casa-icon {
  color: white;
  background: #2c3e50;
  padding: 5px 8px;
  border-radius: 4px;
  margin: 0 5px;
  display: inline-block;
}

/* Botones verdes específicos */
.btn-registrar-equipo,
.btn-agregar-pago {
  background: #27ae60 !important;
  color: #fff !important;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  font-size: 14px;
  transition: background-color 0.3s ease;
}

.btn-registrar-equipo:hover,
.btn-agregar-pago:hover {
  background: #219150 !important;
}

/* Si los botones están dentro de enlaces */
a.btn-registrar-equipo,
a.btn-agregar-pago {
  background: #27ae60;
  color: #fff;
  padding: 10px 20px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 14px;
  transition: background-color 0.3s ease;
}

a.btn-registrar-equipo:hover,
a.btn-agregar-pago:hover {
  background: #219150;
}
      background: #3498db;
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    input[type="submit"]:hover {
      background: #2980b9;
    }

    .nuevo-cliente,
    .nuevo-equipo {
      text-align: right;
      margin-top: 10px;
    }

    .nuevo-cliente a,
    .nuevo-equipo a {
      background: #27ae60;
      color: #fff;
      padding: 8px 12px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
    }

    .nuevo-cliente a:hover,
    .nuevo-equipo a:hover {
      background: #219150;
    }
  </style>
</head>

<body>
  <form method="POST">
    <h1>Crear Incidencias</h1>

    <?php if ($permiso === 'cliente'): ?>
      <?php if ($tieneEquipos): ?>
        <p><label>Equipo:</label><br>
          <select name="equipo" id="equipo" required>
            <option value="">Seleccione un equipo</option>
            <?php
            $qe = $bd->prepare("
              SELECT numEquipo, cp, provincia, localidad, direccion,
                     tipoMantenimiento, modelo
              FROM Equipos WHERE idUsuario = ?
            ");
            $qe->execute([$_SESSION['idUsuario']]);
            while ($eq = $qe->fetch()) {
              echo '<option value="' . htmlspecialchars($eq['numEquipo']) . '" '
                . 'data-cp="' . htmlspecialchars($eq['cp']) . '" '
                . 'data-provincia="' . htmlspecialchars($eq['provincia']) . '" '
                . 'data-localidad="' . htmlspecialchars($eq['localidad']) . '" '
                . 'data-direccion="' . htmlspecialchars($eq['direccion']) . '">'
                . htmlspecialchars($eq['numEquipo']) . ' - '
                . htmlspecialchars($eq['modelo']) . ' ('
                . htmlspecialchars($eq['tipoMantenimiento']) . ')</option>';
            }
            ?>
          </select>
        </p>
        <p><label>Correo electrónico:</label><br>
          <input type="email" name="correo" value="<?= htmlspecialchars($emailCliente) ?>" required>
        </p>
        <p><label>Nombre:</label><br>
          <input type="text" name="nombre" required value="<?= htmlspecialchars($nombrePrev) ?>">
        </p>
        <p><label>Número (9 dígitos):</label><br>
          <input type="number" name="numero" required min="100000000" max="999999999" step="1"
            value="<?= htmlspecialchars($numeroPrev) ?>">
        </p>
        <p><label>Incidencia:</label><br>
          <textarea name="incidencia" required><?= htmlspecialchars($incidenciaPrev) ?></textarea>
        </p>
        <p><label>Dirección del Equipo:</label><br>
          <input type="text" id="direccionEquipo" readonly>
        </p>
        <p><input type="submit" name="crear" value="Crear incidencia"></p>
      <?php else: ?>
        <p style="color:red;">No tienes equipos asignados.</p>
      <?php endif; ?>

    <?php else: ?>
      <?php if (!isset($_GET['clienteElegido'])): ?>
        <p><label>Cliente:</label><br>
          <select name="cliente" id="cliente" required>
            <option value="">Seleccione un cliente</option>
            <?php
            try {
              $qc = $bd->query("
                SELECT idUsuarios, usuario, correo
                FROM Usuarios WHERE permiso = 'cliente'
              ");
              while ($cli = $qc->fetch()) {
                echo '<option value="' . htmlspecialchars($cli['idUsuarios']) . '" '
                  . 'data-correo="' . htmlspecialchars($cli['correo']) . '">'
                  . htmlspecialchars($cli['idUsuarios']) . ' - '
                  . htmlspecialchars($cli['usuario']) . '</option>';
              }
            } catch (PDOException $e) {
              registrarLog(
                $bd,
                'error al listar clientes',
                "Error al obtener lista de clientes: " . $e->getMessage(),
                $nombreUsuario
              );
              echo '<option value="">Error al cargar clientes</option>';
            }
            ?>
          </select>
        </p>
        <p><label>Correo electrónico:</label><br>
          <input type="email" name="correo" value="<?= htmlspecialchars($emailCliente) ?>" required>
        </p>
        <p><label>Nombre:</label><br>
          <input type="text" name="nombre" required value="<?= htmlspecialchars($nombrePrev) ?>">
        </p>
        <p><label>Número (9 dígitos):</label><br>
          <input type="number" name="numero" required min="100000000" max="999999999" step="1"
            value="<?= htmlspecialchars($numeroPrev) ?>">
        </p>
        <p><label>Incidencia:</label><br>
          <textarea name="incidencia" required><?= htmlspecialchars($incidenciaPrev) ?></textarea>
        </p>
        <p>
          <input type="submit" name="elegirCliente" value="Continuar">
          <span class="nuevo-cliente">
            <a href="../usuarios/crearUsuarios.php" target="_blank">Crear nuevo cliente</a>
          </span>
        </p>

      <?php else:
        $clienteElegido = $_GET['clienteElegido'];
        echo '<input type="hidden" name="cliente" value="' . htmlspecialchars($clienteElegido) . '">';
        try {
          $qcl = $bd->prepare("SELECT usuario FROM Usuarios WHERE idUsuarios = ?");
          $qcl->execute([$clienteElegido]);
          $cliData = $qcl->fetch();
        } catch (PDOException $e) {
          registrarLog(
            $bd,
            'error al obtener datos de cliente',
            "Error al obtener datos del cliente ID {$clienteElegido}: " . $e->getMessage(),
            $nombreUsuario
          );
          $cliData = ['usuario' => 'Desconocido'];
        }
        ?>
        <p>Cliente seleccionado: <?= htmlspecialchars($cliData['usuario']) ?></p>
        <p><label>Correo electrónico:</label><br>
          <input type="email" name="correo" value="<?= htmlspecialchars($emailCliente) ?>" required>
        </p>
        <p><label>Nombre:</label><br>
          <input type="text" name="nombre" required value="<?= htmlspecialchars($nombrePrev) ?>">
        </p>
        <p><label>Número (9 dígitos):</label><br>
          <input type="number" name="numero" required min="100000000" max="999999999" step="1"
            value="<?= htmlspecialchars($numeroPrev) ?>">
        </p>
        <p><label>Incidencia:</label><br>
          <textarea name="incidencia" required><?= htmlspecialchars($incidenciaPrev) ?></textarea>
        </p>
        <p><label>Equipo:</label><br>
          <select name="equipo" id="equipo" required>
            <option value="">Seleccione un equipo</option>
            <?php
            try {
              $qe2 = $bd->prepare("
                SELECT numEquipo, cp, provincia, localidad, direccion,
                       tipoMantenimiento, modelo
                FROM Equipos WHERE idUsuario = ?
              ");
              $qe2->execute([$clienteElegido]);
              while ($eq = $qe2->fetch()) {
                echo '<option value="' . htmlspecialchars($eq['numEquipo']) . '" '
                  . 'data-cp="' . htmlspecialchars($eq['cp']) . '" '
                  . 'data-provincia="' . htmlspecialchars($eq['provincia']) . '" '
                  . 'data-localidad="' . htmlspecialchars($eq['localidad']) . '" '
                  . 'data-direccion="' . htmlspecialchars($eq['direccion']) . '">'
                  . htmlspecialchars($eq['numEquipo']) . ' - '
                  . htmlspecialchars($eq['modelo']) . ' ('
                  . htmlspecialchars($eq['tipoMantenimiento']) . ')</option>';
              }
            } catch (PDOException $e) {
              registrarLog(
                $bd,
                'error al listar equipos',
                "Error al obtener equipos del cliente ID {$clienteElegido}: " . $e->getMessage(),
                $nombreUsuario
              );
              echo '<option value="">Error al cargar equipos</option>';
            }
            ?>
          </select>
        </p>
        <?php if (in_array($permiso, ['admin', 'recepcion', 'jefetecnico'])): ?>
          <div class="nuevo-equipo">
            <a href="../equipos/crearEquipos.php" target="_blank">Crear nuevo equipo</a>
          </div>
        <?php endif; ?>
        <p><label>Dirección del Equipo:</label><br>
          <input type="text" id="direccionEquipo" readonly>
        </p>
        <?php if (in_array($permiso, ['admin', 'jefetecnico'])): ?>
          <p><label>Técnico asignado:</label><br>
            <select name="tecnico">
              <option value="sin asignar">Sin asignar</option>
              <?php
              try {
                $qTech = $bd->query("
                  SELECT idUsuarios, usuario 
                  FROM Usuarios 
                  WHERE permiso = 'tecnico'
                  ORDER BY usuario
                ");
                while ($tech = $qTech->fetch()) {
                  echo '<option value="' . htmlspecialchars($tech['usuario']) . '">'
                    . htmlspecialchars($tech['usuario']) . '</option>';
                }
              } catch (PDOException $e) {
                registrarLog(
                  $bd,
                  'error al listar técnicos',
                  "Error al obtener lista de técnicos: " . $e->getMessage(),
                  $nombreUsuario
                );
                echo '<option value="sin asignar">Error al cargar técnicos</option>';
              }
              ?>
            </select>
          </p>
        <?php endif; ?>
        <p><input type="submit" name="crear" value="Crear incidencia"></p>
      <?php endif; ?>
    <?php endif; ?>
  </form>

  <input type="hidden" name="cp" id="cp">
  <input type="hidden" name="provincia" id="provincia">
  <input type="hidden" name="localidad" id="localidad">
  <input type="hidden" name="direccionDetalle" id="direccionDetalle">

  <script>
    function actualizarDatosEquipo(sel) {
      const o = sel.options[sel.selectedIndex];
      document.getElementById('direccionEquipo').value = o.getAttribute('data-direccion') || '';
      document.getElementById('cp').value = o.getAttribute('data-cp') || '';
      document.getElementById('provincia').value = o.getAttribute('data-provincia') || '';
      document.getElementById('localidad').value = o.getAttribute('data-localidad') || '';
      document.getElementById('direccionDetalle').value = o.getAttribute('data-direccion') || '';
    }

    document.addEventListener('DOMContentLoaded', function () {
      const equipoSelect = document.getElementById('equipo');
      if (equipoSelect && equipoSelect.value) {
        actualizarDatosEquipo(equipoSelect);
      }
    });

    document.getElementById('equipo')?.addEventListener('change', e => actualizarDatosEquipo(e.target));

    <?php if (in_array($permiso, ['recepcion', 'admin', 'jefetecnico']) && !isset($_GET['clienteElegido'])): ?>
      document.getElementById('cliente')?.addEventListener('change', function () {
        const c = this.options[this.selectedIndex].getAttribute('data-correo') || '';
        document.querySelector('input[name="correo"]').value = c;
      });
    <?php endif; ?>
  </script>
</body>

</html>