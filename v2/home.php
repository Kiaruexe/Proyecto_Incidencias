<?php
session_start();

// Si no hay sesión iniciada, redirige al login
if (!isset($_SESSION["idUsuario"])) {
    header("Location: login.php");
    exit;
}

try {
    // Conexión a la base de datos
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
    
    // Consulta para obtener el permiso del usuario
    $query = $bd->prepare("SELECT permiso FROM Usuarios WHERE idUsuarios = ?");
    $query->execute([$_SESSION['idUsuario']]);
    $user = $query->fetch();

    if (!$user) {
        header("Location: login.php");
        exit;
    }

    // Convierte a minúsculas el permiso para evitar problemas de mayúsculas/minúsculas
    $permiso = strtolower($user['permiso']);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Aquí van tus estilos */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f0f2f5;
        }

        header {
            width: 100%;
            background-color: #000;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 20px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px 60px;
        }

        .content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* Estilos para sección de Cliente, Jefe Técnico y Recepción */
        .cliente-jefe-recepcion {
            display: flex;
            flex-direction: column;
            gap: 15px;
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .cliente-jefe-recepcion a {
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
            transition: background-color 0.3s ease, transform 0.2s;
            text-align: center;
            text-decoration: none;
        }

        .cliente-jefe-recepcion a:hover {
            background-color: #2980b9;
            transform: scale(1.05);
        }

        /* Estilos para sección de Admin */
        .admin {
            display: flex;
            flex-direction: column;
            gap: 20px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .admin-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .admin-section h2 {
            position: relative;
            text-align: center;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .admin-section h2::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 2px;
            background-color: #3498db;
        }

        .admin-section-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .admin a {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s;
            min-width: 120px;
        }

        .admin a:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Estilos para Técnico */
        .tecnico {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .tecnico-header {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-bottom: 15px;
        }

        .tecnico-header h2 {
            text-align: center;
            color: #2c3e50;
            position: relative;
            margin-bottom: 10px;
        }

        .tecnico-header h2::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 2px;
            background-color: #3498db;
        }

        .tecnico a {
            background-color: #2ecc71;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
            transition: background-color 0.3s ease, transform 0.2s;
            text-align: center;
            text-decoration: none;
            width: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tecnico a:hover {
            background-color: #27ae60;
            transform: scale(1.05);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Sin permisos */
        .sin-permiso p {
            color: #e74c3c;
            text-align: center;
            font-weight: bold;
        }

        footer {
            width: 100%;
            background-color: #000;
            color: white;
            text-align: center;
            padding: 10px;
            position: fixed;
            bottom: 0;
            left: 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>
        Mi Aplicación (<?= ucfirst($permiso); ?>)
    </header>

    <main>
        <div class="content">
            <h1>Bienvenido</h1>

            <?php if ($permiso === 'admin'): ?>
                <div class="admin">
                    <div class="admin-section">
                        <h2>Gestión de Equipos</h2>
                        <div class="admin-section-buttons">
                            <a href="equipos/crearEquipos.php">Crear Equipos</a>
                            <a href="equipos/modificarEquipo.php">Modificar Equipos</a>
                            <a href="equipos/verEquipos.php">Ver Equipos</a>
                        </div>
                    </div>
                    <div class="admin-section">
                        <h2>Configuracion</h2>
                        <div class="admin-section-buttons">
                            <a href="configuracion/confTiposEquipo.php">Configurar Equipo</a>
                            <a href="configuracion/confPagos.php">Configurar tipos mantenimiento</a>
                        </div>
                    </div>
                    <div class="admin-section">
                        <h2>Gestión de Incidencias</h2>
                        <div class="admin-section-buttons">
                            <a href="incidencias/crearIncidencias.php">Crear Incidencias</a>
                            <a href="incidencias/verIncidencias.php">Ver Incidencias</a>
                        </div>
                    </div>

                    <div class="admin-section">
                        <h2>Gestión de Usuarios</h2>
                        <div class="admin-section-buttons">
                            <a href="usuarios/crearUsuarios.php">Crear Usuarios</a>
                            <a href="usuarios/modificarUsuarios.php">Modificar Usuarios</a>
                            <a href="usuarios/verUsuarios.php">Ver Usuarios</a>
                        </div>
                    </div>
                </div>

            <?php elseif (in_array($permiso, ['cliente', 'jefetecnico', 'recepcion'])): ?>
                <div class="cliente-jefe-recepcion">
                    <a href="equipos/verEquipos.php">Ver Equipos</a>
                    <a href="incidencias/crearIncidencias.php">Crear Incidencias</a>
                    <a href="incidencias/verIncidencias.php">Ver Incidencias</a>
                </div>

            <?php elseif ($permiso === 'tecnico'): ?>
                <div class="tecnico">
                    <div class="tecnico-header">
                        <h2>Gestión de Incidencias</h2>
                    </div>
                    <a href="incidencias/verIncidencias.php">Ver Incidencias</a>
                </div>

            <?php else: ?>
                <div class="sin-permiso">
                    <p>No tienes permisos para acceder a esta página.</p>
                </div>
            <?php endif; ?>
        </div>
        <p><a href="logout.php">Ir al logout</a></p>
    </main>

    <footer>
        <p>&copy; <?= date('Y'); ?> Mi Aplicación</p>
    </footer>
</body>
</html>
