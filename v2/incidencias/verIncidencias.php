<?php
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
    $query = $bd->prepare("SELECT * FROM Usuarios WHERE idUsuarios = ?");
    $query->execute([$_SESSION['idUsuario']]);
    $userRow = $query->fetch();
    $permiso = strtolower($userRow['permiso']);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage(); // Muestra error de conexión
    exit;
}

/* 
  Para usuarios con permiso 'recepcion', 'admin' o 'jefetecnico', se requiere seleccionar un cliente
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

// Definir los campos permitidos para el filtrado (se usa el nombre completo de la tabla en algunos casos)
$allowedFields = [
    'idIncidencias'   => 'Incidencias.idIncidencias',
    'fecha'           => 'Incidencias.fecha',
    'tecnicoAsignado' => 'Incidencias.tecnicoAsignado',
    'incidencia'      => 'Incidencias.incidencia',
    'usuario'         => 'Usuarios.usuario',
    'numEquipo'       => 'Incidencias.numEquipo',
    'estado'          => 'Incidencias.estado',
    'cp'              => 'Incidencias.cp',
    'localidad'       => 'Incidencias.localidad',
    'provincia'       => 'Incidencias.provincia',
    'direccion'       => 'Incidencias.direccion',
    'correo'          => 'Incidencias.correo',
    'firma'           => 'Incidencias.firma'
];

$filterField = isset($_GET['filter_field']) && array_key_exists($_GET['filter_field'], $allowedFields)
    ? $allowedFields[$_GET['filter_field']]
    : '';
$filterValue = isset($_GET['filter_value']) ? trim($_GET['filter_value']) : '';
$filterClause = "";
$params = [];

// Comparación exacta para el filtro
if ($filterField !== '' && $filterValue !== '') {
    $filterClause = " AND $filterField = ?";
    $params[] = $filterValue;
}

// Solo "admin" y "jefetecnico" pueden filtrar por estado
if ($filterField === 'Incidencias.estado' && !in_array($permiso, ['admin', 'jefetecnico'])) {
    $filterField = '';
    $filterClause = '';
    $params = [];
}

$sql = "";
if ($permiso == 'admin' || $permiso == 'recepcion' || $permiso == 'jefetecnico') {
    $sql = "SELECT Incidencias.*, Usuarios.usuario 
            FROM Incidencias 
            LEFT JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios 
            WHERE 1=1" . $filterClause;
} elseif ($permiso == 'cliente') {
    $sql = "SELECT Incidencias.*, Usuarios.usuario 
            FROM Incidencias 
            LEFT JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios 
            WHERE Incidencias.idUsuario = ?" . $filterClause;
    array_unshift($params, $_SESSION['idUsuario']);
} elseif ($permiso == 'tecnico') {
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

</head>
<body>
  <div class="container">
    <h1>Lista de Incidencias</h1>
    <p>Usuario: <?= htmlspecialchars($userRow['usuario']); ?> - Tipo: <?= htmlspecialchars($permiso); ?></p>
    
    <!-- Formulario de filtrado -->
    <form method="GET" action="">
      <label for="filter_field">Filtrar por:</label>
      <select name="filter_field" id="filter_field">
        <option value="">-- Seleccione --</option>
        <option value="idIncidencias" <?= $filterField=='Incidencias.idIncidencias' ? 'selected' : ''; ?>>ID Incidencia</option>
        <option value="fecha" <?= $filterField=='Incidencias.fecha' ? 'selected' : ''; ?>>Fecha</option>
        <option value="tecnicoAsignado" <?= $filterField=='Incidencias.tecnicoAsignado' ? 'selected' : ''; ?>>Técnico Asignado</option>
        <option value="incidencia" <?= $filterField=='Incidencias.incidencia' ? 'selected' : ''; ?>>Incidencia</option>
        <option value="usuario" <?= $filterField=='Usuarios.usuario' ? 'selected' : ''; ?>>Cliente</option>
        <option value="numEquipo" <?= $filterField=='Incidencias.numEquipo' ? 'selected' : ''; ?>>Nº Equipo</option>
        <option value="estado" <?= $filterField=='Incidencias.estado' ? 'selected' : ''; ?>>Estado (1=Completo, 0=Incompleto)</option>
        <option value="cp" <?= $filterField=='Incidencias.cp' ? 'selected' : ''; ?>>Código Postal</option>
        <option value="localidad" <?= $filterField=='Incidencias.localidad' ? 'selected' : ''; ?>>Localidad</option>
        <option value="provincia" <?= $filterField=='Incidencias.provincia' ? 'selected' : ''; ?>>Provincia</option>
        <option value="direccion" <?= $filterField=='Incidencias.direccion' ? 'selected' : ''; ?>>Dirección</option>
        <option value="correo" <?= $filterField=='Incidencias.correo' ? 'selected' : ''; ?>>Correo</option>
        <option value="firma" <?= $filterField=='Incidencias.firma' ? 'selected' : ''; ?>>Firma</option>
      </select>
      <input type="text" name="filter_value" placeholder="Valor a buscar" value="<?= htmlspecialchars($filterValue); ?>">
      <input type="submit" value="Filtrar">
      <a href="<?= $_SERVER['PHP_SELF']; ?>">Limpiar filtro</a>
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
          <th>Código Postal</th>
          <th>Localidad</th>
          <th>Provincia</th>
          <th>Dirección</th>
          <th>Correo</th>
          <th>Firma</th>
        </tr>
        <?php while ($row = $stmt->fetch()): ?>
          <tr>
            <td><?= htmlspecialchars($row['idIncidencias']); ?></td>
            <td><?= htmlspecialchars($row['fecha']); ?></td>
            <td><?= ($row['estado'] ? 'Completo' : 'Incompleto'); ?></td>
            <td><?= htmlspecialchars($row['tecnicoAsignado']); ?></td>
            <td><?= htmlspecialchars($row['observaciones']); ?></td>
            <td><?= htmlspecialchars($row['TDesplazamiento']); ?> min</td>
            <td><?= htmlspecialchars($row['TIntervencion']); ?> min</td>
            <td><?= htmlspecialchars($row['tipoFinanciacion']); ?></td>
            <td><?= htmlspecialchars($row['usuario']); ?></td>
            <td><?= htmlspecialchars($row['numEquipo']); ?></td>
            <td><?= htmlspecialchars($row['incidencia']); ?></td>
            <td><?= htmlspecialchars($row['cp']); ?></td>
            <td><?= htmlspecialchars($row['localidad']); ?></td>
            <td><?= htmlspecialchars($row['provincia']); ?></td>
            <td><?= htmlspecialchars($row['direccion']); ?></td>
            <td><?= htmlspecialchars($row['correo']); ?></td>
            <td>
              <?php 
              if (!empty($row['firma'])) {
                  echo "<span style='color:green; font-weight:bold;'>Firmado</span>";
              } else {
                  echo "<span style='color:red; font-weight:bold;'>Sin firmar</span>";
              }
              ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?>
      <p>No hay incidencias disponibles.</p>
    <?php endif; ?>

    <p><a href="../index.php">Volver al inicio</a></p>
  </div>
</body>
</html>
