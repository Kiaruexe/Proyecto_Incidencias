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
    $cpFiscal          = limpiarCampo($_POST['cpFiscal']);
    $provinciaFiscal   = limpiarCampo($_POST['provinciaFiscal']);
    $localidadFiscal   = limpiarCampo($_POST['localidadFiscal']);
    $direccionFiscal   = limpiarCampo($_POST['direccionFiscal']);
    $cp1               = limpiarCampo($_POST['cp1']);
    $provincia1        = limpiarCampo($_POST['provincia1']);
    $localidad1        = limpiarCampo($_POST['localidad1']);
    $direccion1        = limpiarCampo($_POST['direccion1']);
    $cp2               = limpiarCampo($_POST['cp2']);
    $provincia2        = limpiarCampo($_POST['provincia2']);
    $localidad2        = limpiarCampo($_POST['localidad2']);
    $direccion2        = limpiarCampo($_POST['direccion2']);

    $contrasenaHash = password_hash($contrasenaTexto, PASSWORD_DEFAULT);

    try {
        $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com:3306;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');

        $sql = "INSERT INTO Usuarios (
            usuario, correo, contrasena, permiso,
            cpFiscal, provinciaFiscal, localidadFiscal, direccionFiscal,
            cp1, provincia1, localidad1, direccion1,
            cp2, provincia2, localidad2, direccion2
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $bd->prepare($sql);
        $stmt->execute([
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
    <link rel="stylesheet" href="../css/style.css">
    <script>
      function toggleCampos() {
        const permisoSelect = document.getElementById('permiso');
        const valorPermiso = permisoSelect.value;
        const direccionesContainer = document.getElementById('direcciones-container');

        if (valorPermiso === 'cliente') {
          direccionesContainer.style.display = 'block';
        } else {
          direccionesContainer.style.display = 'none';
        }
      }
      document.addEventListener('DOMContentLoaded', () => {
        toggleCampos(); 
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
    <div id="direcciones-container">
      <h3>Dirección Fiscal</h3>
      <label>CP Fiscal:</label><br>
      <input type="number" name="cpFiscal"><br><br>
      
      <label>Provincia Fiscal:</label><br>
      <input type="text" name="provinciaFiscal"><br><br>
      
      <label>Localidad Fiscal:</label><br>
      <input type="text" name="localidadFiscal"><br><br>
      
      <label>Dirección Fiscal:</label><br>
      <input type="text" name="direccionFiscal"><br><br>

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