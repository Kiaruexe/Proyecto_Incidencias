<?php
// Iniciar sesión
session_start();

// Verificar si el usuario ha iniciado sesión, si no, redirigirlo al login
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../login.php");
    exit;
}

try {
    // Conexión a la base de datos utilizando PDO
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 
        'Mapapli', 
        '9R%d5cf62'
    );
    
    // Obtener los datos del usuario actual desde la base de datos
    $query = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
    $query->execute([$_SESSION['idUsuario']]);
    $userRow = $query->fetch();

    // Obtener el tipo de permiso del usuario
    $permiso = $userRow['permiso'];
} catch (PDOException $e) {
    // Manejo de errores en la conexión a la base de datos
    echo "Error: " . $e->getMessage();
    exit;
}

// Definir los campos permitidos para el filtrado de incidencias
$allowedFields = [
    'idIncidencias'   => 'idIncidencias',
    'fecha'           => 'fecha',
    'tecnicoAsignado' => 'tecnicoAsignado',
    'incidencia'      => 'incidencia',
    'idUsuario'       => 'idUsuario',
    'numEquipo'       => 'numEquipo'
];

// Obtener el campo y el valor de filtrado desde la URL
$filterField = isset($_GET['filter_field']) && array_key_exists($_GET['filter_field'], $allowedFields)
    ? $allowedFields[$_GET['filter_field']]
    : '';
$filterValue = isset($_GET['filter_value']) ? trim($_GET['filter_value']) : '';
$filterClause = "";
$params = [];

// Construcción de la condición de filtrado si se ha seleccionado un campo válido y un valor
if ($filterField !== '' && $filterValue !== '') {
    $filterClause = " AND $filterField LIKE ?";
    $params[] = '%' . $filterValue . '%'; // Se usa LIKE para permitir coincidencias parciales
}

// Construcción de la consulta según el permiso del usuario
$sql = "";
if ($permiso == 'admin' || $permiso == 'recepcion' || $permiso == 'jefetecnico') {
    // Administradores, recepción y jefe técnico pueden ver todas las incidencias
    $sql = "SELECT * FROM Incidencias WHERE 1=1" . $filterClause;
} elseif ($permiso == 'cliente') {
    // Los clientes solo pueden ver sus propias incidencias
    $sql = "SELECT * FROM Incidencias WHERE idUsuario = ? " . $filterClause;
    array_unshift($params, $_SESSION['idUsuario']); // Agregar el ID del usuario a los parámetros
} elseif ($permiso == 'tecnico') {
    // Los técnicos solo ven incidencias inactivas que les han sido asignadas
    $sql = "SELECT * FROM Incidencias WHERE estado = 0 AND tecnicoAsignado = ? " . $filterClause;
    array_unshift($params, $userRow['usuario']); // Agregar el nombre de usuario del técnico
} else {
    // Si el usuario no tiene un permiso válido, redirigirlo al login
    header("Location: ../login.php");
    exit;
}

try {
    // Preparar y ejecutar la consulta con los parámetros correspondientes
    $stmt = $bd->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    // Manejo de errores en la consulta
    echo "Error en la consulta: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
  <p>Usuario ID: <?php echo ($_SESSION['idUsuario']); ?> - Tipo: <?php echo ($permiso); ?></p>

  <!-- Formulario de filtrado -->
  <form method="GET" action="">
    <label for="filter_field">Filtrar por:</label>
    <select name="filter_field" id="filter_field">
      <option value="">-- Seleccione --</option>
      <option value="idIncidencias" <?php if($filterField=='idIncidencias') echo 'selected'; ?>>ID Incidencia</option>
      <option value="fecha" <?php if($filterField=='fecha') echo 'selected'; ?>>Fecha</option>
      <option value="tecnicoAsignado" <?php if($filterField=='tecnicoAsignado') echo 'selected'; ?>>Técnico Asignado</option>
      <option value="incidencia" <?php if($filterField=='incidencia') echo 'selected'; ?>>Incidencia</option>
      <option value="idUsuario" <?php if($filterField=='idUsuario') echo 'selected'; ?>>ID Usuario</option>
      <option value="numEquipo" <?php if($filterField=='numEquipo') echo 'selected'; ?>>ID Equipo</option>
    </select>
    <input type="text" name="filter_value" placeholder="Valor a buscar" value="<?php echo ($filterValue); ?>">
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
        <th>ID Usuario</th>
        <th>Num Equipo</th>
        <th>Incidencia</th>
      </tr>
      <?php while ($row = $stmt->fetch()): ?>
        <tr>
          <td><?php echo ($row['idIncidencias']); ?></td>
          <td><?php echo ($row['fecha']); ?></td>
          <td><?php echo $row['estado'] ? 'Activo' : 'Inactivo'; ?></td>
          <td><?php echo ($row['tecnicoAsignado']); ?></td>
          <td><?php echo ($row['observaciones']); ?></td>
          <td><?php echo ($row['TDesplazamiento']); ?> min</td>
          <td><?php echo ($row['TIntervencion']); ?> min</td>
          <td><?php echo ($row['tipoFinanciacion']); ?></td>
          <td><?php echo ($row['idUsuario']); ?></td>
          <td><?php echo ($row['numEquipo']); ?></td>
          <td><?php echo ($row['incidencia']); ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php else: ?>
    <p>No hay incidencias disponibles.</p>
  <?php endif; ?>

  <p><a href="../index.php">Volver al inicio</a></p>
</body>
</html>
