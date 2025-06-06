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

// Verificar que solo admin y técnico puedan acceder a funciones de modificación
if (!in_array($permisoUsuario, ['admin', 'tecnico'])) {
    echo "<script>
            alert('⚠️ Acceso denegado. No tiene permisos para realizar esta acción.');
            window.location.href = '../home.php';
          </script>";
    exit;
}

// Obtener el permiso del usuario actual
$permisoUsuarioActual = $permisoUsuario;

// Función para leer tipos de equipo desde archivos de configuración
function leerTiposEquipo() {
    $archivoJson = '../configuracion/tiposEquipo.json';
    $archivoPhp = '../configuracion/cofTiposEquipo.php';
    
    // Intentar leer desde el archivo JSON (prioridad)
    if (file_exists($archivoJson)) {
        $contenido = file_get_contents($archivoJson);
        $datos = json_decode($contenido, true);
        if ($datos !== null) {
            return $datos;
        }
    }
    
    // Si no existe el JSON o falla la decodificación, intentar el PHP
    if (file_exists($archivoPhp)) {
        include_once($archivoPhp);
        if (isset($tiposEquipo)) {
            return $tiposEquipo;
        }
    }
    
    // Si ninguno existe o fallan, devolver los tipos por defecto
    return [
        'pc' => ['label' => 'PC', 'prefijo' => 'PC', 'campos' => ['marca','modelo','serie','placa','procesador','memoria','disco','observaciones','costo','sistema','ubicacion']],
        'portatil' => ['label' => 'Portátil', 'prefijo' => 'port', 'campos' => ['marca','modelo','serie','procesador','memoria','disco','pantalla','observaciones','costo','sistema','ubicacion']],
        'impresora' => ['label' => 'Impresora', 'prefijo' => 'imp', 'campos' => ['marca','modelo','serie','observaciones','ubicacion','costo']],
        'monitor' => ['label' => 'Monitor', 'prefijo' => 'mon', 'campos' => ['marca','modelo','serie','observaciones','ubicacion','costo']],
        'otro' => ['label' => 'Otro', 'prefijo' => 'ot', 'campos' => ['tipo','marca','modelo','serie','observaciones','ubicacion','costo']],
        'teclado' => ['label' => 'Teclado', 'prefijo' => 'tecl', 'campos' => ['marca','modelo','serie','observaciones','costo','ubicacion']],
        'raton' => ['label' => 'Ratón', 'prefijo' => 'rat', 'campos' => ['marca','modelo','serie','observaciones','costo','ubicacion']],
        'router' => ['label' => 'Router', 'prefijo' => 'rou', 'campos' => ['marca','modelo','serie','observaciones','costo','ubicacion']],
        'sw' => ['label' => 'Switch', 'prefijo' => 'sw', 'campos' => ['marca','modelo','serie','observaciones','costo','ubicacion']],
        'sai' => ['label' => 'SAI', 'prefijo' => 'sai', 'campos' => ['marca','modelo','serie','observaciones','costo','ubicacion']]
    ];
}

// Función para leer los tipos de mantenimiento desde el archivo de configuración
function leerTiposMantenimiento() {
    $archivoJson = '../configuracion/tiposMantenimiento.json';
    $archivoPhp = '../configuracion/tiposMantenimiento.php';
    
    // Intentar leer desde el archivo JSON (prioridad)
    if (file_exists($archivoJson)) {
        $contenido = file_get_contents($archivoJson);
        return json_decode($contenido, true);
    }
    
    // Si no existe el JSON, intentar el PHP
    if (file_exists($archivoPhp)) {
        include_once($archivoPhp);
        if (isset($tiposMantenimiento)) {
            return $tiposMantenimiento;
        }
    }
    
    // Si ninguno existe, devolver los tipos por defecto
    return [
        'mantenimientoCompleto' => [
            'label' => 'Completo',
            'descripcion' => 'Servicio de mantenimiento completo que incluye mano de obra y materiales'
        ],
        'mantenimientoManoObra' => [
            'label' => 'Mano de Obra',
            'descripcion' => 'Servicio que incluye solo mano de obra, sin materiales'
        ],
        'mantenimientoFacturable' => [
            'label' => 'Facturable',
            'descripcion' => 'Servicio facturable a terceros'
        ],
        'mantenimientoGarantia' => [
            'label' => 'Garantía',
            'descripcion' => 'Servicio cubierto por garantía'
        ]
    ];
}

// Cargar tipos de equipo y mantenimiento
$tiposEquipo = leerTiposEquipo();
$tiposMantenimiento = leerTiposMantenimiento();

// Manejar solicitud de borrado (solo admin y técnico)
if (isset($_POST['borrar']) && isset($_POST['numEquipo'])) {
    // Verificación adicional de permisos para operaciones críticas
    if (!in_array($permisoUsuarioActual, ['admin', 'tecnico'])) {
        echo "<script>
                alert('⚠️ No tiene permisos para borrar equipos.');
                window.history.back();
              </script>";
        exit;
    }
    
    $numEquipoBorrar = $_POST['numEquipo'];
    try {
        $sqlBorrar = "DELETE FROM Equipos WHERE numEquipo = ?";
        $stmtBorrar = $bd->prepare($sqlBorrar);
        $stmtBorrar->execute([$numEquipoBorrar]);
        
        // Redireccionar después del borrado
        header("Location: ../home.php");
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('Error al borrar equipo: " . addslashes($e->getMessage()) . "');</script>";
        exit;
    }
}

// Obtener la lista de clientes para el filtro
try {
    $sqlClientes = "SELECT idUsuarios, usuario FROM Usuarios WHERE permiso = 'cliente' ORDER BY usuario";
    $stmtClientes = $bd->prepare($sqlClientes);
    $stmtClientes->execute();
    $listaClientes = $stmtClientes->fetchAll();
} catch (PDOException $e) {
    echo "<script>alert('Error al cargar clientes: " . addslashes($e->getMessage()) . "');</script>";
    $listaClientes = [];
}

if (!isset($_GET['id'])) {
    // Obtener los filtros del formulario
    $filtroCliente = isset($_GET['filtroCliente']) ? $_GET['filtroCliente'] : '';
    $filtroTipoEquipo = isset($_GET['filtroTipoEquipo']) ? $_GET['filtroTipoEquipo'] : '';
    
    try {
        // Construir la consulta SQL con filtros
        $sql = "SELECT e.numEquipo, e.tipoEquipo, u.usuario as nombreCliente 
                FROM Equipos e 
                LEFT JOIN Usuarios u ON e.idUsuario = u.idUsuarios 
                WHERE 1=1";
        $params = [];
        
        if (!empty($filtroCliente)) {
            $sql .= " AND e.idUsuario = ?";
            $params[] = $filtroCliente;
        }
        
        if (!empty($filtroTipoEquipo)) {
            $sql .= " AND e.tipoEquipo = ?";
            $params[] = $filtroTipoEquipo;
        }
        
        $sql .= " ORDER BY e.numEquipo";
        
        $stmt = $bd->prepare($sql);
        $stmt->execute($params);
        $equipos = $stmt->fetchAll();
    } catch (PDOException $e) {
        echo "<script>alert('Error al obtener equipos: " . addslashes($e->getMessage()) . "');</script>";
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Seleccionar Equipo a Modificar</title>
        <link rel="stylesheet" href="../css/style.css">
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

    h1 {
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
        <script>
            function confirmarBorrado(numEquipo, tipoEquipo, cliente) {
                return confirm(`¿Está seguro que desea eliminar el equipo ${numEquipo} (${tipoEquipo}) de ${cliente}?\n\nEsta acción no se puede deshacer.`);
            }
        </script>
    </head>
    <body>
        <div class="header-mapache">
            <h1>Mapache Security</h1>
            <a href="../home.php" class="home-icon">
                <i class="fas fa-home"></i>
            </a>
        </div>
        
        <div class="container">
            <h1>Seleccionar Equipo a Modificar</h1>
            
            <!-- Sección de filtros -->
            <div class="filter-section">
                <form method="get" action="">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="filtroCliente">Cliente:</label>
                            <select name="filtroCliente" id="filtroCliente">
                                <option value="">-- Todos los clientes --</option>
                                <?php foreach ($listaClientes as $cliente): ?>
                                    <option value="<?= htmlspecialchars($cliente['idUsuarios']); ?>" 
                                        <?= ($filtroCliente == $cliente['idUsuarios']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($cliente['usuario']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filtroTipoEquipo">Tipo Equipo:</label>
                            <select name="filtroTipoEquipo" id="filtroTipoEquipo">
                                <option value="">-- Todos los tipos --</option>
                                <?php foreach ($tiposEquipo as $codigo => $info): ?>
                                    <option value="<?= htmlspecialchars($codigo); ?>" 
                                        <?= ($filtroTipoEquipo == $codigo) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($info['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-buttons">
                            <input type="submit" value="Filtrar" class="btn-primary" >
                            <button type="button" onclick="window.location.href='<?= $_SERVER['PHP_SELF']; ?>'" class="btn-secondary">Limpiar</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tabla de equipos -->
            <?php if (count($equipos) > 0): ?>
                <div class="tabla-lista">
                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th>NUMERO EQUIPO</th>
                                <th>TIPO</th>
                                <th>CLIENTE</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipos as $eq): ?>
                                <tr>
                                    <td><?= htmlspecialchars($eq['numEquipo']); ?></td>
                                    <td>
                                        <?= htmlspecialchars($tiposEquipo[$eq['tipoEquipo']]['label'] ?? $eq['tipoEquipo']); ?>
                                    </td>
                                    <td><?= htmlspecialchars($eq['nombreCliente']); ?></td>
                                    <td class="acciones">
                                        <a href="?id=<?= htmlspecialchars($eq['numEquipo']); ?>" class="btn-modificar">Modificar</a>
                                        <form method="post" action="" style="display:inline;" 
                                              onsubmit="return confirmarBorrado('<?= htmlspecialchars($eq['numEquipo']); ?>', 
                                                                       '<?= htmlspecialchars($tiposEquipo[$eq['tipoEquipo']]['label'] ?? $eq['tipoEquipo']); ?>', 
                                                                       '<?= htmlspecialchars($eq['nombreCliente']); ?>')">
                                            <input type="hidden" name="numEquipo" value="<?= htmlspecialchars($eq['numEquipo']); ?>">
                                            <button type="submit" name="borrar" class="btn-borrar">Borrar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="mensaje-info">
                    <p>No se encontraron equipos con los filtros seleccionados.</p>
                </div>
            <?php endif; ?>
            
            <div class="enlaces-navegacion">
                <p><a href="../home.php">Volver al home</a></p>
            </div>
        </div>
        
        <footer class="footer">
            <p>&copy; 2025 Todos los derechos reservados.</p>
        </footer>
    </body>
    </html>

    
<?php
exit;
}
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Variables iniciales
$usuarioLogueado = null;
$permisoUsuario = null;

// Verificar si existe idUsuario en la sesión
if (isset($_SESSION['idUsuario'])) {
    try {
        // Usar tus credenciales de conexión directamente
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
            
            // Establecer variables en la sesión para futuros usos
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
    echo "<script>alert('⚠️ Debe iniciar sesión para acceder a esta página.'); window.location.href='../login.php';</script>";
    exit;
}

// Si no hay permiso definido, redirigir al login
if (!$permisoUsuario) {
    echo "<script>alert('⚠️ No tiene permisos para realizar esta acción. Contacte al administrador.'); window.location.href='../login.php';</script>";
    exit;
}
// Solo admin y tecnico pueden modificar equipos
if (!in_array($permisoUsuario, ['admin', 'tecnico'])) {
    echo "<script>alert('⚠️ Acceso denegado. No tiene permisos para realizar esta acción.'); window.location.href='../home.php';</script>";
    exit;
}

// Verificar si se pasó el ID del equipo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('ID de equipo no válido.'); window.location.href='../home.php';</script>";
    exit;
}

$numEquipoModificar = $_GET['id'];

try {
    $sql = "SELECT * FROM Equipos WHERE numEquipo = ?";
    $stmt = $bd->prepare($sql);
    $stmt->execute([$numEquipoModificar]);
    $equipoData = $stmt->fetch();
    
    if (!$equipoData) {
        echo "<script>alert('Equipo no encontrado.'); window.location.href='../home.php';</script>";
        exit;
    }
} catch (PDOException $e) {
    echo "<script>alert('Error al obtener el equipo: " . addslashes($e->getMessage()) . "'); window.location.href='../home.php';</script>";
    exit;
}

// Obtener lista de usuarios clientes
try {
    $sqlUsuarios = "SELECT idUsuarios, usuario,
                           cpFiscal, provinciaFiscal, localidadFiscal, direccionFiscal,
                           cp1, provincia1, localidad1, direccion1,
                           cp2, provincia2, localidad2, direccion2
                    FROM Usuarios
                    WHERE permiso = 'cliente'";
    $stmtUsr = $bd->prepare($sqlUsuarios);
    $stmtUsr->execute();
    $listaUsuarios = $stmtUsr->fetchAll();
} catch (PDOException $e) {
    echo "<script>alert('Error al cargar usuarios: " . addslashes($e->getMessage()) . "'); window.location.href='../home.php';</script>";
    $listaUsuarios = [];
}

// Funciones auxiliares
function limpiarCampo($valor) {
    return !empty($valor) ? $valor : null;
}

function obtenerValor($campo, $actual) {
    return (isset($_POST[$campo]) && trim($_POST[$campo]) !== '') ? trim($_POST[$campo]) : $actual;
}

$errores = [];
$exito = false;

// Procesar formulario de modificación
if (isset($_POST['modificar'])) {
   $tipoEquipo = isset($_POST['tipoEquipo']) ? $_POST['tipoEquipo'] : $equipoData['tipoEquipo'];

    
    $marca             = obtenerValor('marca', $equipoData['marca']);
    $modelo            = obtenerValor('modelo', $equipoData['modelo']);
    $procesador        = obtenerValor('procesador', $equipoData['procesador']);
    $memoria           = obtenerValor('memoria', $equipoData['memoria']);
    $disco             = obtenerValor('disco', $equipoData['disco']);
    $tipo              = obtenerValor('tipo', $equipoData['tipo']);
    $placa             = obtenerValor('placa', $equipoData['placa']);
    $serie             = obtenerValor('serie', $equipoData['serie']);
    $ubicacion         = obtenerValor('ubicacion', $equipoData['ubicacion']);
    $costo             = obtenerValor('costo', $equipoData['costo']);
    $sistema           = obtenerValor('sistema', $equipoData['sistema']);
    $pantalla          = obtenerValor('pantalla', $equipoData['pantalla']);
    $observaciones     = obtenerValor('observaciones', $equipoData['observaciones']);
    $tipoMantenimiento = obtenerValor('tipoMantenimiento', $equipoData['tipoMantenimiento']);
    $cp                = obtenerValor('cp', $equipoData['cp']);
    $provincia         = obtenerValor('provincia', $equipoData['provincia']);
    $localidad         = obtenerValor('localidad', $equipoData['localidad']);
    $direccion         = obtenerValor('direccion', $equipoData['direccion']);
    $idUsuario         = obtenerValor('idUsuario', $equipoData['idUsuario']);
    $fechaCompra       = obtenerValor('fechaCompra', $equipoData['fechaCompra'] ?? null);

    try {
        $sqlUpdate = "UPDATE Equipos SET 
            tipoEquipo = ?, marca = ?, modelo = ?, procesador = ?, memoria = ?, disco = ?, 
            tipo = ?, placa = ?, serie = ?, ubicacion = ?, costo = ?, sistema = ?, 
            pantalla = ?, observaciones = ?, tipoMantenimiento = ?, cp = ?, provincia = ?, 
            localidad = ?, direccion = ?, idUsuario = ?, fechaCompra = ?
            WHERE numEquipo = ?";
        
        $stmtUpdate = $bd->prepare($sqlUpdate);
        $stmtUpdate->execute([
            $tipoEquipo, $marca, $modelo, $procesador, $memoria, $disco, 
            $tipo, $placa, $serie, $ubicacion, $costo, $sistema, 
            $pantalla, $observaciones, $tipoMantenimiento, $cp, $provincia, 
            $localidad, $direccion, $idUsuario, $fechaCompra, $numEquipoModificar
        ]);
        
        echo "<script>alert('✅ Equipo modificado correctamente.'); window.location.href='../home.php';</script>";
        exit;
        
    } catch (PDOException $e) {
        echo "<script>alert('⚠️ Error al modificar equipo: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Modificar Equipo</title>
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="…">
    <style>
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

    .container {
        flex: 1 0 auto;
        max-width: 1000px;
        margin: 50px auto 80px;
        background: #fff;
        border-radius: 12px;
        padding: 30px 40px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }

    .registro {
        text-align: center;
        font-size: 2.4rem;
        margin-bottom: 20px;
        color: #00225a;
        font-weight: 800;
        letter-spacing: 1.5px;
        user-select: none;
    }

    .descripcion {
        font-size: 1.1rem;
        color: #555;
        margin-bottom: 10px;
        text-align: center;
    }

    .descripcion strong {
        color: #00225a;
    }

    form {
        margin-top: 30px;
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        align-items: flex-start;
    }

    .form-group {
        flex: 1 1 45%;
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        flex: 1 1 100%;
    }

    label {
        font-weight: 700;
        color: #00225a;
        margin-bottom: 8px;
        user-select: none;
        font-size: 1.1rem;
    }

    input[type="text"],
    input[type="date"],
    input[type="number"],
    select,
    textarea {
        font-size: 1rem;
        padding: 12px 16px;
        border-radius: 10px;
        border: 2px solid #2573fa;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        resize: vertical;
        color: #333;
        background-color: #f9fbff;
        box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.06);
        min-height: 45px;
    }

    /* CORRECCION IMPORTANTE: Aseguramos que los select NO tengan altura fija */
    select {
        min-height: 45px;
        height: auto; /* Permitir altura automática */
        appearance: auto; /* Mantener apariencia nativa del select */
        -webkit-appearance: auto;
        -moz-appearance: auto;
    }

    /* Eliminamos cualquier override que pueda estar causando problemas */
    select:not([multiple]) {
        height: 45px !important;
        overflow: visible !important;
    }

    input[type="text"]:focus,
    input[type="date"]:focus,
    input[type="number"]:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #f9ab25;
        box-shadow: 0 0 6px #f9ab25;
        background-color: #fff;
    }

    textarea {
        min-height: 120px;
        max-height: 180px;
        padding-top: 14px;
    }

    .error-input {
        border-color: #e74c3c !important;
        box-shadow: 0 0 6px rgba(231, 76, 60, 0.5) !important;
    }

    .error-message {
        color: #e74c3c;
        font-size: 0.9rem;
        margin-top: 4px;
    }

    div[id^="grupo-"] {
        flex: 1 1 45%;
        display: flex;
        flex-direction: column;
        margin-bottom: 0;
    }

    #grupo-observaciones {
        flex: 1 1 100% !important;
        width: 100% !important;
        order: 999;
    }

    #grupo-observaciones textarea {
        min-height: 150px !important;
        max-height: 200px !important;
        width: 100% !important;
    }

    .btn-group {
        width: 100%;
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 30px;
        order: 1000;
    }

    .btn-modificar,
    .btn-cancelar {
        flex: 1 1 160px;
        max-width: 200px;
        padding: 14px 20px;
        border: none;
        border-radius: 30px;
        font-weight: 800;
        font-size: 1.05rem;
        cursor: pointer;
        text-align: center;
        user-select: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .btn-modificar {
        background-color: #f9ab25;
        color: #000;
    }

    .btn-modificar:hover {
        background-color: #d38e00;
        transform: translateY(-2px);
    }

    .btn-cancelar {
        background-color: #6c757d;
        color: #fff;
    }

    .btn-cancelar:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
    }

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

    @media (max-width: 768px) {
        .container {
            margin: 60px 15px 80px;
            padding: 25px 20px;
        }

        .form-group,
        div[id^="grupo-"] {
            flex: 1 1 100%;
        }

        .btn-group {
            justify-content: center;
        }

        .btn-modificar,
        .btn-cancelar {
            max-width: 100%;
            flex: 1 1 100%;
        }

        .home-icon {
            right: 20px;
            font-size: 1.3rem;
        }
    }

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

    .container {
        animation: fadeIn 0.6s ease-out;
    }

    input[type="text"]:disabled,
    input[type="date"]:disabled,
    input[type="number"]:disabled,
    select:disabled {
        background-color: #e9ecef;
        cursor: not-allowed;
        color: #6c757d;
    }

    select option:disabled {
        color: #6c757d;
        background-color: #f8f9fa;
    }
    </style>
<script>
       function autocompletarDireccion() {
            const selectUser = document.getElementById('idUsuario');
            const selectedOption = selectUser.options[selectUser.selectedIndex];
            const dirType = document.getElementById('tipoDireccion').value;
            
            if (!selectedOption || selectedOption.value === "") {
                document.getElementById('cp').value = "";
                document.getElementById('provincia').value = "";
                document.getElementById('localidad').value = "";
                document.getElementById('direccion').value = "";
                return;
            }
            
            let cp = "";
            let provincia = "";
            let localidad = "";
            let direccion = "";
            
            if (dirType === "fiscal") {
                cp = selectedOption.getAttribute('data-cpfiscal') || "";
                provincia = selectedOption.getAttribute('data-provinciafiscal') || "";
                localidad = selectedOption.getAttribute('data-localidadfiscal') || "";
                direccion = selectedOption.getAttribute('data-direccionfiscal') || "";
            } else if (dirType === "1") {
                cp = selectedOption.getAttribute('data-cp1') || "";
                provincia = selectedOption.getAttribute('data-provincia1') || "";
                localidad = selectedOption.getAttribute('data-localidad1') || "";
                direccion = selectedOption.getAttribute('data-direccion1') || "";
            } else if (dirType === "2") {
                cp = selectedOption.getAttribute('data-cp2') || "";
                provincia = selectedOption.getAttribute('data-provincia2') || "";
                localidad = selectedOption.getAttribute('data-localidad2') || "";
                direccion = selectedOption.getAttribute('data-direccion2') || "";
            }
            
            document.getElementById('cp').value = cp;
            document.getElementById('provincia').value = provincia;
            document.getElementById('localidad').value = localidad;
            document.getElementById('direccion').value = direccion;
        }

        function actualizarCampos() {
           const select = document.getElementsByName('tipoEquipo')[0];
           let tipoEquipo = select.value;
            const grupos = [
                'grupo-marca', 'grupo-modelo', 'grupo-serie', 'grupo-placa', 'grupo-procesador',
                'grupo-memoria', 'grupo-disco', 'grupo-pantalla', 'grupo-observaciones',
                'grupo-costo', 'grupo-sistema', 'grupo-ubicacion', 'grupo-tipo'
            ];
            
            // Ocultar todos los grupos primero
            grupos.forEach(function(id) {
                const elem = document.getElementById(id);
                if (elem) { elem.style.display = 'none'; }
            });
            
            // Si no hay tipo seleccionado, no mostrar nada
            if (!tipoEquipo) return;
            
            
            // Usar los tipos de equipo definidos en PHP
            const tiposEquipo = <?= json_encode($tiposEquipo) ?>;
            
            // Verificar si existe el tipo y tiene campos definidos
            if (tiposEquipo[tipoEquipo] && tiposEquipo[tipoEquipo].campos) {
                // Mostrar solo los campos correspondientes al tipo seleccionado
                tiposEquipo[tipoEquipo].campos.forEach(function(campo) {
                const elemento = document.getElementById('grupo-'+campo);
                if (elemento) {
                    elemento.style.display = 'block';
                }
                });
            }
        }

        // Helper function para validar formatos de fecha
        function isValidDate(dateString) {
            // Comprobar si la fecha es válida usando el objeto Date
            const dateObj = new Date(dateString);
            return !isNaN(dateObj.getTime());
        }

        // Validación mejorada para entradas de moneda/costo
        function validarCosto(input) {
            // Convertir coma a punto para validación
            let valor = input.value.replace(',', '.');
            if (input.value !== "" && (isNaN(parseFloat(valor)) || parseFloat(valor) < 0)) {
                alert("El costo debe ser un número válido mayor o igual a cero");
                input.classList.add('error-input');
                return false;
            }
            input.classList.remove('error-input');
            return true;
        }

        // Función para normalizar el valor del costo antes del envío
        function normalizarCosto() {
            const costoInput = document.getElementsByName('costo')[0];
            if (costoInput && costoInput.value) {
                // Convertir coma a punto para la base de datos
                costoInput.value = costoInput.value.replace(',', '.');
            }
        }

        // Validar longitud mínima de texto
        function validarTextoMinimo(input, minLength, fieldName) {
            if (input.value.trim() !== "" && input.value.trim().length < minLength) {
                alert(`El campo ${fieldName} debe tener al menos ${minLength} caracteres`);
                input.classList.add('error-input');
                return false;
            }
            input.classList.remove('error-input');
            return true;
        }

        // Validar campos obligatorios
        function validarCampoRequerido(input, fieldName) {
            if (input.value.trim() === "") {
                alert(`El campo ${fieldName} es obligatorio`);
                input.classList.add('error-input');
                return false;
            }
            input.classList.remove('error-input');
            return true;
        }

        // Validar elementos select
        function validarSelect(input, fieldName) {
            if (input.value === "") {
                alert(`Debe seleccionar ${fieldName}`);
                input.classList.add('error-input');
                return false;
            }
            input.classList.remove('error-input');
            return true;
        }

        // Función para limpiar errores de campos específicos
        function clearFieldError(input) {
            input.classList.remove('error-input');
        }

        // Función para validar campo específico cuando cambia
        function validarCampoOnChange(input, tipo, nombreCampo) {
            const fieldName = nombreCampo || input.name;
            switch(tipo) {
                case 'cp':
                    return validarCP(input);
                case 'costo':
                    return validarCosto(input);
                case 'texto':
                    return validarTextoMinimo(input, 3, fieldName);
                case 'requerido':
                    return validarCampoRequerido(input, fieldName);
                case 'select':
                    return validarSelect(input, fieldName);
                default:
                    return true;
            }
        }

        // Resetear todos los errores del formulario
        function resetFormErrors() {
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.classList.remove('error-input');
            });
        }

        // Función para confirmar antes de enviar el formulario
        function confirmarModificacion() {
            if (validateForm()) {
                return confirm("¿Está seguro que desea modificar este equipo?");
            }
            return false;
        }

        // Función mejorada de validateForm con validaciones más específicas
        function validateForm() {
            let isValid = true;
            resetFormErrors();
            
            // Validar tipo de equipo
            const tipoEquipo = document.getElementsByName('tipoEquipo')[0];
            if (!validarSelect(tipoEquipo, "Tipo de Equipo")) {
            isValid = false;
            }
            
            // Validar fecha de compra (opcional)
            const fechaCompra = document.getElementById('fechaCompra');
            if (fechaCompra.value !== "" && !isValidDate(fechaCompra.value)) {
                alert("El formato de fecha de compra no es válido");
                isValid = false;
                fechaCompra.classList.add('error-input');
            }
            
            // Validar tipo de mantenimiento
            const tipoMantenimiento = document.getElementsByName('tipoMantenimiento')[0];
            if (!validarSelect(tipoMantenimiento, "un tipo de mantenimiento")) {
                isValid = false;
            }
            
            // Añadir validación de garantía
            if (!validarGarantia()) {
                isValid = false;
            }
            
            // Validar CP
            const cp = document.getElementById('cp');
            if (!validarCP(cp)) {
                isValid = false;
            }
            
            // Validar provincia
            const provincia = document.getElementById('provincia');
            if (!validarCampoRequerido(provincia, "Provincia")) {
                isValid = false;
            }
            
            // Validar localidad
            const localidad = document.getElementById('localidad');
            if (!validarCampoRequerido(localidad, "Localidad")) {
                isValid = false;
            }
            
            // Validar dirección
            const direccion = document.getElementById('direccion');
            if (!validarCampoRequerido(direccion, "Dirección")) {
                isValid = false;
            }
            
            // Validar usuario
            const idUsuario = document.getElementById('idUsuario');
            if (!validarSelect(idUsuario, "un usuario")) {
                isValid = false;
            }
            
            // Validar campos específicos según el tipo de equipo seleccionado
            if (tipoEquipo.value) {
             const selectedEquipo = tipoEquipo.value;
                const tiposEquipo = <?= json_encode($tiposEquipo) ?>;
                
                if (tiposEquipo[selectedEquipo] && tiposEquipo[selectedEquipo].campos) {
                    const camposRequeridos = tiposEquipo[selectedEquipo].camposRequeridos || [];
                    
                    // Validar campos requeridos específicos por tipo
                    camposRequeridos.forEach(function(campo) {
                        const input = document.getElementsByName(campo)[0];
                        if (input && !validarCampoRequerido(input, campo)) {
                            isValid = false;
                        }
                    });
                    
                    // Validar costo si está visible
                    const costoDiv = document.getElementById('grupo-costo');
                    if (costoDiv && costoDiv.style.display !== 'none') {
                        const costo = document.getElementsByName('costo')[0];
                        if (costo && !validarCosto(costo)) {
                            isValid = false;
                        }
                    }
                }
            }
            
            return isValid;
        }

        // Función mejorada para validar CP con mensajes de alerta
        function validarCP(input) {
            if (input.value.trim() === "") {
                alert("El código postal es obligatorio");
                input.classList.add('error-input');
                return false;
            } else if (input.value.length < 5) {
                alert("El código postal debe tener al menos 5 dígitos");
                input.classList.add('error-input');
                return false;
            } else if (!/^\d+$/.test(input.value)) {
                alert("El código postal debe contener solo números");
                input.classList.add('error-input');
                return false;
            }
            input.classList.remove('error-input');
            return true;
        }

         // Función para validar garantía
        function validarGarantia() {
            const tipoMantenimiento = document.getElementsByName('tipoMantenimiento')[0];
            const fechaCompra = document.getElementById('fechaCompra');
            const opcionGarantia = tipoMantenimiento.querySelector('option[value="mantenimientoGarantia"]');
            
            // Si no hay fecha de compra, no permitimos garantía
            if (!fechaCompra.value) {
                opcionGarantia.disabled = true;
                if (tipoMantenimiento.value === 'mantenimientoGarantia') {
                    tipoMantenimiento.value = '';
                    alert("Debe ingresar una fecha de compra para seleccionar garantía");
                }
                return true;
            }
            
            // Obtener la fecha de compra y convertirla correctamente
            let fechaCompraStr = fechaCompra.value;
            let fechaCompraObj;
            
            // Detectar si el formato es dd/mm/yyyy y convertirlo
            if (fechaCompraStr.includes('/')) {
                const partes = fechaCompraStr.split('/');
                if (partes.length === 3) {
                    // Convertir de formato dd/mm/yyyy a yyyy-mm-dd para crear Date
                    fechaCompraObj = new Date(partes[2], partes[1] - 1, partes[0]);
                } else {
                    fechaCompraObj = new Date(fechaCompraStr);
                }
            } else {
                // Si es formato yyyy-mm-dd (HTML5 date input)
                fechaCompraObj = new Date(fechaCompraStr);
            }
            
            // Verificar si la conversión fue exitosa
            if (isNaN(fechaCompraObj.getTime())) {
                opcionGarantia.disabled = true;
                if (tipoMantenimiento.value === 'mantenimientoGarantia') {
                    tipoMantenimiento.value = '';
                }
                return false;
            }
            
            const hoy = new Date();
            
            // Calcular la diferencia en años
            const diffAnios = hoy.getFullYear() - fechaCompraObj.getFullYear();
            const mesActual = hoy.getMonth() < fechaCompraObj.getMonth() || 
                            (hoy.getMonth() === fechaCompraObj.getMonth() && 
                            hoy.getDate() < fechaCompraObj.getDate());
            const aniosDiff = mesActual ? diffAnios - 1 : diffAnios;
            
            if (aniosDiff >= 3) {
                // Desactivar la opción de garantía
                opcionGarantia.disabled = true;
                
                // Si ya estaba seleccionada garantía, cambiarla y mostrar mensaje
                if (tipoMantenimiento.value === 'mantenimientoGarantia') {
                    tipoMantenimiento.value = '';
                    alert("La garantía sólo es válida hasta 3 años desde la fecha de compra. Han pasado " + aniosDiff + " años.");
                }
            } else {
                // Activar la opción de garantía si está dentro del plazo
                opcionGarantia.disabled = false;
            }
            
            return true;
        }

        // Inicializar todas las validaciones cuando el DOM esté cargado
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar el formulario
            actualizarCampos();
            
            // Cargar la dirección basada en la selección inicial
            const tipoDireccion = document.getElementById('tipoDireccion');
            if (tipoDireccion.value) {
                autocompletarDireccion();
            }
            // Configurar validación del formulario
            const form = document.querySelector('form');
            if (form) {
                // Usar onsubmit en lugar de addEventListener para mantener compatibilidad
                form.onsubmit = function(event) {
                    // Normalizar el costo antes de validar y enviar
                    normalizarCosto();
                    return validateForm() && confirm("¿Está seguro que desea modificar este equipo?");
                };
            }
            // Agregar validación en tiempo real para todos los campos importantes
            // Validar CP
            const cpInput = document.getElementById('cp');
            if (cpInput) {
                cpInput.addEventListener('blur', function() {
                    validarCP(this);
                });
            }
            // Validar costo
            const costoInput = document.getElementsByName('costo')[0];
            if (costoInput) {
                costoInput.addEventListener('blur', function() {
                    validarCosto(this);
                });
            }
            
            // Validar campos requeridos
            const camposRequeridos = [
                { id: 'provincia', tipo: 'requerido', nombre: 'Provincia' },
                { id: 'localidad', tipo: 'requerido', nombre: 'Localidad' },
                { id: 'direccion', tipo: 'requerido', nombre: 'Dirección' }
            ];
            
            camposRequeridos.forEach(function(campo) {
                const elemento = document.getElementById(campo.id);
                if (elemento) {
                    elemento.addEventListener('blur', function() {
                        validarCampoOnChange(this, campo.tipo, campo.nombre);
                    });
                }
            });
            
            // Validar selects
            const selects = [
                { name: 'tipoMantenimiento', tipo: 'select', nombre: 'un tipo de mantenimiento' },
                { id: 'idUsuario', tipo: 'select', nombre: 'un usuario' }
            ];
            
            selects.forEach(function(sel) {
                let elemento;
                if (sel.id) {
                    elemento = document.getElementById(sel.id);
                } else if (sel.name) {
                    elemento = document.getElementsByName(sel.name)[0];
                }
                
                if (elemento) {
                    elemento.addEventListener('change', function() {
                        validarCampoOnChange(this, sel.tipo, sel.nombre);
                    });
                }
            });
            // Validar select simple de tipo de equipo
            const tipoEquipo = document.getElementsByName('tipoEquipo')[0];
            if (tipoEquipo) {
             tipoEquipo.addEventListener('change', function() {
             validarSelect(this, "Tipo de Equipo");
             actualizarCampos();
                });
            }
            // Añadir validación de garantía en tiempo real
            const tipoMantenimiento = document.getElementsByName('tipoMantenimiento')[0];
            // Validar fecha de compra cuando cambie
            const fechaCompra = document.getElementById('fechaCompra');
            if (fechaCompra) {
                fechaCompra.addEventListener('change', function() {
                    validarGarantia();
                });
            }

             validarGarantia();
        });
    </script>
</head>
<body onload="actualizarCampos()">
    <div class="header-mapache">
        <h1>Mapache Security</h1>
       <a href="../home.php" class="home-icon">
        <i class="fas fa-home"></i>
        </a>
    </div>
     <div class="container">
    <h1 class= "registro">Modificar Equipo</h1>
    
    <p class= "descripcion"><strong>Número de Equipo:</strong> 001</p>
    <p class= "descripcion"><strong>Fecha de Alta:</strong> 12/05/2025</p>
    
    <form method="post" action="" onsubmit="return validateForm() && confirm('¿Está seguro que desea modificar este equipo?')">
        <div class="form-group">
        <label>Tipo de Equipo:</label><br/>
        <select name="tipoEquipo" onchange="actualizarCampos()" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($tiposEquipo as $val => $info): ?>
                <option value="<?= htmlspecialchars($val) ?>"
                    <?= ($val === $equipoData['tipoEquipo']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($info['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        </div>

        <div class="form-group">
        <label>Fecha de Compra:</label><br/>
        <input type="date" id="fechaCompra" name="fechaCompra" value="<?= htmlspecialchars($equipoData['fechaCompra'] ?? '') ?>">
        </div>
        
        <div id="grupo-marca" style="display:none;"  class="form-group">
            <label>Marca:</label><br/>
            <input type="text" name="marca" value="<?= htmlspecialchars($equipoData['marca'] ?? '') ?>">
        </div>
                
        <div id="grupo-modelo" style="display:none;" class="form-group">
            <label>Modelo:</label><br/>
            <input type="text" name="modelo" value="<?= htmlspecialchars($equipoData['modelo'] ?? '') ?>">
        </div>
       
        <div id="grupo-serie" style="display:none;" class="form-group">
            <label>Serie:</label><br/>
            <input type="text" name="serie" value="<?= htmlspecialchars($equipoData['serie'] ?? '') ?>">
        </div>

        <div id="grupo-placa" style="display:none;" class="form-group">
            <label>Placa:</label><br/>
            <input type="text" name="placa" value="<?= htmlspecialchars($equipoData['placa'] ?? '') ?>">
        </div>

        <div id="grupo-procesador" style="display:none;" class="form-group">
            <label>Procesador:</label><br/>
            <input type="text" name="procesador" value="<?= htmlspecialchars($equipoData['procesador'] ?? '') ?>">
        </div>

        <div id="grupo-memoria" style="display:none;" class="form-group">
            <label>Memoria:</label><br/>
            <input type="text" name="memoria" value="<?= htmlspecialchars($equipoData['memoria'] ?? '') ?>">
        </div>

        <div id="grupo-disco" style="display:none;" class="form-group">
            <label>Disco:</label><br/>
            <input type="text" name="disco" value="<?= htmlspecialchars($equipoData['disco'] ?? '') ?>">
        </div>

        <div id="grupo-pantalla" style="display:none;" class="form-group">
            <label>Pantalla:</label><br/>
            <input type="text" name="pantalla" value="<?= htmlspecialchars($equipoData['pantalla'] ?? '') ?>">
        </div>

        <div id="grupo-observaciones" style="display:none;" class="form-group full-width">
            <label>Observaciones:</label><br/>
            <textarea name="observaciones"><?= htmlspecialchars($equipoData['observaciones'] ?? '') ?></textarea>
        </div>

        <div id="grupo-costo" style="display:none;" class="form-group">
            <label>Costo:</label><br/>
            <input type="text" step="0.01" name="costo" value="<?= htmlspecialchars($equipoData['costo'] ?? '') ?>">
        </div>

        <div id="grupo-sistema" style="display:none;" class="form-group">
            <label>Sistema:</label><br/>
            <input type="text" name="sistema" value="<?= htmlspecialchars($equipoData['sistema'] ?? '') ?>">
        </div>

        <div id="grupo-ubicacion" style="display:none;" class="form-group">
            <label>Ubicación:</label><br/>
            <input type="text" name="ubicacion" value="<?= htmlspecialchars($equipoData['ubicacion'] ?? '') ?>">
        </div>

        <div id="grupo-tipo" style="display:none;" class="form-group">
            <label>Tipo (Especifique):</label><br/>
            <input type="text" name="tipo" value="<?= htmlspecialchars($equipoData['tipo'] ?? '') ?>">
        </div>
    <div class="form-group">
        <label>Tipo de Servicio:</label><br/>
        <select name="tipoMantenimiento" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($tiposMantenimiento as $val => $info): ?>
                <option value="<?= htmlspecialchars($val) ?>" 
                        <?= $equipoData['tipoMantenimiento'] === $val ? 'selected' : '' ?>>
                    <?= htmlspecialchars($info['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
           <div class="form-group">     
        <label>Código Postal:</label><br/>
        <input type="text" id="cp" name="cp" value="<?= htmlspecialchars($equipoData['cp'] ?? '') ?>" required minlength="5">
        <div id="cp-error" class="error-message" style="display: none;"></div>
           </div>
                <div class="form-group">
        <label>Provincia:</label><br/>
        <input type="text" id="provincia" name="provincia" value="<?= htmlspecialchars($equipoData['provincia'] ?? '') ?>" required>
    </div>
    <div class="form-group">
        <label>Localidad:</label><br/>
        <input type="text" id="localidad" name="localidad" value="<?= htmlspecialchars($equipoData['localidad'] ?? '') ?>" required>
    </div>
    <div class="form-group">
        <label>Dirección:</label><br/>
        <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($equipoData['direccion'] ?? '') ?>" required>
    </div>
    <div class="form-group">
        <label>Seleccione el Cliente:</label><br/>
        <select id="idUsuario" name="idUsuario" onchange="autocompletarDireccion()" required>
            <option value="">-- Selecciona un Cliente --</option>
            <?php foreach ($listaUsuarios as $usr): ?>
                <option 
                    value="<?= htmlspecialchars($usr['idUsuarios']); ?>"
                    data-cpfiscal="<?= htmlspecialchars($usr['cpFiscal']); ?>"
                    data-provinciafiscal="<?= htmlspecialchars($usr['provinciaFiscal']); ?>"
                    data-localidadfiscal="<?= htmlspecialchars($usr['localidadFiscal']); ?>"
                    data-direccionfiscal="<?= htmlspecialchars($usr['direccionFiscal']); ?>"
                    data-cp1="<?= htmlspecialchars($usr['cp1']); ?>"
                    data-provincia1="<?= htmlspecialchars($usr['provincia1']); ?>"
                    data-localidad1="<?= htmlspecialchars($usr['localidad1']); ?>"
                    data-direccion1="<?= htmlspecialchars($usr['direccion1']); ?>"
                    data-cp2="<?= htmlspecialchars($usr['cp2']); ?>"
                    data-provincia2="<?= htmlspecialchars($usr['provincia2']); ?>"
                    data-localidad2="<?= htmlspecialchars($usr['localidad2']); ?>"
                    data-direccion2="<?= htmlspecialchars($usr['direccion2']); ?>"
                    <?= ($usr['idUsuarios'] == $equipoData['idUsuario']) ? 'selected' : ''; ?>
                >
                    <?= htmlspecialchars($usr['usuario']); ?> (ID: <?= htmlspecialchars($usr['idUsuarios']); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Tipo de dirección:</label><br/>
        <select id="tipoDireccion" onchange="autocompletarDireccion()">
            <option value="fiscal" <?= ($equipoData['tipoDireccion'] ?? 'fiscal') === 'fiscal' ? 'selected' : '' ?>>Fiscal</option>
            <option value="1" <?= ($equipoData['tipoDireccion'] ?? '') === '1' ? 'selected' : '' ?>>Dirección 1</option>
            <option value="2" <?= ($equipoData['tipoDireccion'] ?? '') === '2' ? 'selected' : '' ?>>Dirección 2</option>
        </select>
    </div>
    <div class="btn-group">
        <input type="submit" name="modificar" value="Modificar Equipo" class="btn-modificar" onclick="normalizarCosto()">
        <button type="button" onclick="window.location.href='../home.php'" class="btn-cancelar">Cancelar</button>
        <a href="pdf_equipo.php?numEquipo=<?= urlencode($equipoData['numEquipo']) ?>" target="_blank"
        style="display: inline-block; padding: 12px 24px; background-color: #d73838; color: white; text-decoration: none; font-weight: bold; border-radius: 6px;">
        <i class="bi bi-file-earmark-pdf-fill"></i> Generar PDF
    </a>
    </div>
    </form>
    </div>
    
    <div class="footer">
    <p>&copy;  <?php echo date('Y'); ?> Todos los derechos reservados.</p>
  </div>
</body>
</html>