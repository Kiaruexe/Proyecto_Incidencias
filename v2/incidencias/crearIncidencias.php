<?php
session_start();

function registrarLog(PDO $bd, string $accion, string $descripcion, string $usuario): void {
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
    $qU = $bd->prepare("SELECT usuario, permiso, correo FROM Usuarios WHERE idUsuarios = ?");
    $qU->execute([$_SESSION['idUsuario']]);
    $userRow = $qU->fetch();
    if ($userRow) {
      $nombreUsuario = $userRow['usuario'];
      $permiso = strtolower($userRow['permiso']);
      $emailCliente = $userRow['correo'] ?? '';
    } else {
      $permiso = '';
      $emailCliente = '';
    }
  } else {
    header("Location: ../login.php");
    exit;
  }
} catch (PDOException $e) {
  error_log("Error de conexión BD al iniciar: " . $e->getMessage());
  die("<h1>Error de conexión</h1>");
}

$tieneEquipos = false;
if ($permiso === 'cliente') {
  $qCnt = $bd->prepare("SELECT COUNT(*) FROM Equipos WHERE idUsuario = ?");
  $qCnt->execute([$_SESSION['idUsuario']]);
  $tieneEquipos = $qCnt->fetchColumn() > 0;
}

$incidenciaPrev = $_POST['incidencia'] ?? $_GET['incidenciaPrev'] ?? '';
$nombrePrev = $_POST['nombre'] ?? $_GET['nombrePrev'] ?? '';
$numeroPrev = $_POST['numero'] ?? $_GET['numeroPrev'] ?? '';

$alertMsg = '';
$alertType = '';
$redirectHome = false;

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

if (isset($_POST['crear']) && ($permiso !== 'cliente' || $tieneEquipos)) {
  function limpiar($v) {
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

  if (!preg_match('/^\d{9}$/', $numero)) {
    $alertMsg = "El número debe contener exactamente 9 dígitos.";
    $alertType = "error";
  } else {
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
      $alertMsg = "Error al generar número de incidencia: " . $e->getMessage();
      $alertType = "error";
    }

    if ($alertType !== "error") {
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

        $alertMsg = "Incidencia creada con éxito.";
        $alertType = "success";
        $redirectHome = true;

      } catch (PDOException $e) {
        $alertMsg = "Error al registrar incidencia: " . $e->getMessage();
        $alertType = "error";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Crear Incidencias</title>
  <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png" />
  <style>
    *, *::before, *::after {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f0f2f5;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      color: #333;
    }

    header {
      background-color: #00225a;
      color: white;
      padding: 20px 30px;
      font-size: 2rem;
      font-weight: 700;
      text-align: center;
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 1000;
      box-shadow: 0 2px 5px rgba(0,0,0,0.3);
      user-select: none;
    }

    main {
      flex: 1 0 auto;
      max-width: 900px;
      margin: 100px auto 80px;
      background: #fff;
      border-radius: 12px;
      padding: 30px 40px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
      display: flex;
      flex-wrap: wrap;
      gap: 24px;
      align-items: flex-start;
      justify-content: space-between;
    }

    h1 {
      width: 100%;
      text-align: center;
      font-size: 2.4rem;
      margin-bottom: 30px;
      color: #00225a;
      font-weight: 800;
      letter-spacing: 1.5px;
      user-select: none;
    }

    form {
      display: contents;
      width: 100%;
    }

    .form-group {
      flex: 1 1 45%;
      display: flex;
      flex-direction: column;
    }

    .form-group.textarea-group {
      flex: 1 1 100%;
    }

    label {
      font-weight: 700;
      color: #00225a;
      margin-bottom: 8px;
      user-select: none;
      font-size: 1.1rem;
    }

    input[type="text"],
    input[type="email"],
    input[type="number"],
    select,
    textarea {
      font-size: 1rem;
      padding: 12px 16px;
      border-radius: 10px;
      border: 2px solid #2573fa;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      resize: vertical;
      color: #333;
      background-color: #f9fbff;
      box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.06);
      min-height: 45px;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="number"]:focus,
    select:focus,
    textarea:focus {
      outline: none;
      border-color: #f9ab25;
      box-shadow: 0 0 6px #f9ab25;
      background-color: #fff;
    }

    textarea {
      min-height: 120px;
      max-height: 180px;
      padding-top: 14px;
    }

    #direccionEquipo {
      background-color: #e1e9ff;
      cursor: not-allowed;
      color: #555;
    }

    .btn-group {
      width: 100%;
      display: flex;
      gap: 20px;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 15px;
    }

    .btn-group input[type="submit"],
    .btn-group input[name="elegirCliente"] {
      flex: 1 1 160px;
      max-width: 160px;
      padding: 14px 0;
      background-color: #f9ab25;
      border: none;
      border-radius: 30px;
      font-weight: 800;
      font-size: 1.05rem;
      color: #000;
      cursor: pointer;
      text-align: center;
      user-select: none;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.3s ease;
      box-shadow: none; /* Quitada la sombra naranja */
    }

    .btn-group input[type="submit"]:hover,
    .btn-group input[name="elegirCliente"]:hover {
      background-color: #d38e00;
      color: #000;
      box-shadow: none; /* Sin sombra en hover también */
    }

    .nuevo-cliente,
    .nuevo-equipo {
      width: 100%;
      text-align: right;
      margin-top: 10px;
    }

    /* Botones "Crear nuevo..." visuales */
    .nuevo-cliente button,
    .nuevo-equipo button {
      background-color: #2573fa;
      color: #fff;
      padding: 10px 16px;
      border-radius: 6px;
      border: none;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      user-select: none;
      transition: background-color 0.3s ease;
    }

    .nuevo-cliente button:hover,
    .nuevo-equipo button:hover {
      background-color: #1c56d1;
    }

    /* Estilo cliente seleccionado */
    #cliente-seleccionado {
      font-size: 1.4rem;
      font-weight: 700;
      color: #00225a;
      margin-bottom: 12px;
      user-select: none;
      text-align: left;
      width: 100%;
    }

    footer {
      background-color: #000;
      color: #fff;
      padding: 16px 10px;
      font-size: 0.9rem;
      text-align: center;
      user-select: none;
      flex-shrink: 0;
      box-shadow: 0 -2px 10px rgba(0,0,0,0.5);
      position: relative;
      z-index: 100;
    }

    @media (max-width: 768px) {
      main {
        margin: 110px 15px 80px;
        padding: 25px 20px;
      }

      .form-group {
        flex: 1 1 100%;
      }

      .btn-group {
        justify-content: center;
      }

      .nuevo-cliente,
      .nuevo-equipo {
        text-align: center;
        margin-top: 15px;
      }

      .btn-group input[type="submit"],
      .btn-group input[name="elegirCliente"] {
        max-width: 100%;
      }
    }
  </style>
</head>
<body>
  <header>Mapache Security</header>
  <main>
    <form method="POST" autocomplete="off" novalidate>
      <h1>Crear Incidencias</h1>

      <?php if ($permiso === 'cliente'): ?>
        <?php if ($tieneEquipos): ?>
          <div class="form-group">
            <label for="equipo">Equipo</label>
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
          </div>

          <div class="form-group">
            <label for="correo">Correo electrónico</label>
            <input type="email" name="correo" id="correo" value="<?= htmlspecialchars($emailCliente) ?>" required>
          </div>

          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" id="nombre" required value="<?= htmlspecialchars($nombrePrev) ?>">
          </div>

          <div class="form-group">
            <label for="numero">Teléfono (9 dígitos)</label>
            <input type="number" name="numero" id="numero" required min="100000000" max="999999999" step="1" maxlength="9"
              value="<?= htmlspecialchars($numeroPrev) ?>" pattern="\d{9}" title="Debe contener exactamente 9 dígitos">
          </div>

          <div class="form-group textarea-group">
            <label for="incidencia">Incidencia</label>
            <textarea name="incidencia" id="incidencia" required><?= htmlspecialchars($incidenciaPrev) ?></textarea>
          </div>

          <div class="form-group">
            <label for="direccionEquipo">Dirección del Equipo</label>
            <input type="text" id="direccionEquipo" readonly>
          </div>

          <div class="btn-group">
            <input type="submit" name="crear" value="Crear incidencia">
          </div>
          <?php if (in_array($permiso, ['admin', 'recepcion', 'jefetecnico'])): ?>

          <div class="nuevo-equipo">
            <button type="button" onclick="window.open('../equipos/crearEquipos.php', '_blank')">Crear nuevo equipo</button>
          </div>
          <?php endif; ?>

        <?php else: ?>
          <p style="color:red;">No tienes equipos asignados.</p>
        <?php endif; ?>

      <?php else: ?>
        <?php if (!isset($_GET['clienteElegido'])): ?>
          <div class="form-group">
            <label for="cliente">Cliente</label>
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
          </div>

          <div class="form-group">
            <label for="correo">Correo electrónico</label>
            <input type="email" name="correo" id="correo" value="<?= htmlspecialchars($emailCliente) ?>" required>
          </div>

          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" id="nombre" required value="<?= htmlspecialchars($nombrePrev) ?>">
          </div>

          <div class="form-group">
            <label for="numero">Teléfono (9 dígitos)</label>
            <input type="number" name="numero" id="numero" required min="100000000" max="999999999" step="1" maxlength="9"
              value="<?= htmlspecialchars($numeroPrev) ?>" pattern="\d{9}" title="Debe contener exactamente 9 dígitos">
          </div>

          <div class="form-group textarea-group">
            <label for="incidencia">Incidencia</label>
            <textarea name="incidencia" id="incidencia" required><?= htmlspecialchars($incidenciaPrev) ?></textarea>
          </div>

          <div class="btn-group">
            <input type="submit" name="elegirCliente" value="Continuar">
          </div>

          <div class="nuevo-cliente">
            <button type="button" onclick="window.open('../usuarios/crearUsuarios.php', '_blank')">Crear nuevo cliente</button>
          </div>

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
          <p id="cliente-seleccionado">Cliente seleccionado: <?= htmlspecialchars($cliData['usuario']) ?></p>

          <div class="form-group">
            <label for="correo">Correo electrónico</label>
            <input type="email" name="correo" id="correo" value="<?= htmlspecialchars($emailCliente) ?>" required>
          </div>

          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" id="nombre" required value="<?= htmlspecialchars($nombrePrev) ?>">
          </div>

          <div class="form-group">
            <label for="numero">Teléfono (9 dígitos)</label>
            <input type="number" name="numero" id="numero" required min="100000000" max="999999999" step="1" maxlength="9"
              value="<?= htmlspecialchars($numeroPrev) ?>" pattern="\d{9}" title="Debe contener exactamente 9 dígitos">
          </div>

          <div class="form-group textarea-group">
            <label for="incidencia">Incidencia</label>
            <textarea name="incidencia" id="incidencia" required><?= htmlspecialchars($incidenciaPrev) ?></textarea>
          </div>

          <div class="form-group">
            <label for="equipo">Equipo</label>
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
          </div>

          <?php if (in_array($permiso, ['admin', 'recepcion', 'jefetecnico'])): ?>
            <div class="nuevo-equipo">
              <button type="button" onclick="window.open('../equipos/crearEquipos.php', '_blank')">Crear nuevo equipo</button>
            </div>
          <?php endif; ?>

          <div class="form-group">
            <label for="direccionEquipo">Dirección del Equipo</label>
            <input type="text" id="direccionEquipo" readonly>
          </div>

          <?php if (in_array($permiso, ['admin', 'jefetecnico'])): ?>
            <div class="form-group">
              <label for="tecnico">Técnico asignado</label>
              <select name="tecnico" id="tecnico">
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
            </div>
          <?php endif; ?>

          <div class="btn-group">
            <input type="submit" name="crear" value="Crear incidencia">
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </form>
  </main>

  <footer>
    &copy; <?= date('Y') ?> Mapache Security. Todos los derechos reservados.
  </footer>

  <input type="hidden" name="cp" id="cp" />
  <input type="hidden" name="provincia" id="provincia" />
  <input type="hidden" name="localidad" id="localidad" />
  <input type="hidden" name="direccionDetalle" id="direccionDetalle" />

  <script>
    <?php if ($redirectHome): ?>
      alert("<?= addslashes($alertMsg) ?>");
      window.location.href = "../home.php";
    <?php elseif ($alertType === "error"): ?>
      alert("<?= addslashes($alertMsg) ?>");
    <?php endif; ?>

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
