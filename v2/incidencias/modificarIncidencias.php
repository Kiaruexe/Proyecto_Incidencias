<?php
session_start();

if (!isset($_SESSION["idUsuario"])) {
    header("Location: ../login.php");
    exit;
}

try {
    // Conexión a la base de datos y obtención de datos del usuario autenticado
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
        'Mapapli',
        '9R%d5cf62'
    );
    $query = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
    $query->execute([$_SESSION['idUsuario']]);
    $userRow = $query->fetch();
    $permiso = strtolower($userRow['permiso']);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Si no se recibe el parámetro 'id' en la URL, se muestra el formulario para seleccionar la incidencia a modificar.
if (!isset($_GET['id'])) {
    if ($permiso == 'cliente') {
        $sql = "SELECT Incidencias.idIncidencias, Incidencias.incidencia, Usuarios.usuario 
                FROM Incidencias 
                JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios 
                WHERE Incidencias.idUsuario = ?";
        $stmt = $bd->prepare($sql);
        $stmt->execute([$_SESSION['idUsuario']]);
    } elseif ($permiso == 'tecnico') {
        $sql = "SELECT Incidencias.idIncidencias, Incidencias.incidencia, Usuarios.usuario 
                FROM Incidencias 
                JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios 
                WHERE estado = 0 AND tecnicoAsignado = ?";
        $stmt = $bd->prepare($sql);
        $stmt->execute([$userRow['usuario']]);
    } else { // Para admin, recepcion, jefeTecnico
        $sql = "SELECT Incidencias.idIncidencias, Incidencias.incidencia, Usuarios.usuario 
                FROM Incidencias 
                JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios";
        $stmt = $bd->prepare($sql);
        $stmt->execute();
    }
    $incidencias = $stmt->fetchAll();
    if (!$incidencias) {
        echo "<script>alert('No hay incidencias disponibles para modificar.');</script>";
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Seleccionar Incidencia a Modificar</title>
        <link rel="stylesheet" href="../css/style.css">
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f0f2f5;
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                max-width: 600px;
                width: 90%;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                text-align: center;
            }
            h1 {
                margin-bottom: 20px;
            }
            form select, form input[type="submit"] {
                width: 100%;
                padding: 10px;
                margin-top: 10px;
                border-radius: 5px;
                border: 1px solid #ccc;
            }
            a {
                display: block;
                margin-top: 20px;
                text-decoration: none;
                color: #3498db;
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
                    <option value="<?= htmlspecialchars($inc['idIncidencias']); ?>">
                        <?= "ID: " . htmlspecialchars($inc['idIncidencias']) . " - " . htmlspecialchars($inc['incidencia']) . " (Cliente: " . htmlspecialchars($inc['usuario']) . ")" ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br>
            <input type="submit" value="Modificar Incidencia">
        </form>
        <a href="../home.php">Volver al home</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Si se ha seleccionado una incidencia, se obtiene su información.
$idIncidencia = $_GET['id'];
try {
    $sql = "SELECT * FROM Incidencias WHERE idIncidencias = ?";
    $stmt = $bd->prepare($sql);
    $stmt->execute([$idIncidencia]);
    $incidenciaData = $stmt->fetch();
    if (!$incidenciaData) {
        echo "<script>alert('Incidencia no encontrada.');</script>";
        exit;
    }
} catch (PDOException $e) {
    echo "<script>alert('Error al obtener la incidencia: " . $e->getMessage() . "');</script>";
    exit;
}

// Función para obtener el valor del formulario o mantener el valor actual
function obtenerValor($campo, $actual) {
    return (isset($_POST[$campo]) && trim($_POST[$campo]) !== '') ? trim($_POST[$campo]) : $actual;
}

// Procesa el formulario de modificación si se envía
if (isset($_POST['modificar'])) {
    $estado = isset($_POST['estado']) ? intval($_POST['estado']) : $incidenciaData['estado'];
    $tecnicoAsignado = obtenerValor('tecnico', $incidenciaData['tecnicoAsignado']);
    $observaciones = obtenerValor('observaciones', $incidenciaData['observaciones']);
    $incidenciaText = obtenerValor('incidencia', $incidenciaData['incidencia']);
    $TDesplazamiento = obtenerValor('TDesplazamiento', $incidenciaData['TDesplazamiento']);
    $TIntervencion = obtenerValor('TIntervencion', $incidenciaData['TIntervencion']);
    $cp = obtenerValor('cp', $incidenciaData['cp']);
    $provincia = obtenerValor('provincia', $incidenciaData['provincia']);
    $localidad = obtenerValor('localidad', $incidenciaData['localidad']);
    $direccionDetalle = obtenerValor('direccion', $incidenciaData['direccion']);
    $idUsuario = obtenerValor('idUsuario', $incidenciaData['idUsuario']);
    $numEquipo = obtenerValor('numEquipo', $incidenciaData['numEquipo']);
    $correo = obtenerValor('correo', $incidenciaData['correo']);
    $firma = obtenerValor('firma', $incidenciaData['firma'] ?? '');

    try {
        $sqlUpdate = "UPDATE Incidencias SET 
            estado = ?,
            tecnicoAsignado = ?,
            observaciones = ?,
            incidencia = ?,
            TDesplazamiento = ?,
            TIntervencion = ?,
            cp = ?,
            provincia = ?,
            localidad = ?,
            direccion = ?,
            idUsuario = ?,
            numEquipo = ?,
            correo = ?,
            firma = ?
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
            $idIncidencia
        ]);
        echo "<script>alert('Incidencia modificada con éxito.');</script>";
        // Actualiza los datos de la incidencia para reflejarlos en el formulario.
        $stmt = $bd->prepare("SELECT * FROM Incidencias WHERE idIncidencias = ?");
        $stmt->execute([$idIncidencia]);
        $incidenciaData = $stmt->fetch();
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
          padding: 0;
      }
      .container {
          background: white;
          padding: 30px;
          border-radius: 10px;
          max-width: 700px;
          width: 100%;
          box-shadow: 0 4px 12px rgba(0,0,0,0.1);
          text-align: center;
      }
      form {
          text-align: left;
      }
      form label {
          font-weight: bold;
      }
      form input[type="text"],
      form input[type="number"],
      form input[type="email"],
      form select,
      form textarea {
          width: 100%;
          padding: 8px;
          margin-top: 5px;
          margin-bottom: 15px;
          border: 1px solid #ccc;
          border-radius: 5px;
      }
      form input[type="submit"] {
          background-color: #3498db;
          color: white;
          padding: 10px 20px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          width: 100%;
      }
      form input[type="submit"]:hover {
          background-color: #2980b9;
      }
      .nuevo-equipo {
          text-align: right;
          margin-top: 10px;
      }
      .nuevo-equipo a {
          background-color: #27ae60;
          color: white;
          padding: 8px 12px;
          border-radius: 4px;
          text-decoration: none;
          font-size: 14px;
      }
      .nuevo-equipo a:hover {
          background-color: #219150;
      }
  </style>
</head>
<body>
  <div class="container">
    <h1>Modificar Incidencia</h1>
    <form method="post" id="modificarIncidenciaForm">
      <!-- Muestra la ID y la fecha de la incidencia (solo lectura) -->
      <p>
        <label>ID Incidencia (no modificable):</label><br>
        <input type="text" value="<?= htmlspecialchars($incidenciaData['idIncidencias']); ?>" disabled>
      </p>
      <p>
        <label>Fecha (no modificable):</label><br>
        <input type="text" value="<?= htmlspecialchars($incidenciaData['fecha']); ?>" disabled>
      </p>
      <!-- Campo Estado -->
      <p>
        <label>Estado:</label><br>
        <select name="estado">
          <option value="0" <?= $incidenciaData['estado'] == 0 ? 'selected' : ''; ?>>Abierta (0)</option>
          <option value="1" <?= $incidenciaData['estado'] == 1 ? 'selected' : ''; ?>>Cerrada (1)</option>
        </select>
      </p>
      <!-- Técnico Asignado -->
      <p>
        <label>Técnico Asignado:</label><br>
        <select name="tecnico">
          <option value="">Sin asignar</option>
          <?php
          $tecnicos = $bd->query("SELECT usuario FROM Usuarios WHERE permiso = 'tecnico'");
          foreach ($tecnicos as $tec) {
              $sel = ($tec['usuario'] == $incidenciaData['tecnicoAsignado']) ? 'selected' : '';
              echo "<option value=\"" . htmlspecialchars($tec['usuario']) . "\" $sel>" . htmlspecialchars($tec['usuario']) . "</option>";
          }
          ?>
        </select>
      </p>
      <!-- Observaciones -->
      <p>
        <label>Observaciones:</label><br>
        <input type="text" name="observaciones" placeholder="<?= htmlspecialchars($incidenciaData['observaciones']); ?>">
      </p>
      <!-- Incidencia -->
      <p>
        <label>Incidencia:</label><br>
        <textarea name="incidencia" required><?= htmlspecialchars($incidenciaData['incidencia']); ?></textarea>
      </p>
      <!-- Tiempos -->
      <p>
        <label>Tiempo Desplazamiento (de 30 en 30):</label><br>
        <input type="number" name="TDesplazamiento" step="30" placeholder="<?= htmlspecialchars($incidenciaData['TDesplazamiento']); ?>">
      </p>
      <p>
        <label>Tiempo Intervención (de 30 en 30):</label><br>
        <input type="number" name="TIntervencion" step="30" placeholder="<?= htmlspecialchars($incidenciaData['TIntervencion']); ?>">
      </p>
      <!-- Campos de Dirección (los inputs están deshabilitados ya que se actualizan según el equipo seleccionado) -->
      <p>
        <label>Código Postal:</label><br>
        <input type="text" name="cp" id="cp" placeholder="<?= htmlspecialchars($incidenciaData['cp']); ?>" disabled>
      </p>
      <p>
        <label>Provincia:</label><br>
        <input type="text" name="provincia" id="provincia" placeholder="<?= htmlspecialchars($incidenciaData['provincia']); ?>" disabled>
      </p>
      <p>
        <label>Localidad:</label><br>
        <input type="text" name="localidad" id="localidad" placeholder="<?= htmlspecialchars($incidenciaData['localidad']); ?>" disabled>
      </p>
      <p>
        <label>Dirección:</label><br>
        <input type="text" name="direccion" id="direccionDetalle" placeholder="<?= htmlspecialchars($incidenciaData['direccion']); ?>" disabled>
      </p>
      <!-- Correo electrónico, con valor por defecto el del cliente -->
      <p>
        <label>Correo electrónico:</label><br>
        <input type="email" name="correo" value="<?= htmlspecialchars($incidenciaData['correo']); ?>" placeholder="Introduzca el correo del cliente" required>
      </p>
      <!-- Selección del Cliente -->
      <p>
        <label>Cliente:</label><br>
        <select name="idUsuario">
          <?php
          $clientes = $bd->query("SELECT idUsuarios, usuario FROM Usuarios WHERE permiso = 'cliente'");
          foreach ($clientes as $cli) {
              $sel = ($cli['idUsuarios'] == $incidenciaData['idUsuario']) ? 'selected' : '';
              echo "<option value=\"" . htmlspecialchars($cli['idUsuarios']) . "\" $sel>" . htmlspecialchars($cli['usuario']) . "</option>";
          }
          ?>
        </select>
      </p>
      <!-- Selección del Nº Equipo (como select con atributos para actualizar la dirección) -->
      <p>
        <label>Nº Equipo:</label><br>
        <select name="numEquipo" id="numEquipo" required>
          <?php
          $queryEquipos = $bd->prepare("SELECT numEquipo, cp, provincia, localidad, direccion FROM Equipos WHERE idUsuario = ?");
          $queryEquipos->execute([$incidenciaData['idUsuario']]);
          while ($eq = $queryEquipos->fetch()) {
              $sel = ($eq['numEquipo'] == $incidenciaData['numEquipo']) ? 'selected' : '';
              echo "<option value=\"" . htmlspecialchars($eq['numEquipo']) . "\" 
                        data-cp=\"" . htmlspecialchars($eq['cp']) . "\" 
                        data-provincia=\"" . htmlspecialchars($eq['provincia']) . "\" 
                        data-localidad=\"" . htmlspecialchars($eq['localidad']) . "\" 
                        data-direccion=\"" . htmlspecialchars($eq['direccion']) . "\" $sel>"
                   . htmlspecialchars($eq['numEquipo']) . " (" . htmlspecialchars($eq['cp']) . ")"
                   . "</option>";
          }
          ?>
        </select>
      </p>
      <!-- Botón para crear nuevo equipo -->
      <p style="text-align:right;">
        <a href="../equipos/crearEquipos.php" target="_blank" style="background-color:#27ae60; color:white; padding:8px 12px; border-radius:4px; text-decoration:none; font-size:14px;">
          Crear nuevo equipo
        </a>
      </p>
      <!-- Sección de Firma -->
      <p>
        <label>Firma:</label><br>
        <canvas id="firma-canvas" width="600" height="150" style="border:1px solid #000;"></canvas>
      </p>
      <p style="text-align:right;">
        <button type="button" id="limpiar-firma" style="background-color:#f44336; color:white; padding:5px 10px; border:none; border-radius:4px; cursor:pointer;">Limpiar firma</button>
      </p>
      <!-- Input oculto para enviar la firma (se llenará con el DataURL) -->
      <input type="hidden" name="firma" id="firma">
      <br>
      <input type="submit" name="modificar" value="Modificar Incidencia">
    </form>
    <a href="../home.php">Volver al home</a>
  </div>

  <!-- Campos ocultos para enviar datos de dirección (actualizados por JavaScript) -->
  <input type="hidden" name="cp" id="cp">
  <input type="hidden" name="provincia" id="provincia">
  <input type="hidden" name="localidad" id="localidad">
  <input type="hidden" name="direccionDetalle" id="direccionDetalle">

  <script>
    // Función para actualizar los campos de dirección al cambiar el Nº Equipo
    function actualizarEquipo(selectElement) {
      var selectedOption = selectElement.options[selectElement.selectedIndex];
      document.getElementById('direccionEquipo').value = selectedOption.getAttribute('data-direccion') || '';
      document.getElementById('cp').value = selectedOption.getAttribute('data-cp') || '';
      document.getElementById('provincia').value = selectedOption.getAttribute('data-provincia') || '';
      document.getElementById('localidad').value = selectedOption.getAttribute('data-localidad') || '';
      document.getElementById('direccionDetalle').value = selectedOption.getAttribute('data-direccion') || '';
    }

    // Listener para actualizar la dirección al cambiar el equipo
    document.getElementById('numEquipo')?.addEventListener('change', function () {
      actualizarEquipo(this);
    });

    // Actualiza la dirección al cargar la página si ya hay un equipo seleccionado.
    window.addEventListener('load', function() {
      var equipoSelect = document.getElementById('numEquipo');
      if (equipoSelect && equipoSelect.selectedIndex > 0) {
        actualizarEquipo(equipoSelect);
      }
    });

    // --- Código para gestionar la Firma ---
    const canvas = document.getElementById('firma-canvas');
    const ctx = canvas.getContext('2d');
    let dibujando = false;
    let firmado = false; // Variable para verificar si se ha añadido firma

    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#000';

    // Eventos para mouse
    canvas.addEventListener('mousedown', function(e) {
        dibujando = true;
        ctx.beginPath();
        const rect = canvas.getBoundingClientRect();
        ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
    });

    canvas.addEventListener('mousemove', function(e) {
        if (!dibujando) return;
        const rect = canvas.getBoundingClientRect();
        ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
        ctx.stroke();
        firmado = true;
    });

    canvas.addEventListener('mouseup', function() {
        dibujando = false;
    });

    canvas.addEventListener('mouseout', function() {
        dibujando = false;
    });

    // Eventos para dispositivos táctiles
    canvas.addEventListener('touchstart', function(e) {
        e.preventDefault();
        dibujando = true;
        ctx.beginPath();
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
    });

    canvas.addEventListener('touchmove', function(e) {
        e.preventDefault();
        if (!dibujando) return;
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
        ctx.stroke();
        firmado = true;
    });

    canvas.addEventListener('touchend', function(e) {
        e.preventDefault();
        dibujando = false;
    });

    // Botón para limpiar la firma
    document.getElementById('limpiar-firma').addEventListener('click', function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        firmado = false;
    });

    // Antes de enviar el formulario, actualizar el input oculto "firma"
    document.getElementById('modificarIncidenciaForm')?.addEventListener('submit', function(e) {
        if (firmado) {
            document.getElementById('firma').value = canvas.toDataURL("image/png");
        } else {
            document.getElementById('firma').value = "";
        }
    });
  </script>
</body>
</html>
