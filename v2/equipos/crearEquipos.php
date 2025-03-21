<?php
// -- PROCESO DE REGISTRO (PHP) --
if (isset($_POST['registrar'])){ 
    // Recoger los datos del formulario
    $numEquipo          = $_POST['numEquipo']        ?? null;
    $fechaAlta          = $_POST['fechaAlta']        ?? null;
    $descripcion        = $_POST['descripcion']      ?? null;
    $cp                 = $_POST['cp']               ?? null;
    $provincia          = $_POST['provincia']        ?? null;  // Corregido nombre de campo
    $localidad          = $_POST['localidad']        ?? null;
    $idUsuario          = $_POST['idUsuario']        ?? null;

    // Validación básica
    if (!$numEquipo || !$fechaAlta || !$descripcion || !$cp || !$provincia || !$localidad || !$idUsuario) {
        echo "<p style='color:red;'>Por favor, complete todos los campos.</p>";
    } else {
        try {
            // Establecer la conexión a la base de datos
            $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com:3306;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');
            $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Consulta SQL para insertar datos
            $sql = "INSERT INTO Equipos (numEquipo, fechaAlta, descripcion, cp, provincia, localidad, idUsuario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            // Preparar la consulta
            $stmt = $bd->prepare($sql);
            $stmt->execute([
                $numEquipo,
                $fechaAlta,
                $descripcion,
                $cp,
                $provincia,
                $localidad,
                $idUsuario
            ]);

            echo "<p style='color:green;'>Equipo registrado con éxito.</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Error al registrar: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registro de Equipos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<h1>Registrar nuevo equipo</h1>
<form method="post">

    <label>numEquipo:</label><br>
    <input type="text" name="numEquipo" required><br><br>

    <label>Fecha de alta:</label><br>
    <input type="date" name="fechaAlta" required><br><br>

    <label>Descripción:</label><br>
    <input type="text" name="descripcion" required><br><br>

    <label>CP:</label><br>
    <input type="number" name="cp" required><br><br>

    <label>Provincia:</label><br>
    <input type="text" name="provincia" required><br><br>  <!-- Corregido nombre de campo -->

    <label>Localidad:</label><br>
    <input type="text" name="localidad" required><br><br>

    <label>ID de usuario:</label><br>
    <input type="text" name="idUsuario" required><br><br>

    <input type="submit" name="registrar" value="Registrar Equipo">
</form>
</body>
</html>
