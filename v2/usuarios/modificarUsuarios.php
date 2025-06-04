
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
    $sql = "SELECT idUsuarios, usuario, correo, permiso, restablecer FROM Usuarios";
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
        $usuarioData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuarioData) {
            echo "<p style='color:red;'>Usuario no encontrado.</p>";
            exit;
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error al obtener el usuario: " . $e->getMessage() . "</p>";
        exit;
    }

    if (isset($_POST['modificar'])) {
        // Obtener valores del formulario, manteniendo los actuales si están vacíos
        $usuario = !empty(trim($_POST['usuario'])) ? trim($_POST['usuario']) : $usuarioData['usuario'];
        $correo = !empty(trim($_POST['correo'])) ? trim($_POST['correo']) : $usuarioData['correo'];
        $permiso = !empty(trim($_POST['permiso'])) ? trim($_POST['permiso']) : $usuarioData['permiso'];
        
        // Manejar direcciones (solo para clientes)
        $cpFiscal = !empty(trim($_POST['cpFiscal'])) ? trim($_POST['cpFiscal']) : $usuarioData['cpFiscal'];
        $provinciaFiscal = !empty(trim($_POST['provinciaFiscal'])) ? trim($_POST['provinciaFiscal']) : $usuarioData['provinciaFiscal'];
        $localidadFiscal = !empty(trim($_POST['localidadFiscal'])) ? trim($_POST['localidadFiscal']) : $usuarioData['localidadFiscal'];
        $direccionFiscal = !empty(trim($_POST['direccionFiscal'])) ? trim($_POST['direccionFiscal']) : $usuarioData['direccionFiscal'];

        $cp1 = !empty(trim($_POST['cp1'])) ? trim($_POST['cp1']) : $usuarioData['cp1'];
        $provincia1 = !empty(trim($_POST['provincia1'])) ? trim($_POST['provincia1']) : $usuarioData['provincia1'];
        $localidad1 = !empty(trim($_POST['localidad1'])) ? trim($_POST['localidad1']) : $usuarioData['localidad1'];
        $direccion1 = !empty(trim($_POST['direccion1'])) ? trim($_POST['direccion1']) : $usuarioData['direccion1'];

        $cp2 = !empty(trim($_POST['cp2'])) ? trim($_POST['cp2']) : $usuarioData['cp2'];
        $provincia2 = !empty(trim($_POST['provincia2'])) ? trim($_POST['provincia2']) : $usuarioData['provincia2'];
        $localidad2 = !empty(trim($_POST['localidad2'])) ? trim($_POST['localidad2']) : $usuarioData['localidad2'];
        $direccion2 = !empty(trim($_POST['direccion2'])) ? trim($_POST['direccion2']) : $usuarioData['direccion2'];

        // Manejar contraseña y restablecimiento
        $contrasenaHash = $usuarioData['contrasena']; // Por defecto mantener la actual
        $restablecer = $usuarioData['restablecer']; // Por defecto mantener el valor actual
        
        // Si se marcó restablecer contraseña
        if (isset($_POST['restablecer'])) {
            $restablecer = 1;
            // Si se proporcionó una contraseña provisional
            if (!empty(trim($_POST['contrasena_provisional']))) {
                $contrasenaHash = password_hash(trim($_POST['contrasena_provisional']), PASSWORD_DEFAULT);
            }
        } else {
            // Si no se marcó restablecer, pero se proporcionó una nueva contraseña
            if (!empty(trim($_POST['contrasena']))) {
                $contrasenaHash = password_hash(trim($_POST['contrasena']), PASSWORD_DEFAULT);
                $restablecer = 0; // Al cambiar contraseña normalmente, no necesita restablecimiento
            }
        }

        try {
            $sqlUpdate = "UPDATE Usuarios SET
                usuario = ?, correo = ?, contrasena = ?, permiso = ?, restablecer = ?,
                cpFiscal = ?, provinciaFiscal = ?, localidadFiscal = ?, direccionFiscal = ?,
                cp1 = ?, provincia1 = ?, localidad1 = ?, direccion1 = ?,
                cp2 = ?, provincia2 = ?, localidad2 = ?, direccion2 = ?
                WHERE idUsuarios = ?";
            $stmtUpdate = $bd->prepare($sqlUpdate);
            $resultado = $stmtUpdate->execute([
                $usuario, $correo, $contrasenaHash, $permiso, $restablecer,
                $cpFiscal, $provinciaFiscal, $localidadFiscal, $direccionFiscal,
                $cp1, $provincia1, $localidad1, $direccion1,
                $cp2, $provincia2, $localidad2, $direccion2,
                $idUsuarioModificar
            ]);
            
            if ($resultado) {
                echo "<script>alert('Usuario modificado con éxito.');</script>";
                // Actualizar los datos para mostrar en el formulario
                $usuarioData['usuario'] = $usuario;
                $usuarioData['correo'] = $correo;
                $usuarioData['permiso'] = $permiso;
                $usuarioData['restablecer'] = $restablecer;
                $usuarioData['cpFiscal'] = $cpFiscal;
                $usuarioData['provinciaFiscal'] = $provinciaFiscal;
                $usuarioData['localidadFiscal'] = $localidadFiscal;
                $usuarioData['direccionFiscal'] = $direccionFiscal;
                $usuarioData['cp1'] = $cp1;
                $usuarioData['provincia1'] = $provincia1;
                $usuarioData['localidad1'] = $localidad1;
                $usuarioData['direccion1'] = $direccion1;
                $usuarioData['cp2'] = $cp2;
                $usuarioData['provincia2'] = $provincia2;
                $usuarioData['localidad2'] = $localidad2;
                $usuarioData['direccion2'] = $direccion2;
            } else {
                echo "<script>alert('Error: No se pudo modificar el usuario.');</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Error al modificar usuario: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
    
    // Mostrar el formulario de modificación de usuario
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Modificar Usuario</title>
 
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset y configuración base */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #333;
        }

        /* Header principal */
        .header-mapache {
            background: #002255;
            color: white;
            padding: 15px 0;
            text-align: center;
            position: relative;
            flex-shrink: 0;
        }

        .header-mapache h1 {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
        }

        /* Información de usuario en el header */
        .user-info {
            position: absolute;
            top: 15px;
            left: 20px;
            font-size: 14px;
            color: #ccc;
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
            color: white;
            font-size: 24px;
        }

        .home-icon:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Contenido principal */
        .main-content {
            flex: 1 0 auto;
            max-width: 1000px;
            margin: 25px auto 40px;
            padding: 0 15px;
        }

        /* Título del formulario */
        .form-title {
            text-align: center;
            font-size: 2.4rem;
            margin-bottom: 20px;
            color: #00225a;
            font-weight: 800;
            letter-spacing: 1.5px;
            user-select: none;
        }

        /* Contenedor del formulario */
        .form-container {
            background: #fff;
            border-radius: 12px;
            padding: 35px 45px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            width: 100%;
            animation: fadeIn 0.6s ease-out;
        }

        /* Estilos del formulario */
        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        label {
            font-weight: 700;
            color: #00225a;
            margin-bottom: 8px;
            user-select: none;
            font-size: 1.1rem;
            display: block;
        }

        /* Campos de entrada */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        select,
        textarea {
            font-size: 1rem;
            padding: 12px 16px;
            border-radius: 10px;
            border: 2px solid #2573fa;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background-color: #f9fbff;
            box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.06);
            min-height: 45px;
            width: 100%;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #f9ab25;
            box-shadow: 0 0 6px #f9ab25;
            background-color: #fff;
        }

        input[type="text"]:hover,
        input[type="email"]:hover,
        input[type="password"]:hover,
        input[type="number"]:hover,
        select:hover {
            border-color: #1a5fdd;
        }

        /* Select específico */
        select {
            cursor: pointer;
        }

        select option:disabled {
            color: #6c757d;
            background-color: #f8f9fa;
        }

        /* Contenedor del checkbox de restablecer */
        .checkbox-container {
            grid-column: 1 / -1;
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            width: 100%
        }

        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            font-weight: 700;
            line-height: 1.4;
        }

        .checkbox-label input[type="checkbox"] {
            width: 18px !important;
            height: 18px !important;
            min-height: 18px !important;
            margin: 2px 0 0 0 !important;
            padding: 0 !important;
            border: 2px solid #2573fa !important;
            border-radius: 3px !important;
            background-color: #fff !important;
            box-shadow: none !important;
            flex-shrink: 0;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"]:checked {
            background-color: #2573fa !important;
            border-color: #2573fa !important;
        }

        #contrasena-provisional {
            margin-top: 15px;
            display: none;
        }

        #contrasena-provisional.show {
            display: block;
        }

        #contrasena-provisional small {
            color: #666;
            display: block;
            margin-top: 5px;
            font-size: 0.9rem;
        }

        /* Contenedor de direcciones */
        #direcciones-container {
            grid-column: 1 / -1;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
            background-color: #fafbfc;
            display: none;
        }

        #direcciones-container.show {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        #direcciones-container h3 {
            color: #00225a;
            font-size: 1.3rem;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #2573fa;
            font-weight: 700;
            grid-column: 1 / -1;
        }

        #direcciones-container h3:first-child {
            margin-top: 0;
        }

        #direcciones-container label {
            margin-top: 15px;
            margin-bottom: 5px;
        }

        #direcciones-container label:first-of-type {
            margin-top: 0;
        }

        /* Botón modificar */
        .btn-modificar {
            grid-column: 1 / -1;
            margin-top: 25px;
            text-align: center;
        }

        input[type="submit"] {
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: 14px 30px;
            font-weight: 800;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 200px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        input[type="submit"]:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        input[type="submit"]:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        /* Enlace volver al home */
        .volver-home {
            text-align: center;
            margin: 15px 0;
        }

        .volver-home a {
            color: #00225a;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .volver-home a:hover {
            color: #f9ab25;
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            background-color: #000;
            color: #fff;
            padding: 16px 10px;
            font-size: 0.9rem;
            text-align: center;
            user-select: none;
            flex-shrink: 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.5);
            position: relative;
            z-index: 100;
        }

        /* Campos deshabilitados */
        input[type="text"]:disabled,
        input[type="email"]:disabled,
        input[type="password"]:disabled,
        input[type="number"]:disabled,
        select:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
            color: #6c757d;
        }

        /* Animaciones */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Loading state para botón */
        input[type="submit"]:disabled::after {
            content: "";
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 8px;
        }

        /* Responsive design */
        @media (max-width: 1024px) {
            .main-content {
                max-width: 90%;
                margin: 20px auto 30px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin: 20px 10px 40px;
                padding: 0 8px;
                max-width: 100%;
            }

            .form-container {
                padding: 25px 20px;
            }

            .form-title {
                font-size: 2rem;
                margin-bottom: 15px;
            }

            .user-info {
                position: static;
                text-align: center;
                margin-bottom: 10px;
            }

            .home-icon {
                right: 15px;
                font-size: 20px;
            }

            #direcciones-container.show {
                grid-template-columns: 1fr;
                padding: 20px 15px;
            }

            input[type="submit"] {
                width: 100%;
                min-width: auto;
            }

            /* Formulario en una sola columna en móvil */
            form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .header-mapache h1 {
                font-size: 24px;
            }

            .form-title {
                font-size: 1.8rem;
            }

            .form-container {
                padding: 20px 15px;
                margin-bottom: 15px;
            }

            .main-content {
                margin: 15px 8px 30px;
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
                <input type="text" name="usuario" value="<?= htmlspecialchars($usuarioData['usuario'] ?? ''); ?>" placeholder="Nombre de usuario">

                <label>Correo:</label>
                <input type="email" name="correo" value="<?= htmlspecialchars($usuarioData['correo'] ?? ''); ?>" placeholder="correo@ejemplo.com">

                <label>Contraseña (dejar en blanco para mantener):</label>
                <input type="password" name="contrasena" placeholder="Nueva contraseña">

                <label>Permiso:</label>
                <select name="permiso" id="permiso">
                    <option value="cliente" <?= ($usuarioData['permiso'] ?? '') === 'cliente' ? 'selected' : ''; ?>>Cliente</option>
                    <option value="recepcion" <?= ($usuarioData['permiso'] ?? '') === 'recepcion' ? 'selected' : ''; ?>>Recepción</option>
                    <option value="tecnico" <?= ($usuarioData['permiso'] ?? '') === 'tecnico' ? 'selected' : ''; ?>>Técnico</option>
                    <option value="admin" <?= ($usuarioData['permiso'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="jefeTecnico" <?= ($usuarioData['permiso'] ?? '') === 'jefeTecnico' ? 'selected' : ''; ?>>Jefe Técnico</option>
                </select>
                
                <div style="margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="restablecer" id="restablecer" value="1" <?= ($usuarioData['restablecer'] ?? 0) == 1 ? 'checked' : ''; ?>>
                        <strong>Restablecer contraseña (el usuario deberá cambiarla en el próximo login)</strong>
                    </label>
                    
                    <div id="contrasena-provisional" style="display: <?= ($usuarioData['restablecer'] ?? 0) == 1 ? 'block' : 'none'; ?>; margin-top: 15px;">
                        <label>Contraseña provisional:</label>
                        <input type="password" name="contrasena_provisional" placeholder="Ingresa contraseña provisional">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Esta será la contraseña temporal que el usuario deberá usar para iniciar sesión
                        </small>
                    </div>
                </div>
                
                <div id="direcciones-container" style="display: <?= ($usuarioData['permiso'] ?? '') === 'cliente' ? 'block' : 'none'; ?>;">
                    <h3>Dirección Fiscal</h3>
                    <label>CP Fiscal:</label>
                    <input type="number" name="cpFiscal" value="<?= htmlspecialchars($usuarioData['cpFiscal'] ?? ''); ?>" placeholder="12345">

                    <label>Provincia Fiscal:</label>
                    <input type="text" name="provinciaFiscal" value="<?= htmlspecialchars($usuarioData['provinciaFiscal'] ?? ''); ?>" placeholder="Provincia">

                    <label>Localidad Fiscal:</label>
                    <input type="text" name="localidadFiscal" value="<?= htmlspecialchars($usuarioData['localidadFiscal'] ?? ''); ?>" placeholder="Localidad">

                    <label>Dirección Fiscal:</label>
                    <input type="text" name="direccionFiscal" value="<?= htmlspecialchars($usuarioData['direccionFiscal'] ?? ''); ?>" placeholder="Calle 123">

                    <h3>Primera dirección adicional</h3>
                    <label>CP:</label>
                    <input type="number" name="cp1" value="<?= htmlspecialchars($usuarioData['cp1'] ?? ''); ?>" placeholder="54321">

                    <label>Provincia:</label>
                    <input type="text" name="provincia1" value="<?= htmlspecialchars($usuarioData['provincia1'] ?? ''); ?>" placeholder="Provincia">

                    <label>Localidad:</label>
                    <input type="text" name="localidad1" value="<?= htmlspecialchars($usuarioData['localidad1'] ?? ''); ?>" placeholder="Localidad">

                    <label>Dirección:</label>
                    <input type="text" name="direccion1" value="<?= htmlspecialchars($usuarioData['direccion1'] ?? ''); ?>" placeholder="Calle 456">

                    <h3>Segunda dirección adicional</h3>
                    <label>CP:</label>
                    <input type="number" name="cp2" value="<?= htmlspecialchars($usuarioData['cp2'] ?? ''); ?>" placeholder="67890">

                    <label>Provincia:</label>
                    <input type="text" name="provincia2" value="<?= htmlspecialchars($usuarioData['provincia2'] ?? ''); ?>" placeholder="Provincia">

                    <label>Localidad:</label>
                    <input type="text" name="localidad2" value="<?= htmlspecialchars($usuarioData['localidad2'] ?? ''); ?>" placeholder="Localidad">

                    <label>Dirección:</label>
                    <input type="text" name="direccion2" value="<?= htmlspecialchars($usuarioData['direccion2'] ?? ''); ?>" placeholder="Calle 789">
                </div>

                <input type="submit" name="modificar" value="Modificar Usuario" class="btn-modificar"
                       onclick="return confirm('¿Estás seguro de modificar este usuario?');">
            </form>
        </div>
        
        <p class="volver-home">
            <a href="../home.php">Volver al home</a>
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy;  <?php echo date('Y'); ?>Todos los derechos reservados.</p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const permisoEl = document.getElementById('permiso');
        const direccionesEl = document.getElementById('direcciones-container');
        const restablecerEl = document.getElementById('restablecer');
        const contrasenaProvisionalEl = document.getElementById('contrasena-provisional');

        function toggleCampos() {
            direccionesEl.style.display = permisoEl.value === 'cliente' ? 'block' : 'none';
        }

        function toggleContrasenaProvisional() {
            contrasenaProvisionalEl.style.display = restablecerEl.checked ? 'block' : 'none';
            
            // Si se desmarca, limpiar el campo de contraseña provisional
            if (!restablecerEl.checked) {
                const inputProvisional = contrasenaProvisionalEl.querySelector('input[name="contrasena_provisional"]');
                if (inputProvisional) {
                    inputProvisional.value = '';
                }
            }
        }

        permisoEl.addEventListener('change', toggleCampos);
        restablecerEl.addEventListener('change', toggleContrasenaProvisional);
        
        // Ejecutar al cargar para establecer el estado inicial correcto
        toggleCampos();
        toggleContrasenaProvisional();
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
            <p>&copy;  <?php echo date('Y'); ?> Todos los derechos reservados.</p>
</footer>
</body>

</html>