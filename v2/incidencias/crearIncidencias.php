<?php
// Inicia la sesión y verifica si el usuario está autenticado
session_start();
if (!isset($_SESSION["idUsuario"])) {
  header("Location: ../login.php"); // Redirige al login si no hay sesión
  exit;
}

// Conexión a la base de datos y obtención de datos del usuario autenticado
try {
  $bd = new PDO(
    'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
    'Mapapli',
    '9R%d5cf62'
  );
  // Se obtiene la información del usuario mediante su id
  $query = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
  $query->execute([$_SESSION['idUsuario']]);
  $userRow = $query->fetch(PDO::FETCH_ASSOC);
  // Se normaliza el permiso a minúsculas
  $permiso = strtolower($userRow['permiso']);
} catch (PDOException $e) {
  echo "Error: " . $e->getMessage(); // Muestra error de conexión
  exit;
}

/* 
  Para usuarios con permiso 'recepcion', 'admin' o 'jefetecnico' se requiere seleccionar un cliente.
  Si se envía el formulario de selección, se redirige a este mismo archivo con el parámetro GET 'clienteElegido'
  y se conserva el contenido previamente escrito en el textarea "incidencia".
*/
$incidenciaPrev = $_POST['incidencia'] ?? '';
if (($permiso == 'recepcion' || $permiso == 'admin' || $permiso == 'jefetecnico') && isset($_POST['elegirCliente'])) {
  $clienteElegido = $_POST['cliente'] ?? '';
  if (!empty($clienteElegido)) {
    header("Location: " . $_SERVER['PHP_SELF'] . "?clienteElegido=" . urlencode($clienteElegido) . "&incidenciaPrev=" . urlencode($incidenciaPrev));
    exit;
  }
}

// Para usuarios con permiso 'cliente', se verifica que tengan equipos asignados
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

// Procesar el formulario de creación de incidencia si se envía y, en el caso de clientes, tienen equipos
if (isset($_POST["crear"]) && ($permiso != 'cliente' || $tieneEquipos)) {
  // Función para limpiar campos, retornando null si están vacíos
  function limpiarCampo($valor) {
    return !empty($valor) ? $valor : null;
  }

  // Se obtienen los datos del formulario
  $incidencia = $_POST['incidencia'] ?? '';
  $fecha = date('Y-m-d');  // Se obtiene la fecha actual (día/mes/año)
  $estado = 0;             // Se establece el estado como false (0)
  $observaciones = null;
  $TDesplazamiento = null;
  $TIntervencion = null;
  $tecnicoAsignado = "sin asignar"; // Valor por defecto para técnico asignado
  // Se toman los datos de dirección desde los campos ocultos
  $cp = $_POST['cp'] ?? null;
  $provincia = $_POST['provincia'] ?? null;
  $localidad = $_POST['localidad'] ?? null;
  $direccionDetalle = $_POST['direccionDetalle'] ?? null;

  $idUsuario = null;
  $numEquipo = null;
  $tipoFinanciacion = null; // Se obtendrá del campo tipoMantenimiento del equipo seleccionado

  if ($permiso == 'cliente') {
    // Para clientes, se utiliza su propio id y se carga el equipo seleccionado
    $numEquipo = limpiarCampo($_POST['equipo']);
    $idUsuario = $_SESSION['idUsuario'];
    if ($numEquipo) {
      // Consulta para obtener datos del equipo seleccionado del cliente
      $qEquipo = $bd->prepare("SELECT tipoMantenimiento, cp, provincia, localidad, direccion FROM Equipos WHERE numEquipo = ? AND idUsuario = ?");
      $qEquipo->execute([$numEquipo, $_SESSION['idUsuario']]);
      $eqData = $qEquipo->fetch();
      $tipoFinanciacion = $eqData['tipoMantenimiento'] ?? null;
      $cp = $eqData['cp'] ?? $cp;
      $provincia = $eqData['provincia'] ?? $provincia;
      $localidad = $eqData['localidad'] ?? $localidad;
      $direccionDetalle = $eqData['direccion'] ?? $direccionDetalle;
    }
  } elseif ($permiso == 'recepcion' || $permiso == 'admin' || $permiso == 'jefetecnico') {
    // Para estos roles, se utiliza el id del cliente seleccionado
    $idUsuario = limpiarCampo($_POST['cliente'] ?? '');
    $numEquipo = limpiarCampo($_POST['equipo'] ?? '');
    if ($numEquipo) {
      // Consulta para obtener datos del equipo seleccionado del cliente elegido
      $qEquipo = $bd->prepare("SELECT tipoMantenimiento, cp, provincia, localidad, direccion FROM Equipos WHERE numEquipo = ? AND idUsuario = ?");
      $qEquipo->execute([$numEquipo, $idUsuario]);
      $eqData = $qEquipo->fetch();
      $tipoFinanciacion = $eqData['tipoMantenimiento'] ?? null;
      $cp = $eqData['cp'] ?? $cp;
      $provincia = $eqData['provincia'] ?? $provincia;
      $localidad = $eqData['localidad'] ?? $localidad;
      $direccionDetalle = $eqData['direccion'] ?? $direccionDetalle;
    }
    // Para admin y jefeTecnico se permite asignar un técnico
    if ($permiso == 'admin' || $permiso == 'jefetecnico') {
      $tecnicoAsignado = limpiarCampo($_POST['tecnico'] ?? '') ?: "sin asignar";
    }
  }

  // Inserción de la incidencia en la tabla Incidencias
  try {
    $sql = "INSERT INTO Incidencias (
            fecha, estado, tecnicoAsignado, observaciones, TDesplazamiento, TIntervencion,
            tipoFinanciacion, idUsuario, numEquipo, incidencia, cp, localidad, provincia, direccion
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
      $direccionDetalle
    ]);
    echo "<p style='color:green;'>Incidencia registrada con éxito.</p>";
  } catch (PDOException $e) {
    echo "<p style='color:red;'>Error al registrar: " . $e->getMessage() . "</p>";
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Incidencias</title>
  <style>
    /* Estilos básicos para la página */
    body {
      font-family: Arial, sans-serif;
      background-color: #f0f2f5;
      padding: 20px;
    }
    /* Estilos para el formulario */
    form {
      background: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 600px;
      margin: 0 auto;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    h1 {
      text-align: center;
      margin-bottom: 20px;
      color: #2c3e50;
    }
    p {
      margin-bottom: 15px;
    }
    label {
      font-weight: bold;
    }
    select,
    textarea,
    input[type="text"] {
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
    /* Estilos para el enlace de crear nuevo cliente */
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
    <!-- Campo para ingresar la incidencia; se conserva el contenido previo si ya se había escrito -->
    <p>
      <label>Incidencia:</label><br>
      <textarea name="incidencia" placeholder="Introduzca la incidencia" required><?= htmlspecialchars($_GET['incidenciaPrev'] ?? ''); ?></textarea>
    </p>

    <?php if ($permiso == 'cliente'): ?>
      <!-- Para usuarios cliente, se muestran directamente sus equipos y direcciones -->
      <?php if ($tieneEquipos): ?>
        <p>
          <label>Equipo:</label><br>
          <select name="equipo" required>
            <option value="">Seleccione un equipo</option>
            <?php
            try {
              $queryEquipos = $bd->prepare("SELECT numEquipo FROM Equipos WHERE idUsuario = ?");
              $queryEquipos->execute([$_SESSION['idUsuario']]);
              while ($eq = $queryEquipos->fetch(PDO::FETCH_ASSOC)) {
                echo "<option value='" . htmlspecialchars($eq['numEquipo']) . "'>" . htmlspecialchars($eq['numEquipo']) . "</option>";
              }
            } catch (PDOException $e) {
              echo "<option value=''>Error cargando equipos</option>";
            }
            ?>
          </select>
        </p>
        <?php
          // Construye las opciones de dirección a partir de los datos del cliente
          $direccionOptions = [];
          if (!empty($userRow['direccionFiscal']) && !empty($userRow['cpFiscal']) && !empty($userRow['provinciaFiscal']) && !empty($userRow['localidadFiscal'])) {
            $direccionOptions[] = [
              'id' => 'fiscal',
              'label' => "Fiscal: {$userRow['cpFiscal']} - {$userRow['provinciaFiscal']} - {$userRow['localidadFiscal']} - {$userRow['direccionFiscal']}"
            ];
          }
          if (!empty($userRow['direccion1']) && !empty($userRow['cp1'])) {
            $direccionOptions[] = [
              'id' => 'adicional1',
              'label' => "Adicional 1: {$userRow['cp1']} - {$userRow['provincia1']} - {$userRow['localidad1']} - {$userRow['direccion1']}"
            ];
          }
          if (!empty($userRow['direccion2']) && !empty($userRow['cp2'])) {
            $direccionOptions[] = [
              'id' => 'adicional2',
              'label' => "Adicional 2: {$userRow['cp2']} - {$userRow['provincia2']} - {$userRow['localidad2']} - {$userRow['direccion2']}"
            ];
          }
        ?>
        <?php if (!empty($direccionOptions)): ?>
          <p>
            <label>Dirección:</label><br>
            <select name="direccion" id="direccion" required>
              <option value="">Seleccione una dirección</option>
              <?php foreach ($direccionOptions as $opt): ?>
                <option value="<?= htmlspecialchars($opt['id']); ?>" data-cp="<?= htmlspecialchars($opt['label']); ?>">
                  <?= htmlspecialchars($opt['label']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </p>
        <?php else: ?>
          <p style="color:red;">No hay direcciones disponibles.</p>
        <?php endif; ?>
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
              $queryClientes = $bd->query("SELECT idUsuarios, usuario, cpFiscal, provinciaFiscal, localidadFiscal, direccionFiscal, cp1, provincia1, localidad1, direccion1, cp2, provincia2, localidad2, direccion2 FROM Usuarios WHERE permiso = 'cliente'");
              while ($cli = $queryClientes->fetch(PDO::FETCH_ASSOC)) {
                echo "<option value='" . htmlspecialchars($cli['idUsuarios']) . "'>" . htmlspecialchars($cli['idUsuarios']) . " - " . htmlspecialchars($cli['usuario']) . "</option>";
              }
            } catch (PDOException $e) {
              echo "<option value=''>Error cargando clientes</option>";
            }
            ?>
          </select>
        </p>
        <p>
          <input type="submit" name="elegirCliente" value="Seleccionar Cliente">
          <!-- Botón para crear nuevo cliente en nueva pestaña -->
          <span class="nuevo-cliente">
            <a href="../usuarios/crearUsuarios.php" target="_blank">Crear nuevo cliente</a>
          </span>
        </p>
      <?php else:
              // Se recibe el cliente seleccionado a través de GET 'clienteElegido'
              $clienteElegido = $_GET['clienteElegido'];
              echo '<input type="hidden" name="cliente" value="' . htmlspecialchars($clienteElegido) . '">';
              // Consulta para obtener los datos del cliente seleccionado
              $queryCliente = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
              $queryCliente->execute([$clienteElegido]);
              $clienteData = $queryCliente->fetch(PDO::FETCH_ASSOC);
      ?>
        <p>Cliente seleccionado: <?= htmlspecialchars($clienteData['usuario']); ?></p>
        <!-- Se muestran los equipos del cliente seleccionado -->
        <p>
          <label>Equipo:</label><br>
          <select name="equipo" required>
            <option value="">Seleccione un equipo</option>
            <?php
            try {
              $queryEquipos = $bd->prepare("SELECT numEquipo, tipoMantenimiento, cp, provincia, localidad, direccion FROM Equipos WHERE idUsuario = ?");
              $queryEquipos->execute([$clienteElegido]);
              while ($eq = $queryEquipos->fetch(PDO::FETCH_ASSOC)) {
                echo "<option value='" . htmlspecialchars($eq['numEquipo']) . "' 
                           data-tipo='" . htmlspecialchars($eq['tipoMantenimiento']) . "'
                           data-cp='" . htmlspecialchars($eq['cp']) . "'
                           data-provincia='" . htmlspecialchars($eq['provincia']) . "'
                           data-localidad='" . htmlspecialchars($eq['localidad']) . "'
                           data-direccion='" . htmlspecialchars($eq['direccion']) . "'>"
                  . htmlspecialchars($eq['numEquipo']) . " - " . htmlspecialchars($eq['numEquipo']) . "</option>";
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
        <!-- Se muestran las direcciones del cliente seleccionado -->
        <?php
        $direccionOptions = [];
        if (!empty($clienteData['direccionFiscal']) && !empty($clienteData['cpFiscal']) && !empty($clienteData['provinciaFiscal']) && !empty($clienteData['localidadFiscal'])) {
          $direccionOptions[] = [
            'id' => 'fiscal',
            'label' => "Fiscal: {$clienteData['cpFiscal']} - {$clienteData['provinciaFiscal']} - {$clienteData['localidadFiscal']} - {$clienteData['direccionFiscal']}"
          ];
        }
        if (!empty($clienteData['direccion1']) && !empty($clienteData['cp1'])) {
          $direccionOptions[] = [
            'id' => 'adicional1',
            'label' => "Adicional 1: {$clienteData['cp1']} - {$clienteData['provincia1']} - {$clienteData['localidad1']} - {$clienteData['direccion1']}"
          ];
        }
        if (!empty($clienteData['direccion2']) && !empty($clienteData['cp2'])) {
          $direccionOptions[] = [
            'id' => 'adicional2',
            'label' => "Adicional 2: {$clienteData['cp2']} - {$clienteData['provincia2']} - {$clienteData['localidad2']} - {$clienteData['direccion2']}"
          ];
        }
        ?>
        <?php if (!empty($direccionOptions)): ?>
          <p>
            <label>Dirección:</label><br>
            <select name="direccion" id="direccion" required>
              <option value="">Seleccione una dirección</option>
              <?php foreach ($direccionOptions as $opt): ?>
                <option value="<?= htmlspecialchars($opt['id']); ?>" data-cp="<?= htmlspecialchars($opt['label']); ?>">
                  <?= htmlspecialchars($opt['label']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </p>
        <?php else: ?>
          <p style="color:red;">No hay direcciones disponibles.</p>
        <?php endif; ?>
        <?php if ($permiso == 'admin' || $permiso == 'jefetecnico'): ?>
          <p>
            <label>Técnico:</label><br>
            <select name="tecnico">
              <option value="">sin asignar</option>
              <?php
              try {
                $queryTecnico = $bd->query("SELECT usuario FROM Usuarios WHERE permiso = 'tecnico'");
                while ($tec = $queryTecnico->fetch(PDO::FETCH_ASSOC)) {
                  echo "<option value='" . htmlspecialchars($tec['usuario']) . "'>" . htmlspecialchars($tec['usuario']) . "</option>";
                }
              } catch (PDOException $e) {
                echo "<option value=''>Error cargando técnicos</option>";
              }
              ?>
            </select>
          </p>
        <?php endif; ?>
        <p><input type="submit" value="Crear incidencia" name="crear"></p>
      <?php endif; ?>

    <?php endif; ?>
  </form>

  <!-- Campos ocultos para enviar datos de dirección -->
  <input type="hidden" name="cp" id="cp">
  <input type="hidden" name="localidad" id="localidad">
  <input type="hidden" name="provincia" id="provincia">
  <input type="hidden" name="direccionDetalle" id="direccionDetalle">

  <script>
    // Función para actualizar los campos ocultos con los datos de la dirección seleccionada
    function actualizarDireccion(selectElement) {
      var selectedOption = selectElement.options[selectElement.selectedIndex];
      document.getElementById('cp').value = selectedOption.getAttribute('data-cp') || '';
      document.getElementById('provincia').value = selectedOption.getAttribute('data-provincia') || '';
      document.getElementById('localidad').value = selectedOption.getAttribute('data-localidad') || '';
      document.getElementById('direccionDetalle').value = selectedOption.getAttribute('data-direccion') || '';
    }

    // Se añade el evento 'change' para actualizar los campos ocultos cuando se selecciona una dirección
    document.getElementById('direccion')?.addEventListener('change', function () {
      actualizarDireccion(this);
    });
  </script>
</body>
</html>
