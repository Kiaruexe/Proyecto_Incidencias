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
        <style>
          #direcciones-container {
            display: none;
          }
        </style>
    </head>
    <body>
      <h1>Modificar Usuario</h1>
      <form method="post">
        <label>Nombre de usuario:</label><br>
        <input type="text" name="usuario"
          placeholder="<?= htmlspecialchars($usuarioData['usuario']); ?>"><br><br>

        <label>Correo:</label><br>
        <input type="email" name="correo"
          placeholder="<?= htmlspecialchars($usuarioData['correo']); ?>"><br><br>

        <label>Contraseña (dejar en blanco para mantener):</label><br>
        <input type="password" name="contrasena" placeholder="Nueva contraseña"><br><br>

        <label>Permiso:</label><br>
        <select name="permiso" id="permiso">
          <option value="cliente"     <?= $usuarioData['permiso']=='cliente'     ? 'selected' : ''; ?>>Cliente</option>
          <option value="recepcion"   <?= $usuarioData['permiso']=='recepcion'   ? 'selected' : ''; ?>>Recepción</option>
          <option value="tecnico"     <?= $usuarioData['permiso']=='tecnico'     ? 'selected' : ''; ?>>Técnico</option>
          <option value="admin"       <?= $usuarioData['permiso']=='admin'       ? 'selected' : ''; ?>>Admin</option>
          <option value="jefeTecnico" <?= $usuarioData['permiso']=='jefeTecnico' ? 'selected' : ''; ?>>Jefe Técnico</option>
        </select><br><br>

        <div id="direcciones-container">
          <h3>Dirección Fiscal</h3>
          <label>CP Fiscal:</label><br>
          <input type="number" name="cpFiscal"
            placeholder="<?= htmlspecialchars($usuarioData['cpFiscal']); ?>"><br><br>

          <label>Provincia Fiscal:</label><br>
          <input type="text" name="provinciaFiscal"
            placeholder="<?= htmlspecialchars($usuarioData['provinciaFiscal']); ?>"><br><br>

          <label>Localidad Fiscal:</label><br>
          <input type="text" name="localidadFiscal"
            placeholder="<?= htmlspecialchars($usuarioData['localidadFiscal']); ?>"><br><br>

          <label>Dirección Fiscal:</label><br>
          <input type="text" name="direccionFiscal"
            placeholder="<?= htmlspecialchars($usuarioData['direccionFiscal']); ?>"><br><br>

          <h3>Primera dirección adicional</h3>
          <label>CP:</label><br>
          <input type="number" name="cp1"
            placeholder="<?= htmlspecialchars($usuarioData['cp1']); ?>"><br><br>

          <label>Provincia:</label><br>
          <input type="text" name="provincia1"
            placeholder="<?= htmlspecialchars($usuarioData['provincia1']); ?>"><br><br>

          <label>Localidad:</label><br>
          <input type="text" name="localidad1"
            placeholder="<?= htmlspecialchars($usuarioData['localidad1']); ?>"><br><br>

          <label>Dirección:</label><br>
          <input type="text" name="direccion1"
            placeholder="<?= htmlspecialchars($usuarioData['direccion1']); ?>"><br><br>

          <h3>Segunda dirección adicional</h3>
          <label>CP:</label><br>
          <input type="number" name="cp2"
            placeholder="<?= htmlspecialchars($usuarioData['cp2']); ?>"><br><br>

          <label>Provincia:</label><br>
          <input type="text" name="provincia2"
            placeholder="<?= htmlspecialchars($usuarioData['provincia2']); ?>"><br><br>

          <label>Localidad:</label><br>
          <input type="text" name="localidad2"
            placeholder="<?= htmlspecialchars($usuarioData['localidad2']); ?>"><br><br>

          <label>Dirección:</label><br>
          <input type="text" name="direccion2"
            placeholder="<?= htmlspecialchars($usuarioData['direccion2']); ?>"><br><br>
        </div>

        <input type="submit" name="modificar" value="Modificar Usuario" onclick="return confirm('¿Estás seguro de modificar este usuario?');">
      </form>
      <p><a href="../home.php">Volver al home</a></p>

      <script>
        document.addEventListener('DOMContentLoaded', function() {
          const permisoEl = document.getElementById('permiso');
          const direccionesEl = document.getElementById('direcciones-container');

          function toggleCampos() {
            direccionesEl.style.display = permisoEl.value === 'cliente'
              ? 'block'
              : 'none';
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
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .filters-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .buttons-container {
            margin: 15px 0;
        }
        .btn {
            padding: 8px 15px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #e74c3c;
        }
        .btn-secondary:hover {
            background-color: #c0392b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
            cursor: pointer;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        tbody tr:hover {
            background-color: #f5f5f5;
        }
        .action-btn {
            display: inline-block;
            padding: 6px 12px;
            margin: 0 3px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
            cursor: pointer;
        }
        .btn-modificar {
            background-color: #2ecc71;
        }
        .btn-modificar:hover {
            background-color: #27ae60;
        }
        .btn-borrar {
            background-color: #e74c3c;
        }
        .btn-borrar:hover {
            background-color: #c0392b;
        }
        .no-results {
            padding: 15px;
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            border-radius: 5px;
            margin: 10px 0;
        }
        .loader {
            display: none;
            margin: 20px auto;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <h1>Seleccionar Usuario a Modificar</h1>
    
    <div id="loader" class="loader"></div>
    
    <div class="filters-container">
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
    </div>
    
    <div class="buttons-container">
        <button id="btn-filtrar" class="btn btn-primary">Aplicar Filtros</button>
        <button id="btn-reset" class="btn btn-secondary">Limpiar Filtros</button>
    </div>
    
    <table id="tabla-usuarios">
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
            <?php foreach ($usuarios as $u): ?>
                <tr data-permiso="<?= htmlspecialchars($u['permiso']); ?>" data-id="<?= htmlspecialchars($u['idUsuarios']); ?>">
                    <td><?= htmlspecialchars($u['idUsuarios']); ?></td>
                    <td><?= htmlspecialchars($u['usuario']); ?></td>
                    <td><?= htmlspecialchars($u['correo']); ?></td>
                    <td><?= htmlspecialchars($u['permiso']); ?></td>
                    <td>
                        <a href="?id=<?= htmlspecialchars($u['idUsuarios']); ?>" class="action-btn btn-modificar" onclick="return confirm('¿Deseas modificar este usuario?');">Modificar</a>
                        <a href="#" class="action-btn btn-borrar" data-id="<?= htmlspecialchars($u['idUsuarios']); ?>">Borrar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div id="no-results" class="no-results" style="display: none;">
        No se encontraron usuarios con los filtros seleccionados.
    </div>
    
    <p><a href="../home.php">Volver al home</a></p>

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
</body>
</html>