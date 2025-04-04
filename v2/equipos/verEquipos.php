<?php 
session_start();

// Si no hay sesión iniciada, redirigir al login
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../login.php");
    exit;
}

// Conexión a la base de datos
try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 
        'Mapapli', 
        '9R%d5cf62'
    );

    // Obtener datos del usuario en sesión
    $query = $bd->prepare("SELECT permiso FROM Usuarios WHERE idUsuarios = ?");
    $query->execute([$_SESSION['idUsuario']]);
    $userRow = $query->fetch();
    $permiso = $userRow['permiso'];

    // Filtros
    $filtroTipoEquipo = $_GET['tipoEquipo'] ?? 'pc';
    $filtroMantenimiento = $_GET['tipoMantenimiento'] ?? '';
    $filtroCP = $_GET['cp'] ?? '';
    $filtroProvincia = $_GET['provincia'] ?? '';
    $filtroLocalidad = $_GET['localidad'] ?? '';
    $filtroUsuario = $_GET['usuario'] ?? '';

    // Ordenación
    $ordenarPor = $_GET['ordenarPor'] ?? 'numEquipo';
    $orden = ($_GET['orden'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

    // Columnas permitidas para ordenar (para evitar inyección SQL)
    $columnasOrden = ['numEquipo', 'fechaAlta', 'usuario', 'costo'];
    if (!in_array($ordenarPor, $columnasOrden)) {
        $ordenarPor = 'numEquipo';
    }

    // Construcción de la consulta con filtros
    $sql = "SELECT e.*, u.usuario FROM Equipos e 
            LEFT JOIN Usuarios u ON e.idUsuario = u.idUsuarios WHERE 1=1";
    $params = [];

    // Permiso de cliente (solo ve sus equipos)
    if ($permiso === 'cliente') {
        $sql .= " AND e.idUsuario = ?";
        $params[] = $_SESSION['idUsuario'];
    }

    // Aplicar filtros
    if (!empty($filtroTipoEquipo)) {
        $sql .= " AND e.tipoEquipo = ?";
        $params[] = $filtroTipoEquipo;
    }
    if (!empty($filtroMantenimiento)) {
        $sql .= " AND e.tipoMantenimiento LIKE ?";
        $params[] = "%$filtroMantenimiento%";
    }
    if (!empty($filtroCP)) {
        $sql .= " AND e.cp LIKE ?";
        $params[] = "%$filtroCP%";
    }
    if (!empty($filtroProvincia)) {
        $sql .= " AND e.provincia LIKE ?";
        $params[] = "%$filtroProvincia%";
    }
    if (!empty($filtroLocalidad)) {
        $sql .= " AND e.localidad LIKE ?";
        $params[] = "%$filtroLocalidad%";
    }
    if (!empty($filtroUsuario)) {
        $sql .= " AND u.usuario LIKE ?";
        $params[] = "%$filtroUsuario%";
    }

    // Aplicar ordenación
    $sql .= " ORDER BY $ordenarPor $orden";

    $query = $bd->prepare($sql);
    $query->execute($params);

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Equipos</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
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
    </style>
</head>
<body>
    <h1>Lista de Equipos</h1>

    <form method="get" action="">
        <label for="tipoEquipo">Tipo de Equipo:</label>
        <select name="tipoEquipo">
            <option value="pc" <?= $filtroTipoEquipo=='pc' ? 'selected' : ''; ?>>PC</option>
            <option value="portatil" <?= $filtroTipoEquipo=='portatil' ? 'selected' : ''; ?>>Portátil</option>
            <option value="impresora" <?= $filtroTipoEquipo=='impresora' ? 'selected' : ''; ?>>Impresora</option>
            <option value="monitor" <?= $filtroTipoEquipo=='monitor' ? 'selected' : ''; ?>>Monitor</option>
            <option value="otro" <?= $filtroTipoEquipo=='otro' ? 'selected' : ''; ?>>Otro</option>
            <option value="teclado" <?= $filtroTipoEquipo=='teclado' ? 'selected' : ''; ?>>Teclado</option>
            <option value="raton" <?= $filtroTipoEquipo=='raton' ? 'selected' : ''; ?>>Ratón</option>
            <option value="router" <?= $filtroTipoEquipo=='router' ? 'selected' : ''; ?>>Router</option>
            <option value="sw" <?= $filtroTipoEquipo=='sw' ? 'selected' : ''; ?>>Switch</option>
            <option value="sai" <?= $filtroTipoEquipo=='sai' ? 'selected' : ''; ?>>SAI</option>
        </select>

        <input type="text" name="tipoMantenimiento" placeholder="Tipo de Mantenimiento" value="<?= ($filtroMantenimiento); ?>">
        <input type="text" name="cp" placeholder="Código Postal" value="<?= ($filtroCP); ?>">
        <input type="text" name="provincia" placeholder="Provincia" value="<?= ($filtroProvincia); ?>">
        <input type="text" name="localidad" placeholder="Localidad" value="<?= ($filtroLocalidad); ?>">
        <input type="text" name="usuario" placeholder="Usuario Responsable" value="<?= ($filtroUsuario); ?>">

        <label for="ordenarPor">Ordenar por:</label>
        <select name="ordenarPor">
            <option value="numEquipo" <?= $ordenarPor=='numEquipo' ? 'selected' : ''; ?>>Número de Equipo</option>
            <option value="fechaAlta" <?= $ordenarPor=='fechaAlta' ? 'selected' : ''; ?>>Fecha de Alta</option>
            <option value="usuario" <?= $ordenarPor=='usuario' ? 'selected' : ''; ?>>Usuario</option>
            <option value="costo" <?= $ordenarPor=='costo' ? 'selected' : ''; ?>>Costo</option>
        </select>

        <label for="orden">Orden:</label>
        <select name="orden">
            <option value="ASC" <?= $orden=='ASC' ? 'selected' : ''; ?>>Ascendente</option>
            <option value="DESC" <?= $orden=='DESC' ? 'selected' : ''; ?>>Descendente</option>
        </select>

        <button type="submit">Aplicar Filtros</button>
    </form>

    <table>
        <tr>
            <th>Número de Equipo</th>
            <th>Fecha de Alta</th>
            <th>Tipo de Mantenimiento</th>
            <th>Código Postal</th>
            <th>Provincia</th>
            <th>Localidad</th>
            <th>Dirección</th>
            <th>Usuario Responsable</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Serie</th>
            <th>Observaciones</th>
            <th>Ubicación</th>
            <th>Costo</th>
            <th>Acciones</th>
        </tr>

        <?php while ($row = $query->fetch()): ?>
            <tr>
                <td><?= ($row['numEquipo']); ?></td>
                <td><?= ($row['fechaAlta']); ?></td>
                <td><?= ($row['tipoMantenimiento']); ?></td>
                <td><?= ($row['cp']); ?></td>
                <td><?= ($row['provincia']); ?></td>
                <td><?= ($row['localidad']); ?></td>
                <td><?= ($row['direccion']); ?></td>
                <td><?= ($row['usuario']); ?></td>
                <td><?= ($row['marca']); ?></td>
                <td><?= ($row['modelo']); ?></td>
                <td><?= ($row['serie']); ?></td>
                <td><?= ($row['observaciones']); ?></td>
                <td><?= ($row['ubicacion']); ?></td>
                <td><?= ($row['costo']); ?></td>
                <td><button>Crear Incidencia</button></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <p><a href="../home.php">Volver al home</a></p>
</body>
</html>
