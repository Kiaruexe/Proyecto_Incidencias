<?php
session_start();
if (isset($_SESSION['email'])) {
    header("Location: ../v2/home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../v2/css/style.css">
    <style>
         body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        background: #f4f4f4;
      }
      nav {
        background: #333;
        color: #fff;
        padding: 10px;
        text-align: center;
      }
      nav h2 {
        margin: 0;
      }
      .container {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }
      main {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
      }
      .login-box {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        width: 300px;
        text-align: center;
      }
      .login-box h1 {
        margin-bottom: 20px;
      }
      .login-box input[type="email"],
      .login-box input[type="password"] {
        width: 100%;
        padding: 8px;
        margin: 8px 0;
      }
      .login-box input[type="submit"] {
        background: #333;
        color: #fff;
        border: none;
        padding: 10px 15px;
        cursor: pointer;
      }
      footer {
        background: #333;
        color: #fff;
        text-align: center;
        padding: 10px;
      }
    </style>
</head>
<body>
  <div class="container">
    <nav>
      <h2>Mi Aplicación</h2>
    </nav>

    <main>
      <div class="login-box">
        <h1>Inicie Sesión</h1>
        <form method="post">
          <input type="email" name="correo" placeholder="Correo" required><br>
          <input type="password" name="contrasena" placeholder="Contraseña" required><br>
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
                    $usuario = $consulta->fetch();
                    if (password_verify($contrasenaIngresada, $usuario['contrasena'])) {
                        $_SESSION['email']     = $usuario['correo'];
                        $_SESSION['idUsuario'] = $usuario['idUsuarios']; 

                        header("Location: ../v2/home.php");
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
      </div>
    </main>

    <footer>
      <p>© 2025 Mi Aplicación</p>
    </footer>
  </div>
</body>
</html>
