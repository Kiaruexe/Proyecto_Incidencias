<?php
// gestionar_tipos_equipo.php

// Mostrar todos los errores (en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el usuario es administrador (agrega tu lógica de verificación de admin aquí)
// Por ejemplo:
session_start();
// Inicializar variables
$mensaje = '';
$error = '';
$tiposEquipo = [];

// Función para leer los tipos de equipo desde el archivo
function leerTiposEquipo() {
    $archivoJson = '../configuracion/tiposEquipo.json';
    $archivoPhp = '../configuracion/tiposEquipo.php'; 
    // Intentar leer desde el archivo JSON (prioridad)
    if (file_exists($archivoJson)) {
        $contenido = file_get_contents($archivoJson);
        return json_decode($contenido, true);
    }
    
    // Si no existe el JSON, intentar el PHP
    if (file_exists($archivoPhp)) {
        include_once($archivoPhp);
        if (isset($tiposEquipo)) {
            return $tiposEquipo;
        }
    }
    
    // Si ninguno existe, devolver los tipos por defecto
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

// Función para guardar los tipos de equipo en el archivo
function guardarTiposEquipo($tipos) {
    // Corregido: Usar config en lugar de configuracion
    $directorio = '../configuracion';
    
    // Crear directorio si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    // Guardar como JSON
    $archivoJson = $directorio . '/tiposEquipo.json';
    file_put_contents($archivoJson, json_encode($tipos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // También guardar como PHP para compatibilidad
    $archivoPHP = $directorio . '/tiposEquipo.php';
    $contenido = "<?php\n// Archivo generado automáticamente por gestionar_tipos_equipo.php\n";
    $contenido .= "\$tiposEquipo = " . var_export($tipos, true) . ";\n";
    file_put_contents($archivoPHP, $contenido);
}

// Carga los tipos existentes
$tiposEquipo = leerTiposEquipo();

// Procesar formulario de agregar nuevo tipo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_tipo'])) {
        $valor = isset($_POST['valor']) ? strtolower(trim($_POST['valor'])) : '';
        $etiqueta = isset($_POST['etiqueta']) ? trim($_POST['etiqueta']) : '';
        $prefijo = isset($_POST['prefijo']) ? trim($_POST['prefijo']) : '';
        
        // Validar entradas
        if (empty($valor) || empty($etiqueta) || empty($prefijo)) {
            $error = 'Todos los campos son obligatorios.';
        } elseif (preg_match('/[^a-z0-9_]/', $valor)) {
            $error = 'El valor solo puede contener letras minúsculas, números y guiones bajos.';
        } elseif (isset($tiposEquipo[$valor])) {
            $error = 'Ya existe un tipo de equipo con ese valor.';
        } else {
            // Agregar nuevo tipo
            $tiposEquipo[$valor] = [
                'label' => $etiqueta,
                'prefijo' => $prefijo,
                'campos' => [] // Inicializar array de campos vacío
            ];
            
            // Guardar en archivo
            guardarTiposEquipo($tiposEquipo);
            
            $mensaje = 'Tipo de equipo agregado correctamente.';
        }
    } elseif (isset($_POST['eliminar_tipo'])) {
        $valorEliminar = $_POST['eliminar_tipo'];
        
        // No permitir eliminar tipos básicos
        $tiposBasicos = ['pc', 'portatil', 'impresora', 'monitor', 'otro'];
        if (in_array($valorEliminar, $tiposBasicos)) {
            $error = 'No se pueden eliminar los tipos básicos de equipo.';
        } else {
            // Eliminar tipo
            unset($tiposEquipo[$valorEliminar]);
            
            // Guardar en archivo
            guardarTiposEquipo($tiposEquipo);
            
            $mensaje = 'Tipo de equipo eliminado correctamente.';
        }
    }
}

// Definir los campos disponibles para cada tipo de equipo
$camposPorTipo = [
    'pc' => ['marca','modelo','serie','placa','procesador','memoria','disco','observaciones','costo','sistema','ubicacion'],
    'portatil' => ['marca','modelo','serie','procesador','memoria','disco','pantalla','observaciones','costo','sistema','ubicacion'],
    'impresora' => ['marca','modelo','serie','observaciones','ubicacion','costo'],
    'monitor' => ['marca','modelo','serie','observaciones','ubicacion','costo'],
    'otro' => ['tipo','marca','modelo','serie','observaciones','ubicacion','costo'],
    'teclado' => ['marca','modelo','serie','observaciones','costo','ubicacion'],
    'raton' => ['marca','modelo','serie','observaciones','costo','ubicacion'],
    'router' => ['marca','modelo','serie','observaciones','costo','ubicacion'],
    'sw' => ['marca','modelo','serie','observaciones','costo','ubicacion'],
    'sai' => ['marca','modelo','serie','observaciones','costo','ubicacion'],
];

// Si llega un formulario para editar los campos asociados a un tipo
if (isset($_POST['guardar_campos'])) {
    $tipoEditar = $_POST['tipo_editar'];
    $camposSeleccionados = isset($_POST['campos']) ? $_POST['campos'] : [];
    
    // Guardar los campos para este tipo
    if (isset($tiposEquipo[$tipoEditar])) {
        $tiposEquipo[$tipoEditar]['campos'] = $camposSeleccionados;
        guardarTiposEquipo($tiposEquipo);
        $mensaje = 'Campos actualizados correctamente para el tipo "' . $tiposEquipo[$tipoEditar]['label'] . '".';
    } else {
        $error = 'El tipo de equipo no existe.';
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Tipos de Equipo</title>
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
        .campos-container {
            margin-top: 20px;
            display: none;
        }
    </style>
    <script>
        function mostrarFormularioCampos(tipo, label) {
            document.getElementById('formulario-campos').style.display = 'block';
            document.getElementById('tipo_editar').value = tipo;
            document.getElementById('titulo-campos').textContent = 'Configurar campos para: ' + label;
            
            // Desmarcar todos los checkboxes
            const checkboxes = document.querySelectorAll('input[name="campos[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Marcar los checkboxes asociados a este tipo si existen
            const tiposEquipo = <?= json_encode($tiposEquipo) ?>;
            if (tiposEquipo[tipo] && tiposEquipo[tipo].campos) {
                tiposEquipo[tipo].campos.forEach(campo => {
                    const checkbox = document.querySelector(`input[name="campos[]"][value="${campo}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            } else {
                // Si no tiene campos definidos, usar los de los tipos predefinidos
                const camposPorTipo = <?= json_encode($camposPorTipo) ?>;
                if (camposPorTipo[tipo]) {
                    camposPorTipo[tipo].forEach(campo => {
                        const checkbox = document.querySelector(`input[name="campos[]"][value="${campo}"]`);
                        if (checkbox) checkbox.checked = true;
                    });
                }
            }
            
            // Scroll al formulario
            document.getElementById('formulario-campos').scrollIntoView();
        }
    </script>
</head>
<body>
    <h1>Gestión de Tipos de Equipo</h1>
    
    <nav>
        <ul>
            <li><a href="../home.php">Volver al inicio</a></li>
            <li><a href="../equipos/crearEquipos.php">Registrar Equipo</a></li>
        </ul>
    </nav>
    
    <?php if (!empty($mensaje)): ?>
        <div class="mensaje exito"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <h2>Tipos de Equipo Existentes</h2>
    <table>
        <thead>
            <tr>
                <th>Valor</th>
                <th>Etiqueta</th>
                <th>Prefijo</th>
                <th>Campos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tiposEquipo as $valor => $datos): ?>
                <tr>
                    <td><?= htmlspecialchars($valor) ?></td>
                    <td><?= htmlspecialchars($datos['label']) ?></td>
                    <td><?= htmlspecialchars($datos['prefijo']) ?></td>
                    <td>
                        <?php 
                            if(isset($datos['campos']) && is_array($datos['campos'])) {
                                echo htmlspecialchars(implode(', ', $datos['campos']));
                            } else {
                                echo '<em>(Sin campos configurados)</em>';
                            }
                        ?>
                    </td>
                    <td>
                        <button class="accion" onclick="mostrarFormularioCampos('<?= htmlspecialchars($valor) ?>', '<?= htmlspecialchars($datos['label']) ?>')">Configurar Campos</button>
                        
                        <?php if (!in_array($valor, ['pc', 'portatil', 'impresora', 'monitor', 'otro'])): ?>
                            <form method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este tipo de equipo?');">
                                <button type="submit" name="eliminar_tipo" value="<?= htmlspecialchars($valor) ?>" class="accion">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h2>Agregar Nuevo Tipo de Equipo</h2>
    <form method="post" action="">
        <div>
            <label for="valor">Valor (identificador único):</label>
            <input type="text" id="valor" name="valor" pattern="[a-z0-9_]+" 
                   title="Solo letras minúsculas, números o guiones bajos" required>
            <small>Este valor se usa internamente. Solo use letras minúsculas, números y guiones bajos, sin espacios.</small>
        </div>
        <div>
            <label for="etiqueta">Etiqueta (nombre visible):</label>
            <input type="text" id="etiqueta" name="etiqueta" required>
            <small>Este es el nombre que se mostrará a los clientes.</small>
        </div>
        <div>
            <label for="prefijo">Prefijo (para códigos de equipo):</label>
            <input type="text" id="prefijo" name="prefijo" required maxlength="5">
            <small>Código corto usado para generar el número de equipo (máx. 5 caracteres).</small>
        </div>
        <div>
            <button type="submit" name="agregar_tipo">Agregar Tipo de Equipo</button>
        </div>
    </form>
    
    <div id="formulario-campos" class="campos-container">
        <h2 id="titulo-campos">Configurar campos para tipo</h2>
        <form method="post" action="">
            <input type="hidden" id="tipo_editar" name="tipo_editar" value="">
            
            <p>Seleccione los campos que se mostrarán para este tipo de equipo:</p>
            
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <div style="margin-right: 20px;">
                    <label><input type="checkbox" name="campos[]" value="marca"> Marca</label><br>
                    <label><input type="checkbox" name="campos[]" value="modelo"> Modelo</label><br>
                    <label><input type="checkbox" name="campos[]" value="serie"> Serie</label><br>
                    <label><input type="checkbox" name="campos[]" value="placa"> Placa</label><br>
                </div>
                <div style="margin-right: 20px;">
                    <label><input type="checkbox" name="campos[]" value="procesador"> Procesador</label><br>
                    <label><input type="checkbox" name="campos[]" value="memoria"> Memoria</label><br>
                    <label><input type="checkbox" name="campos[]" value="disco"> Disco</label><br>
                    <label><input type="checkbox" name="campos[]" value="pantalla"> Pantalla</label><br>
                </div>
                <div style="margin-right: 20px;">
                    <label><input type="checkbox" name="campos[]" value="observaciones"> Observaciones</label><br>
                    <label><input type="checkbox" name="campos[]" value="costo"> Costo</label><br>
                    <label><input type="checkbox" name="campos[]" value="sistema"> Sistema</label><br>
                    <label><input type="checkbox" name="campos[]" value="ubicacion"> Ubicación</label><br>
                </div>
                <div>
                    <label><input type="checkbox" name="campos[]" value="tipo"> Tipo (Específico)</label><br>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <button type="submit" name="guardar_campos">Guardar configuración de campos</button>
            </div>
        </form>
    </div>
</body>
</html>