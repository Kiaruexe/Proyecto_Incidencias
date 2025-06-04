<?php
session_start();

// Verificar si el usuario está logueado
$usuarioLogueado = false;
$permisoUsuario = null;

// CORREGIDO: Primero verificar idUsuario (que es lo que establece el login)
if (isset($_SESSION['idUsuario'])) {
    // Si tenemos idUsuario, necesitamos obtener los datos del usuario de la BD
    try {
        $bd = new PDO(
            'mysql:host=PMYSQL168.dns-servicio.com;port=3306;dbname=9981336_aplimapa;charset=utf8', 
            'Mapapli', 
            '9R%d5cf62',
            [ 
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        $sql = "SELECT usuario, correo, permiso FROM Usuarios WHERE idUsuarios = ?";
        $stmt = $bd->prepare($sql);
        $stmt->execute([$_SESSION['idUsuario']]);
        $datosUsuario = $stmt->fetch();
        
        if ($datosUsuario) {
            $usuarioLogueado = $datosUsuario['usuario'] ?? $datosUsuario['correo'];
            $permisoUsuario = $datosUsuario['permiso'];
            
            // Opcionalmente, establecer estas variables en la sesión para futuros usos
            $_SESSION['usuario'] = $datosUsuario['usuario'];
            $_SESSION['correo'] = $datosUsuario['correo'];
            $_SESSION['permiso'] = $datosUsuario['permiso'];
        }
        
    } catch (PDOException $e) {
        error_log("Error al obtener datos del usuario: " . $e->getMessage());
    }
} else {
    // Fallback: buscar otras posibles variables de sesión
    if (isset($_SESSION['usuario'])) {
        $usuarioLogueado = $_SESSION['usuario'];
    } elseif (isset($_SESSION['correo'])) {
        $usuarioLogueado = $_SESSION['correo'];
    } elseif (isset($_SESSION['email'])) {
        $usuarioLogueado = $_SESSION['email'];
    } elseif (isset($_SESSION['user'])) {
        $usuarioLogueado = $_SESSION['user'];
    }

    // Buscar permiso
    if (isset($_SESSION['permiso'])) {
        $permisoUsuario = $_SESSION['permiso'];
    } elseif (isset($_SESSION['tipo'])) {
        $permisoUsuario = $_SESSION['tipo'];
    } elseif (isset($_SESSION['rol'])) {
        $permisoUsuario = $_SESSION['rol'];
    } elseif (isset($_SESSION['nivel'])) {
        $permisoUsuario = $_SESSION['nivel'];
    }
}

// Si no hay usuario logueado, redirigir al login
if (!$usuarioLogueado) {
    echo "<script>
            alert('⚠️ Acceso denegado. Debe iniciar sesión.');
            window.location.href = '../login.php';
          </script>";
    exit;
}

// Si no hay permiso definido, redirigir al login
if (!$permisoUsuario) {
    echo "<script>
            alert('⚠️ Acceso denegado. Permiso no encontrado en sesión.');
            window.location.href = '../login.php';
          </script>";
    exit;
}

// Obtener el permiso del usuario actual
$permisoUsuarioActual = $permisoUsuario;

// Verificar permisos de acceso
if (!in_array($permisoUsuarioActual, ['admin', 'recepcion', 'jefeTecnico'])) {
    echo "<script>
            alert('⚠️ No tiene permisos para acceder a esta función. Su permiso actual es: $permisoUsuarioActual');
            window.location.href = '../home.php';
          </script>";
    exit;
}

// Definir qué tipos de usuarios puede crear cada rol
$permisosCreacion = [
    'admin' => ['cliente', 'recepcion', 'tecnico', 'admin', 'jefeTecnico'],
    'recepcion' => ['cliente'],
    'jefeTecnico' => ['cliente']
];

$tiposPermitidos = $permisosCreacion[$permisoUsuarioActual] ?? [];

// Verificar si es una petición POST con datos de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['usuario'])) {  
    function limpiarCampo($valor) {
        return !empty($valor) ? $valor : null; 
    }

    $usuario           = $_POST['usuario']           ?? null;
    $correo            = $_POST['correo']            ?? null;
    $contrasenaTexto   = $_POST['contrasena']        ?? null;
    $permiso           = $_POST['permiso']           ?? null;
    $cpFiscal          = limpiarCampo($_POST['cpFiscal'] ?? '');
    $provinciaFiscal   = limpiarCampo($_POST['provinciaFiscal'] ?? '');
    $localidadFiscal   = limpiarCampo($_POST['localidadFiscal'] ?? '');
    $direccionFiscal   = limpiarCampo($_POST['direccionFiscal'] ?? '');
    $cp1               = limpiarCampo($_POST['cp1'] ?? '');
    $provincia1        = limpiarCampo($_POST['provincia1'] ?? '');
    $localidad1        = limpiarCampo($_POST['localidad1'] ?? '');
    $direccion1        = limpiarCampo($_POST['direccion1'] ?? '');
    $cp2               = limpiarCampo($_POST['cp2'] ?? '');
    $provincia2        = limpiarCampo($_POST['provincia2'] ?? '');
    $localidad2        = limpiarCampo($_POST['localidad2'] ?? '');
    $direccion2        = limpiarCampo($_POST['direccion2'] ?? '');

    // Validación de campos obligatorios
    if (empty($usuario) || empty($correo) || empty($contrasenaTexto) || empty($permiso)) {
        echo "<script>
                alert('⚠️ Por favor, complete todos los campos obligatorios (usuario, correo, contraseña y permiso).');
                history.back();
              </script>";
        exit;
    }

    // Verificar si el usuario actual puede crear este tipo de usuario
    if (!in_array($permiso, $tiposPermitidos)) {
        echo "<script>
                alert('⚠️ No tiene permisos para crear usuarios de tipo: $permiso');
                history.back();
              </script>";
        exit;
    }

    try {
        $bd = new PDO(
            'mysql:host=PMYSQL168.dns-servicio.com;port=3306;dbname=9981336_aplimapa;charset=utf8', 
            'Mapapli', 
            '9R%d5cf62',
            [ 
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        // Verificar duplicados
        $sql = "SELECT COUNT(*) FROM Usuarios WHERE usuario = ? OR correo = ?";
        $stmt = $bd->prepare($sql);
        $stmt->execute([$usuario, $correo]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            echo "<script>
                    alert('⚠️ Ya existe un usuario con ese nombre o correo electrónico. Por favor, utilice otros datos.');
                    history.back();
                  </script>";
            exit;
        } else {
            // Proceder con el registro
            $contrasenaHash = password_hash($contrasenaTexto, PASSWORD_DEFAULT);
            
            // Iniciar transacción
            $bd->beginTransaction();
            
            $sql = "INSERT INTO Usuarios (
                usuario, correo, contrasena, permiso, restablecer,
                cpFiscal, provinciaFiscal, localidadFiscal, direccionFiscal,
                cp1, provincia1, localidad1, direccion1,
                cp2, provincia2, localidad2, direccion2
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $bd->prepare($sql);
            
            $params = [
                $usuario,
                $correo,
                $contrasenaHash,
                $permiso,
                1, // restablecer = TRUE (1)
                $cpFiscal,
                $provinciaFiscal,
                $localidadFiscal,
                $direccionFiscal,
                $cp1,
                $provincia1,
                $localidad1,
                $direccion1,
                $cp2,
                $provincia2,
                $localidad2,
                $direccion2
            ];
            
            $resultado = $stmt->execute($params);
            
            if ($resultado) {
                // Confirmar la transacción
                $bd->commit();
                
                // Mostrar mensaje de éxito y redirigir
                echo "<script>
                        alert('✅ Usuario registrado con éxito.');
                        window.location.href = '../home.php';
                      </script>";
                exit;
            } else {
                // Revertir la transacción
                $bd->rollBack();
                throw new Exception("Error al insertar el usuario");
            }
        }
    } catch (PDOException $e) {
        // Revertir transacción si está activa
        if (isset($bd) && $bd->inTransaction()) {
            $bd->rollBack();
        }
        
        echo "<script>
                alert('⚠️ Error al registrar el usuario: " . addslashes($e->getMessage()) . "');
              </script>";
    } catch (Exception $e) {
        // Revertir transacción si está activa
        if (isset($bd) && $bd->inTransaction()) {
            $bd->rollBack();
        }
        
        echo "<script>
                alert('⚠️ " . addslashes($e->getMessage()) . "');
              </script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuarios</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
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
      flex-shrink: 0;
    }

    .header-mapache h1 {
      font-size: 32px;
      font-weight: bold;
      margin: 0;
    }

    /* Usuario actual en el header */
    .user-info {
        position: absolute;
        top: 15px;
        left: 20px;
        color: white;
        font-size: 14px;
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

    /* Opciones deshabilitadas */
    option:disabled {
        color: #999;
        background-color: #f5f5f5;
    }

    /* Botones */
    .button-container {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-top: 30px;
    }

    .btn,
    input[type="submit"],
    button {
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
    input[type="submit"]:hover,
    button:hover {
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
        flex-shrink: 0;
        margin-top: auto;
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

        .user-info {
            position: static;
            text-align: center;
            margin-bottom: 10px;
        }
    }

    /* Ajuste para pantallas grandes */
    @media (min-width: 1200px) {
        .form-container {
            max-width: 900px;
        }
    }
      </style>
    <script>
      // Permisos permitidos para el usuario actual (desde PHP)
      const tiposPermitidos = <?php echo json_encode($tiposPermitidos); ?>;

      // Mostrar/ocultar direcciones + asignar/quitar "required"
      function toggleCampos() {
        const permisoSelect = document.getElementById('permiso');
        const valorPermiso = permisoSelect.value;
        
        const direccionesContainer = document.getElementById('direcciones-container');
        const camposFiscales = document.querySelectorAll('.fiscal-field');

        if (valorPermiso === 'cliente') {
          direccionesContainer.style.display = 'block';
          camposFiscales.forEach(campo => {
            campo.setAttribute('required', 'true');
          });
        } else {
          direccionesContainer.style.display = 'none';
          camposFiscales.forEach(campo => {
            campo.removeAttribute('required');
          });
        }
      }

      // Función para configurar opciones del select según permisos
      function configurarOpcionesPermiso() {
        const permisoSelect = document.getElementById('permiso');
        const opciones = permisoSelect.querySelectorAll('option');
        
        opciones.forEach(opcion => {
          if (opcion.value && !tiposPermitidos.includes(opcion.value)) {
            opcion.disabled = true;
            opcion.style.display = 'none';
          }
        });
      }

      // Función mejorada para prevenir envíos duplicados
      function prevenirEnvioDuplicado() {
        const form = document.getElementById('registro-form');
        const submitBtn = document.getElementById('submit-btn');
        
        if (form && submitBtn) {
          form.addEventListener('submit', function(e) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Procesando...';
            return true;
          });
        }
      }

      // Función para validar antes del envío
      function validarFormulario() {
        const usuario = document.querySelector('input[name="usuario"]').value.trim();
        const correo = document.querySelector('input[name="correo"]').value.trim();
        const contrasena = document.querySelector('input[name="contrasena"]').value.trim();
        const permiso = document.querySelector('select[name="permiso"]').value;

        if (!usuario || !correo || !contrasena || !permiso) {
          alert('Por favor, complete todos los campos obligatorios');
          return false;
        }

        // Verificar permisos
        if (!tiposPermitidos.includes(permiso)) {
          alert('No tiene permisos para crear usuarios de tipo: ' + permiso);
          return false;
        }

        // Validación específica para clientes
        if (permiso === 'cliente') {
          const cpFiscal = document.querySelector('input[name="cpFiscal"]').value.trim();
          const provinciaFiscal = document.querySelector('input[name="provinciaFiscal"]').value.trim();
          const localidadFiscal = document.querySelector('input[name="localidadFiscal"]').value.trim();
          const direccionFiscal = document.querySelector('input[name="direccionFiscal"]').value.trim();

          if (!cpFiscal || !provinciaFiscal || !localidadFiscal || !direccionFiscal) {
            alert('Para usuarios tipo "Cliente", todos los campos fiscales son obligatorios');
            return false;
          }
        }

        return confirm('¿Estás seguro de registrar este usuario?');
      }

      document.addEventListener('DOMContentLoaded', () => {
        configurarOpcionesPermiso();
        toggleCampos(); 
        document.getElementById('permiso').addEventListener('change', toggleCampos);
        prevenirEnvioDuplicado();
      });
    </script>
</head>
<body>
    <!-- Header principal -->
    <div class="header-mapache">
        <div class="user-info">
            Usuario: <?php echo htmlspecialchars($usuarioLogueado ?? 'Desconocido'); ?> 
            (<?php echo ucfirst($permisoUsuarioActual); ?>)
        </div>
        <h1>Mapache Security</h1>
        <a href="../home.php" class="home-icon">
            <i class="fas fa-home"></i>
        </a>
    </div>
    
    <div class="main-content">
        <h1 class="form-title">Crear Usuario</h1>
        
        <div class="form-container">
            <form method="post" id="registro-form" onsubmit="return validarFormulario()">
                <label>Nombre de usuario: *</label>
                <input type="text" name="usuario" placeholder="Cliente actual" required>

                <label>Correo: *</label>
                <input type="email" name="correo" placeholder="correo@ejemplo.com" required>

                <label>Contraseña: *</label>
                <input type="password" name="contrasena" placeholder="Nueva contraseña" required>

                <label>Permiso: *</label>
                <select name="permiso" id="permiso" required>
                    <option value="">Seleccione un permiso</option>
                    <?php if (in_array('cliente', $tiposPermitidos)): ?>
                    <option value="cliente">Cliente</option>
                    <?php endif; ?>
                    <?php if (in_array('recepcion', $tiposPermitidos)): ?>
                    <option value="recepcion">Recepción</option>
                    <?php endif; ?>
                    <?php if (in_array('tecnico', $tiposPermitidos)): ?>
                    <option value="tecnico">Técnico</option>
                    <?php endif; ?>
                    <?php if (in_array('admin', $tiposPermitidos)): ?>
                    <option value="admin">Admin</option>
                    <?php endif; ?>
                    <?php if (in_array('jefeTecnico', $tiposPermitidos)): ?>
                    <option value="jefeTecnico">Jefe Técnico</option>
                    <?php endif; ?>
                </select>

                <div id="direcciones-container">
                    <h3>Dirección Fiscal</h3>
                    <label>CP Fiscal:</label>
                    <input type="number" name="cpFiscal" placeholder="12345" class="fiscal-field">

                    <label>Provincia Fiscal:</label>
                    <input type="text" name="provinciaFiscal" placeholder="Provincia" class="fiscal-field">

                    <label>Localidad Fiscal:</label>
                    <input type="text" name="localidadFiscal" placeholder="Localidad" class="fiscal-field">

                    <label>Dirección Fiscal:</label>
                    <input type="text" name="direccionFiscal" placeholder="Calle 123" class="fiscal-field">

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
                
                <input type="hidden" name="accion" value="registrar">
                
                <div class="btn-modificar">
                    <input type="submit" value="Registrar Usuario" id="submit-btn">
                </div>
            </form>
        </div>
        
        <p class="volver-home">
            <a href="../home.php">Volver al home</a>
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy;  <?php echo date('Y'); ?> Todos los derechos reservados.</p>
    </div>
</body>
</html>