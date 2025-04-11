<?php
session_start();
// Verifica que el usuario está autenticado; si no, redirige a login.
if (!isset($_SESSION["idUsuario"])) {
  header("Location: ../login.php"); // Redirige al login si no hay sesión.
  exit;
}

try {
  // Conexión a la base de datos.
  $bd = new PDO(
    'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
    'Mapapli',
    '9R%d5cf62'
  );
  // Se obtiene la información del usuario autenticado mediante su ID.
  $query = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
  $query->execute([$_SESSION['idUsuario']]);
  $userRow = $query->fetch();
  // Se normaliza el permiso a minúsculas.
  $permiso = strtolower($userRow['permiso']);
} catch (PDOException $e) {
  echo "Error: " . $e->getMessage(); // Muestra error de conexión.
  exit;
}

/* 
  Para usuarios con permiso 'recepcion', 'admin' o 'jefetecnico' se requiere seleccionar un cliente
  antes de crear la incidencia. Se conserva el contenido previamente escrito en el textarea "incidencia"
  mediante el parámetro GET 'incidenciaPrev'.
*/
$incidenciaPrev = $_POST['incidencia'] ?? '';
if (($permiso == 'recepcion' || $permiso == 'admin' || $permiso == 'jefetecnico') && isset($_POST['elegirCliente'])) {
  $clienteElegido = $_POST['cliente'] ?? '';
  if (!empty($clienteElegido)) {
    header("Location: " . $_SERVER['PHP_SELF'] . "?clienteElegido=" . urlencode($clienteElegido) . "&incidenciaPrev=" . urlencode($incidenciaPrev));
    exit;
  }
}

// Para usuarios con permiso 'cliente', se verifica que tengan equipos asignados.
$tieneEquipos = false;
if ($permiso == 'cliente') {
  try {
    $qEquipos = $bd->prepare("SELECT COUNT(*) FROM Equipos WHERE idUsuario = ?");
    $qEquipos->execute([$_SESSION['idUsuario']]);
    $numEquipos = $qEquipos->fetchColumn();
    $tieneEquipos = $numEquipos > 0;
  } catch (PDOException $e) {
    echo "<p style='color:red;'>Error verificando equipos.</p>";
  }
}

// Procesa el formulario de creación de incidencia si se envía y, en el caso de clientes, tienen equipos.
if (isset($_POST["crear"]) && ($permiso != 'cliente' || $tieneEquipos)) {
  // Función para limpiar campos, retornando null si están vacíos.
  function limpiarCampo($valor) {
    return !empty($valor) ? $valor : null;
  }

  // Se obtienen los datos del formulario.
  $incidencia = $_POST['incidencia'] ?? '';
  // Se obtiene el correo ingresado.
  $correo = limpiarCampo($_POST['correo'] ?? '');
  $fecha = date('Y-m-d');  // Se obtiene la fecha actual (día/mes/año).
  $estado = 0;             // Se establece el estado como false (0).
  $observaciones = null;
  $TDesplazamiento = null;
  $TIntervencion = null;
  $tecnicoAsignado = "sin asignar"; // Valor por defecto para técnico asignado.
  // Se toman los datos de dirección desde los campos ocultos (rellenados automáticamente).
  $cp = $_POST['cp'] ?? null;
  $provincia = $_POST['provincia'] ?? null;
  $localidad = $_POST['localidad'] ?? null;
  $direccionDetalle = $_POST['direccionDetalle'] ?? null;

  $idUsuario = null;
  $numEquipo = null;
  $tipoFinanciacion = null; // Se obtendrá del campo tipoMantenimiento del equipo seleccionado.

  if ($permiso == 'cliente') {
    // Para clientes, se utiliza su propio id y se carga el equipo seleccionado.
    $numEquipo = limpiarCampo($_POST['equipo']);
    $idUsuario = $_SESSION['idUsuario'];
    if ($numEquipo) {
      // Consulta para obtener datos del equipo seleccionado del cliente.
      $qEquipo = $bd->prepare("SELECT tipoMantenimiento, cp, provincia, localidad, direccion FROM Equipos WHERE numEquipo = ? AND idUsuario = ?");
      $qEquipo->execute([$numEquipo, $_SESSION['idUsuario']]);
      $eqData = $qEquipo->fetch();
      $tipoFinanciacion = $eqData['tipoMantenimiento'] ?? null;
      // Se actualizan los datos de dirección según los datos del equipo seleccionado.
      $cp = $eqData['cp'] ?? $cp;
      $provincia = $eqData['provincia'] ?? $provincia;
      $localidad = $eqData['localidad'] ?? $localidad;
      $direccionDetalle = $eqData['direccion'] ?? $direccionDetalle;
    }
  } elseif ($permiso == 'recepcion' || $permiso == 'admin' || $permiso == 'jefetecnico') {
    // Para estos roles, se utiliza el id del cliente seleccionado.
    $idUsuario = limpiarCampo($_POST['cliente'] ?? '');
    $numEquipo = limpiarCampo($_POST['equipo'] ?? '');
    if ($numEquipo) {
      // Consulta para obtener datos del equipo seleccionado del cliente elegido.
      $qEquipo = $bd->prepare("SELECT tipoMantenimiento, cp, provincia, localidad, direccion FROM Equipos WHERE numEquipo = ? AND idUsuario = ?");
      $qEquipo->execute([$numEquipo, $idUsuario]);
      $eqData = $qEquipo->fetch();
      $tipoFinanciacion = $eqData['tipoMantenimiento'] ?? null;
      $cp = $eqData['cp'] ?? $cp;
      $provincia = $eqData['provincia'] ?? $provincia;
      $localidad = $eqData['localidad'] ?? $localidad;
      $direccionDetalle = $eqData['direccion'] ?? $direccionDetalle;
    }
    // Para admin y jefeTecnico se permite asignar un técnico.
    if ($permiso == 'admin' || $permiso == 'jefetecnico') {
      $tecnicoAsignado = limpiarCampo($_POST['tecnico'] ?? '') ?: "sin asignar";
    }
  }

  // Inserta la incidencia en la tabla Incidencias.
  try {
    $sql = "INSERT INTO Incidencias (
            fecha, estado, tecnicoAsignado, observaciones, TDesplazamiento, TIntervencion,
            tipoFinanciacion, idUsuario, numEquipo, incidencia, cp, localidad, provincia, direccion, correo
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $bd->prepare($sql);
    $stmt->execute([
      $fecha,
      $estado,
      $tecnicoAsignado,
      $observaciones,
      $TDesplazamiento,
      $TIntervencion,
      $tipoFinanciacion,
      $idUsuario,
      $numEquipo,
      $incidencia,
      $cp,
      $localidad,
      $provincia,
      $direccionDetalle,
      $correo
    ]);
    // Se muestra la notificación usando un alert.
    echo "<script>alert('Incidencia registrada con éxito.');</script>";
  } catch (PDOException $e) {
    echo "<script>alert('Error al registrar: " . $e->getMessage() . "');</script>";
  }
}

// Variable para almacenar el correo del cliente
$emailCliente = '';

// Si es un usuario cliente, obtenemos su correo directamente
if ($permiso == 'cliente') {
  $emailCliente = $userRow['correo'] ?? '';
} 
// Si es admin/recepcion/jefetecnico y ha seleccionado un cliente, obtenemos el correo del cliente seleccionado
elseif (isset($_GET['clienteElegido']) && ($permiso == 'admin' || $permiso == 'recepcion' || $permiso == 'jefetecnico')) {
  try {
    $clienteQuery = $bd->prepare("SELECT correo FROM Usuarios WHERE idUsuarios = ?");
    $clienteQuery->execute([$_GET['clienteElegido']]);
    $clienteData = $clienteQuery->fetch();
    $emailCliente = $clienteData['correo'] ?? '';
  } catch (PDOException $e) {
    // En caso de error, dejamos el email vacío
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Incidencias</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* Estilos básicos para la página */
    body {
      font-family: Arial, sans-serif;
      background-color: #f0f2f5;
      padding: 20px;
      text-align: center;
    }
    /* Estilos para el formulario */
    form {
      background: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 600px;
      margin: 0 auto;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      text-align: left;
    }
    h1 {
      margin-bottom: 20px;
      color: #2c3e50;
      text-align: center;
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
    input[type="email"] {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      margin-bottom: 10px;
    }
    input[type="submit"] {
      background-color: #3498db;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    input[type="submit"]:hover {
      background-color: #2980b9;
    }
    .nuevo-cliente {
      margin-top: 10px;
      text-align: right;
    }
    .nuevo-cliente a {
      background-color: #27ae60;
      color: white;
      padding: 8px 12px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
    }
    .nuevo-cliente a:hover {
      background-color: #219150;
    }
  </style>
</head>
<body>
  <form method="POST">
    <h1>Crear Incidencias</h1>
    <!-- Campo para ingresar la incidencia; se conserva el contenido previo (si lo hubiera) -->
    <p>
      <label>Incidencia:</label><br>
      <textarea name="incidencia" placeholder="Introduzca la incidencia" required><?= htmlspecialchars($_GET['incidenciaPrev'] ?? ''); ?></textarea>
    </p>
    <!-- Campo para el correo electrónico que ahora muestra el correo del cliente por defecto -->
    <p>
      <label>Correo electrónico:</label><br>
      <input type="email" name="correo" value="<?= htmlspecialchars($emailCliente); ?>" placeholder="Introduzca el correo del cliente" required>
    </p>

    <?php if ($permiso == 'cliente'): ?>
      <!-- Para usuarios cliente, se muestran directamente el select de equipos.
           La dirección se obtiene del equipo seleccionado y no se permite elegir otra. -->
      <?php if ($tieneEquipos): ?>
        <p>
          <label>Equipo:</label><br>
          <select name="equipo" id="equipo" required>
            <option value="">Seleccione un equipo</option>
            <?php
            try {
              $queryEquipos = $bd->prepare("SELECT numEquipo, cp, provincia, localidad, direccion, tipoMantenimiento FROM Equipos WHERE idUsuario = ?");
              $queryEquipos->execute([$_SESSION['idUsuario']]);
              while ($eq = $queryEquipos->fetch()) {
                // Cada opción incluye atributos con los datos del equipo para actualizar el campo de dirección.
                echo "<option value=\"" . htmlspecialchars($eq['numEquipo']) . "\" 
                        data-cp=\"" . htmlspecialchars($eq['cp']) . "\"
                        data-provincia=\"" . htmlspecialchars($eq['provincia']) . "\"
                        data-localidad=\"" . htmlspecialchars($eq['localidad']) . "\"
                        data-direccion=\"" . htmlspecialchars($eq['direccion']) . "\">
                        " . htmlspecialchars($eq['numEquipo']) . " (" . htmlspecialchars($eq['tipoMantenimiento']) . ")
                      </option>";
              }
            } catch (PDOException $e) {
              echo "<option value=''>Error cargando equipos</option>";
            }
            ?>
          </select>
        </p>
        <!-- Se muestra la dirección del equipo seleccionado en un campo de solo lectura -->
        <p>
          <label>Dirección del Equipo:</label><br>
          <input type="text" id="direccionEquipo" readonly>
        </p>
        <p><input type="submit" value="Crear incidencia" name="crear"></p>
      <?php else: ?>
        <p style="color:red;">No tienes equipos asignados.</p>
      <?php endif; ?>

    <?php elseif ($permiso == 'recepcion' || $permiso == 'admin' || $permiso == 'jefetecnico'): ?>
      <?php if (!isset($_GET['clienteElegido'])): ?>
        <!-- Primer formulario para seleccionar un cliente -->
        <p>
          <label>Cliente:</label><br>
          <select name="cliente" id="cliente" required>
            <option value="">Seleccione un cliente</option>
            <?php
            try {
              $queryClientes = $bd->query("SELECT idUsuarios, usuario, correo FROM Usuarios WHERE permiso = 'cliente'");
              while ($cli = $queryClientes->fetch()) {
                echo "<option value=\"" . htmlspecialchars($cli['idUsuarios']) . "\" 
                      data-correo=\"" . htmlspecialchars($cli['correo']) . "\">
                      " . htmlspecialchars($cli['idUsuarios']) . " - " . htmlspecialchars($cli['usuario']) . "</option>";
              }
            } catch (PDOException $e) {
              echo "<option value=''>Error cargando clientes</option>";
            }
            ?>
          </select>
        </p>
        <p>
          <input type="submit" name="elegirCliente" value="Seleccionar Cliente">
          <span class="nuevo-cliente">
            <a href="../usuarios/crearUsuarios.php" target="_blank">Crear nuevo cliente</a>
          </span>
        </p>
      <?php else:
              // Se ha recibido el cliente seleccionado a través del parámetro GET 'clienteElegido'.
              $clienteElegido = $_GET['clienteElegido'];
              echo '<input type="hidden" name="cliente" value="' . htmlspecialchars($clienteElegido) . '">';
              // Se consulta la información del cliente seleccionado.
              $queryCliente = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
              $queryCliente->execute([$clienteElegido]);
              $clienteData = $queryCliente->fetch();
      ?>
        <p>Cliente seleccionado: <?= htmlspecialchars($clienteData['usuario']); ?></p>
        <!-- Se muestran los equipos del cliente seleccionado -->
        <p>
          <label>Equipo:</label><br>
          <select name="equipo" id="equipo" required>
            <option value="">Seleccione un equipo</option>
            <?php
            try {
              $queryEquipos = $bd->prepare("SELECT numEquipo, cp, provincia, localidad, direccion, tipoMantenimiento FROM Equipos WHERE idUsuario = ?");
              $queryEquipos->execute([$clienteElegido]);
              while ($eq = $queryEquipos->fetch()) {
                echo "<option value=\"" . htmlspecialchars($eq['numEquipo']) . "\" 
                        data-cp=\"" . htmlspecialchars($eq['cp']) . "\"
                        data-provincia=\"" . htmlspecialchars($eq['provincia']) . "\"
                        data-localidad=\"" . htmlspecialchars($eq['localidad']) . "\"
                        data-direccion=\"" . htmlspecialchars($eq['direccion']) . "\">
                        " . htmlspecialchars($eq['numEquipo']) . " (" . htmlspecialchars($eq['tipoMantenimiento']) . ")
                      </option>";
              }
            } catch (PDOException $e) {
              echo "<option value=''>Error cargando equipos</option>";
            }
            ?>
          </select>
        </p>
        <!-- Botón para crear nuevo equipo (visible para admin, recepcion o jefeTecnico) -->
        <?php if ($permiso == 'admin' || $permiso == 'recepcion' || $permiso == 'jefetecnico'): ?>
          <div class="nuevo-equipo" style="text-align:right; margin-top:10px;">
            <a href="../equipos/crearEquipos.php" target="_blank"
              style="background-color:#27ae60; color:white; padding:8px 12px; border-radius:4px; text-decoration:none; font-size:14px;">
              Crear nuevo equipo
            </a>
          </div>
        <?php endif; ?>
        <!-- En este caso, la dirección se muestra en un campo de solo lectura -->
        <p>
          <label>Dirección del Equipo:</label><br>
          <input type="text" id="direccionEquipo" readonly>
        </p>
        <p><input type="submit" value="Crear incidencia" name="crear"></p>
      <?php endif; ?>
    <?php endif; ?>
  </form>

  <!-- Campos ocultos para enviar los datos de dirección obtenidos desde el equipo seleccionado -->
  <input type="hidden" name="cp" id="cp">
  <input type="hidden" name="provincia" id="provincia">
  <input type="hidden" name="localidad" id="localidad">
  <input type="hidden" name="direccionDetalle" id="direccionDetalle">

  <script>
    // Función para actualizar el campo de dirección y los campos ocultos
    function actualizarDatosEquipo(selectElement) {
      var selectedOption = selectElement.options[selectElement.selectedIndex];
      // Se actualiza el campo de texto que muestra la dirección del equipo.
      document.getElementById('direccionEquipo').value = selectedOption.getAttribute('data-direccion') || '';
      // Se actualizan los campos ocultos para ser enviados con el formulario.
      document.getElementById('cp').value = selectedOption.getAttribute('data-cp') || '';
      document.getElementById('provincia').value = selectedOption.getAttribute('data-provincia') || '';
      document.getElementById('localidad').value = selectedOption.getAttribute('data-localidad') || '';
      document.getElementById('direccionDetalle').value = selectedOption.getAttribute('data-direccion') || '';
    }

    // Se añade un listener para actualizar la dirección cuando se cambia el select de Equipo.
    document.getElementById('equipo')?.addEventListener('change', function () {
      actualizarDatosEquipo(this);
    });

    // Función para actualizar el correo cuando se selecciona un cliente (solo en la primera pantalla)
    <?php if (($permiso == 'recepcion' || $permiso == 'admin' || $permiso == 'jefetecnico') && !isset($_GET['clienteElegido'])): ?>
    document.getElementById('cliente')?.addEventListener('change', function() {
      var selectedOption = this.options[this.selectedIndex];
      var correoCliente = selectedOption.getAttribute('data-correo') || '';
      document.querySelector('input[name="correo"]').value = correoCliente;
    });
    <?php endif; ?>
  </script>
</body>
</html>