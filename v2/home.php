<?php
session_start();

try {
  $bd = new PDO(
    'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
    'Mapapli',
    '9R%d5cf62'
  );
  $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
  error_log("Conexión BD fallida: " . $e->getMessage());
  die("Error crítico de servidor.");
}

function registrarLog(PDO $bd, string $accion, string $descripcion, string $usuario): void
{
  try {
    $stmt = $bd->prepare("
      INSERT INTO `Log` (`accion`, `descripcion`, `fecha`, `usuario`)
      VALUES (?, ?, NOW(), ?)
    ");
    $stmt->execute([$accion, $descripcion, $usuario]);
  } catch (Exception $e) {
    error_log("Fallo registrarLog(): " . $e->getMessage());
  }
}

set_exception_handler(function (Throwable $e) use ($bd) {
  $usuario = $_SESSION['idUsuario'] ?? 'anónimo';
  $detalle = sprintf(
    "Excepción no capturada: %s en %s:%d\nStack trace:\n%s",
    $e->getMessage(),
    $e->getFile(),
    $e->getLine(),
    $e->getTraceAsString()
  );
  registrarLog($bd, 'error general', $detalle, $usuario);
  http_response_code(500);
  echo "<h1>Lo sentimos, ha ocurrido un error.</h1>";
  exit;
});

if (!isset($_SESSION["idUsuario"])) {
  header("Location: login.php");
  exit;
}

try {
  $query = $bd->prepare("SELECT permiso, usuario FROM Usuarios WHERE idUsuarios = ?");
  $query->execute([$_SESSION['idUsuario']]);
  $user = $query->fetch();

  if (!$user) {
    throw new Exception("Usuario con ID {$_SESSION['idUsuario']} no existe");
  }

  $permiso = strtolower($user['permiso']);
  $nombreUsuario = $user['usuario'];

  registrarLog(
    $bd,
    'entrar en el home',
    "El usuario '{$nombreUsuario}' con permiso '{$permiso}' ha accedido al home.",
    $nombreUsuario
  );

} catch (Exception $e) {
  throw $e;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mapache Security</title>
  <link rel="icon" href="multimedia/logo-mapache.png" type="image/png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
      background: #f0f2f5;
    }

    header {
      width: 100%;
      background: #00225a;
      color: #fff;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      flex-wrap: wrap;
    }

    .header-left,
    .header-right {
      display: flex;
      align-items: center;
    }

    .header-left {
      flex: 1;
      min-width: 180px;
    }

    .header-title {
      flex: 2;
      text-align: center;
      font-size: 2rem;
      font-weight: bold;
    }

    .header-right {
      flex: 1;
      justify-content: flex-end;
      min-width: 100px;
    }

    .header-user {
      display: flex;
      align-items: center;
      color: #fff;
    }

    .header-user i {
      font-size: 1.8rem;
      margin-right: 8px;
    }

    .username {
      font-size: 1rem;
      font-weight: 500;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 120px;
    }

    .header-icon {
      font-size: 1.6rem;
      color: #fff;
      margin-left: 15px;
      cursor: pointer;
    }

    main {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 120px 20px 60px;
    }

    .logo-container {
      margin-bottom: 20px;
    }

    .logo-container img {
      max-width: 150px;
      height: auto;
    }

    .content {
      background: #fff;
      padding: 30px;
      border-radius: 18px;
      border: 2px solid #2573fa;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 600px;
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
      color: #2c3e50;
    }

    .section {
      margin-bottom: 30px;
    }

    .section h2 {
      text-align: center;
      color: #2c3e50;
      margin-bottom: 10px;
      position: relative;
    }

    .section h2::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 50%;
      transform: translateX(-50%);
      width: 50px;
      height: 2px;
      background: #2573fa;
    }

    .btn-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }

    .btn-group a {
      display: inline-block;
      padding: 10px 16px;
      border-radius: 10px;
      text-decoration: none;
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: .5px;
      color: #fff;
      transition: background .3s, transform .2s;
      min-width: 120px;
      text-align: center;
    }

    .btn-create {
      background: #2573fa;
    }

    .btn-view {
      background: #2ecc71;
    }

    .btn-edit {
      background: #f9ab25;
    }

    .btn-create:hover {
      background: #1e60d2;
      transform: translateY(-2px);
    }

    .btn-view:hover {
      background: #27ae60;
      transform: translateY(-2px);
    }

    .btn-edit:hover {
      background: #e5970f;
      transform: translateY(-2px);
    }

    .logout {
      display: block;
      text-align: center;
      margin-top: 20px;
      color: #2573fa;
      text-decoration: none;
    }

    footer {
      width: 100%;
      background: #000;
      color: #fff;
      text-align: center;
      padding: 10px;
      position: fixed;
      bottom: 0;
      left: 0;
      font-size: 14px;
    }

    @media (max-width: 768px) {
      header {
        flex-direction: column;
        padding: 10px 20px;
        text-align: center;
      }

      .header-left,
      .header-right {
        width: 100%;
        justify-content: center;
        margin: 5px 0;
      }

      .header-title {
        order: -1;
        width: 100%;
        font-size: 1.5rem;
        margin-bottom: 10px;
        text-align: center;
      }

      .header-user i {
        font-size: 1.5rem;
      }

      main {
        padding-top: 140px;
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="header-left">
      <div class="header-user">
        <i class="bi bi-person-circle"></i>
        <span class="username"><?php echo htmlspecialchars($nombreUsuario); ?></span>
      </div>
    </div>
    <div class="header-title">Mapache Security</div>
    <div class="header-right">
      <?php if ($permiso === 'admin'): ?>
        <a href="ajustes.php" class="header-icon" style="text-decoration: none;">
          <i class="bi bi-gear-fill"></i>
        </a>
      <?php else: ?>
        <div class="header-icon" style="visibility: hidden;">
          <i class="bi bi-gear-fill"></i>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <main>
    <div class="logo-container">
      <img src="multimedia/logo-mapache.png" alt="Logo Mapache">
    </div>

    <div class="content">
      <h1>¡ Bienvenido !</h1>

      <?php if ($permiso === 'admin'): ?>
        <div class="section">
          <h2>Gestión de Clientes/Usuarios</h2>
          <div class="btn-group">
            <a href="usuarios/verUsuarios.php" class="btn-view">Ver</a>
            <a href="usuarios/modificarUsuarios.php" class="btn-edit">Modificar</a>
            <a href="usuarios/crearUsuarios.php" class="btn-create">Crear</a>
          </div>
        </div>
        <div class="section">
          <h2>Gestión de Equipos</h2>
          <div class="btn-group">
            <a href="equipos/verEquipos.php" class="btn-view">Ver</a>
            <a href="equipos/modificarEquipo.php" class="btn-edit">Modificar</a>
            <a href="equipos/crearEquipos.php" class="btn-create">Crear</a>
          </div>
        </div>
        <div class="section">
          <h2>Gestión de Incidencias</h2>
          <div class="btn-group">
            <a href="incidencias/verIncidencias.php" class="btn-view">Ver</a>
            <a href="incidencias/modificarIncidencias.php" class="btn-edit">Modificar</a>
            <a href="incidencias/crearIncidencias.php" class="btn-create">Crear</a>
          </div>
        </div>


      <?php elseif ($permiso === 'recepcion'): ?>

        <div class="section">
          <h2>Gestión de Incidencias</h2>
          <div class="btn-group">
            <a href="incidencias/verIncidencias.php" class="btn-view">Ver</a>
            <a href="incidencias/crearIncidencias.php" class="btn-create">Crear</a>
          </div>
        </div>
        <div class="section">
          <h2>Gestión de Equipos</h2>
          <div class="btn-group">
            <a href="equipos/verEquipos.php" class="btn-view">Ver</a>
            <a href="equipos/modificarEquipo.php" class="btn-edit">Modificar</a>
          </div>
        </div>
      <?php elseif ($permiso === 'cliente'): ?>
        <div class="section">
          <h2>Equipos</h2>
          <div class="btn-group">
            <a href="equipos/verEquipos.php" class="btn-view">Ver</a>
          </div>
        </div>
        <div class="section">
          <h2>Incidencias</h2>
          <div class="btn-group">
            <a href="incidencias/verIncidencias.php" class="btn-view">Ver</a>
            <a href="incidencias/crearIncidencias.php" class="btn-create">Crear</a>
          </div>
        </div>

      <?php elseif ($permiso === 'jefetecnico'): ?>
        <div class="section">
          <h2>Gestión de Equipos</h2>
          <div class="btn-group">
            <a href="equipos/verEquipos.php" class="btn-view">Ver</a>
            <a href="equipos/modificarEquipo.php" class="btn-edit">Modificar</a>
          </div>
        </div>
        <div class="section">
          <h2>Gestión de Incidencias</h2>
          <div class="btn-group">
            <a href="incidencias/verIncidencias.php" class="btn-view">Ver</a>
            <a href="incidencias/modificarIncidencias.php" class="btn-edit">Modificar</a>
            <a href="incidencias/crearIncidencias.php" class="btn-create">Crear</a>
          </div>
        </div>

      <?php elseif ($permiso === 'tecnico'): ?>
        <div class="section">
          <h2>Gestión de Incidencias</h2>
          <div class="btn-group">
            <a href="incidencias/verIncidencias.php" class="btn-view">Ver</a>
            <a href="incidencias/modificarIncidencias.php" class="btn-edit">Modificar</a>
          </div>
        </div>
        <div class="section">
          <h2>Gestión de Equipos</h2>
          <div class="btn-group">
            <a href="equipos/verEquipos.php" class="btn-view">Ver</a>
            <a href="equipos/modificarEquipo.php" class="btn-edit">Modificar</a>
          </div>
        </div>
      <?php else: ?>
        <div class="section sin-permiso">
          <p>No tienes permisos para acceder a esta página.</p>
        </div>
      <?php endif; ?>

      <a href="logout.php" class="logout">Cerrar sesión</a>
    </div>
  </main>

  <footer>&copy; <?= date('Y') ?> Mapache Security</footer>
</body>

</html>