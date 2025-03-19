<?php
// -- PROCESO DE REGISTRO (PHP) --
if (isset($_POST['registrar'])) {
    function limpiarCampo($valor) {
        return !empty($valor) ? $valor : null; 
    }

    $usuario           = $_POST['usuario']           ?? null;
    $correo            = $_POST['correo']            ?? null;
    $contrasenaTexto   = $_POST['contrasena']        ?? null;
    $permiso           = $_POST['permiso']           ?? null;
    $tipoContrato      = $_POST['tipoContrato']      ?? null;
    $cpFiscal          = limpiarCampo($_POST['cpFiscal']);
    $provinciaFiscal   = limpiarCampo($_POST['provinciaFiscal']);
    $localidadFiscal   = limpiarCampo($_POST['localidadFiscal']);
    $cp1               = limpiarCampo($_POST['cp1']);
    $provincia1        = limpiarCampo($_POST['provincia1']);
    $localidad1        = limpiarCampo($_POST['localidad1']);
    $cp2               = limpiarCampo($_POST['cp2']);
    $provincia2        = limpiarCampo($_POST['provincia2']);
    $localidad2        = limpiarCampo($_POST['localidad2']);

    $contrasenaHash = password_hash($contrasenaTexto, PASSWORD_DEFAULT);

    try {
        $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com:3306;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');
        $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO Usuarios (
            usuario, correo, contrasena, permiso, tipoContrato,
            cpFiscal, provinciaFiscal, localidadFiscal,
            cp1, provincia1, localidad1,
            cp2, provincia2, localidad2
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $bd->prepare($sql);
        $stmt->execute([
            $usuario,
            $correo,
            $contrasenaHash,
            $permiso,
            $tipoContrato,
            $cpFiscal,
            $provinciaFiscal,
            $localidadFiscal,
            $cp1,
            $provincia1,
            $localidad1,
            $cp2,
            $provincia2,
            $localidad2
        ]);

        echo "<p style='color:green;'>Usuario registrado con éxito.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error al registrar: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuarios</title>
    <script>
      // Función para mostrar/ocultar campos según el "permiso" seleccionado
      function toggleCampos() {
        const permisoSelect = document.getElementById('permiso');
        const valorPermiso = permisoSelect.value;

        // Contenedores que queremos mostrar/ocultar
        const contratoContainer = document.getElementById('contrato-container');
        const direccionesContainer = document.getElementById('direcciones-container');

        if (valorPermiso === 'cliente') {
          // Mostrar
          contratoContainer.style.display = 'block';
          direccionesContainer.style.display = 'block';
        } else {
          // Ocultar
          contratoContainer.style.display = 'none';
          direccionesContainer.style.display = 'none';
        }
      }

      // Cuando cargue la página, configuramos la visibilidad inicial
      document.addEventListener('DOMContentLoaded', () => {
        toggleCampos(); // Llamamos una vez para ajustar según el valor por defecto
        // Y cada vez que cambie el select
        document.getElementById('permiso').addEventListener('change', toggleCampos);
      });
    </script>
</head>
<body>
<h1>Registrar nuevo usuario</h1>
<form method="post">

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

    <!-- Contenedor para Tipo de Contrato -->
    <div id="contrato-container">
      <label>Tipo de Contrato:</label><br>
      <select name="tipoContrato">
          <option value="mantenimientoCompleto">Mantenimiento Completo</option>
          <option value="mantenimientoManoObra">Mantenimiento Mano de Obra</option>
      </select><br><br>
    </div>

    <!-- Contenedor para todas las direcciones -->
    <div id="direcciones-container">
      <!-- DIRECCIÓN FISCAL -->
      <h3>Dirección Fiscal</h3>
      <label>CP Fiscal:</label><br>
      <input type="number" name="cpFiscal"><br><br>
      
      <label>Provincia Fiscal:</label><br>
      <input type="text" name="provinciaFiscal"><br><br>
      
      <label>Localidad Fiscal:</label><br>
      <input type="text" name="localidadFiscal"><br><br>

      <!-- PRIMERA DIRECCIÓN ADICIONAL -->
      <h3>Primera dirección adicional</h3>
      <label>CP:</label><br>
      <input type="number" name="cp1"><br><br>

      <label>Provincia:</label><br>
      <input type="text" name="provincia1"><br><br>

      <label>Localidad:</label><br>
      <input type="text" name="localidad1"><br><br>

      <!-- SEGUNDA DIRECCIÓN ADICIONAL -->
      <h3>Segunda dirección adicional</h3>
      <label>CP:</label><br>
      <input type="number" name="cp2"><br><br>

      <label>Provincia:</label><br>
      <input type="text" name="provincia2"><br><br>

      <label>Localidad:</label><br>
      <input type="text" name="localidad2"><br><br>
    </div>

    <input type="submit" name="registrar" value="Registrar Usuario">
</form>
</body>
</html>
