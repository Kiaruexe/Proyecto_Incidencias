<?php
session_start();
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../login.php");
    exit;
}

// Procesar solicitud de borrado AJAX si es recibida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'borrarUsuario' && isset($_POST['id'])) {
    try {
        $bd = new PDO(
            'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62' );
        $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $idUsuario = $_POST['id'];
        
        // Preparar y ejecutar la consulta de eliminación
        $sql = "DELETE FROM Usuarios WHERE idUsuarios = ?";
        $stmt = $bd->prepare($sql);
        $resultado = $stmt->execute([$idUsuario]);
        
        // Devolver respuesta JSON
        header('Content-Type: application/json');
        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el usuario']);
        }
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario: ' . $e->getMessage()]);
        exit;
    }
}

// Conexión a la base de datos para operaciones normales
try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62' );
    $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error de conexión: " . $e->getMessage() . "</p>";
    exit;
}

// Obtener todos los usuarios
try {
    $sql = "SELECT idUsuarios, usuario, correo, permiso FROM Usuarios";
    $stmt = $bd->prepare($sql);
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error al obtener usuarios: " . $e->getMessage() . "</p>";
    exit;
}

// Si hay un ID específico, continuar con la modificación del usuario
if (isset($_GET['id'])) {
    $idUsuarioModificar = $_GET['id'];
    try {
        $sql = "SELECT * FROM Usuarios WHERE idUsuarios = ?";
        $stmt = $bd->prepare($sql);
        $stmt->execute([$idUsuarioModificar]);
        $usuarioData = $stmt->fetch();
        if (!$usuarioData) {
            echo "<p style='color:red;'>Usuario no encontrado.</p>";
            exit;
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error al obtener el usuario: " . $e->getMessage() . "</p>";
        exit;
    }

    function limpiarCampo($valor) {
        return !empty($valor) ? $valor : null;
    }

    function obtenerValor($campo, $actual) {
        return isset($_POST[$campo]) && trim($_POST[$campo]) !== ''
            ? trim($_POST[$campo])
            : $actual;
    }

    if (isset($_POST['modificar'])) {
        $usuario         = obtenerValor('usuario', $usuarioData['usuario']);
        $correo          = obtenerValor('correo', $usuarioData['correo']);
        $contrasenaTexto = $_POST['contrasena'] ?? '';
        $permiso         = obtenerValor('permiso', $usuarioData['permiso']);

        $cpFiscal        = limpiarCampo(obtenerValor('cpFiscal', $usuarioData['cpFiscal']));
        $provinciaFiscal = limpiarCampo(obtenerValor('provinciaFiscal', $usuarioData['provinciaFiscal']));
        $localidadFiscal = limpiarCampo(obtenerValor('localidadFiscal', $usuarioData['localidadFiscal']));
        $direccionFiscal = limpiarCampo(obtenerValor('direccionFiscal', $usuarioData['direccionFiscal']));

        $cp1             = limpiarCampo(obtenerValor('cp1', $usuarioData['cp1']));
        $provincia1      = limpiarCampo(obtenerValor('provincia1', $usuarioData['provincia1']));
        $localidad1      = limpiarCampo(obtenerValor('localidad1', $usuarioData['localidad1']));
        $direccion1      = limpiarCampo(obtenerValor('direccion1', $usuarioData['direccion1']));

        $cp2             = limpiarCampo(obtenerValor('cp2', $usuarioData['cp2']));
        $provincia2      = limpiarCampo(obtenerValor('provincia2', $usuarioData['provincia2']));
        $localidad2      = limpiarCampo(obtenerValor('localidad2', $usuarioData['localidad2']));
        $direccion2      = limpiarCampo(obtenerValor('direccion2', $usuarioData['direccion2']));

        if (!empty($contrasenaTexto)) {
            $contrasenaHash = password_hash($contrasenaTexto, PASSWORD_DEFAULT);
        } else {
            $contrasenaHash = $usuarioData['contrasena'];
        }

        try {
            $sqlUpdate = "UPDATE Usuarios SET
                usuario = ?, correo = ?, contrasena = ?, permiso = ?,
                cpFiscal = ?, provinciaFiscal = ?, localidadFiscal = ?, direccionFiscal = ?,
                cp1 = ?, provincia1 = ?, localidad1 = ?, direccion1 = ?,
                cp2 = ?, provincia2 = ?, localidad2 = ?, direccion2 = ?
                WHERE idUsuarios = ?";
            $stmtUpdate = $bd->prepare($sqlUpdate);
            $stmtUpdate->execute([
                $usuario, $correo, $contrasenaHash, $permiso,
                $cpFiscal, $provinciaFiscal, $localidadFiscal, $direccionFiscal,
                $cp1, $provincia1, $localidad1, $direccion1,
                $cp2, $provincia2, $localidad2, $direccion2,
                $idUsuarioModificar
            ]);
            echo "<script>alert('Usuario modificado con éxito.');</script>";
            $usuarioData = array_merge($usuarioData, [
                'usuario' => $usuario,
                'correo' => $correo,
                'permiso' => $permiso,
                'cpFiscal' => $cpFiscal,
                'provinciaFiscal' => $provinciaFiscal,
                'localidadFiscal' => $localidadFiscal,
                'direccionFiscal' => $direccionFiscal,
                'cp1' => $cp1,
                'provincia1' => $provincia1,
                'localidad1' => $localidad1,
                'direccion1' => $direccion1,
                'cp2' => $cp2,
                'provincia2' => $provincia2,
                'localidad2' => $localidad2,
                'direccion2' => $direccion2
            ]);
        } catch (PDOException $e) {
            echo "<script>alert('Error al modificar usuario: " . $e->getMessage() . "');</script>";
        }
    }
    
    // Mostrar el formulario de modificación de usuario
    ?>
    <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Modificar Usuario</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
       * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

   body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: white;
        min-height: 100vh;
        color: #333;
        display: flex;
        flex-direction: column;
    }

    /* Header principal */
    .header-mapache {
      background: #002255;
      color: white;
      padding: 15px 0;
      text-align: center;
      position: relative;
      flex-shrink: 0; /* Evita que el header se comprima */
    }

    .header-mapache h1 {
      font-size: 32px;
      font-weight: bold;
      margin: 0;
    }

    /* Icono de casa en la esquina superior derecha */
    .home-icon {
        position: absolute;
        top: 15px;
        right: 20px;
        text-decoration: none;
        transition: background-color 0.3s ease;
        padding: 5px;
        border-radius: 4px;
    }
    .home-icon .fas {
        color: white;
        font-size: 24px;
    }
    .home-icon:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    /* Contenedor principal que crece para empujar el footer */
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-bottom: 20px;
    }

    /* Título del formulario */
    .form-title {
        text-align: center;
        font-size: 2rem;
        color: #333;
        margin: 30px 0;
        font-weight: 600;
        margin-right: 300px;

    }

    /* Contenedor principal del formulario */
    .form-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      flex: 1;
      margin: 0 auto;
      margin-right: 500px;
  }

  .volver-home {
        text-align: center;
        margin-top: 10px;
        margin-right: 300px;
        
    }

    .btn-modificar {
       text-align: center;
        margin-top: 15px;
        margin-left: 300px;
    }

    /* Formulario con fondo azul claro y caja contenedora */
    form {
        background: #e6f3ff;
        padding: 40px;
        border: 3px solid #000;
        border-radius: 0;
        width: 100%;
        max-width: 900px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    /* Grid compacto para los campos principales */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
        margin-bottom: 25px;
        align-items: end;
    }

    /* Grupo de campo más compacto */
    .campo-grupo {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;
    }

    /* Labels más pequeños */
    label {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    /* Inputs más compactos */
    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="number"],
    input[type="date"],
    select,
    textarea {
        padding: 8px 12px;
        border: 2px solid #333;
        border-radius: 4px;
        font-size: 0.9rem;
        font-family: inherit;
        background: white;
        outline: none;
        height: 38px;
        margin-bottom: 10px;
    }

    /* Inputs redondeados para campos específicos */
    input[name*="cp"], 
    input[name*="provincia"], 
    input[name*="localidad"], 
    input[name*="direccion"] {
        border-radius: 25px;
        padding: 8px 16px;
    }

    /* Select normal */
    select:not([multiple]) {
        height: 38px;
    }

    /* Botones */
    .button-container {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-top: 30px;
    }

    .btn,
    input[type="submit"] {
        background: #2563eb;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 25px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .btn:hover,
    input[type="submit"]:hover {
        background: #1d4ed8;
    }

    /* Secciones de direcciones */
    #direcciones-container h3 {
        color: #333;
        margin: 20px 0 10px 0;
        font-size: 1.2rem;
        border-bottom: 2px solid #333;
        padding-bottom: 5px;
    }

    /* Enlaces */
    a {
        color: #2563eb;
        text-decoration: none;
        margin: 20px 0;
        display: inline-block;
    }

    a:hover {
        text-decoration: underline;
    }

    /* Footer fijo en la parte inferior */
    .footer {
        background: rgb(0, 0, 0);
        color: white;
        text-align: center;
        padding: 15px 0;
        font-size: 14px;
        flex-shrink: 0; /* Evita que el footer se comprima */
        margin-top: auto; /* Empuja el footer hacia abajo */
    }

    /* Responsivo mejorado */
    @media (max-width: 768px) {
        .form-container {
            max-width: 100%;
            padding: 0 15px;
        }

        form {
            padding: 25px;
            max-width: 100%;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .button-container {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .header-mapache h1 {
            font-size: 24px;
        }

        .form-title {
            font-size: 1.5rem;
        }
    }

    /* Ajuste para pantallas grandes */
    @media (min-width: 1200px) {
        .form-container {
            max-width: 900px;
        }
    }
    </style>
</head>
<body>
    <!-- Header principal -->
    <div class="header-mapache">
        <h1>Mapache Security</h1>
        <a href="../home.php" class="home-icon">
            <i class="fas fa-home"></i>
        </a>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <h1 class="form-title">Modificar Usuario</h1>
        
        <div class="form-container">
            <form method="post">
                <label>Nombre de usuario:</label>
                <input type="text" name="usuario" placeholder="Cliente actual">

                <label>Correo:</label>
                <input type="email" name="correo" placeholder="correo@ejemplo.com">

                <label>Contraseña (dejar en blanco para mantener):</label>
                <input type="password" name="contrasena" placeholder="Nueva contraseña">

                <label>Permiso:</label>
                <select name="permiso" id="permiso">
                    <option value="cliente">Cliente</option>
                    <option value="recepcion">Recepción</option>
                    <option value="tecnico">Técnico</option>
                    <option value="admin">Admin</option>
                    <option value="jefeTecnico">Jefe Técnico</option>
                </select>

                <div id="direcciones-container">
                    <h3>Dirección Fiscal</h3>
                    <label>CP Fiscal:</label>
                    <input type="number" name="cpFiscal" placeholder="12345">

                    <label>Provincia Fiscal:</label>
                    <input type="text" name="provinciaFiscal" placeholder="Provincia">

                    <label>Localidad Fiscal:</label>
                    <input type="text" name="localidadFiscal" placeholder="Localidad">

                    <label>Dirección Fiscal:</label>
                    <input type="text" name="direccionFiscal" placeholder="Calle 123">

                    <h3>Primera dirección adicional</h3>
                    <label>CP:</label>
                    <input type="number" name="cp1" placeholder="54321">

                    <label>Provincia:</label>
                    <input type="text" name="provincia1" placeholder="Provincia">

                    <label>Localidad:</label>
                    <input type="text" name="localidad1" placeholder="Localidad">

                    <label>Dirección:</label>
                    <input type="text" name="direccion1" placeholder="Calle 456">

                    <h3>Segunda dirección adicional</h3>
                    <label>CP:</label>
                    <input type="number" name="cp2" placeholder="67890">

                    <label>Provincia:</label>
                    <input type="text" name="provincia2" placeholder="Provincia">

                    <label>Localidad:</label>
                    <input type="text" name="localidad2" placeholder="Localidad">

                    <label>Dirección:</label>
                    <input type="text" name="direccion2" placeholder="Calle 789">
                </div>

                <input type="submit" name="modificar" value="Modificar Usuario"  class="btn-modificar"
                       onclick="return confirm('¿Estás seguro de modificar este usuario?');">
            </form>
        </div>
        
        <p class="volver-home">
            <a href="../home.php">Volver al home</a>
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 Todos los derechos reservados.</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const permisoEl = document.getElementById('permiso');
            const direccionesEl = document.getElementById('direcciones-container');

            function toggleCampos() {
                direccionesEl.style.display = permisoEl.value === 'cliente' ? 'block' : 'none';
            }

            permisoEl.addEventListener('change', toggleCampos);
            toggleCampos();
        });
    </script>
</body>
</html>
    <?php
    exit;
}
// Si no hay ID, mostrar la tabla de usuarios con filtro
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Seleccionar Usuario a Modificar</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         :root {
        --azul-principal: #4A90E2;
        --azul-oscuro: #002255;
        --verde: #27ae60;
        --verde-hover: #219150;
        --rojo: #e74c3c;
        --rojo-hover: #c0392b;
        --naranja: #f9ab25;
        --naranja-hover: #e0941f;
        --gris-claro: #f8f9fa;
        --gris-medio: #e9ecef;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background: var(--gris-claro);
        color: #333;
    }

    .header-mapache {
        background: var(--azul-oscuro);
        color: var(--azul-oscuro);
        padding: 15px 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .header-mapache h1 {
        font-size: 28px;
        color : white;
        font-weight: 600;
        margin: 0;
    }

    .home-icon {
        position: absolute;
        right: 30px;
        color: white;
        font-size: 24px;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .home-icon:hover {
        color: var(--azul-principal);
    }

    .container {
        flex: 1;
        width: 95%;
        max-width: 1400px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .container h1 {
        color: var(--azul-oscuro);
        margin-bottom: 30px;
        font-size: 24px;
        font-weight: 600;
        padding-bottom: 10px;
    }

    .filter-section {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #e1e8ed;
    }

    .filter-row {
        display: flex;
        gap: 20px;
        align-items: end;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 200px;
    }

    .filter-group label {
        color: var(--azul-oscuro);
        font-weight: 500;
        margin-bottom: 5px;
        font-size: 14px;
    }

    .filter-group select {
        padding: 10px 12px;
        border: 2px solid #e1e8ed;
        border-radius: 6px;
        font-size: 14px;
        background: white;
        transition: border-color 0.3s ease;
    }

    .filter-group select:focus {
        outline: none;
        border-color: var(--azul-principal);
        box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
        align-items: end;
    }

    .btn-primary {
        background: var(--azul-principal);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .btn-primary:hover {
        background: #357abd;
    }

    .btn-secondary {
        background: var(--naranja);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .btn-secondary:hover {
        background: var(--naranja-hover);
    }

    .tabla-lista {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #e1e8ed;
    }

    .tabla-datos {
        width: 100%;
        border-collapse: collapse;
    }

    .tabla-datos th {
        background: var(--azul-principal);
        color: white;
        padding: 15px 12px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
    }

    .tabla-datos td {
        padding: 12px;
        border-bottom: 1px solid #f1f3f4;
        font-size: 14px;
        vertical-align: middle;
    }

    .tabla-datos tr:hover {
        background: #f8f9ff;
    }

    .tabla-datos tr:last-child td {
        border-bottom: none;
    }

    .acciones {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .btn-modificar {
        background: var(--verde);
        color: white;
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        transition: background 0.3s ease;
        display: inline-block;
    }

    .btn-modificar:hover {
        background: var(--verde-hover);
        text-decoration: none;
        color: white;
    }

    .btn-borrar {
        background: var(--rojo);
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .btn-borrar:hover {
        background: var(--rojo-hover);
    }

    .mensaje-info {
        background: white;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #e1e8ed;
    }

    .mensaje-info p {
        color: #666;
        font-size: 16px;
    }

    .enlaces-navegacion {
        margin-top: 30px;
        text-align: center;
    }

    .enlaces-navegacion a {
        color: var(--azul-principal);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .enlaces-navegacion a:hover {
        color: var(--azul-oscuro);
        text-decoration: underline;
    }

    footer {
        background: rgb(0, 0, 0);
        color: white;
        text-align: center;
        padding: 20px;
        margin-top: 40px;
        font-size: 14px;
    }

    .loader {
        display: none;
        border: 4px solid #f3f3f3;
        border-top: 4px solid var(--azul-principal);
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
        margin: 20px auto;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .no-results {
        background: white;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #e1e8ed;
        color: #666;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-mapache {
            padding: 15px 20px;
        }
        
        .header-mapache h1 {
            font-size: 24px;
        }
        
        .home-icon {
            right: 20px;
            font-size: 20px;
        }
        
        .filter-row {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-group {
            min-width: auto;
        }
        
        .filter-buttons {
            justify-content: center;
        }
        
        .tabla-lista {
            overflow-x: auto;
        }
        
        .acciones {
            flex-direction: column;
            gap: 4px;
        }
    }

    @media (max-width: 576px) {
        .container {
            width: 98%;
            padding: 0 10px;
        }
        
        .tabla-datos th,
        .tabla-datos td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .btn-modificar,
        .btn-borrar {
            padding: 4px 8px;
            font-size: 11px;
        }
    }
    </style>
</head>
<body>
    <!-- Header que faltaba -->
    <div class="header-mapache">
            <h1>Mapache Security</h1>
            <a href="../home.php" class="home-icon">
                <i class="fas fa-home"></i>
            </a>
        </div>
    
    <!-- Container principal -->
    <div class="container">
        <h1>Seleccionar Usuario a Modificar</h1>
        
        <div id="loader" class="loader"></div>
        
        <!-- Sección de filtros con la estructura correcta -->
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filtro-permiso">Tipo Permiso:</label>
                    <select id="filtro-permiso">
                        <option value="todos">-- Todos los tipos --</option>
                        <option value="cliente">Cliente</option>
                        <option value="recepcion">Recepción</option>
                        <option value="tecnico">Técnico</option>
                        <option value="admin">Admin</option>
                        <option value="jefeTecnico">Jefe Técnico</option>
                    </select>
                </div>
                
                <div class="filter-buttons">
                    <button id="btn-filtrar" class="btn-primary">Aplicar Filtros</button>
                    <button id="btn-reset" class="btn-secondary">Limpiar Filtros</button>
                </div>
            </div>
        </div>
        
        <!-- Tabla con la estructura correcta -->
        <div class="tabla-lista">
            <table id="tabla-usuarios" class="tabla-datos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>USUARIO</th>
                        <th>CORREO</th>
                        <th>PERMISO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>                    
                    <!-- Aquí irían más filas con tu código PHP -->
                    <?php if(isset($usuarios)): ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr data-permiso="<?= htmlspecialchars($u['permiso']); ?>" data-id="<?= htmlspecialchars($u['idUsuarios']); ?>">
                                <td><?= htmlspecialchars($u['idUsuarios']); ?></td>
                                <td><?= htmlspecialchars($u['usuario']); ?></td>
                                <td><?= htmlspecialchars($u['correo']); ?></td>
                                <td><?= htmlspecialchars($u['permiso']); ?></td>
                                <td>
                                    <div class="acciones">
                                        <a href="?id=<?= htmlspecialchars($u['idUsuarios']); ?>" class="btn-modificar" onclick="return confirm('¿Deseas modificar este usuario?');">Modificar</a>
                                        <button class="btn-borrar" data-id="<?= htmlspecialchars($u['idUsuarios']); ?>">Borrar</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div id="no-results" class="no-results" style="display: none;">
            No se encontraron usuarios con los filtros seleccionados.
        </div>
        
        <div class="enlaces-navegacion">
            <a href="../home.php">Volver al home</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filtroPermiso = document.getElementById('filtro-permiso');
            const btnFiltrar = document.getElementById('btn-filtrar');
            const btnReset = document.getElementById('btn-reset');
            const tablaUsuarios = document.getElementById('tabla-usuarios');
            const noResults = document.getElementById('no-results');
            const filas = tablaUsuarios.querySelectorAll('tbody tr');
            const loader = document.getElementById('loader');
            
            // Función para filtrar por permiso
            function filtrarTabla() {
                const permiso = filtroPermiso.value;
                let hayResultados = false;
                
                filas.forEach(fila => {
                    const permisoFila = fila.getAttribute('data-permiso');
                    
                    const coincidePermiso = permiso === 'todos' || permisoFila === permiso;
                    
                    if (coincidePermiso) {
                        fila.style.display = '';
                        hayResultados = true;
                    } else {
                        fila.style.display = 'none';
                    }
                });
                
                noResults.style.display = hayResultados ? 'none' : 'block';
                
                if (!hayResultados) {
                    alert('No se encontraron usuarios con el tipo de permiso seleccionado.');
                } else {
                    alert('Filtro aplicado: ' + (permiso === 'todos' ? 'Todos los tipos' : permiso));
                }
            }
            
            // Función para ordenar la tabla por columna
            function ordenarTabla(columna) {
                const thead = tablaUsuarios.querySelector('thead');
                const tbody = tablaUsuarios.querySelector('tbody');
                const filas = Array.from(tbody.querySelectorAll('tr'));
                
                const ascendente = thead.querySelectorAll('th')[columna].classList.toggle('asc');
                
                // Resetear otros encabezados
                thead.querySelectorAll('th').forEach((th, i) => {
                    if (i !== columna) {
                        th.classList.remove('asc', 'desc');
                    }
                });
                
                // Ordenar filas
                filas.sort((a, b) => {
                    const textoA = a.querySelectorAll('td')[columna].textContent.trim();
                    const textoB = b.querySelectorAll('td')[columna].textContent.trim();
                    
                    // Para columna ID, ordenar numéricamente
                    if (columna === 0) {
                        return ascendente 
                            ? parseInt(textoA) - parseInt(textoB)
                            : parseInt(textoB) - parseInt(textoA);
                    }
                    
                    // Para otras columnas, ordenar alfabéticamente
                    return ascendente
                        ? textoA.localeCompare(textoB)
                        : textoB.localeCompare(textoA);
                });
                
                // Reordenar nodos en el DOM
                filas.forEach(fila => {
                    tbody.appendChild(fila);
                });
                
                alert('Tabla ordenada por ' + tablaUsuarios.querySelectorAll('th')[columna].textContent.trim());
            }
            
            // Función para borrar usuario con AJAX usando el mismo archivo
            async function borrarUsuario(userId) {
                if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
                    try {
                        loader.style.display = 'block';
                        
                        // Crear FormData para enviar datos
                        const formData = new FormData();
                        formData.append('action', 'borrarUsuario');
                        formData.append('id', userId);
                        
                        // Realizar petición AJAX al mismo archivo
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            alert('Usuario eliminado correctamente');
                            // Eliminar la fila de la tabla
                            const filaEliminar = document.querySelector(`tr[data-id="${userId}"]`);
                            if (filaEliminar) {
                                filaEliminar.remove();
                            }
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (error) {
                        console.error(error);
                        alert('Error al procesar la solicitud. Consulta la consola para más detalles.');
                    } finally {
                        loader.style.display = 'none';
                    }
                }
            }
            
            // Manejar botones de borrar
            document.querySelectorAll('.btn-borrar').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const userId = this.getAttribute('data-id');
                    borrarUsuario(userId);
                });
            });
            
            // Eventos
            btnFiltrar.addEventListener('click', filtrarTabla);
            btnReset.addEventListener('click', function() {
                filtroPermiso.value = 'todos';
                filtrarTabla();
                alert('Filtros eliminados. Mostrando todos los usuarios.');
            });
            
            // Añadir evento de clic en encabezados para ordenar
            tablaUsuarios.querySelectorAll('thead th').forEach((th, index) => {
                th.addEventListener('click', function() {
                    ordenarTabla(index);
                });
            });
        });
    </script>
    <footer class="footer">
            <p>&copy; 2025 Todos los derechos reservados.</p>
</footer>
</body>

</html>