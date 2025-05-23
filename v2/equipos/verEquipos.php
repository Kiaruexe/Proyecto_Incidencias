<?php 
session_start();

// Si no hay sesión iniciada, redirigir al login
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../login.php");
    exit;
}

// Función para leer tipos de equipo (igual que en crearEquipo.php)
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

// Función para leer tipos de mantenimiento (igual que en crearEquipo.php)
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

// Cargar los tipos de equipo y mantenimiento
$tiposEquipo = leerTiposEquipo();
$tiposMantenimiento = leerTiposMantenimiento();

// Conexión a la base de datos
try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 
        'Mapapli', 
        '9R%d5cf62'
    );

    // Obtener datos del usuario en sesión
    $query = $bd->prepare("SELECT permiso FROM Usuarios WHERE idUsuarios = ?");
    $query->execute([$_SESSION['idUsuario']]);
    $userRow = $query->fetch();
    $permiso = $userRow['permiso'];

    // Filtros
    $filtroTipoEquipo = $_GET['tipoEquipo'] ?? 'todos';
    $filtroMantenimiento = $_GET['tipoMantenimiento'] ?? '';
    $filtroCP = $_GET['cp'] ?? '';
    $filtroProvincia = $_GET['provincia'] ?? '';
    $filtroLocalidad = $_GET['localidad'] ?? '';
    $filtroUsuario = $_GET['usuario'] ?? '';

    // Ordenación
    $ordenarPor = $_GET['ordenarPor'] ?? 'numEquipo';
    $orden = ($_GET['orden'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

    // Columnas permitidas para ordenar (para evitar inyección SQL)
    $columnasOrden = ['numEquipo', 'fechaAlta', 'fechaCompra', 'usuario', 'costo'];
    if (!in_array($ordenarPor, $columnasOrden)) {
        $ordenarPor = 'numEquipo';
    }

    // Construcción de la consulta con filtros
    $sql = "SELECT e.*, u.usuario FROM Equipos e 
            LEFT JOIN Usuarios u ON e.idUsuario = u.idUsuarios WHERE 1=1";
    $params = [];

    // Permiso de cliente (solo ve sus equipos)
    if ($permiso === 'cliente') {
        $sql .= " AND e.idUsuario = ?";
        $params[] = $_SESSION['idUsuario'];
    }

    // Aplicar filtros
    if (!empty($filtroTipoEquipo) && $filtroTipoEquipo !== 'todos') {
        $sql .= " AND e.tipoEquipo = ?";
        $params[] = $filtroTipoEquipo;
    }
    if (!empty($filtroMantenimiento)) {
        $sql .= " AND e.tipoMantenimiento LIKE ?";
        $params[] = "%$filtroMantenimiento%";
    }
    if (!empty($filtroCP)) {
        $sql .= " AND e.cp LIKE ?";
        $params[] = "%$filtroCP%";
    }
    if (!empty($filtroProvincia)) {
        $sql .= " AND e.provincia LIKE ?";
        $params[] = "%$filtroProvincia%";
    }
    if (!empty($filtroLocalidad)) {
        $sql .= " AND e.localidad LIKE ?";
        $params[] = "%$filtroLocalidad%";
    }
    if (!empty($filtroUsuario)) {
        $sql .= " AND u.usuario LIKE ?";
        $params[] = "%$filtroUsuario%";
    }

    // Aplicar ordenación
    $sql .= " ORDER BY $ordenarPor $orden";

    $query = $bd->prepare($sql);
    $query->execute($params);
    
    // Valores por defecto para campos vacíos
    $valoresPorDefecto = [
        'tipoEquipo' => 'No especificado',
        'marca' => 'No especificada',
        'modelo' => 'No especificado',
        'procesador' => 'No especificado',
        'memoria' => 'No especificada',
        'disco' => 'No especificado',
        'tipo' => 'No especificado',
        'placa' => 'No especificada',
        'serie' => 'No especificado',
        'ubicacion' => 'No especificada',
        'costo' => '0',
        'sistema' => 'No especificado',
        'pantalla' => 'No especificada',
        'observaciones' => 'Sin observaciones',
        'tipoMantenimiento' => 'No especificado',
        'fechaCompra' => 'No especificada',
        'cp' => 'No especificado',
        'provincia' => 'No especificada',
        'localidad' => 'No especificada',
        'direccion' => 'No especificada',
        'idUsuario' => '0',
        'tipoDireccion' => 'fiscal'
    ];

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}

// Función para formatear el nombre de tipo de mantenimiento
function formatearTipoMantenimiento($clave) {
    global $tiposMantenimiento;
    
    if (isset($tiposMantenimiento[$clave])) {
        return $tiposMantenimiento[$clave]['label'];
    }
    
    return $clave; // Devolver la clave como está si no se encuentra
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Equipos</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ccc;
        }
        th {
            background-color: #f2f2f2;
        }
        .boton-limpiar {
            background-color: #f44336;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        .boton-limpiar:hover {
            background-color: #d32f2f;
        }
        .form-container {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .form-buttons {
            margin-top: 10px;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .volver-home {
            background-color: #4CAF50;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
        }
        .volver-home:hover {
            background-color: #388E3C;
        }
        .boton-aplicar {
            background-color: #2196F3;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .boton-aplicar:hover {
            background-color: #0b7dda;
        }
        .campo-vacio {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <h1>Lista de Equipos</h1>
        <a href="../home.php" class="volver-home">Volver al home</a>
    </div>

    <div class="form-container">
        <form method="get" action="">
            <label for="tipoEquipo">Tipo de Equipo:</label>
            <select name="tipoEquipo">
                <option value="todos" <?= $filtroTipoEquipo=='todos' ? 'selected' : ''; ?>>Todos</option>
                <?php foreach ($tiposEquipo as $val => $info): ?>
                <option value="<?= htmlspecialchars($val) ?>" <?= $filtroTipoEquipo==$val ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($info['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <label for="tipoMantenimiento">Tipo de Pago:</label>
            <select name="tipoMantenimiento">
                <option value="" <?= $filtroMantenimiento=='' ? 'selected' : ''; ?>>Todos</option>
                <?php foreach ($tiposMantenimiento as $val => $info): ?>
                <option value="<?= htmlspecialchars($val) ?>" <?= $filtroMantenimiento==$val ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($info['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="cp" placeholder="Código Postal" value="<?= htmlspecialchars($filtroCP); ?>">
            <input type="text" name="provincia" placeholder="Provincia" value="<?= htmlspecialchars($filtroProvincia); ?>">
            <input type="text" name="localidad" placeholder="Localidad" value="<?= htmlspecialchars($filtroLocalidad); ?>">
            <input type="text" name="usuario" placeholder="Nombre de cliente" value="<?= htmlspecialchars($filtroUsuario); ?>">

            <label for="ordenarPor">Ordenar por:</label>
            <select name="ordenarPor">
                <option value="numEquipo" <?= $ordenarPor=='numEquipo' ? 'selected' : ''; ?>>Número de Equipo</option>
                <option value="fechaAlta" <?= $ordenarPor=='fechaAlta' ? 'selected' : ''; ?>>Fecha de Alta</option>
                <option value="fechaCompra" <?= $ordenarPor=='fechaCompra' ? 'selected' : ''; ?>>Fecha de Compra</option>
                <option value="usuario" <?= $ordenarPor=='usuario' ? 'selected' : ''; ?>>Usuario</option>
                <option value="costo" <?= $ordenarPor=='costo' ? 'selected' : ''; ?>>Costo</option>
            </select>

            <label for="orden">Orden:</label>
            <select name="orden">
                <option value="ASC" <?= $orden=='ASC' ? 'selected' : ''; ?>>Ascendente</option>
                <option value="DESC" <?= $orden=='DESC' ? 'selected' : ''; ?>>Descendente</option>
            </select>
            
            <div class="form-buttons">
                <button type="submit" class="boton-aplicar">Aplicar Filtros</button>
                <a href="<?= $_SERVER['PHP_SELF']; ?>" class="boton-limpiar">Limpiar Filtros</a>
            </div>
        </form>
    </div>

    <table>
        <tr>
            <th>Número de Equipo</th>
            <th>Fecha de Alta</th>
            <th>Fecha de Compra</th>
            <th>Tipo de Equipo</th>
            <th>Tipo de Pago</th>
            <th>Código Postal</th>
            <th>Provincia</th>
            <th>Localidad</th>
            <th>Dirección</th>
            <th>Cliente</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Serie</th>
            <th>Observaciones</th>
            <th>Ubicación</th>
            <th>Costo</th>
        </tr>

        <?php while ($row = $query->fetch()): ?>
            <tr>
                <td><?= !empty($row['numEquipo']) ? htmlspecialchars($row['numEquipo']) : '<span class="campo-vacio">No especificado</span>'; ?></td>
                <td><?= !empty($row['fechaAlta']) ? htmlspecialchars($row['fechaAlta']) : '<span class="campo-vacio">No especificada</span>'; ?></td>
                <td><?= !empty($row['fechaCompra']) ? htmlspecialchars($row['fechaCompra']) : '<span class="campo-vacio">No especificada</span>'; ?></td>
                <td><?= !empty($row['tipoEquipo']) && isset($tiposEquipo[$row['tipoEquipo']]) ? 
                        htmlspecialchars($tiposEquipo[$row['tipoEquipo']]['label']) : 
                        '<span class="campo-vacio">No especificado</span>'; ?></td>
                <td><?= !empty($row['tipoMantenimiento']) ? 
                        htmlspecialchars(formatearTipoMantenimiento($row['tipoMantenimiento'])) : 
                        '<span class="campo-vacio">No especificado</span>'; ?></td>
                <td><?= !empty($row['cp']) ? htmlspecialchars($row['cp']) : '<span class="campo-vacio">No especificado</span>'; ?></td>
                <td><?= !empty($row['provincia']) ? htmlspecialchars($row['provincia']) : '<span class="campo-vacio">No especificada</span>'; ?></td>
                <td><?= !empty($row['localidad']) ? htmlspecialchars($row['localidad']) : '<span class="campo-vacio">No especificada</span>'; ?></td>
                <td><?= !empty($row['direccion']) ? htmlspecialchars($row['direccion']) : '<span class="campo-vacio">No especificada</span>'; ?></td>
                <td><?= !empty($row['usuario']) ? htmlspecialchars($row['usuario']) : '<span class="campo-vacio">No especificado</span>'; ?></td>
                <td><?= !empty($row['marca']) ? htmlspecialchars($row['marca']) : '<span class="campo-vacio">No especificada</span>'; ?></td>
                <td><?= !empty($row['modelo']) ? htmlspecialchars($row['modelo']) : '<span class="campo-vacio">No especificado</span>'; ?></td>
                <td><?= !empty($row['serie']) ? htmlspecialchars($row['serie']) : '<span class="campo-vacio">No especificado</span>'; ?></td>
                <td><?= !empty($row['observaciones']) ? htmlspecialchars($row['observaciones']) : '<span class="campo-vacio">Sin observaciones</span>'; ?></td>
                <td><?= !empty($row['ubicacion']) ? htmlspecialchars($row['ubicacion']) : '<span class="campo-vacio">No especificada</span>'; ?></td>
                <td><?= !empty($row['costo']) ? htmlspecialchars($row['costo']) : '<span class="campo-vacio">0</span>'; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

</body>
</html>