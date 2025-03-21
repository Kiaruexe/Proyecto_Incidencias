<?php
session_start();
if (!isset($_SESSION["idUsuario"])) {
    header("Location: ../login.php");
    exit;
}
try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
    $query = $bd->query("SELECT * FROM Usuarios WHERE idUsuario = " . $_SESSION['idUsuario']);
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $permiso = $row['permiso'];
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
if (isset($_POST["crear"])) {
    function limpiarCampo($valor)
    {
        return !empty($valor) ? $valor : null;
    }
    $nEquipo = limpiarCampo($_POST['nEquipo']);
    $incidencia = $_POST['incidencia'] ?? null;
    $tecnicoAsignado = limpiarCampo($_POST['tecnico']);
    $observaciones = null;
    $TDesplazamient = null;
    $TIntervencion = null;
    $tipoFinanciacion = null;
    $idUsuario = null;
    $idEquipo = null;
    $fecha = date('Y-m-d H:i:s');
    $estado = false;

    try {
        $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com:3306;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');
        $sql = "INSERT INTO Incidencias (
            fecha, estado, tecnicoAsignado, observaciones, TDesplazamient, TIntervencion,
            tipoFinanciacion, idUsuario, idEquipo
        ) VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $bd->prepare($sql);
        $stmt->execute([
            $fecha,
            $estado,
            $tecnicoAsignado,
            $observaciones,
            $TDesplazamient,
            $TIntervencion,
            $tipoFinanciacion,
            $idUsuario,
            $idEquipo
        ]);
        echo "<p style='color:green;'>Incidencia registrada con éxito.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error al registrar: " . $e->getMessage() . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Incidencias</title>
</head>
<body>
    <form method="POST">
        <h1>Crear Incidencias</h1>
        <p>Nº Equipo: <input type="text" placeholder="Introduzca el nº del equipo" name="nEquipo"></p>
        <span>Incidencia:</span>
        <p>
            <textarea name="incidencia" placeholder="Introduzca la incidencia" required></textarea>
        </p>
        <p>
            <?php if ($permiso == 'admin' || $permiso == 'jefeTecnico'): ?>
                <select name="tecnico">
                    <?php
                    try {
                        $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
                        $queryTecnico = $bd->query("SELECT idUsuario, usuario FROM Usuarios WHERE permiso = 'tecnico'");
                        while ($tec = $queryTecnico->fetch()) {
                            echo "<option value='{$tec['idUsuario']}'>{$tec['usuario']}</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option value=''>Error cargando técnicos</option>";
                    }
                    ?>
                </select>
            <?php endif ?>
        </p>
        <p><input type="submit" value="Crear incidencia" name="crear"></p>
    </form>
</body>
</html>
