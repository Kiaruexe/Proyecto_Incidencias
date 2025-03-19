<?php
session_start();
if (isset($_SESSION['email'])) {
    header("Location: cliente/verEquipos.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
<h1>Login</h1>

<form method="post">
    Email: <input type="email" name="correo" required>
    <br><br>
    Contraseña: <input type="password" name="contrasena" required>
    <br><br>
    <input type="submit" name="login" value="Iniciar sesión">
</form>

<?php
if (isset($_POST["login"])) {
    $correo = $_POST["correo"];
    $contrasenaIngresada = $_POST["contrasena"];

    try {
        $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com:3306;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');
        $consulta = $bd->prepare("SELECT * FROM Usuarios WHERE correo = ?");
        $consulta->execute([$correo]);

        if ($consulta->rowCount() === 1) {
            $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

            if (password_verify($contrasenaIngresada, $usuario['contrasena'])) {
                $_SESSION['email'] = $usuario['correo'];
                header("Location: cliente/verEquipos.php");
                exit();
            } else {
                echo "<p style='color:red;'>Contraseña incorrecta.</p>";
            }
        } else {
            echo "<p style='color:red;'>Usuario no encontrado.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error de conexión: " . $e->getMessage() . "</p>";
    }
}
?>
</body>
</html>

