<?php
session_start();
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../login.php");
    exit;
}

try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 
        'Mapapli', 
        '9R%d5cf62'
    );
    $query = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
    $query->execute([$_SESSION['idUsuario']]);
    $userRow = $query->fetch();
    $permiso = $userRow['permiso'];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Restringir acceso a técnicos
if ($permiso == 'tecnico') {
    header("Location: ../index.php");
    exit;
}

// --- Configuración del filtrado ---
// Campos permitidos para filtrar
$allowedFields = [
    'idIncidencias'      => 'idIncidencias',
    'fecha'              => 'fecha',
    'tecnicoAsignado'    => 'tecnicoAsignado',
    'incidencia'         => 'incidencia',
    'idUsuario'          => 'idUsuario',
    'idEquipo'           => 'idEquipo'
];

$filterField = isset($_GET['filter_field']) && array_key_exists($_GET['filter_field'], $allowedFields)
    ? $allowedFields[$_GET['filter_field']]
    : '';
$filterValue = isset($_GET['filter_value']) ? trim($_GET['filter_value']) : ''; // trim() elimina espacios en blanco (u otros caracteres) al inicio y final de una cadena.
$filterClause = "";
$params = [];

// Si se eligió un campo y se ingresó un valor, se construye la condición de filtrado
if ($filterField !== '' && $filterValue !== '') {
    // Usamos LIKE para búsquedas parciales
    $filterClause = " AND $filterField LIKE ?";
    $params[] = '%' . $filterValue . '%';
}

// --- Construcción de la consulta según el permiso ---
$sql = "";
if ($permiso == 'admin' || $permiso == 'receptor' || $permiso == 'jefeTecnico') {
    // Para estos roles se muestran todas las incidencias y se puede aplicar el filtro
    $sql = "SELECT * FROM Incidencias WHERE 1=1" . $filterClause;
    // No hay parámetro adicional para el usuario
} elseif ($permiso == 'cliente') {
    // Los clientes solo ven sus propias incidencias
    $sql = "SELECT * FROM Incidencias WHERE idUsuario = ? " . $filterClause;
    array_unshift($params, $_SESSION['idUsuario']);     // array_unshift() añade uno o más elementos al inicio del array, desplazando los existentes.
} elseif ($permiso == 'tecnico') {
    // Técnicos ven incidencias inactivas asignadas a ellos
    $sql = "SELECT * FROM Incidencias WHERE estado = 0 AND tecnicoAsignado = ? " . $filterClause;
    array_unshift($params, $_SESSION['idUsuario']);
} else {
    header("Location: ../login.php");
    exit;
}

try {
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ver Incidencias</title>
  <link rel="stylesheet" href="../css/style.css">
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
      <option value="idEquipo" <?php if($filterField=='idEquipo') echo 'selected'; ?>>ID Equipo</option>
    </select>
    <input type="text" name="filter_value" placeholder="Valor a buscar" value="<?php echo ($filterValue); ?>">
    <input type="submit" value="Filtrar">
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>">Limpiar filtro</a>
  </form>

  <?php if ($stmt->rowCount() > 0): ?>
    <table border="1">
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
        <th>ID Equipo</th>
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
          <td><?php echo ($row['idEquipo']); ?></td>
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
