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
    echo "<script>alert('Error de conexión: " . addslashes($e->getMessage()) . "');</script>";
    exit;
}

// Manejar solicitud de borrado
if (isset($_POST['borrar']) && isset($_POST['numEquipo'])) {
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
                margin-right: 5px;
            }
            
            .action-button:hover {
                background-color: #45a049;
            }
            
            .delete-button {
                background-color: #f44336;
                color: white;
                padding: 5px 10px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
            
            .delete-button:hover {
                background-color: #d32f2f;
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
        <script>
            function confirmarBorrado(numEquipo, tipoEquipo, cliente) {
                return confirm(`¿Está seguro que desea eliminar el equipo ${numEquipo} (${tipoEquipo}) de ${cliente}?\n\nEsta acción no se puede deshacer.`);
            }
        </script>
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
                        <th>Acciones</th>
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
                                <form method="post" action="" style="display:inline;" 
                                      onsubmit="return confirmarBorrado('<?= htmlspecialchars($eq['numEquipo']); ?>', 
                                                               '<?= htmlspecialchars($tiposEquipo[$eq['tipoEquipo']]['label'] ?? $eq['tipoEquipo']); ?>', 
                                                               '<?= htmlspecialchars($eq['nombreCliente']); ?>')">
                                    <input type="hidden" name="numEquipo" value="<?= htmlspecialchars($eq['numEquipo']); ?>">
                                    <button type="submit" name="borrar" class="delete-button">Borrar</button>
                                </form>
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
        echo "<script>alert('Equipo no encontrado.'); window.location.href='../home.php';</script>";
        exit;
    }
} catch (PDOException $e) {
    echo "<script>alert('Error al obtener el equipo: " . addslashes($e->getMessage()) . "'); window.location.href='../home.php';</script>";
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
    echo "<script>alert('Error al cargar usuarios: " . addslashes($e->getMessage()) . "'); window.location.href='../home.php';</script>";
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
    // Errores se manejarán con JavaScript alerts en lugar de errores PHP
    
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
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
    <style>
        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .delete-button {
            background-color: #f44336;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-button:hover {
            background-color: #d32f2f;
        }
        .modify-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .modify-button:hover {
            background-color: #45a049;
        }
        .cancel-button {
            background-color: #ccc;
            color: black;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .cancel-button:hover {
            background-color: #bbb;
        }
        /* Añadido para destacar campos con errores */
        .error-input {
            border: 2px solid #f44336 !important;
            background-color: #ffe6e6 !important;
        }
        .error-message {
            color: #f44336;
            font-size: 0.9em;
            margin-top: 5px;
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

        // Helper function para validar formatos de fecha
        function isValidDate(dateString) {
            // Comprobar si la fecha es válida usando el objeto Date
            const dateObj = new Date(dateString);
            return !isNaN(dateObj.getTime());
        }

        // Validación mejorada para entradas de moneda/costo
        function validarCosto(input) {
            if (input.value !== "" && (isNaN(parseFloat(input.value)) || parseFloat(input.value) < 0)) {
                alert("El costo debe ser un número válido mayor o igual a cero");
                input.classList.add('error-input');
                return false;
            }
            input.classList.remove('error-input');
            return true;
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

        // Validar elementos multi-select
        function validarMultiSelect(input, fieldName) {
            if (input.selectedOptions.length === 0) {
                alert(`Debe seleccionar al menos una opción en ${fieldName}`);
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
            const tipoEquipo = document.getElementsByName('tipoEquipo[]')[0];
            if (!validarMultiSelect(tipoEquipo, "Tipo de Equipo")) {
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
            if (tipoEquipo.selectedOptions.length > 0) {
                const selectedEquipo = tipoEquipo.selectedOptions[0].value;
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
            
            // Validar multiselect
            const tipoEquipo = document.getElementsByName('tipoEquipo[]')[0];
            if (tipoEquipo) {
                tipoEquipo.addEventListener('change', function() {
                    validarMultiSelect(this, "Tipo de Equipo");
                    // Actualizar campos visibles basados en la selección
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
    <h1>Modificar Equipo</h1>
    
    <p><strong>Número de Equipo:</strong> 001</p>
    <p><strong>Fecha de Alta:</strong> 12/05/2025</p>
    
    <form method="post" action="" onsubmit="return validateForm() && confirm('¿Está seguro que desea modificar este equipo?')">
        <label>Tipo de Equipo:</label><br/>
        <select name="tipoEquipo[]" multiple onchange="actualizarCampos()" required>
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
        <input type="date" id="fechaCompra" name="fechaCompra" value=""><br/><br/>

        <div id="grupo-marca" style="display:none;">
            <label>Marca:</label><br/>
            <input type="text" name="marca" value=""><br/><br/>
        </div>

        <div id="grupo-modelo" style="display:none;">
            <label>Modelo:</label><br/>
            <input type="text" name="modelo" value=""><br/><br/>
        </div>

        <div id="grupo-serie" style="display:none;">
            <label>Serie:</label><br/>
            <input type="text" name="serie" value=""><br/><br/>
        </div>

        <div id="grupo-placa" style="display:none;">
            <label>Placa:</label><br/>
            <input type="text" name="placa" value=""><br/><br/>
        </div>

        <div id="grupo-procesador" style="display:none;">
            <label>Procesador:</label><br/>
            <input type="text" name="procesador" value=""><br/><br/>
        </div>

        <div id="grupo-memoria" style="display:none;">
            <label>Memoria:</label><br/>
            <input type="text" name="memoria" value=""><br/><br/>
        </div>

        <div id="grupo-disco" style="display:none;">
            <label>Disco:</label><br/>
            <input type="text" name="disco" value=""><br/><br/>
        </div>

        <div id="grupo-pantalla" style="display:none;">
            <label>Pantalla:</label><br/>
            <input type="text" name="pantalla" value=""><br/><br/>
        </div>

        <div id="grupo-observaciones" style="display:none;">
            <label>Observaciones:</label><br/>
            <textarea name="observaciones"></textarea><br/><br/>
        </div>

        <div id="grupo-costo" style="display:none;">
            <label>Costo:</label><br/>
            <input type="number" step="0.01" name="costo" value=""><br/><br/>
        </div>

        <div id="grupo-sistema" style="display:none;">
            <label>Sistema:</label><br/>
            <input type="text" name="sistema" value=""><br/><br/>
        </div>

        <div id="grupo-ubicacion" style="display:none;">
            <label>Ubicación:</label><br/>
            <input type="text" name="ubicacion" value=""><br/><br/>
        </div>

        <div id="grupo-tipo" style="display:none;">
            <label>Tipo (Especifique):</label><br/>
            <input type="text" name="tipo" value=""><br/><br/>
        </div>

        <label>Tipo de Pago:</label><br/>
        <select name="tipoMantenimiento" required>
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
        <input type="text" id="cp" name="cp" value="" required minlength="5">
        <div id="cp-error" class="error-message" style="display: none;"></div>
        <br/><br/>

        <label>Provincia:</label><br/>
        <input type="text" id="provincia" name="provincia" value="" required><br/><br/>

        <label>Localidad:</label><br/>
        <input type="text" id="localidad" name="localidad" value="" required><br/><br/>

        <label>Dirección:</label><br/>
        <input type="text" id="direccion" name="direccion" value="" required><br/><br/>

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