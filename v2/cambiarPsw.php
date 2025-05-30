<?php
session_start();

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

// Procesar formulario
if (isset($_POST['cambiar'])) {
    $nuevaContrasena = $_POST['nuevaContrasena'];
    $confirmacion = $_POST['confirmacion'];

    if ($nuevaContrasena === $confirmacion && !empty($nuevaContrasena)) {
        try {
            $bd = new PDO(
                'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
                'Mapapli',
                '9R%d5cf62'
            );
            $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $hash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
            $sql = "UPDATE Usuarios SET contrasena = ?, restablecer = 0 WHERE idUsuarios = ?";
            $stmt = $bd->prepare($sql);
            $stmt->execute([$hash, $_SESSION['idUsuario']]);

            // Redirigir al inicio tras cambio exitoso
            header("Location: home.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error al actualizar contraseña: " . $e->getMessage();
        }
    } else {
        $error = "Las contraseñas no coinciden o están vacías.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        input {
            display: block;
            margin: 10px auto;
            padding: 10px;
            width: 250px;
            border-radius: 10px;
            border: 1px solid #ccc;
        }
        button {
            padding: 10px 25px;
            border: none;
            background-color: #2573f9;
            color: white;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
        }
        p.error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <form method="post">
        <h2>Restablecer Contraseña</h2>
        <input type="password" name="nuevaContrasena" placeholder="Nueva contraseña" required>
        <input type="password" name="confirmacion" placeholder="Confirmar contraseña" required>
        <button type="submit" name="cambiar">Cambiar</button>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    </form>
</body>
</html>
