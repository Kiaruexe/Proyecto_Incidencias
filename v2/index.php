<?php
session_start();
$mensaje = "Index";

if (isset($_SESSION['idUsuario'])) {
  try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');

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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mapache Security</title>
  <link rel="icon" href="multimedia/logo-mapache.png" type="image/png">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html,
    body {
      height: 100%;
      font-family: Arial, sans-serif;
    }

    body {
      background-color: #f2f2f2;
      display: flex;
      flex-direction: column;
    }

    .nav {
      background-color: #00225a;
      padding: 0.5rem;
      text-align: center;
    }

    .nav .brand {
      color: white;
      font-size: 2.5rem;
      font-weight: bold;
    }

    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      padding: 12rem 2rem 2rem;
      text-align: center;
    }

    .main-content form {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .logo {
      max-width: 200px;
      margin-bottom: 2rem;
    }

    .login-btn {
      display: inline-block;
      background-color: #2573fa;
      color: black;
      padding: 0.6rem 2rem;
      font-size: 1.4rem;
      text-decoration: none;
      border-radius: 12px;
      margin: 1.5rem 0;
      font-weight: bold;
      min-width: 300px;
      text-align: center;
    }

    .login-btn:hover {
      background-color: #0056b3;
    }

    .main-content form p {
      margin-top: 1rem;
      font-size: 1.1rem;
    }

    /* FOOTER */
    footer.footer {
      background-color: #000;
      color: white;
      padding: 1rem 2rem;
      text-align: center;
      width: 100%;
    }

    footer.footer .footer-content {
      position: relative;
      left: 20px;
      display: inline-block;
    }

    .footer a {
      color: white;
      text-decoration: none;
    }

    .footer .conocenos {
      font-size: 1.2rem;
      font-weight: bold;
      margin: 0 1.5rem;
    }

    /* RESPONSIVE TABLET */
    @media (max-width: 768px) {
      .nav .brand {
        font-size: 2rem;
      }

      .main-content {
        padding: 8rem 1rem 2rem;
      }

      .logo {
        max-width: 150px;
        margin-bottom: 1.5rem;
      }

      .login-btn {
        min-width: 80%;
        font-size: 1.2rem;
        padding: 0.6rem 1rem;
      }

      .main-content form p {
        font-size: 1rem;
      }

      .footer .conocenos {
        display: block;
        margin: 1rem 0;
      }

      footer.footer .footer-content {
        left: 0;
        text-align: center;
        width: 100%;
      }
    }

    /* RESPONSIVE MÓVIL */
    @media (max-width: 480px) {
      .nav .brand {
        font-size: 1.8rem;
      }

      .main-content {
        padding: 6rem 1rem 2rem;
      }

      .logo {
        max-width: 130px;
      }

      .login-btn {
        font-size: 1.1rem;
        padding: 0.5rem 1rem;
      }

      .main-content form p {
        font-size: 0.95rem;
      }
    }

    @media (min-width: 1024px) {
      .logo {
        max-width: 230px;
      }

      .login-btn {
        font-size: 1.6rem;
        min-width: 320px;
        padding: 0.7rem 2rem;
      }

      .main-content form p {
        font-size: 1.25rem;
      }
    }
  </style>
</head>

<body>
  <header>
    <nav class="nav">
      <span class="brand">Mapache Security</span>
    </nav>
  </header>

  <main class="main-content">
    <form action="" method="">
      <img src="multimedia/logo-mapache.png" alt="Logo Mapache" class="logo" />
      <a href="login.php" class="login-btn">LOGIN</a>
      <p>Texto informativo</p>
    </form>
  </main>

  <footer class="footer">
    <div class="footer-content">
      <span>&copy; Copyright 2025</span>
      <a href="https://www.mapachesecurity.com/" class="conocenos">Conócenos</a>
      <span>Tfno Soporte: 634 804 659</span>
    </div>
  </footer>
</body>

</html>