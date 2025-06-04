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
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
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

/* Contenido principal - Reducido margen y aumentado ancho máximo */
.main-content {
    flex: 1 0 auto;
    max-width: 1000px; /* Aumentado de 800px a 1000px */
    margin: 25px auto 40px; /* Reducido margen superior e inferior */
    padding: 0 15px; /* Reducido padding lateral */
}

/* Título del formulario */
.form-title {
    text-align: center;
    font-size: 2.4rem;
    margin-bottom: 20px; /* Reducido de 30px a 20px */
    color: #00225a;
    font-weight: 800;
    letter-spacing: 1.5px;
    user-select: none;
}

/* Contenedor del formulario - Más ancho */
.form-container {
    background: #fff;
    border-radius: 12px;
    padding: 35px 45px; /* Aumentado padding horizontal para más espacio interno */
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    margin-bottom: 20px; /* Reducido de 30px a 20px */
    width: 100%; /* Asegurar que ocupe todo el ancho disponible */
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

/* Select específico */
select {
    cursor: pointer;
}

select option:disabled {
    color: #6c757d;
    background-color: #f8f9fa;
}

/* Contenedor de direcciones */
#direcciones-container {
    grid-column: 1 / -1; /* Ocupa toda la fila */
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 25px;
    margin: 20px 0;
    background-color: #fafbfc;
    display: none; /* Oculto por defecto */
}

#direcciones-container h3 {
    color: #00225a;
    font-size: 1.3rem;
    margin: 25px 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #2573fa;
    font-weight: 700;
    grid-column: 1 / -1; /* Los títulos ocupan toda la fila */
}

#direcciones-container h3:first-child {
    margin-top: 0;
}

/* Grid interno para las direcciones */
#direcciones-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}

#direcciones-container.hidden {
    display: none;
}

/* Campos fiscales obligatorios */
.fiscal-field {
    border-color: #2573fa !important;
}

/* Grupos de campos específicos que ocupan toda la fila */
.full-width {
    grid-column: 1 / -1;
}

/* Contenedor de direcciones específicamente cuando está visible */
#direcciones-container:not(.hidden) {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}

/* Labels y campos dentro del contenedor de direcciones */
#direcciones-container label {
    margin-top: 15px;
    margin-bottom: 5px;
}

#direcciones-container label:first-of-type {
    margin-top: 0;
}

.fiscal-field:focus {
    border-color: #f9ab25 !important;
    box-shadow: 0 0 6px #f9ab25 !important;
}

/* Botones */
.btn-modificar {
    grid-column: 1 / -1; /* Ocupa toda la fila */
    margin-top: 25px; /* Reducido de 30px a 25px */
    text-align: center;
}

/* Campo oculto */
input[type="hidden"] {
    grid-column: 1 / -1;
    display: none;
}

.btn-modificar input[type="submit"] {
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
}

.btn-modificar input[type="submit"]:hover {
    background-color: #218838;
    transform: translateY(-2px);
}

.btn-modificar input[type="submit"]:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
    transform: none;
}

/* Enlace volver al home */
.volver-home {
    text-align: center;
    margin: 15px 0; /* Reducido de 20px a 15px */
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

.form-container {
    animation: fadeIn 0.6s ease-out;
}

/* Responsive design */
@media (max-width: 1024px) {
    .main-content {
        max-width: 90%; /* En pantallas medianas, usar 90% del ancho */
        margin: 20px auto 30px;
    }
}

@media (max-width: 768px) {
    .main-content {
        margin: 20px 10px 40px; /* Reducido margen en móvil */
        padding: 0 8px; /* Reducido padding lateral */
        max-width: 100%;
    }

    .form-container {
        padding: 25px 20px; /* Mantenido padding interno */
    }

    .form-title {
        font-size: 2rem;
        margin-bottom: 15px; /* Reducido margen */
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

    #direcciones-container {
        padding: 20px 15px;
        grid-template-columns: 1fr; /* Una sola columna en móvil */
    }

    .btn-modificar input[type="submit"] {
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
        margin: 15px 8px 30px; /* Márgenes más pequeños en móviles pequeños */
    }
}

/* Estilos adicionales para mejor UX */
.required-field::after {
    content: " *";
    color: #dc3545;
}

/* Hover effects para mejor interactividad */
input[type="text"]:hover,
input[type="email"]:hover,
input[type="password"]:hover,
input[type="number"]:hover,
select:hover {
    border-color: #1a5fdd;
}

/* Loading state para botón */
.btn-modificar input[type="submit"]:disabled {
    position: relative;
}

.btn-modificar input[type="submit"]:disabled::after {
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

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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