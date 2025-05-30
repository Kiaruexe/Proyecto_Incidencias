<?php
session_start();

try {
  $bd = new PDO(
    'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
    'Mapapli',
    '9R%d5cf62'
  );
  $filtroPermiso = $_GET['permiso'] ?? 'todos';
  if ($filtroPermiso !== 'todos') {
    $query = $bd->prepare("SELECT * FROM Usuarios WHERE permiso = ?");
    $query->execute([$filtroPermiso]);
  } else {
    $query = $bd->query("SELECT * FROM Usuarios");
  }
} catch (PDOException $e) {
  echo "Error: " . $e->getMessage();
  exit;
}

$isClienteView = $filtroPermiso === 'cliente';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Ver Usuarios</title>
  <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: Arial, sans-serif;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background: #f5f7fa;
    }

    .nav {
      background: #00225a;
      padding: 1rem;
      text-align: center;
    }

    .nav .brand {
      color: #fff;
      font-size: 1.8rem;
      font-weight: bold;
    }

    .container {
      flex: 1;
      width: 95%;
      max-width: 1200px;
      margin: 20px auto;
    }

    h1 {
      color: #00225a;
      margin-bottom: 20px;
      border-bottom: 2px solid #2573fa;
      padding-bottom: 8px;
    }

    .button-container {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .button {
      padding: 10px 15px;
      background: #4caf50;
      color: #fff;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      cursor: pointer;
      font-weight: bold;
      transition: background .3s;
    }

    .button:hover {
      background: #45a049;
    }

    .home-button {
      background: #2196f3;
    }

    .home-button:hover {
      background: #0b7dda;
    }

    form {
      margin-bottom: 20px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      background: #eaf1ff;
      padding: 15px;
      border: 1px solid #2573fa;
      border-radius: 5px;
    }

    form label {
      color: #00225a;
      font-weight: 500;
    }

    form select,
    form button {
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    form button {
      background: #00225a;
      color: #fff;
      cursor: pointer;
      transition: background .3s;
    }

    form button:hover {
      background: #2573fa;
    }

    .table-container {
      overflow-x: auto;
      background: #fff;
      border: 1px solid #2573fa;
      border-radius: 5px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      padding: 10px;
      border: 1px solid #c5d5ff;
      text-align: left;
    }

    th {
      background: #2573fa;
      color: #fff;
      position: sticky;
      top: 0;
    }

    tr:nth-child(even) {
      background: #f0f5ff;
    }

    tr:hover {
      background: #e5eeff;
    }

    .card-view {
      display: none;
    }

    .card {
      background: #fff;
      border: 1px solid #c5d5ff;
      border-radius: 8px;
      margin-bottom: 15px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .card-header {
      background: #eaf1ff;
      border-bottom: 2px solid #2573fa;
      padding: 12px 15px;
      font-size: 1.1rem;
      font-weight: bold;
      color: #00225a;
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
    }

    .card-body {
      padding: 10px 15px;
    }

    .card-row {
      display: flex;
      margin-bottom: 8px;
    }

    .card-label {
      flex: 0 0 45%;
      font-weight: bold;
      color: #2573fa;
    }

    .card-value {
      flex: 1;
      color: #333;
    }

    @media(max-width:768px) {
      form {
        flex-direction: column;
        align-items: flex-start;
      }

      form label,
      form select,
      form button {
        width: 100%;
      }
    }

    @media(max-width:576px) {
      .table-container {
        display: none;
      }

      .card-view {
        display: block;
      }
    }

    footer {
      background: #000;
      color: #fff;
      text-align: center;
      padding: 10px;
      margin-top: auto;
    }
  </style>
</head>

<body>
  <nav class="nav"><span class="brand">Mapache Security</span></nav>
  <div class="container">
    <h1>Lista de Usuarios</h1>

    <div class="button-container">
      <a href="../home.php" class="button home-button">Volver al Inicio</a>
      <a href="crearUsuarios.php" class="button">Crear Nuevo Usuario</a>
    </div>

    <form method="get">
      <label for="permiso">Filtrar por Permiso:</label>
      <select name="permiso" id="permiso">
        <option value="todos" <?= $filtroPermiso === 'todos' ? 'selected' : ''; ?>>Todos</option>
        <option value="cliente" <?= $filtroPermiso === 'cliente' ? 'selected' : ''; ?>>Cliente</option>
        <option value="recepcion" <?= $filtroPermiso === 'recepcion' ? 'selected' : ''; ?>>Recepción</option>
        <option value="tecnico" <?= $filtroPermiso === 'tecnico' ? 'selected' : ''; ?>>Técnico</option>
        <option value="admin" <?= $filtroPermiso === 'admin' ? 'selected' : ''; ?>>Admin</option>
        <option value="jefeTecnico" <?= $filtroPermiso === 'jefeTecnico' ? 'selected' : ''; ?>>Jefe Técnico</option>
      </select>
      <button type="submit">Filtrar</button>
    </form>

    <?php if ($isClienteView): ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Usuario</th>
              <th>Correo</th>
              <th>Permiso</th>
              <th>CP Fiscal</th>
              <th>Provincia Fiscal</th>
              <th>Localidad Fiscal</th>
              <th>Dirección Fiscal</th>
              <th>CP1</th>
              <th>Provincia1</th>
              <th>Localidad1</th>
              <th>Dirección1</th>
              <th>CP2</th>
              <th>Provincia2</th>
              <th>Localidad2</th>
              <th>Dirección2</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $query->fetch()): ?>
              <tr>
                <td><?= htmlspecialchars($row['idUsuarios']) ?></td>
                <td><?= htmlspecialchars($row['usuario']) ?></td>
                <td><?= htmlspecialchars($row['correo']) ?></td>
                <td><?= htmlspecialchars($row['permiso']) ?></td>
                <td><?= htmlspecialchars($row['cpFiscal']) ?></td>
                <td><?= htmlspecialchars($row['provinciaFiscal']) ?></td>
                <td><?= htmlspecialchars($row['localidadFiscal']) ?></td>
                <td><?= htmlspecialchars($row['direccionFiscal']) ?></td>
                <td><?= htmlspecialchars($row['cp1']) ?></td>
                <td><?= htmlspecialchars($row['provincia1']) ?></td>
                <td><?= htmlspecialchars($row['localidad1']) ?></td>
                <td><?= htmlspecialchars($row['direccion1']) ?></td>
                <td><?= htmlspecialchars($row['cp2']) ?></td>
                <td><?= htmlspecialchars($row['provincia2']) ?></td>
                <td><?= htmlspecialchars($row['localidad2']) ?></td>
                <td><?= htmlspecialchars($row['direccion2']) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <div class="card-view">
        <?php
        $query->execute($filtroPermiso !== 'todos' ? [$filtroPermiso] : []);
        while ($row = $query->fetch()): ?>
          <div class="card">
            <div class="card-header">
              <?= htmlspecialchars($row['usuario']) ?> (ID <?= htmlspecialchars($row['idUsuarios']) ?>)
            </div>
            <div class="card-body">
              <div class="card-row">
                <div class="card-label">Correo:</div>
                <div class="card-value"><?= htmlspecialchars($row['correo']) ?></div>
              </div>
              <div class="card-row">
                <div class="card-label">Permiso:</div>
                <div class="card-value"><?= htmlspecialchars($row['permiso']) ?></div>
              </div>
              <div class="card-row">
                <div class="card-label">Fiscal:</div>
                <div class="card-value">
                  <?= htmlspecialchars("{$row['cpFiscal']}, {$row['provinciaFiscal']}, {$row['localidadFiscal']}, {$row['direccionFiscal']}") ?>
                </div>
              </div>
              <div class="card-row">
                <div class="card-label">Ubicación 1:</div>
                <div class="card-value">
                  <?= htmlspecialchars("{$row['cp1']}, {$row['provincia1']}, {$row['localidad1']}, {$row['direccion1']}") ?>
                </div>
              </div>
              <div class="card-row">
                <div class="card-label">Ubicación 2:</div>
                <div class="card-value">
                  <?= htmlspecialchars("{$row['cp2']}, {$row['provincia2']}, {$row['localidad2']}, {$row['direccion2']}") ?>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Usuario</th>
              <th>Correo</th>
              <th>Permiso</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $query->fetch()): ?>
              <tr>
                <td><?= htmlspecialchars($row['idUsuarios']) ?></td>
                <td><?= htmlspecialchars($row['usuario']) ?></td>
                <td><?= htmlspecialchars($row['correo']) ?></td>
                <td><?= htmlspecialchars($row['permiso']) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <div class="card-view">
        <?php
        $query->execute($filtroPermiso !== 'todos' ? [$filtroPermiso] : []);
        while ($row = $query->fetch()): ?>
          <div class="card">
            <div class="card-header"><?= htmlspecialchars($row['usuario']) ?> (ID
              <?= htmlspecialchars($row['idUsuarios']) ?>)</div>
            <div class="card-body">
              <div class="card-row">
                <div class="card-label">Correo:</div>
                <div class="card-value"><?= htmlspecialchars($row['correo']) ?></div>
              </div>
              <div class="card-row">
                <div class="card-label">Permiso:</div>
                <div class="card-value"><?= htmlspecialchars($row['permiso']) ?></div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>

  </div>
  <footer>&copy;  <?php echo date('Y'); ?> Mapache Security</footer>
</body>

</html>