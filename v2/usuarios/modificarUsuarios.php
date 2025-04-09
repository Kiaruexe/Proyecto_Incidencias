<?php 
session_start();

if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../login.php");
    exit;
}
try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error de conexión: " . $e->getMessage() . "</p>";
    exit;
}

if (!isset($_GET['id'])) {
    try {
        $sql = "SELECT idUsuarios, usuario FROM Usuarios";
        $stmt = $bd->prepare($sql);
        $stmt->execute();
        $usuarios = $stmt->fetchAll();
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error al obtener usuarios: " . $e->getMessage() . "</p>";
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Seleccionar Usuario a Modificar</title>
        <link rel="stylesheet" href="../css/style.css">
    </head>
    <body>
        <h1>Seleccionar Usuario a Modificar</h1>
        <form method="get" action="">
            <label for="id">Usuario:</label>
            <select name="id" id="id" >
                <option value="">-- Seleccione un usuario --</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= htmlspecialchars($u['idUsuarios']); ?>">
                        <?= htmlspecialchars($u['usuario']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>
            <input type="submit" value="Modificar Usuario">
        </form>
        <p><a href="../home.php">Volver al home</a></p>
    </body>
    </html>
    <?php
    exit;
}
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

// Función para obtener el valor enviado en el formulario o conservar el actual
function obtenerValor($campo, $actual) {
    return isset($_POST[$campo]) && trim($_POST[$campo]) !== '' ? trim($_POST[$campo]) : $actual;
}

if (isset($_POST['modificar'])) {
    // Para cada campo, se usa el valor enviado si no está vacío, de lo contrario se conserva el actual
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
            usuario = ?,
            correo = ?,
            contrasena = ?,
            permiso = ?,
            cpFiscal = ?,
            provinciaFiscal = ?,
            localidadFiscal = ?,
            direccionFiscal = ?,
            cp1 = ?,
            provincia1 = ?,
            localidad1 = ?,
            direccion1 = ?,
            cp2 = ?,
            provincia2 = ?,
            localidad2 = ?,
            direccion2 = ?
            WHERE idUsuarios = ?";
            
        $stmtUpdate = $bd->prepare($sqlUpdate);
        $stmtUpdate->execute([
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
            $direccion2,
            $idUsuarioModificar
        ]);
        echo "<p style='color:green;'>Usuario modificado con éxito.</p>";
        // Actualizar datos para reflejar en el formulario
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
        echo "<p style='color:red;'>Error al modificar usuario: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Modificar Usuario</title>
    <link rel="stylesheet" href="../css/style.css">
    <script>º
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
    <h1>Modificar Usuario</h1>
    <form method="post">
        <label>Nombre de usuario:</label><br>
        <input type="text" name="usuario" placeholder="<?= htmlspecialchars($usuarioData['usuario']); ?>"><br><br>

        <label>Correo:</label><br>
        <input type="email" name="correo" placeholder="<?= htmlspecialchars($usuarioData['correo']); ?>"><br><br>

        <label>Contraseña (déjala en blanco para conservar la actual):</label><br>
        <input type="password" name="contrasena" placeholder="Nueva contraseña"><br><br>

        <label>Permiso:</label><br>
        <select name="permiso" id="permiso">
            <option value="cliente" <?= $usuarioData['permiso']=='cliente' ? 'selected' : ''; ?>>Cliente</option>
            <option value="recepcion" <?= $usuarioData['permiso']=='recepcion' ? 'selected' : ''; ?>>Recepción</option>
            <option value="tecnico" <?= $usuarioData['permiso']=='tecnico' ? 'selected' : ''; ?>>Técnico</option>
            <option value="admin" <?= $usuarioData['permiso']=='admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="jefeTecnico" <?= $usuarioData['permiso']=='jefeTecnico' ? 'selected' : ''; ?>>Jefe Técnico</option>
        </select><br><br>

        <div id="direcciones-container">
            <h3>Dirección Fiscal</h3>
            <label>CP Fiscal:</label><br>
            <input type="number" name="cpFiscal" class="fiscal-field" placeholder="<?= htmlspecialchars($usuarioData['cpFiscal']); ?>"><br><br>

            <label>Provincia Fiscal:</label><br>
            <input type="text" name="provinciaFiscal" class="fiscal-field" placeholder="<?= htmlspecialchars($usuarioData['provinciaFiscal']); ?>"><br><br>

            <label>Localidad Fiscal:</label><br>
            <input type="text" name="localidadFiscal" class="fiscal-field" placeholder="<?= htmlspecialchars($usuarioData['localidadFiscal']); ?>"><br><br>

            <label>Dirección Fiscal:</label><br>
            <input type="text" name="direccionFiscal" class="fiscal-field" placeholder="<?= htmlspecialchars($usuarioData['direccionFiscal']); ?>"><br><br>

            <h3>Primera dirección adicional</h3>
            <label>CP:</label><br>
            <input type="number" name="cp1" placeholder="<?= htmlspecialchars($usuarioData['cp1']); ?>"><br><br>

            <label>Provincia:</label><br>
            <input type="text" name="provincia1" placeholder="<?= htmlspecialchars($usuarioData['provincia1']); ?>"><br><br>

            <label>Localidad:</label><br>
            <input type="text" name="localidad1" placeholder="<?= htmlspecialchars($usuarioData['localidad1']); ?>"><br><br>

            <label>Dirección:</label><br>
            <input type="text" name="direccion1" placeholder="<?= htmlspecialchars($usuarioData['direccion1']); ?>"><br><br>

            <h3>Segunda dirección adicional</h3>
            <label>CP:</label><br>
            <input type="number" name="cp2" placeholder="<?= htmlspecialchars($usuarioData['cp2']); ?>"><br><br>

            <label>Provincia:</label><br>
            <input type="text" name="provincia2" placeholder="<?= htmlspecialchars($usuarioData['provincia2']); ?>"><br><br>

            <label>Localidad:</label><br>
            <input type="text" name="localidad2" placeholder="<?= htmlspecialchars($usuarioData['localidad2']); ?>"><br><br>

            <label>Dirección:</label><br>
            <input type="text" name="direccion2" placeholder="<?= htmlspecialchars($usuarioData['direccion2']); ?>"><br><br>
        </div>

        <input type="submit" name="modificar" value="Modificar Usuario">
    </form>
    <p><a href="../home.php">Volver al home</a></p>
</body>
</html>
