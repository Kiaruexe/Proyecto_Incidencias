<?php
session_start();
$mensajeError = '';
if (isset($_POST['registrar'])) {
  function limpiarCampo($valor)
  {
    return !empty($valor) ? $valor : null;
  }
  $usuario = $_POST['usuario'] ?? null;
  $correo = $_POST['correo'] ?? null;
  $contrasenaTexto = $_POST['contrasena'] ?? null;
  $permiso = $_POST['permiso'] ?? null;
  $cpFiscal = limpiarCampo($_POST['cpFiscal'] ?? '');
  $provinciaFiscal = limpiarCampo($_POST['provinciaFiscal'] ?? '');
  $localidadFiscal = limpiarCampo($_POST['localidadFiscal'] ?? '');
  $direccionFiscal = limpiarCampo($_POST['direccionFiscal'] ?? '');
  $cp1 = limpiarCampo($_POST['cp1'] ?? '');
  $provincia1 = limpiarCampo($_POST['provincia1'] ?? '');
  $localidad1 = limpiarCampo($_POST['localidad1'] ?? '');
  $direccion1 = limpiarCampo($_POST['direccion1'] ?? '');
  $cp2 = limpiarCampo($_POST['cp2'] ?? '');
  $provincia2 = limpiarCampo($_POST['provincia2'] ?? '');
  $localidad2 = limpiarCampo($_POST['localidad2'] ?? '');
  $direccion2 = limpiarCampo($_POST['direccion2'] ?? '');
  try {
    $bd = new PDO(
      'mysql:host=PMYSQL168.dns-servicio.com;port=3306;dbname=9981336_aplimapa',
      'Mapapli',
      '9R%d5cf62',
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $sql = "SELECT COUNT(*) FROM Usuarios WHERE usuario = ? OR correo = ?";
    $stmt = $bd->prepare($sql);
    $stmt->execute([$usuario, $correo]);
    if ($stmt->fetchColumn() > 0) {
      $mensajeError = '⚠️ Ya existe un usuario con ese nombre o correo electrónico.';
    } else {
      $contrasenaHash = password_hash($contrasenaTexto, PASSWORD_DEFAULT);
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
        $bd->commit();
        echo "<script>
                        alert('✅ Usuario registrado con éxito.');
                        window.location.href = '../home.php';
                      </script>";
        exit;
      } else {
        $bd->rollBack();
        $mensajeError = "Error al insertar el usuario.";
      }
    }
  } catch (PDOException $e) {
    if (isset($bd) && $bd->inTransaction()) {
      $bd->rollBack();
    }
    $mensajeError = '⚠️ Error al registrar el usuario: ' . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrar Usuario - Mapache Security</title>
  <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body {
      background: #f0f2f5;
    }

    header,
    footer {
      color: #fff;
      text-align: center;
      padding: 15px;
    }

    header {
      background: #00225a;
      padding: 20px;
    }

    footer {
      background: #000000;
      font-weight: bold;
    }

    header .brand {
      font-weight: bold;
      font-size: 2.5rem;
    }

    .container {
      max-width: 90%;
      margin: 100px auto 40px;
      background: #f1f2f2;
      padding: 30px;
      border: 2px solid #00225a;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    h1 {
      margin-bottom: 20px;
      color: #00225a;
      text-align: center;
    }

    .form-container {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .datos-basicos {
      display: flex;
      flex-direction: column;
      gap: 15px;
      max-width: 400px;
      margin: 0 auto;
    }

    .permiso-section {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
      margin: 20px 0;
    }

    .permiso-section label,
    .permiso-section select {
      max-width: 300px;
      width: 100%;
    }

    .direcciones-section {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .buttons-container {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 20px;
    }

    .btn {
      padding: 10px 20px;
      background: #2573fa;
      color: #fff;
      border: none;
      border-radius: 20px;
      font-size: 1rem;
      cursor: pointer;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      max-width: 200px;
    }

    input[type="submit"].btn {
      width: auto;
    }

    label {
      display: block;
      margin-top: 5px;
      font-weight: 500;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="number"],
    select {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-sizing: border-box;
    }

    input[type="submit"]:hover,
    .btn:hover {
      background: #1e60d2;
    }

    .btn-home {
      background: #00225a;
    }

    .btn-home:hover {
      background: #001845;
    }

    .error-message {
      background-color: #ffdddd;
      color: #900;
      padding: 10px;
      border: 1px solid #f00;
      border-radius: 5px;
      text-align: center;
    }

    .direccion-row {
      display: flex;
      gap: 10px;
      margin-bottom: 15px;
    }

    .direccion-row label {
      margin-top: 0;
    }

    .direccion-row .campo {
      flex: 1;
    }

    h2 {
      margin-top: 20px;
      margin-bottom: 10px;
      font-size: 1.3rem;
      color: #00225a;
    }

    @media (max-width: 768px) {
      .container {
        margin: 80px 10px 20px;
        padding: 20px;
      }

      .direccion-row {
        flex-direction: column;
        gap: 5px;
      }

      .datos-basicos {
        max-width: 100%;
      }
    }
  </style>
  <script>
    function toggleCampos() {
      const permiso = document.getElementById('permiso').value;
      const cont = document.getElementById('direcciones-container');
      cont.style.display = permiso === 'cliente' ? 'block' : 'none';
      document.querySelectorAll('.fiscal-field').forEach(f => {
        permiso === 'cliente' ? f.setAttribute('required', '') : f.removeAttribute('required');
      });
    }
    function prevenirEnvio() {
      const btn = document.querySelector('input[type="submit"]');
      document.getElementById('registro-form').addEventListener('submit', () => {
        btn.disabled = true;
        btn.value = 'Registrando…';
      });
    }
    document.addEventListener('DOMContentLoaded', () => {
      toggleCampos();
      document.getElementById('permiso').addEventListener('change', toggleCampos);
      prevenirEnvio();
    });
  </script>
</head>

<body>
  <header>
    <div class="brand">Mapache Security</div>
  </header>
  <div class="container">
    <h1>Registrar nuevo usuario</h1>
    <?php if (!empty($mensajeError)): ?>
      <div class="error-message"><?= $mensajeError; ?></div>
    <?php endif; ?>
    <form method="post" id="registro-form" class="form-container">
      <div class="datos-basicos">
        <div>
          <label for="usuario">Nombre de usuario:</label>
          <input type="text" id="usuario" name="usuario" required
            value="<?= htmlspecialchars($_POST['usuario'] ?? ''); ?>">
        </div>

        <div>
          <label for="correo">Correo:</label>
          <input type="email" id="correo" name="correo" required
            value="<?= htmlspecialchars($_POST['correo'] ?? ''); ?>">
        </div>

        <div>
          <label for="contrasena">Contraseña:</label>
          <input type="password" id="contrasena" name="contrasena" required>
        </div>
      </div>

      <div class="permiso-section">
        <label for="permiso">Permiso:</label>
        <select id="permiso" name="permiso">
          <?php foreach (['cliente', 'recepcion', 'tecnico', 'admin', 'jefeTecnico'] as $opt): ?>
            <option value="<?= $opt; ?>" <?= (($_POST['permiso'] ?? '') === $opt ? 'selected' : ''); ?>>
              <?= ucfirst($opt); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="direcciones-container" class="direcciones-section">
        <div>
          <h2>Dirección Fiscal</h2>
          <div class="direccion-row">
            <div class="campo">
              <label for="cpFiscal">CP Fiscal:</label>
              <input type="number" id="cpFiscal" name="cpFiscal" class="fiscal-field"
                value="<?= htmlspecialchars($_POST['cpFiscal'] ?? ''); ?>">
            </div>

            <div class="campo">
              <label for="provinciaFiscal">Provincia Fiscal:</label>
              <input type="text" id="provinciaFiscal" name="provinciaFiscal" class="fiscal-field"
                value="<?= htmlspecialchars($_POST['provinciaFiscal'] ?? ''); ?>">
            </div>

            <div class="campo">
              <label for="localidadFiscal">Localidad Fiscal:</label>
              <input type="text" id="localidadFiscal" name="localidadFiscal" class="fiscal-field"
                value="<?= htmlspecialchars($_POST['localidadFiscal'] ?? ''); ?>">
            </div>

            <div class="campo">
              <label for="direccionFiscal">Dirección Fiscal:</label>
              <input type="text" id="direccionFiscal" name="direccionFiscal" class="fiscal-field"
                value="<?= htmlspecialchars($_POST['direccionFiscal'] ?? ''); ?>">
            </div>
          </div>
        </div>

        <div>
          <h2>Primera dirección adicional</h2>
          <div class="direccion-row">
            <div class="campo">
              <label for="cp1">CP:</label>
              <input type="number" id="cp1" name="cp1" value="<?= htmlspecialchars($_POST['cp1'] ?? ''); ?>">
            </div>

            <div class="campo">
              <label for="provincia1">Provincia:</label>
              <input type="text" id="provincia1" name="provincia1"
                value="<?= htmlspecialchars($_POST['provincia1'] ?? ''); ?>">
            </div>

            <div class="campo">
              <label for="localidad1">Localidad:</label>
              <input type="text" id="localidad1" name="localidad1"
                value="<?= htmlspecialchars($_POST['localidad1'] ?? ''); ?>">
            </div>

            <div class="campo">
              <label for="direccion1">Dirección:</label>
              <input type="text" id="direccion1" name="direccion1"
                value="<?= htmlspecialchars($_POST['direccion1'] ?? ''); ?>">
            </div>
          </div>
        </div>

        <div>
          <h2>Segunda dirección adicional</h2>
          <div class="direccion-row">
            <div class="campo">
              <label for="cp2">CP:</label>
              <input type="number" id="cp2" name="cp2" value="<?= htmlspecialchars($_POST['cp2'] ?? ''); ?>">
            </div>

            <div class="campo">
              <label for="provincia2">Provincia:</label>
              <input type="text" id="provincia2" name="provincia2"
                value="<?= htmlspecialchars($_POST['provincia2'] ?? ''); ?>">
            </div>

            <div class="campo">
              <label for="localidad2">Localidad:</label>
              <input type="text" id="localidad2" name="localidad2"
                value="<?= htmlspecialchars($_POST['localidad2'] ?? ''); ?>">
            </div>

            <div class="campo">
              <label for="direccion2">Dirección:</label>
              <input type="text" id="direccion2" name="direccion2"
                value="<?= htmlspecialchars($_POST['direccion2'] ?? ''); ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="buttons-container">
        <input type="submit" name="registrar" value="Registrar Usuario" class="btn">
        <a href="../home.php" class="btn btn-home">Volver a Inicio</a>
      </div>
    </form>
  </div>
  <footer>
    &copy; <?= date('Y'); ?> Mapache Security
  </footer>
</body>

</html>