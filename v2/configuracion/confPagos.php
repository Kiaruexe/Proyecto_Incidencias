<?php

// Mostrar todos los errores (en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el usuario tiene permisos (agrega tu lógica de verificación aquí)
session_start();

// Inicializar variables
$mensaje = '';
$error = '';
$tiposMantenimiento = [];

// Función para leer los tipos de mantenimiento desde el archivo
function leerTiposMantenimiento() {
    $archivoJson = '../configuracion/tiposMantenimiento.json';
    $archivoPhp = '../configuracion/tiposMantenimiento.php';
    
    // Crear un log para depuración
    error_log("Intentando leer tipos de mantenimiento");
    
    // Intentar leer desde el archivo JSON (prioridad)
    if (file_exists($archivoJson)) {
        error_log("Archivo JSON encontrado: $archivoJson");
        $contenido = file_get_contents($archivoJson);
        if ($contenido !== false) {
            $datos = json_decode($contenido, true);
            if (is_array($datos) && !empty($datos)) {
                error_log("Datos JSON leídos correctamente");
                return $datos;
            } else {
                error_log("Error al decodificar JSON o datos vacíos");
            }
        } else {
            error_log("Error al leer el archivo JSON");
        }
    } else {
        error_log("Archivo JSON no encontrado: $archivoJson");
    }
    
    // Si no existe el JSON, intentar el PHP
    if (file_exists($archivoPhp)) {
        error_log("Archivo PHP encontrado: $archivoPhp");
        include_once($archivoPhp);
        if (isset($tiposMantenimiento) && is_array($tiposMantenimiento)) {
            error_log("Datos PHP leídos correctamente");
            return $tiposMantenimiento;
        } else {
            error_log("Variable \$tiposMantenimiento no definida en el archivo PHP o no es un array");
        }
    } else {
        error_log("Archivo PHP no encontrado: $archivoPhp");
    }
    
    // Si ninguno existe, devolver los tipos por defecto
    error_log("Usando tipos de pago por defecto");
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

// Función para guardar los tipos de mantenimiento en los archivos
function guardarTiposMantenimiento($tipos) {
    $directorio = '../configuracion';
    
    // Crear directorio si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    // Guardar como JSON
    $archivoJson = $directorio . '/tiposMantenimiento.json';
    file_put_contents($archivoJson, json_encode($tipos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // También guardar como PHP para compatibilidad
    $archivoPHP = $directorio . '/tiposMantenimiento.php';
    $contenido = "<?php\n// Archivo generado automáticamente por confPagos.php\n";
    $contenido .= "\$tiposMantenimiento = " . var_export($tipos, true) . ";\n";
    file_put_contents($archivoPHP, $contenido);
}

// Cargar los tipos existentes
$tiposMantenimiento = leerTiposMantenimiento();

// Verificar que $tiposMantenimiento sea un array válido
if (!is_array($tiposMantenimiento)) {
    error_log('$tiposMantenimiento no es un array. Usando array vacío.');
    $tiposMantenimiento = [];
    $error = 'Error al cargar los tipos de pago. Se ha inicializado con un conjunto vacío.';
}

// Procesar formulario de agregar nuevo tipo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_tipo'])) {
        $valor = isset($_POST['valor']) ? strtolower(trim($_POST['valor'])) : '';
        $etiqueta = isset($_POST['etiqueta']) ? trim($_POST['etiqueta']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        
        // Validar entradas
        if (empty($valor) || empty($etiqueta)) {
            $error = 'Los campos Valor y Etiqueta son obligatorios.';
        } elseif (preg_match('/[^a-z0-9_]/', $valor)) {
            $error = 'El valor solo puede contener letras minúsculas, números y guiones bajos.';
        } elseif (isset($tiposMantenimiento[$valor])) {
            $error = 'Ya existe un tipo de pago con ese valor.';
        } else {
            // Agregar nuevo tipo
            $tiposMantenimiento[$valor] = [
                'label' => $etiqueta,
                'descripcion' => $descripcion
            ];
            
            // Guardar en archivo
            guardarTiposMantenimiento($tiposMantenimiento);
            
            $mensaje = 'Tipo de pago agregado correctamente.';
        }
    } elseif (isset($_POST['eliminar_tipo'])) {
        $valorEliminar = $_POST['eliminar_tipo'];
        
        // No permitir eliminar tipos básicos
        $tiposBasicos = ['mantenimientoCompleto', 'mantenimientoManoObra', 'mantenimientoFacturable', 'mantenimientoGarantia'];
        if (in_array($valorEliminar, $tiposBasicos)) {
            $error = 'No se pueden eliminar los tipos básicos de pago.';
        } else {
            // Eliminar tipo
            unset($tiposMantenimiento[$valorEliminar]);
            
            // Guardar en archivo
            guardarTiposMantenimiento($tiposMantenimiento);
            
            $mensaje = 'Tipo de pago eliminado correctamente.';
        }
    } elseif (isset($_POST['editar_tipo'])) {
        $valorEditar = $_POST['tipo_editar'];
        $nuevaEtiqueta = trim($_POST['nueva_etiqueta']);
        $nuevaDescripcion = trim($_POST['nueva_descripcion']);
        
        // Validar entradas
        if (empty($nuevaEtiqueta)) {
            $error = 'La etiqueta es obligatoria.';
        } else {
            // Actualizar tipo
            $tiposMantenimiento[$valorEditar]['label'] = $nuevaEtiqueta;
            $tiposMantenimiento[$valorEditar]['descripcion'] = $nuevaDescripcion;
            
            // Guardar en archivo
            guardarTiposMantenimiento($tiposMantenimiento);
            
            $mensaje = 'Tipo de Pago actualizado correctamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Tipos de Pago</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .mensaje {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .accion {
            margin-right: 5px;
        }
        .edit-form {
            display: none;
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .edit-form h3 {
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"], 
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group textarea {
            height: 100px;
        }
    </style>
    <script>
        function mostrarFormularioEdicion(valor, etiqueta, descripcion) {
            document.getElementById('formulario-edicion').style.display = 'block';
            document.getElementById('tipo_editar').value = valor;
            document.getElementById('nueva_etiqueta').value = etiqueta;
            document.getElementById('nueva_descripcion').value = descripcion;
            document.getElementById('titulo-edicion').textContent = 'Editar tipo de Pago: ' + etiqueta;
            
            // Scroll al formulario
            document.getElementById('formulario-edicion').scrollIntoView();
        }
    </script>
</head>
<body>
    <h1>Gestión de Tipos de Pago</h1>
    
    <nav>
        <ul>
            <li><a href="../home.php">Volver al inicio</a></li>
            <li><a href="../equipos/crearEquipos.php">Registrar Equipo</a></li>
        </ul>
    </nav>
    
    <?php if (!empty($mensaje)): ?>
        <script>
            alert("<?= addslashes($mensaje) ?>");
        </script>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <script>
            alert("Error: <?= addslashes($error) ?>");
        </script>
    <?php endif; ?>
    
    <h2>Tipos de Pago Existentes</h2>
    
    <?php if (empty($tiposMantenimiento)): ?>
        <p>No hay tipos de Pago definidos. Por favor, agregue uno a continuación.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Valor</th>
                    <th>Etiqueta</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tiposMantenimiento as $valor => $datos): ?>
                    <tr>
                        <td><?= htmlspecialchars($valor) ?></td>
                        <td><?= htmlspecialchars($datos['label']) ?></td>
                        <td><?= htmlspecialchars($datos['descripcion'] ?? '') ?></td>
                        <td>
                            <button class="accion" onclick="mostrarFormularioEdicion('<?= htmlspecialchars(addslashes($valor)) ?>', '<?= htmlspecialchars(addslashes($datos['label'])) ?>', '<?= htmlspecialchars(addslashes($datos['descripcion'] ?? '')) ?>')">Editar</button>
                            
                            <?php if (!in_array($valor, ['mantenimientoCompleto', 'mantenimientoManoObra', 'mantenimientoFacturable', 'mantenimientoGarantia'])): ?>
                                <form method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este tipo de Pago?');">
                                    <button type="submit" name="eliminar_tipo" value="<?= htmlspecialchars($valor) ?>" class="accion">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <h2>Agregar Nuevo Tipo de Pago</h2>
    <form method="post" action="" id="agregar-form" onsubmit="return validarFormulario()">
        <div class="form-group">
            <label for="valor">Valor (identificador único):</label>
            <input type="text" id="valor" name="valor" pattern="[a-z0-9_]+" 
                   title="Solo letras minúsculas, números o guiones bajos" required>
            <small>Este valor se usa internamente. Solo use letras minúsculas, números y guiones bajos, sin espacios.</small>
        </div>
        <div class="form-group">
            <label for="etiqueta">Etiqueta (nombre visible):</label>
            <input type="text" id="etiqueta" name="etiqueta" required>
            <small>Este es el nombre que se mostrará a los clientes.</small>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion"></textarea>
            <small>Descripción detallada del tipo de Pago.</small>
        </div>
        <div>
            <button type="submit" name="agregar_tipo">Agregar Tipo de Pago</button>
        </div>
    </form>
    
    <div id="formulario-edicion" class="edit-form">
        <h3 id="titulo-edicion">Editar tipo de Pago</h3>
        <form method="post" action="" id="editar-form" onsubmit="return validarFormularioEdicion()">
            <input type="hidden" id="tipo_editar" name="tipo_editar" value="">
            
            <div class="form-group">
                <label for="nueva_etiqueta">Etiqueta:</label>
                <input type="text" id="nueva_etiqueta" name="nueva_etiqueta" required>
            </div>
            <div class="form-group">
                <label for="nueva_descripcion">Descripción:</label>
                <textarea id="nueva_descripcion" name="nueva_descripcion"></textarea>
            </div>
            <div>
                <button type="submit" name="editar_tipo">Guardar Cambios</button>
            </div>
        </form>
    </div>

    <script>
        // Validación del formulario de agregar
        function validarFormulario() {
            var valor = document.getElementById('valor').value.trim();
            var etiqueta = document.getElementById('etiqueta').value.trim();
            
            if (valor === '') {
                alert('El campo Valor es obligatorio.');
                return false;
            }
            
            if (etiqueta === '') {
                alert('El campo Etiqueta es obligatorio.');
                return false;
            }
            
            // Validar que solo contenga letras minúsculas, números y guiones bajos
            var pattern = /^[a-z0-9_]+$/;
            if (!pattern.test(valor)) {
                alert('El valor solo puede contener letras minúsculas, números y guiones bajos.');
                return false;
            }
            
            return true;
        }
        
        // Validación del formulario de edición
        function validarFormularioEdicion() {
            var etiqueta = document.getElementById('nueva_etiqueta').value.trim();
            
            if (etiqueta === '') {
                alert('El campo Etiqueta es obligatorio.');
                return false;
            }
            
            return true;
        }

        // Mostrar errores de PHP como alertas
        window.onload = function() {
            <?php if (!empty($error)): ?>
                alert("Error: <?= addslashes($error) ?>");
            <?php endif; ?>
            
            <?php if (!empty($mensaje)): ?>
                alert("<?= addslashes($mensaje) ?>");
            <?php endif; ?>
        };
    </script>
</body>
</html>