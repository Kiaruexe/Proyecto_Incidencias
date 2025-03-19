<?php
try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
    $query = $bd->query("SELECT * FROM Usuarios");
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Usuarios</title>
</head>

<body>
    <h1>Lista de Usuarios</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Permiso</th>
            <th>Tipo de Contrato</th>
            <th>CP Fiscal</th>
            <th>Provincia Fiscal</th>
            <th>Localidad Fiscal</th>
            <th>CP1</th>
            <th>Provincia1</th>
            <th>Localidad1</th>
            <th>CP2</th>
            <th>Provincia2</th>
            <th>Localidad2</th>
        </tr>
        <?php while ($row = $query->fetch()): ?>
            <tr>
                <td><?php echo $row['idUsuarios']; ?></td>
                <td><?php echo $row['usuario']; ?></td>
                <td><?php echo $row['correo']; ?></td>
                <td><?php echo $row['permiso']; ?></td>
                <td><?php echo $row['tipoContrato']; ?></td>
                <td><?php echo $row['cpFiscal']; ?></td>
                <td><?php echo $row['provinciaFiscal']; ?></td>
                <td><?php echo $row['localidadFiscal']; ?></td>
                <td><?php echo $row['cp1']; ?></td>
                <td><?php echo $row['provincia1']; ?></td>
                <td><?php echo $row['localidad1']; ?></td>
                <td><?php echo $row['cp2']; ?></td>
                <td><?php echo $row['provincia2']; ?></td>
                <td><?php echo $row['localidad2']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>