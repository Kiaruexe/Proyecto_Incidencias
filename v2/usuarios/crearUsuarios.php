<?php
if (isset($_POST['registrar'])) {
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

    try {
    
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;port=3306;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62',
        [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]
    );

    $sql = "SELECT COUNT(*) FROM Usuarios WHERE usuario = ? OR correo = ?";
    $stmt = $bd->prepare($sql);
    $stmt->execute([$usuario, $correo]);

    if ($stmt->fetchColumn() > 0) {
        echo "<script>
                alert('⚠️ Ya existe un usuario con ese nombre o correo electrónico. Por favor, utilice otros datos.');
                history.back();
              </script>";
        exit;
    
        } else {
            // Proceder con el registro ya que no hay duplicados
            $contrasenaHash = password_hash($contrasenaTexto, PASSWORD_DEFAULT);
            
            // Iniciar transacción para asegurar la atomicidad de la operación
            $bd->beginTransaction();
            
            $sql = "INSERT INTO Usuarios (
                usuario, correo, contrasena, permiso,
                cpFiscal, provinciaFiscal, localidadFiscal, direccionFiscal,
                cp1, provincia1, localidad1, direccion1,
                cp2, provincia2, localidad2, direccion2
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $bd->prepare($sql);
            $resultado = $stmt->execute([
                $usuario,
                $correo,
                $contrasenaHash,
                $permiso,
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
            ]);
            
            if ($resultado) {
                // Confirmar la transacción
                $bd->commit();
                
                // Mostrar mensaje de éxito en un alert y redirigir
                echo "<script>
                        alert('✅ Usuario registrado con éxito.');
                        window.location.href = '../home.php'; // Redirigir al home
                      </script>";
                exit; // Detener la ejecución del script
            } else {
                // Si hay algún problema, revertir la transacción
                $bd->rollBack();
                throw new Exception("Error al insertar el usuario");
            }
        }
    } catch (PDOException $e) {
        // Asegurarse de que cualquier transacción abierta se revierta
        if (isset($bd) && $bd->inTransaction()) {
            $bd->rollBack();
        }
        
        // Mostrar mensaje de error en un alert
        echo "<script>
                alert('⚠️ Error al registrar el usuario: " . $e->getMessage() . "');
              </script>";
    } catch (Exception $e) {
        // Asegurarse de que cualquier transacción abierta se revierta
        if (isset($bd) && $bd->inTransaction()) {
            $bd->rollBack();
        }
        
        echo "<script>
                alert('⚠️ " . $e->getMessage() . "');
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
    <script>
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

      // Prevenir envíos duplicados del formulario
      function prevenirEnvioDuplicado() {
        const form = document.getElementById('registro-form');
        const submitBtn = document.querySelector('input[type="submit"]');
        
        form.addEventListener('submit', function() {
          // Deshabilitar el botón después del primer clic
          submitBtn.disabled = true;
          submitBtn.value = 'Procesando...';
          return true;
        });
      }

      document.addEventListener('DOMContentLoaded', () => {
        toggleCampos(); 
        document.getElementById('permiso').addEventListener('change', toggleCampos);
        prevenirEnvioDuplicado();
      });
    </script>
</head>
<body>
<h1>Registrar nuevo usuario</h1>
<form method="post" id="registro-form">

    <label>Nombre de usuario:</label><br>
    <input type="text" name="usuario" required><br><br>

    <label>Correo:</label><br>
    <input type="email" name="correo" required><br><br>

    <label>Contraseña:</label><br>
    <input type="password" name="contrasena" required><br><br>

    <label>Permiso:</label><br>
    <select name="permiso" id="permiso">
        <option value="cliente">Cliente</option>
        <option value="recepcion">Recepción</option>
        <option value="tecnico">Técnico</option>
        <option value="admin">Admin</option>
        <option value="jefeTecnico">Jefe Tecnico</option>
    </select><br><br>

    <div id="direcciones-container">
      <h3>Dirección Fiscal</h3>
      <label>CP Fiscal:</label><br>
      <input type="number" name="cpFiscal" class="fiscal-field"><br><br>
      
      <label>Provincia Fiscal:</label><br>
      <input type="text" name="provinciaFiscal" class="fiscal-field"><br><br>
      
      <label>Localidad Fiscal:</label><br>
      <input type="text" name="localidadFiscal" class="fiscal-field"><br><br>
      
      <label>Dirección Fiscal:</label><br>
      <input type="text" name="direccionFiscal" class="fiscal-field"><br><br>

      <h3>Primera dirección adicional</h3>
      <label>CP:</label><br>
      <input type="number" name="cp1"><br><br>

      <label>Provincia:</label><br>
      <input type="text" name="provincia1"><br><br>

      <label>Localidad:</label><br>
      <input type="text" name="localidad1"><br><br>

      <label>Dirección:</label><br>
      <input type="text" name="direccion1"><br><br>

      <h3>Segunda dirección adicional</h3>
      <label>CP:</label><br>
      <input type="number" name="cp2"><br><br>

      <label>Provincia:</label><br>
      <input type="text" name="provincia2"><br><br>

      <label>Localidad:</label><br>
      <input type="text" name="localidad2"><br><br>

      <label>Dirección:</label><br>
      <input type="text" name="direccion2"><br><br>
    </div>

    <input type="submit" name="registrar" value="Registrar Usuario">
</form>
</body>
</html>