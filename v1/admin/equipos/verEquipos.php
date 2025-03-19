<?php
try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
    $query = $bd->query("SELECT e.*, u.usuario FROM Equipos e LEFT JOIN Usuarios u ON e.idUsuario = u.idUsuarios");
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Equipos</title>
</head>

<body>
    <h1>Lista de Equipos</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Número de Equipo</th>
            <th>Fecha de Alta</th>
            <th>Descripción</th>
            <th>CP</th>
            <th>Provincia</th>
            <th>Localidad</th>
            <th>Usuario Responsable</th>
        </tr>
        <?php while ($row = $query->fetch()): ?>
            <tr>
                <td><?php echo $row['idEquipos']; ?></td>
                <td><?php echo $row['numEquipo']; ?></td>
                <td><?php echo $row['fechaAlta']; ?></td>
                <td><?php echo $row['descripcion']; ?></td>
                <td><?php echo $row['cp']; ?></td>
                <td><?php echo $row['provincia']; ?></td>
                <td><?php echo $row['localidad']; ?></td>
                <td><?php echo $row['usuario']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>