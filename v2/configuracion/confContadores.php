<?php
session_start();

if (!isset($_SESSION['idUsuario'])) {
    header('Location: /login.php');
    exit;
}

try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
        'Mapapli',
        '9R%d5cf62'
    );

    $qU = $bd->prepare("SELECT permiso, usuario FROM Usuarios WHERE idUsuarios = ?");
    $qU->execute([$_SESSION['idUsuario']]);
    $row = $qU->fetch();

    if (!$row || strtolower($row['permiso']) !== 'admin') {
        header('Location: ../error.php');
        exit;
    }

    $nombreUsuario = $row['usuario'] ?? $_SESSION['idUsuario'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['nombre'], $_POST['valor'])) {
            for ($i = 0; $i < count($_POST['nombre']); $i++) {
                $nombre = trim($_POST['nombre'][$i]);
                $valor = intval($_POST['valor'][$i]);

                if ($nombre !== '') {
                    // Solo actualizar si ya existe
                    $qUpdate = $bd->prepare("UPDATE Contadores SET valor = ? WHERE nombre = ?");
                    $qUpdate->execute([$valor, $nombre]);
                }
            }
        }
        // Alerta y redirección al home tras guardar cambios
        echo "<script>
                alert('Cambios guardados correctamente.');
                window.location = '../home.php';
              </script>";
        exit;
    }

    $qContadores = $bd->query("SELECT * FROM Contadores");
    $contadores = $qContadores->fetchAll();

} catch (PDOException $e) {
    header('Location: ../error.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Contadores - Mapache Security</title>
    <link rel="icon" href="/multimedia/logo-mapache.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header {
            background-color: #00225a;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-logo {
            text-align: center;
            flex-grow: 1;
            font-weight: bold;
            font-size: 1.5em;
        }
        .header-user, .header-home {
            width: 150px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header-home {
            justify-content: flex-end;
        }
        .header a {
            color: white;
            text-decoration: none;
        }
        .header i { font-size: 1.2em; }

        .container {
            flex: 1;
            max-width: 900px;
            margin: 0 auto;
            padding: 80px 20px 20px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
        }
        h1 {
            color: #00225a;
            margin-bottom: 30px;
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 70%;
            margin: 0 auto 40px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: left;
            width: 50%;
        }
        th {
            background-color: #2573fa;
            color: white;
            font-weight: bold;
        }
        input[type="number"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        .btn-confirmar {
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 16px;
        }
        .btn-volver {
            background-color: #e5970f;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            font-size: 14px;
            margin-top: 10px;
        }
        .footer {
            background-color: #000;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-user">
            <i class="fas fa-user"></i>
            <?= htmlspecialchars($nombreUsuario) ?>
        </div>
        <div class="header-logo">
            Mapache Security
        </div>
        <div class="header-home">
            <a href="../home.php" title="Inicio"><i class="fas fa-home"></i></a>
        </div>
    </header>

    <div class="container">
        <h1>Configuración de Contadores</h1>

        <form method="post">
            <table>
                <tr>
                    <th>Nombre</th>
                    <th>Valor</th>
                </tr>
                <?php foreach ($contadores as $contador): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($contador['nombre']) ?>
                            <input type="hidden" name="nombre[]" value="<?= htmlspecialchars($contador['nombre']) ?>">
                        </td>
                        <td><input type="number" name="valor[]" value="<?= intval($contador['valor']) ?>" required></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="buttons">
                <button type="submit" class="btn-confirmar"><b>CONFIRMAR CAMBIOS</b></button>
                <a href="../home.php" class="btn-volver"><b>VOLVER AL MENÚ</b></a>
            </div>
        </form>
    </div>

    <footer class="footer">
        &copy; <?= date('Y') ?> Mapache Security
    </footer>
</body>
</html>
