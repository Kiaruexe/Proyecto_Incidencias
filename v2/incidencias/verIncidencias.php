<?php
// Inicia la sesión y verifica si el usuario está autenticado
session_start();
if (!isset($_SESSION["idUsuario"])) {
  header("Location: ../login.php"); // Redirige al login si no hay sesión
  exit;
}

try {
  // Conexión a la base de datos y obtención de datos del usuario autenticado
  $bd = new PDO(
    'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
    'Mapapli',
    '9R%d5cf62'
  );
  // Se obtiene la información del usuario mediante su ID
  $query = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
  $query->execute([$_SESSION['idUsuario']]);
  $userRow = $query->fetch();
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

// Definir los campos permitidos para el filtrado, usando el nombre completo de la tabla
$allowedFields = [
    'idIncidencias'   => 'Incidencias.idIncidencias',
    'fecha'           => 'Incidencias.fecha',
    'tecnicoAsignado' => 'Incidencias.tecnicoAsignado',
    'incidencia'      => 'Incidencias.incidencia',
    'usuario'         => 'Usuarios.usuario',
    'numEquipo'       => 'Incidencias.numEquipo',
    'estado'          => 'Incidencias.estado'
];

// Obtener el campo y el valor del filtro desde la URL
$filterField = isset($_GET['filter_field']) && array_key_exists($_GET['filter_field'], $allowedFields)
    ? $allowedFields[$_GET['filter_field']]
    : '';
$filterValue = isset($_GET['filter_value']) ? trim($_GET['filter_value']) : '';
$filterClause = "";
$params = [];

// Si se ha seleccionado un campo y se ha introducido un valor, se hace la comparación exacta
if ($filterField !== '' && $filterValue !== '') {
    $filterClause = " AND $filterField = ?";
    $params[] = $filterValue;
}

// Para mayor control: Solo 'admin' y 'jefetecnico' pueden filtrar por estado
if ($filterField === 'Incidencias.estado' && !in_array($permiso, ['admin', 'jefetecnico'])) {
    $filterField = '';
    $filterClause = '';
    $params = [];
}

// Construcción de la consulta según el permiso del usuario
$sql = "";
if ($permiso == 'admin' || $permiso == 'recepcion' || $permiso == 'jefetecnico') {
    // Estos roles pueden ver todas las incidencias
    $sql = "SELECT Incidencias.*, Usuarios.usuario 
            FROM Incidencias 
            LEFT JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios 
            WHERE 1=1" . $filterClause;
} elseif ($permiso == 'cliente') {
    // Los clientes solo ven sus propias incidencias
    $sql = "SELECT Incidencias.*, Usuarios.usuario 
            FROM Incidencias 
            LEFT JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios 
            WHERE Incidencias.idUsuario = ?" . $filterClause;
    array_unshift($params, $_SESSION['idUsuario']);
} elseif ($permiso == 'tecnico') {
    // Los técnicos ven incidencias inactivas asignadas a ellos (comparando con su nombre de usuario)
    $sql = "SELECT Incidencias.*, Usuarios.usuario 
            FROM Incidencias 
            LEFT JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios 
            WHERE Incidencias.estado = 0 AND Incidencias.tecnicoAsignado = ?" . $filterClause;
    array_unshift($params, $userRow['usuario']);
} else {
    header("Location: ../login.php");
    exit;
}

try {
    // Preparar y ejecutar la consulta con los parámetros correspondientes
    $stmt = $bd->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    echo "Error en la consulta: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ver Incidencias</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
      /* Estilos básicos para la tabla y formulario */
      table {
          width: 100%;
          border-collapse: collapse;
      }
      th, td {
          padding: 8px;
          text-align: left;
          border: 1px solid #ccc;
      }
      th {
          background-color: #f2f2f2;
      }
      form {
          margin-bottom: 20px;
      }
  </style>
</head>
<body>
  <h1>Lista de Incidencias</h1>
  <p>Usuario: <?php echo htmlspecialchars($userRow['usuario']); ?> - Tipo: <?php echo htmlspecialchars($permiso); ?></p>

  <!-- Formulario de filtrado -->
  <form method="GET" action="">
    <label for="filter_field">Filtrar por:</label>
    <select name="filter_field" id="filter_field">
      <option value="">-- Seleccione --</option>
      <option value="idIncidencias" <?php if($filterField=='Incidencias.idIncidencias') echo 'selected'; ?>>ID Incidencia</option>
      <option value="fecha" <?php if($filterField=='Incidencias.fecha') echo 'selected'; ?>>Fecha</option>
      <option value="tecnicoAsignado" <?php if($filterField=='Incidencias.tecnicoAsignado') echo 'selected'; ?>>Técnico Asignado</option>
      <option value="incidencia" <?php if($filterField=='Incidencias.incidencia') echo 'selected'; ?>>Incidencia</option>
      <option value="usuario" <?php if($filterField=='Usuarios.usuario') echo 'selected'; ?>>Cliente</option>
      <option value="numEquipo" <?php if($filterField=='Incidencias.numEquipo') echo 'selected'; ?>>Nº Equipo</option>
      <option value="estado" <?php if($filterField=='Incidencias.estado') echo 'selected'; ?>>Estado (1=Completo, 0=Incompleto)</option>
    </select>
    <input type="text" name="filter_value" placeholder="Valor a buscar" value="<?php echo htmlspecialchars($filterValue); ?>">
    <input type="submit" value="Filtrar">
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>">Limpiar filtro</a>
  </form>

  <?php if ($stmt->rowCount() > 0): ?>
    <table>
      <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Estado</th>
        <th>Técnico Asignado</th>
        <th>Observaciones</th>
        <th>Tiempo Desplazamiento</th>
        <th>Tiempo Intervención</th>
        <th>Tipo Financiación</th>
        <th>Cliente</th>
        <th>Nº Equipo</th>
        <th>Incidencia</th>
      </tr>
      <?php while ($row = $stmt->fetch()): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['idIncidencias']); ?></td>
          <td><?php echo htmlspecialchars($row['fecha']); ?></td>
          <td><?php echo $row['estado'] ? 'Completo' : 'Incompleto'; ?></td>
          <td><?php echo htmlspecialchars($row['tecnicoAsignado']); ?></td>
          <td><?php echo htmlspecialchars($row['observaciones']); ?></td>
          <td><?php echo htmlspecialchars($row['TDesplazamiento']); ?> min</td>
          <td><?php echo htmlspecialchars($row['TIntervencion']); ?> min</td>
          <td><?php echo htmlspecialchars($row['tipoFinanciacion']); ?></td>
          <td><?php echo htmlspecialchars($row['usuario']); ?></td>
          <td><?php echo htmlspecialchars($row['numEquipo']); ?></td>
          <td><?php echo htmlspecialchars($row['incidencia']); ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php else: ?>
    <p>No hay incidencias disponibles.</p>
  <?php endif; ?>

  <p><a href="../index.php">Volver al inicio</a></p>
</body>
</html>
