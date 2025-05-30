<?php
session_start();
if (isset($_SESSION['idUsuario'])) {
    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="multimedia/logo-mapache.png" type="image/png">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
            font-family: Arial, sans-serif;
        }

        .container {
            text-align: center;
        }

        .logo {
            max-width: 250px;
            margin-bottom: 10px;
        }

        h1 {
            color: black;
            font-size: 40px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 0;
        }

        input[type="email"],
        input[type="password"] {
            width: 350px;
            padding: 14px;
            margin: 12px 0;
            border-radius: 25px;
            border: 1px solid #ccc;
            font-size: 14px;
            text-align: center;
        }

        input::placeholder {
            font-size: 12px;
        }

        .btn-login {
            background-color: #2573f9;
            color: black;
            border: none;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            width: 220px;
            text-transform: uppercase;
            transition: background-color 0.3s, transform 0.3s;
        }

        .btn-login:hover {
            background-color: #1a5fd1;
            transform: scale(1.03);
        }

        p.error {
            color: red;
            margin-top: 15px;
        }

        @media (max-width: 480px) {
            .container {
                width: 90%;
                padding: 20px 10px;
            }

            .logo {
                max-width: 120px;
            }

            h1 {
                font-size: 24px;
                margin-bottom: 5px;
            }

            input[type="email"],
            input[type="password"] {
                width: 85%;
                font-size: 12px;
                padding: 10px;
                margin: 8px 0;
            }

            .btn-login {
                width: 70%;
                padding: 10px;
                font-size: 14px;
                margin-top: 15px;
            }

            p.error {
                font-size: 12px;
                margin-top: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <img src="multimedia/logo-mapache.png" alt="Logo Mapache" class="logo" />

        <h1>Login</h1>

        <form method="post">
            <input type="email" name="correo" placeholder="EMAIL" required>
            <input type="password" name="contrasena" placeholder="CONTRASEÑA" required>
            <input type="submit" name="login" value="Iniciar sesión" class="btn-login">
        </form>

        <?php
        if (isset($_POST["login"])) {
            $correo = $_POST["correo"];
            $contrasenaIngresada = $_POST["contrasena"];

            try {
                $bd = new PDO(
                    'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
                    'Mapapli',
                    '9R%d5cf62'
                );

                // AÑADIMOS 'restablecer' A LA CONSULTA
                $consulta = $bd->prepare("SELECT idUsuarios, contrasena, restablecer FROM Usuarios WHERE correo = ?");
                $consulta->execute([$correo]);
                $usuario = $consulta->fetch();

                if ($usuario && password_verify($contrasenaIngresada, $usuario['contrasena'])) {
                    $_SESSION['idUsuario'] = $usuario['idUsuarios'];

                    // SI TIENE QUE CAMBIAR CONTRASEÑA, REDIRIGE A cambiarPsw.php
                    if (!empty($usuario['restablecer']) && $usuario['restablecer'] == 1) {
                        header("Location: cambiarPsw.php");
                    } else {
                        header("Location: home.php");
                    }

                    exit();
                } else {
                    echo "<p class='error'>Usuario o contraseña incorrectos.</p>";
                }
            } catch (PDOException $e) {
                echo "<p class='error'>Error interno: " . $e->getMessage() . "</p>";
            }
        }
        ?>
    </div>
</body>

</html>
