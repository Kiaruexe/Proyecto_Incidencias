<?php
session_start();
if (!isset($_SESSION["idUsuario"])) {
    header("Location: ../login.php");
    exit;
}

// Conexión a la base de datos
try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
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

// Verificar si el cliente tiene equipos asignados
$tieneEquipos = false;
if ($permiso == 'cliente') {
    try {
        $queryEquipos = $bd->prepare("SELECT COUNT(*) FROM Equipos WHERE idUsuario = ?");
        $queryEquipos->execute([$_SESSION['idUsuario']]);
        $numEquipos = $queryEquipos->fetchColumn();
        $tieneEquipos = $numEquipos > 0;
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error verificando equipos.</p>";
    }
}

// Procesar el formulario si se envía y el cliente tiene equipos asignados
if (isset($_POST["crear"]) && ($permiso != 'cliente' || $tieneEquipos)) {
    function limpiarCampo($valor) {
        return !empty($valor) ? $valor : null;
    }

    $incidencia = $_POST['incidencia'] ?? null;
    $fecha = date('Y-m-d H:i:s');
    $estado = 0; // False en MySQL
    $observaciones = null;
    $TDesplazamiento = null;
    $TIntervencion = null;
    $tipoFinanciacion = null;
    $tecnicoAsignado = "sin asignar";
    $cp = null;
    $localidad = null;
    $provincia = null;
    $direccion = null;

    $idUsuario = null;
    $idEquipo = null;

    if ($permiso == 'cliente') {
        $idEquipo = limpiarCampo($_POST['equipo']);
        $idUsuario = $_SESSION['idUsuario'];
    } elseif ($permiso == 'receptor') {
        $idUsuario = limpiarCampo($_POST['cliente']);
    } elseif ($permiso == 'admin' || $permiso == 'jefeTecnico') {
        $idUsuario = limpiarCampo($_POST['cliente']);
        $tecnicoAsignado = limpiarCampo($_POST['tecnico']) ?: "sin asignar";
    }

    try {
        $sql = "INSERT INTO Incidencias (
            fecha, estado, tecnicoAsignado, observaciones, TDesplazamiento, TIntervencion,
            tipoFinanciacion, idUsuario, idEquipo, incidencia, cp, localidad, provincia, direccion
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        
        $stmt = $bd->prepare($sql);
        $stmt->execute([
            $fecha, $estado, $tecnicoAsignado, $observaciones, $TDesplazamiento, $TIntervencion,
            $tipoFinanciacion, $idUsuario, $idEquipo, $incidencia, $cp, $localidad, $provincia, $direccion
        ]);

        echo "<p style='color:green;'>Incidencia registrada con éxito.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error al registrar: " . $e->getMessage() . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Incidencias</title>
</head>
<body>
    <form method="POST">
        <h1>Crear Incidencias</h1>
        <p>
            Incidencia:<br>
            <textarea name="incidencia" placeholder="Introduzca la incidencia" required></textarea>
        </p>

        <?php if ($permiso == 'cliente'): ?>
            <?php if ($tieneEquipos): ?>
                <p>
                    Equipo:
                    <select name="equipo" required>
                        <option value="">Seleccione un equipo</option>
                        <?php
                        try {
                            $queryEquipos = $bd->prepare("SELECT idEquipo, numEquipo FROM Equipos WHERE idUsuario = ?");
                            $queryEquipos->execute([$_SESSION['idUsuario']]);
                            while ($eq = $queryEquipos->fetch()) {
                                echo "<option value='{$eq['idEquipo']}'>{$eq['numEquipo']}</option>";
                            }
                        } catch (PDOException $e) {
                            echo "<option value=''>Error cargando equipos</option>";
                        }
                        ?>
                    </select>
                </p>
                <p><input type="submit" value="Crear incidencia" name="crear"></p>
            <?php else: ?>
                <p style="color:red;">No tienes equipos asignados.</p>
            <?php endif; ?>

        <?php elseif ($permiso == 'receptor' || $permiso == 'admin' || $permiso == 'jefeTecnico'): ?>
            <p>
                Cliente:
                <select name="cliente" required>
                    <option value="">Seleccione un cliente</option>
                    <?php
                    try {
                        $queryClientes = $bd->query("SELECT idUsuarios, usuario FROM Usuarios WHERE permiso = 'cliente'");
                        while ($cli = $queryClientes->fetch()) {
                            echo "<option value='{$cli['idUsuarios']}'>{$cli['idUsuarios']} - {$cli['usuario']}</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option value=''>Error cargando clientes</option>";
                    }
                    ?>
                </select>

                <?php if ($permiso == 'receptor' || $permiso == 'admin'): ?>
                    <a href="../usuarios/crearUsuarios.php" target="_blank">
                        <button type="button">Crear Usuario</button>
                    </a>
                <?php endif; ?>
            </p>

            <?php if ($permiso == 'admin' || $permiso == 'jefeTecnico'): ?>
                <p>
                    Técnico:
                    <select name="tecnico">
                        <option value="">sin asignar</option>
                        <?php
                        try {
                            $queryTecnico = $bd->query("SELECT usuario FROM Usuarios WHERE permiso = 'tecnico'");
                            while ($tec = $queryTecnico->fetch()) {
                                echo "<option value='{$tec['usuario']}'>{$tec['usuario']}</option>";
                            }
                        } catch (PDOException $e) {
                            echo "<option value=''>Error cargando técnicos</option>";
                        }
                        ?>
                    </select>
                </p>
            <?php endif; ?>
            
            <p><input type="submit" value="Crear incidencia" name="crear"></p>
        <?php endif; ?>
    </form>
</body>
</html>
