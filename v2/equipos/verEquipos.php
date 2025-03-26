<?php
try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
    $filtroMantenimiento = isset($_GET['tipoMantenimiento']) ? $_GET['tipoMantenimiento'] : '';
    if ($filtroMantenimiento && $filtroMantenimiento !== 'todos') {
        $sql = "SELECT e.*, u.usuario FROM Equipos e LEFT JOIN Usuarios u ON e.idUsuario = u.idUsuarios WHERE e.tipoMantenimiento = :filtro";
        $query = $bd->prepare($sql);
        $query->execute([':filtro' => $filtroMantenimiento]);
    } else {
        $sql = "SELECT e.*, u.usuario FROM Equipos e LEFT JOIN Usuarios u ON e.idUsuario = u.idUsuarios";
        $query = $bd->query($sql);
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Equipos</title>
</head>
<body>
    <h1>Lista de Equipos</h1>
    <form method="get" action="">
        <label for="tipoMantenimiento">Filtrar por Tipo de Mantenimiento:</label>
        <select name="tipoMantenimiento" id="tipoMantenimiento">
            <option value="todos">Todos</option>
            <option value="mantenimientoCompleto" 
                <?php if($filtroMantenimiento=='mantenimientoCompleto') echo 'selected'; ?>>
                Mantenimiento Completo
            </option>
            <option value="mantenimientoManoObra"
                <?php if($filtroMantenimiento=='mantenimientoManoObra') echo 'selected'; ?>>
                Mantenimiento Mano de Obra
            </option>
        </select>
        <button type="submit">Filtrar</button>
    </form>

    <br>

    <table border="1">
        <tr>
            <th>ID</th>
            <th>Número de Equipo</th>
            <th>Fecha de Alta</th>
            <th>Descripción</th>
            <th>Tipo de Mantenimiento</th>
            <th>CP</th>
            <th>Provincia</th>
            <th>Localidad</th>
            <th>Dirección</th>
            <th>Usuario Responsable</th>
            <th>Creación de incidencias</th>
        </tr>
        <?php while ($row = $query->fetch()): ?>
            <tr>
                <td><?php echo $row['idEquipos']; ?></td>
                <td><?php echo $row['numEquipo']; ?></td>
                <td><?php echo $row['fechaAlta']; ?></td>
                <td><?php echo $row['descripcion']; ?></td>
                <td><?php echo $row['tipoMantenimiento']; ?></td>
                <td><?php echo $row['cp']; ?></td>
                <td><?php echo $row['provincia']; ?></td>
                <td><?php echo $row['localidad']; ?></td>
                <td><?php echo $row['direccion']; ?></td>
                <td><?php echo $row['usuario']; ?></td>
                <td><button>Crear Incidencia</button></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
