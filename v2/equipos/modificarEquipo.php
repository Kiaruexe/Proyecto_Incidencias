<?php 
session_start();

if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../login.php");
    exit;
}

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

try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error de conexión: " . $e->getMessage() . "</p>";
    exit;
}

// Obtener la lista de clientes para el filtro
try {
    $sqlClientes = "SELECT idUsuarios, usuario FROM Usuarios WHERE permiso = 'cliente' ORDER BY usuario";
    $stmtClientes = $bd->prepare($sqlClientes);
    $stmtClientes->execute();
    $listaClientes = $stmtClientes->fetchAll();
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error al cargar clientes: " . $e->getMessage() . "</p>";
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
        echo "<p style='color:red;'>Error al obtener equipos: " . $e->getMessage() . "</p>";
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Seleccionar Equipo a Modificar</title>
        <link rel="stylesheet" href="../css/style.css">
        <style>
            .filters-container {
                background-color: #f5f5f5;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 5px;
                border: 1px solid #ddd;
            }
            
            .filter-row {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 15px;
            }
            
            .filter-item {
                flex: 1;
                min-width: 200px;
            }
            
            .filter-label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            
            .button-row {
                display: flex;
                gap: 10px;
            }
            
            select, input[type="submit"], button {
                padding: 8px;
                border-radius: 4px;
                border: 1px solid #ccc;
            }
            
            .equipment-list {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            
            .equipment-list th, .equipment-list td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            
            .equipment-list th {
                background-color: #f2f2f2;
            }
            
            .equipment-list tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            
            .equipment-list tr:hover {
                background-color: #f1f1f1;
            }
            
            .action-button {
                background-color: #4CAF50;
                color: white;
                padding: 5px 10px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
            
            .action-button:hover {
                background-color: #45a049;
            }
            
            .clear-button {
                background-color: #f44336;
                color: white;
            }
            
            .clear-button:hover {
                background-color: #d32f2f;
            }
            
            .apply-button {
                background-color: #2196F3;
                color: white;
            }
            
            .apply-button:hover {
                background-color: #0b7dda;
            }
        </style>
    </head>
    <body>
        <h1>Seleccionar Equipo a Modificar</h1>
        
        <div class="filters-container">
            <form method="get" action="">
                <div class="filter-row">
                    <div class="filter-item">
                        <label class="filter-label" for="filtroCliente">Cliente:</label>
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
                    
                    <div class="filter-item">
                        <label class="filter-label" for="filtroTipoEquipo">Tipo Equipo:</label>
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
                </div>
                
                <div class="button-row">
                    <input type="submit" value="Aplicar Filtros" class="apply-button">
                    <button type="button" onclick="window.location.href='<?= $_SERVER['PHP_SELF']; ?>'" class="clear-button">Limpiar Filtros</button>
                </div>
            </form>
        </div>
        
        <?php if (count($equipos) > 0): ?>
            <table class="equipment-list">
                <thead>
                    <tr>
                        <th>Número Equipo</th>
                        <th>Tipo</th>
                        <th>Cliente</th>
                        <th>Acción</th>
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
                            <td>
                                <a href="?id=<?= htmlspecialchars($eq['numEquipo']); ?>" class="action-button">Modificar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron equipos con los filtros seleccionados.</p>
        <?php endif; ?>
        
        <p><a href="../home.php">Volver al home</a></p>
    </body>
    </html>
    <?php
    exit;
}

$numEquipoModificar = $_GET['id'];
try {
    $sql = "SELECT * FROM Equipos WHERE numEquipo = ?";
    $stmt = $bd->prepare($sql);
    $stmt->execute([$numEquipoModificar]);
    $equipoData = $stmt->fetch();
    if (!$equipoData) {
        echo "<p style='color:red;'>Equipo no encontrado.</p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error al obtener el equipo: " . $e->getMessage() . "</p>";
    exit;
}

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
    echo "<p style='color:red;'>Error al cargar usuarios: " . $e->getMessage() . "</p>";
    $listaUsuarios = [];
}

function limpiarCampo($valor) {
    return !empty($valor) ? $valor : null;
}

function obtenerValor($campo, $actual) {
    return (isset($_POST[$campo]) && trim($_POST[$campo]) !== '') ? trim($_POST[$campo]) : $actual;
}

$errores = [];
$exito = false;

if (isset($_POST['modificar'])) {
    // Validar campos obligatorios
    if (!isset($_POST['tipoEquipo']) || empty($_POST['tipoEquipo'])) {
        $errores[] = "Debe seleccionar al menos un tipo de equipo";
    }
    
    if (!isset($_POST['tipoMantenimiento']) || empty($_POST['tipoMantenimiento'])) {
        $errores[] = "Debe seleccionar un tipo de mantenimiento";
    }
    
    if (!isset($_POST['cp']) || empty($_POST['cp'])) {
        $errores[] = "El código postal es obligatorio";
    } elseif (strlen($_POST['cp']) < 5) {
        $errores[] = "El código postal debe tener al menos 5 dígitos";
    }
    
    if (!isset($_POST['provincia']) || empty($_POST['provincia'])) {
        $errores[] = "La provincia es obligatoria";
    }
    
    if (!isset($_POST['localidad']) || empty($_POST['localidad'])) {
        $errores[] = "La localidad es obligatoria";
    }
    
    if (!isset($_POST['direccion']) || empty($_POST['direccion'])) {
        $errores[] = "La dirección es obligatoria";
    }
    
    if (!isset($_POST['idUsuario']) || empty($_POST['idUsuario'])) {
        $errores[] = "Debe seleccionar un usuario";
    }
    
    // Si no hay errores, proceder con la actualización
    if (empty($errores)) {
        $tipoEquipoArray = $_POST['tipoEquipo'] ?? [];
        $tipoEquipo = count($tipoEquipoArray) > 0 ? $tipoEquipoArray[0] : $equipoData['tipoEquipo'];
        
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
                tipoEquipo = ?,
                marca = ?,
                modelo = ?,
                procesador = ?,
                memoria = ?,
                disco = ?,
                tipo = ?,
                placa = ?,
                serie = ?,
                ubicacion = ?,
                costo = ?,
                sistema = ?,
                pantalla = ?,
                observaciones = ?,
                tipoMantenimiento = ?,
                cp = ?,
                provincia = ?,
                localidad = ?,
                direccion = ?,
                idUsuario = ?,
                fechaCompra = ?
                WHERE numEquipo = ?";
            $stmtUpdate = $bd->prepare($sqlUpdate);
            $stmtUpdate->execute([
                $tipoEquipo,
                $marca,
                $modelo,
                $procesador,
                $memoria,
                $disco,
                $tipo,
                $placa,
                $serie,
                $ubicacion,
                $costo,
                $sistema,
                $pantalla,
                $observaciones,
                $tipoMantenimiento,
                $cp,
                $provincia,
                $localidad,
                $direccion,
                $idUsuario,
                $fechaCompra,
                $numEquipoModificar
            ]);
            $exito = true;
            // Redireccionar al home después de una modificación exitosa
            header("Location: ../home.php");
            exit;
        } catch (PDOException $e) {
            $errores[] = "Error al modificar equipo: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Modificar Equipo</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .error-message {
            color: red;
            margin: 5px 0;
        }
        .error-input {
            border: 2px solid red;
        }
        .success-message {
            color: green;
            margin: 10px 0;
            font-weight: bold;
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
        
        // Validar CP después de autocompletar
        validarCP(document.getElementById('cp'));
      }

      function actualizarCampos() {
        const select = document.getElementsByName('tipoEquipo[]')[0];
        let values = Array.from(select.selectedOptions).map(opt => opt.value);
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
        if (!values.length) return;
        
        let tipoEquipo = values[0];
        
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
      
      // Validación del CP
      function validarCP(input) {
        const errorDiv = document.getElementById('cp-error');
        if (input.value.length < 5) {
          input.classList.add('error-input');
          errorDiv.textContent = 'El código postal debe tener al menos 5 dígitos';
          errorDiv.style.display = 'block';
          return false;
        } else {
          input.classList.remove('error-input');
          errorDiv.style.display = 'none';
          return true;
        }
      }
      
      // Validar formulario completo antes de enviar
      function validateForm() {
        let isValid = true;
        const errores = [];
        
        // Validar tipo de equipo
        const tipoEquipo = document.getElementsByName('tipoEquipo[]')[0];
        if (tipoEquipo.selectedOptions.length === 0) {
          errores.push("Debe seleccionar al menos un tipo de equipo");
          tipoEquipo.classList.add('error-input');
          isValid = false;
        } else {
          tipoEquipo.classList.remove('error-input');
        }
        
        // Validar tipo de mantenimiento
        const tipoMantenimiento = document.getElementsByName('tipoMantenimiento')[0];
        if (tipoMantenimiento.value === "") {
          errores.push("Debe seleccionar un tipo de mantenimiento");
          tipoMantenimiento.classList.add('error-input');
          isValid = false;
        } else {
          tipoMantenimiento.classList.remove('error-input');
        }
        
        // Validar CP
        const cp = document.getElementById('cp');
        if (cp.value === "" || cp.value.length < 5) {
          errores.push("El código postal debe tener al menos 5 dígitos");
          cp.classList.add('error-input');
          isValid = false;
        } else {
          cp.classList.remove('error-input');
        }
        
        // Validar provincia
        const provincia = document.getElementById('provincia');
        if (provincia.value.trim() === "") {
          errores.push("La provincia es obligatoria");
          provincia.classList.add('error-input');
          isValid = false;
        } else {
          provincia.classList.remove('error-input');
        }
        
        // Validar localidad
        const localidad = document.getElementById('localidad');
        if (localidad.value.trim() === "") {
          errores.push("La localidad es obligatoria");
          localidad.classList.add('error-input');
          isValid = false;
        } else {
          localidad.classList.remove('error-input');
        }
        
        // Validar dirección
        const direccion = document.getElementById('direccion');
        if (direccion.value.trim() === "") {
          errores.push("La dirección es obligatoria");
          direccion.classList.add('error-input');
          isValid = false;
        } else {
          direccion.classList.remove('error-input');
        }
        
        // Validar usuario
        const idUsuario = document.getElementById('idUsuario');
        if (idUsuario.value === "") {
          errores.push("Debe seleccionar un usuario");
          idUsuario.classList.add('error-input');
          isValid = false;
        } else {
          idUsuario.classList.remove('error-input');
        }
        
        // Mostrar errores si los hay
        const errorSummary = document.getElementById('error-summary');
        if (!isValid) {
          errorSummary.innerHTML = "";
          errores.forEach(function(error) {
            const errorItem = document.createElement('div');
            errorItem.classList.add('error-message');
            errorItem.textContent = error;
            errorSummary.appendChild(errorItem);
          });
          errorSummary.style.display = 'block';
          window.scrollTo(0, 0);
        } else {
          errorSummary.style.display = 'none';
        }
        
        return isValid;
      }
    </script>
</head>
<body onload="actualizarCampos()">
    <h1>Modificar Equipo</h1>
    
    <!-- Mensajes de error -->
    <div id="error-summary" style="display: <?= !empty($errores) ? 'block' : 'none' ?>">
        <?php foreach ($errores as $error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
    
    <p><strong>Número de Equipo:</strong> <?= htmlspecialchars($equipoData['numEquipo']); ?></p>
    <p><strong>Fecha de Alta:</strong> <?= htmlspecialchars($equipoData['fechaAlta']); ?></p>
    
    <form method="post" action="" onsubmit="return validateForm()">
        <label>Tipo de Equipo:</label><br/>
        <select name="tipoEquipo[]" multiple onchange="actualizarCampos()" required
                class="<?= isset($errores['tipoEquipo']) ? 'error-input' : '' ?>">
            <option value="">-- Seleccione --</option>
            <?php foreach ($tiposEquipo as $val => $info): ?>
                <option value="<?= htmlspecialchars($val) ?>"
                    <?= ($val === $equipoData['tipoEquipo']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($info['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br/><br/>

        <!-- Nuevo campo Fecha de Compra -->
        <label>Fecha de Compra:</label><br/>
        <input type="date" name="fechaCompra" value="<?= htmlspecialchars($equipoData['fechaCompra'] ?? ''); ?>"><br/><br/>

        <div id="grupo-marca" style="display:none;">
            <label>Marca:</label><br/>
            <input type="text" name="marca" value="<?= htmlspecialchars($equipoData['marca']); ?>"><br/><br/>
        </div>

        <div id="grupo-modelo" style="display:none;">
            <label>Modelo:</label><br/>
            <input type="text" name="modelo" value="<?= htmlspecialchars($equipoData['modelo']); ?>"><br/><br/>
        </div>

        <div id="grupo-serie" style="display:none;">
            <label>Serie:</label><br/>
            <input type="text" name="serie" value="<?= htmlspecialchars($equipoData['serie']); ?>"><br/><br/>
        </div>

        <div id="grupo-placa" style="display:none;">
            <label>Placa:</label><br/>
            <input type="text" name="placa" value="<?= htmlspecialchars($equipoData['placa']); ?>"><br/><br/>
        </div>

        <div id="grupo-procesador" style="display:none;">
            <label>Procesador:</label><br/>
            <input type="text" name="procesador" value="<?= htmlspecialchars($equipoData['procesador']); ?>"><br/><br/>
        </div>

        <div id="grupo-memoria" style="display:none;">
            <label>Memoria:</label><br/>
            <input type="text" name="memoria" value="<?= htmlspecialchars($equipoData['memoria']); ?>"><br/><br/>
        </div>

        <div id="grupo-disco" style="display:none;">
            <label>Disco:</label><br/>
            <input type="text" name="disco" value="<?= htmlspecialchars($equipoData['disco']); ?>"><br/><br/>
        </div>

        <div id="grupo-pantalla" style="display:none;">
            <label>Pantalla:</label><br/>
            <input type="text" name="pantalla" value="<?= htmlspecialchars($equipoData['pantalla']); ?>"><br/><br/>
        </div>

        <div id="grupo-observaciones" style="display:none;">
            <label>Observaciones:</label><br/>
            <textarea name="observaciones"><?= htmlspecialchars($equipoData['observaciones']); ?></textarea><br/><br/>
        </div>

        <div id="grupo-costo" style="display:none;">
            <label>Costo:</label><br/>
            <input type="number" step="0.01" name="costo" value="<?= htmlspecialchars($equipoData['costo']); ?>"><br/><br/>
        </div>

        <div id="grupo-sistema" style="display:none;">
            <label>Sistema:</label><br/>
            <input type="text" name="sistema" value="<?= htmlspecialchars($equipoData['sistema']); ?>"><br/><br/>
        </div>

        <div id="grupo-ubicacion" style="display:none;">
            <label>Ubicación:</label><br/>
            <input type="text" name="ubicacion" value="<?= htmlspecialchars($equipoData['ubicacion']); ?>"><br/><br/>
        </div>

        <div id="grupo-tipo" style="display:none;">
            <label>Tipo (Especifique):</label><br/>
            <input type="text" name="tipo" value="<?= htmlspecialchars($equipoData['tipo']); ?>"><br/><br/>
        </div>

        <label>Tipo de Pago:</label><br/>
        <select name="tipoMantenimiento" required class="<?= isset($errores['tipoMantenimiento']) ? 'error-input' : '' ?>">
            <option value="">-- Seleccione --</option>
            <?php foreach ($tiposMantenimiento as $val => $info): ?>
                <option value="<?= htmlspecialchars($val) ?>" 
                        <?= $equipoData['tipoMantenimiento'] === $val ? 'selected' : '' ?>>
                    <?= htmlspecialchars($info['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br/><br/>

        <label>Código Postal:</label><br/>
        <input type="text" id="cp" name="cp" value="<?= htmlspecialchars($equipoData['cp']); ?>" 
               required minlength="5" oninput="validarCP(this)" 
               class="<?= isset($errores['cp']) ? 'error-input' : '' ?>">
        <div id="cp-error" class="error-message" style="display: none;"></div>
        <br/><br/>

        <label>Provincia:</label><br/>
        <input type="text" id="provincia" name="provincia" value="<?= htmlspecialchars($equipoData['provincia']); ?>" 
               required class="<?= isset($errores['provincia']) ? 'error-input' : '' ?>"><br/><br/>

        <label>Localidad:</label><br/>
        <input type="text" id="localidad" name="localidad" value="<?= htmlspecialchars($equipoData['localidad']); ?>" 
               required class="<?= isset($errores['localidad']) ? 'error-input' : '' ?>"><br/><br/>

        <label>Dirección:</label><br/>
        <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($equipoData['direccion']); ?>" 
               required class="<?= isset($errores['direccion']) ? 'error-input' : '' ?>"><br/><br/>

        <label>Seleccione el Cliente:</label><br/>
        <select id="idUsuario" name="idUsuario" onchange="autocompletarDireccion()" 
                required class="<?= isset($errores['idUsuario']) ? 'error-input' : '' ?>">
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
        <br/><br/>

        <label>Tipo de dirección:</label><br/>
        <select id="tipoDireccion" onchange="autocompletarDireccion()">
            <option value="fiscal">Fiscal</option>
            <option value="1">Dirección 1</option>
            <option value="2">Dirección 2</option>
        </select>
        <br/><br/>

        <input type="submit" name="modificar" value="Modificar Equipo">
        <button type="button" onclick="window.location.href='../home.php'">Cancelar</button>
    </form>
</body>
</html>