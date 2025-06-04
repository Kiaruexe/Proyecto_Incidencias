<?php
session_start();
if (!isset($_SESSION["idUsuario"])) {
  header("Location: ../login.php");
  exit;
}

try {
  $bd = new PDO(
    'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
    'Mapapli',
    '9R%d5cf62'
  );

  $qU = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
  $qU->execute([$_SESSION['idUsuario']]);
  $userRow = $qU->fetch();
  $permiso = strtolower($userRow['permiso']);
} catch (PDOException $e) {
  die("Error de conexión: " . $e->getMessage());
}

if (!isset($_GET['id'])) {
  if ($permiso === 'cliente') {
    $sql = "SELECT i.idIncidencias, i.incidencia, u.usuario
                  FROM Incidencias i
                  JOIN Usuarios u ON i.idUsuario = u.idUsuarios
                 WHERE i.idUsuario = ?";
    $stmt = $bd->prepare($sql);
    $stmt->execute([$_SESSION['idUsuario']]);
  } elseif ($permiso === 'tecnico') {
    $sql = "SELECT i.idIncidencias, i.incidencia, u.usuario
                  FROM Incidencias i
                  JOIN Usuarios u ON i.idUsuario = u.idUsuarios
                 WHERE i.estado = 0 AND i.tecnicoAsignado = ?";
    $stmt = $bd->prepare($sql);
    $stmt->execute([$userRow['usuario']]);
  } else {
    $sql = "SELECT i.idIncidencias, i.incidencia, u.usuario
                  FROM Incidencias i
                  JOIN Usuarios u ON i.idUsuario = u.idUsuarios";
    $stmt = $bd->prepare($sql);
    $stmt->execute();
  }
  $incidencias = $stmt->fetchAll();
  if (!$incidencias) {
    echo "<script>alert('No hay incidencias disponibles para modificar.'); window.location.href='../home.php';</script>";
    exit;
  }
  ?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <meta charset="UTF-8" />
    <title>Seleccionar Incidencia a Modificar</title>
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png" />
    <style>
      /* Reset y base */
      *,
      *::before,
      *::after {
        box-sizing: border-box;
      }

      body,
      html {
        height: 100%;
        margin: 0;
        font-family: Arial, sans-serif;
        background: #f0f2f5;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }

      header {
        background-color: #00225a;
        color: white;
        padding: 20px 30px;
        font-size: 32px;
        font-weight: bold;
        text-align: center;
        flex-shrink: 0;
        user-select: none;
      }

      main {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px 15px;
      }

      .title-select {
        font-size: 1.8rem;
        font-weight: 700;
        color: #00225a;
        margin-bottom: 10px;
        text-align: center;
      }

      form.select-incidencia {
        background: white;
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        width: 600px;
        max-width: 90vw;
        text-align: center;
      }

      select {
        width: 100%;
        padding: 12px;
        font-size: 1rem;
        border-radius: 6px;
        border: 1px solid #ccc;
        background-color: #2573fa !important;
        color: white !important;
        appearance: none;
        outline: none;
        margin-bottom: 20px;
        cursor: pointer;
      }

      select option:first-child {
        background-color: #2573fa;
        color: white;
      }

      .btn-group {
        display: flex;
        gap: 15px;
      }

      .btn-group>* {
        flex: 1;
        padding: 12px 0;
        font-weight: bold;
        font-size: 16px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        color: black;
        background-color: #f9ab25;
        transition: background-color 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        justify-content: center;
        align-items: center;
      }

      .btn-group>input[type="submit"]:hover {
        background-color: #d38e00;
      }

      .btn-home {
        background-color: #00225a;
        color: white;
        text-decoration: none;
        display: inline-flex;
        justify-content: center;
        align-items: center;
      }

      .btn-home:hover {
        background-color: #001a48;
      }

      footer {
        background-color: #000;
        color: white;
        padding: 12px 0;
        text-align: center;
        font-size: 0.9rem;
        user-select: none;
        flex-shrink: 0;
      }

      @media (max-width: 640px) {
        form.select-incidencia {
          width: 100%;
          padding: 20px;
        }
      }
    </style>
  </head>

  <body>
    <header>Mapache Security</header>
    <main>
      <div class="title-select">Seleccionar Incidencia a Modificar</div>
      <form method="get" action="" class="select-incidencia" autocomplete="off">
        <select name="id" id="id" required>
          <option value="">-- Seleccione una incidencia --</option>
          <?php foreach ($incidencias as $inc): ?>
            <option value="<?= htmlspecialchars($inc['idIncidencias']) ?>">
              <?= "ID: " . htmlspecialchars($inc['idIncidencias']) . " - " . htmlspecialchars($inc['incidencia']) . " (Cliente: " . htmlspecialchars($inc['usuario']) . ")" ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="btn-group">
          <input type="submit" value="Modificar" />
          <a href="../home.php" class="btn-home">Volver al home</a>
        </div>
      </form>
    </main>
    <footer>
      &copy; <?= date('Y') ?> Mapache Security.
    </footer>
  </body>

  </html>
  <?php
  exit;
}

$idIncidencia = $_GET['id'];
try {
  $sql = "SELECT * FROM Incidencias WHERE idIncidencias = ?";
  $stmt = $bd->prepare($sql);
  $stmt->execute([$idIncidencia]);
  $incidenciaData = $stmt->fetch();
  if (!$incidenciaData) {
    echo "<script>alert('Incidencia no encontrada.'); window.location.href='../home.php';</script>";
    exit;
  }
  $TDesplazamiento_minutos = $incidenciaData['TDesplazamiento'] ?? 0;
  $TIntervencion_minutos = $incidenciaData['TIntervencion'] ?? 0;
} catch (PDOException $e) {
  die("Error al obtener la incidencia: " . $e->getMessage());
}

function obtenerValor($campo, $actual)
{
  return isset($_POST[$campo]) && trim($_POST[$campo]) !== ''
    ? trim($_POST[$campo]) : $actual;
}

if (isset($_POST['modificar']) || isset($_POST['finalizar'])) {
  $estado = intval($_POST['estado'] ?? $incidenciaData['estado']);
  $tecnicoAsignado = obtenerValor('tecnico', $incidenciaData['tecnicoAsignado']);
  $observaciones = obtenerValor('observaciones', $incidenciaData['observaciones']);
  $incidenciaText = obtenerValor('incidencia', $incidenciaData['incidencia']);
  $TDesplazamiento = intval($_POST['TDesplazamiento'] ?? $incidenciaData['TDesplazamiento']);
  $TIntervencion = intval($_POST['TIntervencion'] ?? $incidenciaData['TIntervencion']);
  $cp = obtenerValor('cp', $incidenciaData['cp']);
  $provincia = obtenerValor('provincia', $incidenciaData['provincia']);
  $localidad = obtenerValor('localidad', $incidenciaData['localidad']);
  $direccionDetalle = obtenerValor('direccion', $incidenciaData['direccion']);
  $idUsuario = intval(obtenerValor('idUsuario', $incidenciaData['idUsuario']));
  $numEquipo = obtenerValor('numEquipo', $incidenciaData['numEquipo']);
  $correo = obtenerValor('correo', $incidenciaData['correo']);
  $firmaFormulario = isset($_POST['firma']) ? trim($_POST['firma']) : '';
  $firmaBD = $incidenciaData['firma'] ?? '';
  $nombre = obtenerValor('nombre', $incidenciaData['nombre'] ?? '');
  $numero = obtenerValor('numero', $incidenciaData['numero'] ?? '');

  // Validaciones según estado y firma
  if ($estado == 0) {
    if ($firmaFormulario !== '' && $firmaFormulario !== $firmaBD) {
      echo "<script>alert('No se puede firmar una incidencia abierta.'); history.back();</script>";
      exit;
    }
  }

  if ($estado == 1) {
    if ($firmaBD !== '' && $firmaFormulario === '') {
      echo "<script>alert('No se puede borrar la firma de una incidencia cerrada.'); history.back();</script>";
      exit;
    }
  }

  // Validación para "finalizar"
  if (isset($_POST['finalizar'])) {
    if ($estado != 1 || $firmaFormulario === '') {
      echo "<script>alert('Para finalizar la incidencia debe estar cerrada y firmada.'); history.back();</script>";
      exit;
    }
  }

  try {
    $sqlUpdate = "UPDATE Incidencias SET
            estado          = ?,
            tecnicoAsignado = ?,
            observaciones   = ?,
            incidencia      = ?,
            TDesplazamiento = ?,
            TIntervencion   = ?,
            cp              = ?,
            provincia       = ?,
            localidad       = ?,
            direccion       = ?,
            idUsuario       = ?,
            numEquipo       = ?,
            correo          = ?,
            firma           = ?,
            nombre          = ?,
            numero          = ?
            WHERE idIncidencias = ?";
    $stmtUpdate = $bd->prepare($sqlUpdate);
    $stmtUpdate->execute([
      $estado,
      $tecnicoAsignado,
      $observaciones,
      $incidenciaText,
      $TDesplazamiento,
      $TIntervencion,
      $cp,
      $provincia,
      $localidad,
      $direccionDetalle,
      $idUsuario,
      $numEquipo,
      $correo,
      $firmaFormulario,
      $nombre,
      $numero,
      $idIncidencia
    ]);

    if (isset($_POST['finalizar'])) {
      echo "<script>alert('Incidencia finalizada correctamente.'); window.location.href='../home.php';</script>";
    } else {
      echo "<script>alert('Incidencia modificada con éxito.'); window.location.href='../home.php';</script>";
    }
    exit;
  } catch (PDOException $e) {
    echo "<script>alert('Error al modificar la incidencia: " . $e->getMessage() . "');</script>";
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <title>Modificar Incidencia</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png" />
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    body,
    html {
      height: 100%;
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      padding-top: 70px;
      padding-bottom: 70px;
    }

    header {
      background: #00225a;
      color: #fff;
      padding: 20px 30px;
      font-size: 32px;
      font-weight: bold;
      text-align: center;
      user-select: none;
      flex-shrink: 0;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
    }

    main.container {
      flex-grow: 1;
      background: white;
      margin: 20px auto;
      padding: 30px;
      max-width: 900px;
      width: 90%;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      justify-content: center;
      user-select: none;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      flex: 1 1 300px;
      min-width: 280px;
      gap: 6px;
    }

    .row-horizontal {
      display: flex;
      gap: 15px;
      width: 100%;
      flex-wrap: wrap;
      justify-content: center;
    }

    .row-horizontal .form-group {
      min-width: unset;
      flex: 0 1 45%;
    }

    label {
      font-weight: 600;
      color: #00225a;
      font-size: 1.05rem;
      margin-bottom: 4px;
    }

    input[type="text"],
    input[type="email"],
    input[type="number"],
    select,
    textarea {
      border: 2px solid #2573fa;
      border-radius: 6px;
      padding: 8px 10px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
      font-family: inherit;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="number"]:focus,
    select:focus,
    textarea:focus {
      border-color: #f9ab25;
      outline: none;
    }

    textarea {
      resize: vertical;
      min-height: 80px;
      max-height: 150px;
      font-family: inherit;
      font-size: 1rem;
    }

    .btn-group {
      display: flex;
      gap: 15px;
      width: 100%;
      margin-top: 10px;
      justify-content: center;
    }

    .btn-group>* {
      flex: 1;
      padding: 12px 0;
      font-weight: 700;
      font-size: 1rem;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      text-align: center;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.3s ease;
      user-select: none;
      text-decoration: none;
    }

    input[type="submit"] {
      background-color: #f9ab25;
      color: #000;
    }

    input[type="submit"]:hover {
      background-color: #d38e00;
    }

    .btn-home {
      background-color: #00225a;
      color: white;
    }

    .btn-home:hover {
      background-color: #001a48;
    }

    .firma-group {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      margin-top: 15px;
    }

    .firma-group label {
      font-weight: 600;
      font-size: 1.1rem;
      color: #00225a;
    }

    #firma-canvas {
      border: 2px solid #2573fa;
      border-radius: 6px;
      max-width: 100%;
      touch-action: none;
      background: #fff;
    }

    #limpiar-firma {
      background-color: #d73838;
      border: none;
      color: #fff;
      font-weight: 700;
      padding: 10px 24px;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      user-select: none;
    }

    #limpiar-firma:hover {
      background-color: #a82c2c;
    }

    .nuevo-equipo {
      width: 100%;
      text-align: center;
      margin-bottom: 20px;
    }

    .nuevo-equipo a {
      background: #2573fa;
      color: white;
      padding: 10px 24px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      font-size: 1rem;
      display: inline-block;
      transition: background-color 0.3s ease;
      user-select: none;
    }

    .nuevo-equipo a:hover {
      background: #1a56c2;
    }

    footer {
      background-color: #000;
      color: #fff;
      text-align: center;
      padding: 12px 0;
      font-size: 0.9rem;
      user-select: none;
      flex-shrink: 0;
      margin-top: auto;
    }

    @media (max-width: 700px) {
      main.container {
        max-width: 100%;
        padding: 15px 20px;
      }

      .row-horizontal .form-group {
        flex: 1 1 100%;
      }
    }
  </style>
</head>

<body>
  <header>Mapache Security</header>
  <main class="container">
    <form method="post" id="modificarIncidenciaForm" autocomplete="off">
      <div class="form-group">
        <label>ID Incidencia:</label>
        <input type="text" value="<?= htmlspecialchars($incidenciaData['idIncidencias']) ?>" disabled />
      </div>

      <div class="form-group">
        <label>Fecha:</label>
        <input type="text" value="<?= htmlspecialchars($incidenciaData['fecha']) ?>" disabled />
      </div>

      <div class="form-group">
        <label>Cliente:</label>
        <select name="idUsuario" id="idUsuarioSelect">
          <?php
          $clientes = $bd->query("SELECT idUsuarios, usuario FROM Usuarios WHERE permiso='cliente'");
          foreach ($clientes as $cli) {
            $sel = ($cli['idUsuarios'] == $incidenciaData['idUsuario']) ? 'selected' : '';
            echo "<option value=\"" . $cli['idUsuarios'] . "\" $sel>" . htmlspecialchars($cli['usuario']) . "</option>";
          }
          ?>
        </select>
      </div>

      <div class="form-group">
        <label>Nº Equipo:</label>
        <select name="numEquipo" id="numEquipo" required>
          <?php
          $qe = $bd->prepare("SELECT numEquipo, cp, provincia, localidad, direccion FROM Equipos WHERE idUsuario = ?");
          $qe->execute([$incidenciaData['idUsuario']]);
          while ($eq = $qe->fetch()) {
            $sel = ($eq['numEquipo'] == $incidenciaData['numEquipo']) ? 'selected' : '';
            echo "<option value=\"" . $eq['numEquipo'] . "\" data-cp=\"" . $eq['cp'] . "\" data-provincia=\"" . $eq['provincia'] . "\" data-localidad=\"" . $eq['localidad'] . "\" data-direccion=\"" . $eq['direccion'] . "\" $sel>" . htmlspecialchars($eq['numEquipo']) . "</option>";
          }
          ?>
        </select>
      </div>

      <div class="nuevo-equipo">
        <a href="../equipos/crearEquipos.php" target="_blank">Crear nuevo equipo</a>
      </div>

      <div class="form-group">
        <label>Código Postal:</label>
        <input type="text" id="cp" disabled placeholder="<?= htmlspecialchars($incidenciaData['cp']) ?>" />
      </div>

      <div class="form-group">
        <label>Provincia:</label>
        <input type="text" id="provincia" disabled
          placeholder="<?= htmlspecialchars($incidenciaData['provincia']) ?>" />
      </div>

      <div class="form-group">
        <label>Localidad:</label>
        <input type="text" id="localidad" disabled
          placeholder="<?= htmlspecialchars($incidenciaData['localidad']) ?>" />
      </div>

      <div class="form-group">
        <label>Dirección:</label>
        <input type="text" id="direccionDetalle" disabled
          placeholder="<?= htmlspecialchars($incidenciaData['direccion']) ?>" />
      </div>

      <div class="row-horizontal">
        <div class="form-group" style="flex: 0 1 45%;">
          <label>Número (9 dígitos):</label>
          <input type="number" name="numero" required min="100000000" max="999999999" step="1"
            value="<?= htmlspecialchars($incidenciaData['numero']) ?>" />
        </div>

        <div class="form-group" style="flex: 0 1 55%;">
          <label>Incidencia:</label>
          <textarea name="incidencia" required><?= htmlspecialchars($incidenciaData['incidencia']) ?></textarea>
        </div>
      </div>

      <div class="form-group">
        <label>Correo electrónico:</label>
        <input type="email" name="correo" required value="<?= htmlspecialchars($incidenciaData['correo']) ?>" />
      </div>

      <div class="form-group">
        <label>Nombre:</label>
        <input type="text" name="nombre" required value="<?= htmlspecialchars($incidenciaData['nombre']) ?>" />
      </div>

      <div class="form-group">
        <label>Observaciones:</label>
        <textarea name="observaciones"><?= htmlspecialchars($incidenciaData['observaciones']) ?></textarea>
      </div>

      <div class="form-group">
        <label>Técnico Asignado:</label>
        <select name="tecnico">
          <option value="">Sin asignar</option>
          <?php
          $tecnicos = $bd->query("SELECT usuario FROM Usuarios WHERE permiso='tecnico'");
          foreach ($tecnicos as $tec) {
            $sel = ($tec['usuario'] == $incidenciaData['tecnicoAsignado']) ? 'selected' : '';
            echo "<option value=\"" . htmlspecialchars($tec['usuario']) . "\" $sel>" . htmlspecialchars($tec['usuario']) . "</option>";
          }
          ?>
        </select>
      </div>

      <div class="form-group">
        <label>Estado:</label>
        <select name="estado" id="estadoSelect">
          <option value="0" <?= $incidenciaData['estado'] == 0 ? 'selected' : '' ?>>Abierta</option>
          <option value="1" <?= $incidenciaData['estado'] == 1 ? 'selected' : '' ?>>Cerrada</option>
        </select>
      </div>

      <div class="form-group">
        <label>Tiempo Desplazamiento (min):</label>
        <select name="TDesplazamiento">
          <?php for ($m = 0; $m <= 480; $m += 30):
            $h = floor($m / 60);
            $min = $m % 60;
            $label = sprintf("%02d:%02d", $h, $min); ?>
            <option value="<?= $m ?>" <?= $m == $TDesplazamiento_minutos ? 'selected' : '' ?>><?= $label ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Tiempo Intervención (min):</label>
        <select name="TIntervencion">
          <?php for ($m = 0; $m <= 480; $m += 30):
            $h = floor($m / 60);
            $min = $m % 60;
            $label = sprintf("%02d:%02d", $h, $min); ?>
            <option value="<?= $m ?>" <?= $m == $TIntervencion_minutos ? 'selected' : '' ?>><?= $label ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="firma-group">
        <label>Firma:</label>
        <canvas id="firma-canvas" width="600" height="150"></canvas>
        <button type="button" id="limpiar-firma">Limpiar firma</button>
        <input type="hidden" name="firma" id="firma" />
      </div>

      <div class="btn-group" style="margin-top: 20px;">
        <input type="submit" name="modificar" value="Guardar Incidencia" />
        <button type="button" id="finalizarBtn">Finalizar Incidencia</button>
        <a href="../home.php" class="btn-home">Volver al home</a>
      </div>
      <div style="text-align:center; margin-top: 15px;">
        <a href="pdf_incidencia.php?id=<?= urlencode($incidenciaData['idIncidencias']) ?>" target="_blank"
          style="display: inline-block; padding: 12px 24px; background-color: #d73838; color: white; text-decoration: none; font-weight: bold; border-radius: 6px;">
          <i class="bi bi-file-earmark-pdf-fill"></i> Generar PDF
        </a>
      </div>
    </form>
  </main>

  <footer>
    &copy; <?= date('Y') ?> Mapache Security.
  </footer>

  <script>
    // Actualiza datos del equipo
    function actualizarEquipo(sel) {
      const o = sel.options[sel.selectedIndex];
      document.getElementById('cp').value = o.getAttribute('data-cp') || '';
      document.getElementById('provincia').value = o.getAttribute('data-provincia') || '';
      document.getElementById('localidad').value = o.getAttribute('data-localidad') || '';
      document.getElementById('direccionDetalle').value = o.getAttribute('data-direccion') || '';
    }
    document.getElementById('numEquipo').addEventListener('change', function () {
      actualizarEquipo(this);
    });
    window.addEventListener('load', function () {
      actualizarEquipo(document.getElementById('numEquipo'));
      <?php if (!empty($incidenciaData['firma'])): ?>
        const img = new Image();
        img.onload = () => ctx.drawImage(img, 0, 0);
        img.src = "<?= $incidenciaData['firma'] ?>";
      <?php endif; ?>
    });

    // Variables firma
    const canvas = document.getElementById('firma-canvas');
    const ctx = canvas.getContext('2d');
    let drawing = false,
      signed = false;

    function start(ev) {
      drawing = true;
      ctx.beginPath();
      move(ev);
    }

    function move(ev) {
      if (!drawing) return;
      const r = canvas.getBoundingClientRect();
      const x = (ev.clientX || ev.touches[0].clientX) - r.left;
      const y = (ev.clientY || ev.touches[0].clientY) - r.top;
      ctx.lineTo(x, y);
      ctx.stroke();
      signed = true;
    }

    function end() {
      drawing = false;
    }
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', end);
    canvas.addEventListener('mouseout', end);
    canvas.addEventListener('touchstart', e => {
      e.preventDefault();
      start(e);
    });
    canvas.addEventListener('touchmove', e => {
      e.preventDefault();
      move(e);
    });
    canvas.addEventListener('touchend', e => {
      e.preventDefault();
      end();
    });

    // Botón limpiar firma
    const limpiarFirmaBtn = document.getElementById('limpiar-firma');
    limpiarFirmaBtn.addEventListener('click', () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      signed = false;
      document.getElementById('firma').value = '';
      actualizarFirmaState();
    });

    // Guardar firma en hidden al enviar formulario
    document.getElementById('modificarIncidenciaForm').addEventListener('submit', () => {
      if (signed) document.getElementById('firma').value = canvas.toDataURL();
      else document.getElementById('firma').value = '';
    });

    // Control estado y firma para habilitar/deshabilitar elementos
    const estadoSelect = document.getElementById('estadoSelect');
    const finalizarBtn = document.getElementById('finalizarBtn');

    function actualizarFirmaState() {
      const estado = estadoSelect.value;
      if (estado === '0') { // Abierta
        canvas.style.pointerEvents = 'none'; // No se puede firmar
        limpiarFirmaBtn.disabled = false; // Sí se puede limpiar
        limpiarFirmaBtn.style.opacity = '1';
        finalizarBtn.disabled = true; // No se puede finalizar
        finalizarBtn.style.opacity = '0.5';
      } else { // Cerrada
        canvas.style.pointerEvents = 'auto'; // Se puede firmar
        limpiarFirmaBtn.disabled = true; // No se puede limpiar
        limpiarFirmaBtn.style.opacity = '0.5';

        // Comprobar si hay firma guardada o firmada para activar el botón finalizar
        const firmaHidden = document.getElementById('firma').value;
        const hayFirma = firmaHidden !== '' || signed;
        finalizarBtn.disabled = !hayFirma;
        finalizarBtn.style.opacity = hayFirma ? '1' : '0.5';
      }
    }

    estadoSelect.addEventListener('change', actualizarFirmaState);
    window.addEventListener('load', actualizarFirmaState);

    // Botón Finalizar Incidencia
    finalizarBtn.addEventListener('click', () => {
      const estado = estadoSelect.value;
      const firmaHidden = document.getElementById('firma').value;

      if (estado !== '1' || firmaHidden === '') {
        alert('Para finalizar la incidencia debe estar cerrada y firmada.');
        return;
      }
      // Añadir input hidden para finalizar y enviar
      const form = document.getElementById('modificarIncidenciaForm');
      let inputFinalizar = document.querySelector('input[name="finalizar"]');
      if (!inputFinalizar) {
        inputFinalizar = document.createElement('input');
        inputFinalizar.type = 'hidden';
        inputFinalizar.name = 'finalizar';
        inputFinalizar.value = '1';
        form.appendChild(inputFinalizar);
      }
      form.submit();
    });

    // Detectar dibujo para activar botón finalizar
    canvas.addEventListener('mouseup', () => {
      if (signed) {
        document.getElementById('firma').value = canvas.toDataURL();
        actualizarFirmaState();
      }
    });
  </script>
</body>

</html>