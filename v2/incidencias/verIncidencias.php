<?php
try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
    $query = $bd->query("SELECT * FROM Incidencias");
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Incidencias</title>
</head>

<body>
    <h1>Lista de Incidencias</h1>
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
        </tr>
        <?php while ($row = $query->fetch()): ?>
            <tr>
                <td><?php echo $row['idIncidencias']; ?></td>
                <td><?php echo $row['fecha']; ?></td>
                <td><?php echo $row['estado'] ? 'Activo' : 'Inactivo'; ?></td>
                <td><?php echo $row['tecnicoAsignado']; ?></td>
                <td><?php echo $row['observaciones']; ?></td>
                <td><?php echo $row['TDesplazamiento']; ?> min</td>
                <td><?php echo $row['TIntervencion']; ?> min</td>
                <td><?php echo $row['tipoFinanciacion']; ?></td>
                <td><?php echo $row['idUsuario']; ?></td>
                <td><?php echo $row['idEquipo']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>