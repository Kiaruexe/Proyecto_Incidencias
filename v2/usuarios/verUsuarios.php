<?php
session_start();

try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
        'Mapapli',
        '9R%d5cf62'
    );
    $filtroPermiso = $_GET['permiso'] ?? 'todos';
    if ($filtroPermiso !== 'todos') {
        $query = $bd->prepare("SELECT * FROM Usuarios WHERE permiso = ?");
        $query->execute([$filtroPermiso]);
    } else {
        $query = $bd->query("SELECT * FROM Usuarios");
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

$isClienteView = $filtroPermiso === 'cliente';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Usuarios</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Lista de Usuarios</h1>

    <form method="get">
        <label for="permiso">Filtrar por Permiso:</label>
        <select name="permiso" id="permiso">
            <option value="todos"    <?= $filtroPermiso==='todos'    ? 'selected' : '' ?>>Todos</option>
            <option value="cliente"  <?= $filtroPermiso==='cliente'  ? 'selected' : '' ?>>Cliente</option>
            <option value="recepcion"<?= $filtroPermiso==='recepcion'? 'selected' : '' ?>>Recepción</option>
            <option value="tecnico"  <?= $filtroPermiso==='tecnico'  ? 'selected' : '' ?>>Técnico</option>
            <option value="admin"    <?= $filtroPermiso==='admin'    ? 'selected' : '' ?>>Admin</option>
            <option value="jefeTecnico" <?= $filtroPermiso==='jefeTecnico'? 'selected' : '' ?>>Jefe Técnico</option>
        </select>
        <button type="submit">Filtrar</button>
    </form>
    <br>

    <?php if ($isClienteView): ?>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Permiso</th>
                <th>CP Fiscal</th>
                <th>Provincia Fiscal</th>
                <th>Localidad Fiscal</th>
                <th>Dirección Fiscal</th>
                <th>CP1</th>
                <th>Provincia1</th>
                <th>Localidad1</th>
                <th>Dirección1</th>
                <th>CP2</th>
                <th>Provincia2</th>
                <th>Localidad2</th>
                <th>Dirección2</th>
            </tr>
            <?php while ($row = $query->fetch()): ?>
            <tr>
                <td><?= htmlspecialchars($row['idUsuarios']) ?></td>
                <td><?= htmlspecialchars($row['usuario']) ?></td>
                <td><?= htmlspecialchars($row['correo']) ?></td>
                <td><?= htmlspecialchars($row['permiso']) ?></td>
                <td><?= htmlspecialchars($row['cpFiscal']) ?></td>
                <td><?= htmlspecialchars($row['provinciaFiscal']) ?></td>
                <td><?= htmlspecialchars($row['localidadFiscal']) ?></td>
                <td><?= htmlspecialchars($row['direccionFiscal']) ?></td>
                <td><?= htmlspecialchars($row['cp1']) ?></td>
                <td><?= htmlspecialchars($row['provincia1']) ?></td>
                <td><?= htmlspecialchars($row['localidad1']) ?></td>
                <td><?= htmlspecialchars($row['direccion1']) ?></td>
                <td><?= htmlspecialchars($row['cp2']) ?></td>
                <td><?= htmlspecialchars($row['provincia2']) ?></td>
                <td><?= htmlspecialchars($row['localidad2']) ?></td>
                <td><?= htmlspecialchars($row['direccion2']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Correo</th>
            </tr>
            <?php while ($row = $query->fetch()): ?>
            <tr>
                <td><?= htmlspecialchars($row['idUsuarios']) ?></td>
                <td><?= htmlspecialchars($row['usuario']) ?></td>
                <td><?= htmlspecialchars($row['correo']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</body>
</html>
