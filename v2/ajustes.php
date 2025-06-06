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
    <title>Configuración - Mapache Security</title>
    <link rel="icon" href="multimedia/logo-mapache.png" type="image/png">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        /* Root and global styles */
        :root {
            --primary-color: #00225a;
            --accent-blue: #2573fa;
            --accent-green: #2ecc71;
            --accent-yellow: #e5970f;
            --light-bg: #f9f9f9;
            --text-color: #333;
            --border-radius: 10px;
            --spacing: 1rem;
        }
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        a { text-decoration: none; color: inherit; }

        /* Header */
        .header {
            position: relative;
            background: var(--primary-color);
            color: white;
            padding: var(--spacing);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header .logo {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.25rem;
            font-weight: bold;
            z-index: 1;
        }
        .header .user-info,
        .header .home-link {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        .header .user-info i,
        .header .home-link i {
            font-size: 1.25rem;
        }

        /* Main container */
        .container {
            flex: 1;
            padding: calc(var(--spacing) * 2);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .container h1 {
            margin-bottom: calc(var(--spacing) * 1.5);
            color: var(--primary-color);
            font-size: 1.75rem;
        }

        /* Card box */
        .config-box {
            background: white;
            border: 1px solid var(--accent-blue);
            border-radius: var(--border-radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: calc(var(--spacing) * 2);
            width: 100%;
            max-width: 480px;
        }
        .buttons {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--spacing);
        }
        .buttons .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing);
        }
        .btn {
            display: inline-block;
            padding: var(--spacing);
            border: none;
            border-radius: var(--border-radius);
            color: white;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-contadores { background: var(--accent-yellow); }
        .btn-pagos      { background: var(--accent-green); }
        .btn-tipos      { background: var(--accent-blue); }

        /* Footer */
        .footer {
            background: #000;
            color: #fff;
            text-align: center;
            padding: var(--spacing);
            font-size: 0.85rem;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .header {
                flex-direction: column;
                gap: 0.5rem;
            }
            .buttons .row {
                grid-template-columns: 1fr;
            }
            .config-box {
                padding: var(--spacing);
            }
            .header .logo {
                position: static;
                transform: none;
                z-index: 0;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="user-info">
            <i class="bi bi-person-circle"></i>
            <?= htmlspecialchars($nombreUsuario) ?>
        </div>
        <div class="logo">Mapache Security</div>
        <div class="home-link">
            <a href="home.php" title="Inicio"><i class="bi bi-house-fill"></i></a>
        </div>
    </header>

    <main class="container">
        <h1>Menú Configuración</h1>
        <section class="config-box">
            <div class="buttons">
                <a href="configuracion/confContadores.php" class="btn btn-contadores">Contadores</a>
                <div class="row">
                    <a href="configuracion/confPagos.php" class="btn btn-pagos">Tipos de Servicio</a>
                    <a href="configuracion/confTiposEquipo.php" class="btn btn-tipos">Tipos de Equipo</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        &copy; <?= date('Y') ?> Mapache Security
    </footer>
</body>
</html>