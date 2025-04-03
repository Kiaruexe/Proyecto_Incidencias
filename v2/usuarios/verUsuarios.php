<?php
try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');

    $filtroPermiso = isset($_GET['permiso']) ? $_GET['permiso'] : 'todos'; // Valor por defecto: 'todos'
    if ($filtroPermiso && $filtroPermiso !== 'todos') {
        $query = $bd->prepare("SELECT * FROM Usuarios WHERE permiso = ?");
        $query->execute([$filtroPermiso]);
    } else {
        $query = $bd->query("SELECT * FROM Usuarios");
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ver Usuarios</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Lista de Usuarios</h1>

    <form method="get" action="">
        <label for="permiso">Filtrar por Permiso:</label>
        <select name="permiso" id="permiso">
            <option value="todos">Todos</option>
            <option value="cliente"     <?php if($filtroPermiso=='cliente') echo 'selected';?>>Cliente</option>
            <option value="recepcion"   <?php if($filtroPermiso=='recepcion') echo 'selected';?>>Recepción</option>
            <option value="tecnico"     <?php if($filtroPermiso=='tecnico') echo 'selected';?>>Técnico</option>
            <option value="admin"       <?php if($filtroPermiso=='admin') echo 'selected';?>>Admin</option>
            <option value="jefeTecnico" <?php if($filtroPermiso=='jefeTecnico') echo 'selected';?>>Jefe Técnico</option>
        </select>
        <button type="submit">Filtrar</button>
    </form>
    <br>

    <table border="1">
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Permiso</th>
            <?php if($filtroPermiso=='cliente' || $filtroPermiso=='todos'): ?>
                <th>CP Fiscal</th>
                <th>Provincia Fiscal</th>
                <th>Localidad Fiscal</th>
                <th>Direccion Fiscal</th>
                <th>CP1</th>
                <th>Provincia1</th>
                <th>Localidad1</th>
                <th>Direccion1</th>
                <th>CP2</th>
                <th>Provincia2</th>
                <th>Localidad2</th>
                <th>Direccion2</th>
            <?php endif; ?>
        </tr>
        <?php while ($row = $query->fetch()): ?>
            <tr>
                <td><?php echo $row['idUsuarios']; ?></td>
                <td><?php echo $row['usuario']; ?></td>
                <td><?php echo $row['correo']; ?></td>
                <td><?php echo $row['permiso']; ?></td>
                <?php if($filtroPermiso=='cliente' || $filtroPermiso=='todos'): ?>
                    <td><?php echo $row['cpFiscal']; ?></td>
                    <td><?php echo $row['provinciaFiscal']; ?></td>
                    <td><?php echo $row['localidadFiscal']; ?></td>
                    <td><?php echo $row['direccionFiscal']; ?></td>
                    <td><?php echo $row['cp1']; ?></td>
                    <td><?php echo $row['provincia1']; ?></td>
                    <td><?php echo $row['localidad1']; ?></td>
                    <td><?php echo $row['direccion1']; ?></td>
                    <td><?php echo $row['cp2']; ?></td>
                    <td><?php echo $row['provincia2']; ?></td>
                    <td><?php echo $row['localidad2']; ?></td>
                    <td><?php echo $row['direccion2']; ?></td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>