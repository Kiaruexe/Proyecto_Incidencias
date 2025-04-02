<?php
session_start();
$mensaje = "Index";

if (isset($_SESSION['idUsuario'])) {
    try {
        $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');

        // Obtener el nombre del usuario usando el nombre correcto de la columna: idUsuarios
        $consulta = $bd->prepare("SELECT usuario FROM Usuarios WHERE idUsuarios = ?");
        $consulta->execute([$_SESSION['idUsuario']]);
        $usuario = $consulta->fetch();

        if ($usuario) {
            $mensaje = "Index de " . htmlspecialchars($usuario['usuario']);
        }
    } catch (PDOException $e) {
        $mensaje = "Index (Error al obtener usuario)";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../v2/css/style.css">
</head>
<header>
    <nav>
        <img src="" alt="">
        <span>Nombre empresa</span>
    </nav>
</header>
<body>
    <form action="" method="">
        <h1><?php echo $mensaje; ?></h1>
        <a href="login.php">Ir al login</a>
        <p><a href="logout.php">Ir al logout</a></p>
        <p><a href="home.php">Home</a></p>
    </form>
    <button>IR AL HOME</button>
</body>
<footer>
    <span>&copy; Copyright 2025</span>
</footer>
</html>
