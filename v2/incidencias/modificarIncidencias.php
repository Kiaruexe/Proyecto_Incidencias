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
    <meta charset="UTF-8">
    <title>Seleccionar Incidencia a Modificar</title>
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
    <style>
      body {
        font-family: Arial, sans-serif;
        background: #f0f2f5;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
      }

      .container {
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        text-align: center;
      }

      select,
      input[type="submit"] {
        width: 100%;
        padding: 10px;
        margin-top: 10px;
        border-radius: 5px;
        border: 1px solid #ccc;
      }

      a {
        display: block;
        margin-top: 20px;
        color: #3498db;
        text-decoration: none;
      }
    </style>
  </head>

  <body>
    <div class="container">
      <h1>Seleccionar Incidencia a Modificar</h1>
      <form method="get" action="">
        <label for="id">Incidencia:</label>
        <select name="id" id="id" required>
          <option value="">-- Seleccione una incidencia --</option>
          <?php foreach ($incidencias as $inc): ?>
            <option value="<?= htmlspecialchars($inc['idIncidencias']) ?>">
              <?= "ID: " . htmlspecialchars($inc['idIncidencias']) . " - " . htmlspecialchars($inc['incidencia']) . " (Cliente: " . htmlspecialchars($inc['usuario']) . ")" ?>
            </option>
          <?php endforeach; ?>
        </select>
        <input type="submit" value="Modificar Incidencia">
      </form>
      <a href="../home.php">Volver al home</a>
    </div>
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

if (isset($_POST['modificar'])) {
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
  $firma = obtenerValor('firma', $incidenciaData['firma'] ?? '');
  $nombre = obtenerValor('nombre', $incidenciaData['nombre'] ?? '');
  $numero = obtenerValor('numero', $incidenciaData['numero'] ?? '');

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
      $firma,
      $nombre,
      $numero,
      $idIncidencia
    ]);
    echo "<script>alert('Incidencia modificada con éxito.'); window.location.href='../home.php';</script>";
    exit;
  } catch (PDOException $e) {
    echo "<script>alert('Error al modificar la incidencia: " . $e->getMessage() . "');</script>";
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Modificar Incidencia</title>
  <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .container {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      max-width: 700px;
      width: 90%;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    h1 {
      text-align: center;
      margin-bottom: 20px;
    }

    form {
      text-align: left;
    }

    label {
      font-weight: bold;
      display: block;
      margin-bottom: 5px;
    }

    input[type="text"],
    input[type="email"],
    input[type="number"],
    select,
    textarea {
      width: 100%;
      padding: 8px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    input[type="submit"] {
      width: 100%;
      padding: 10px;
      background: #3498db;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    input[type="submit"]:hover {
      background: #2980b9;
    }

    .nuevo-equipo {
      text-align: right;
      margin-bottom: 15px;
    }

    .nuevo-equipo a {
      background: #27ae60;
      color: white;
      padding: 8px 12px;
      border-radius: 4px;
      text-decoration: none;
    }

    .nuevo-equipo a:hover {
      background: #219150;
    }
  </style>
</head>

<body>
  <div class="container">
    <h1>Modificar Incidencia</h1>
    <form method="post" id="modificarIncidenciaForm">
      <label>ID Incidencia:</label>
      <input type="text" value="<?= htmlspecialchars($incidenciaData['idIncidencias']) ?>" disabled>
      <label>Fecha:</label>
      <input type="text" value="<?= htmlspecialchars($incidenciaData['fecha']) ?>" disabled>

      <label>Cliente:</label>
      <select name="idUsuario">
        <?php
        $clientes = $bd->query("SELECT idUsuarios, usuario FROM Usuarios WHERE permiso='cliente'");
        foreach ($clientes as $cli) {
          $sel = ($cli['idUsuarios'] == $incidenciaData['idUsuario']) ? 'selected' : '';
          echo "<option value=\"" . $cli['idUsuarios'] . "\" $sel>" . htmlspecialchars($cli['usuario']) . "</option>";
        }
        ?>
      </select>

      <label>Nº Equipo:</label>
      <select name="numEquipo" id="numEquipo" required>
        <?php
        $qe = $bd->prepare("SELECT numEquipo, cp, provincia, localidad, direccion FROM Equipos WHERE idUsuario = ?");
        $qe->execute([$incidenciaData['idUsuario']]);
        while ($eq = $qe->fetch()) {
          $sel = ($eq['numEquipo'] == $incidenciaData['numEquipo']) ? 'selected' : '';
          echo "<option value=\"" . $eq['numEquipo'] . "\" data-cp=\"" . $eq['cp'] . "\" data-provincia=\"" . $eq['provincia'] .
            "\" data-localidad=\"" . $eq['localidad'] . "\" data-direccion=\"" . $eq['direccion'] . "\" $sel>"
            . htmlspecialchars($eq['numEquipo']) . "</option>";
        }
        ?>
      </select>
      <div class="nuevo-equipo">
        <a href="../equipos/crearEquipos.php" target="_blank">Crear nuevo equipo</a>
      </div>

      <label>Código Postal:</label>
      <input type="text" id="cp" disabled placeholder="<?= htmlspecialchars($incidenciaData['cp']) ?>">
      <label>Provincia:</label>
      <input type="text" id="provincia" disabled placeholder="<?= htmlspecialchars($incidenciaData['provincia']) ?>">
      <label>Localidad:</label>
      <input type="text" id="localidad" disabled placeholder="<?= htmlspecialchars($incidenciaData['localidad']) ?>">
      <label>Dirección:</label>
      <input type="text" id="direccionDetalle" disabled
        placeholder="<?= htmlspecialchars($incidenciaData['direccion']) ?>">

      <label>Correo electrónico:</label>
      <input type="email" name="correo" required value="<?= htmlspecialchars($incidenciaData['correo']) ?>">
      <label>Nombre:</label>
      <input type="text" name="nombre" required value="<?= htmlspecialchars($incidenciaData['nombre']) ?>">
      <label>Número (9 dígitos):</label>
      <input type="number" name="numero" required min="100000000" max="999999999" step="1"
        value="<?= htmlspecialchars($incidenciaData['numero']) ?>">

      <label>Incidencia:</label>
      <textarea name="incidencia" required><?= htmlspecialchars($incidenciaData['incidencia']) ?></textarea>
      <label>Observaciones:</label>
      <textarea name="observaciones"><?= htmlspecialchars($incidenciaData['observaciones']) ?></textarea>

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

      <label>Estado:</label>
      <select name="estado">
        <option value="0" <?= $incidenciaData['estado'] == 0 ? 'selected' : '' ?>>Abierta</option>
        <option value="1" <?= $incidenciaData['estado'] == 1 ? 'selected' : '' ?>>Cerrada</option>
      </select>

      <label>Tiempo Desplazamiento (min):</label>
      <select name="TDesplazamiento">
        <?php for ($m = 0; $m <= 480; $m += 30):
          $h = floor($m / 60);
          $min = $m % 60;
          $label = sprintf("%02d:%02d", $h, $min); ?>
          <option value="<?= $m ?>" <?= $m == $TDesplazamiento_minutos ? 'selected' : '' ?>><?= $label ?></option>
        <?php endfor; ?>
      </select>
      <label>Tiempo Intervención (min):</label>
      <select name="TIntervencion">
        <?php for ($m = 0; $m <= 480; $m += 30):
          $h = floor($m / 60);
          $min = $m % 60;
          $label = sprintf("%02d:%02d", $h, $min); ?>
          <option value="<?= $m ?>" <?= $m == $TIntervencion_minutos ? 'selected' : '' ?>><?= $label ?></option>
        <?php endfor; ?>
      </select>

      <label>Firma:</label>
      <canvas id="firma-canvas" width="600" height="150" style="border:1px solid #000;"></canvas>
      <button type="button" id="limpiar-firma" style="margin-bottom:15px;">Limpiar firma</button>
      <input type="hidden" name="firma" id="firma">

      <input type="submit" name="modificar" value="Finalizar Incidencia">
    </form>
    <a href="../home.php">Volver al home</a>
  </div>

  <script>
    function actualizarEquipo(sel) {
      const o = sel.options[sel.selectedIndex];
      document.getElementById('cp').value = o.getAttribute('data-cp') || '';
      document.getElementById('provincia').value = o.getAttribute('data-provincia') || '';
      document.getElementById('localidad').value = o.getAttribute('data-localidad') || '';
      document.getElementById('direccionDetalle').value = o.getAttribute('data-direccion') || '';
    }
    document.getElementById('numEquipo').addEventListener('change', function () { actualizarEquipo(this); });
    window.addEventListener('load', function () {
      actualizarEquipo(document.getElementById('numEquipo'));
      <?php if (!empty($incidenciaData['firma'])): ?>
        const img = new Image();
        img.onload = () => ctx.drawImage(img, 0, 0);
        img.src = "<?= $incidenciaData['firma'] ?>";
      <?php endif; ?>
    });

    const canvas = document.getElementById('firma-canvas');
    const ctx = canvas.getContext('2d');
    let drawing = false, signed = false;
    function start(ev) { drawing = true; ctx.beginPath(); move(ev); }
    function move(ev) { if (!drawing) return; const r = canvas.getBoundingClientRect(); const x = (ev.clientX || ev.touches[0].clientX) - r.left; const y = (ev.clientY || ev.touches[0].clientY) - r.top; ctx.lineTo(x, y); ctx.stroke(); signed = true; }
    function end() { drawing = false; }
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', end);
    canvas.addEventListener('mouseout', end);
    canvas.addEventListener('touchstart', e => { e.preventDefault(); start(e); });
    canvas.addEventListener('touchmove', e => { e.preventDefault(); move(e); });
    canvas.addEventListener('touchend', e => { e.preventDefault(); end(); });

    document.getElementById('limpiar-firma').addEventListener('click', () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      signed = false;
    });

    document.getElementById('modificarIncidenciaForm').addEventListener('submit', () => {
      if (signed) document.getElementById('firma').value = canvas.toDataURL();
      else document.getElementById('firma').value = '';
    });
  </script>
</body>

</html>