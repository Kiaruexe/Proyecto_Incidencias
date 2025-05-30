<?php
// gestionar_tipos_equipo.php

// Mostrar todos los errores (en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
        $camposSeleccionados = isset($_POST['campos']) ? $_POST['campos'] : [];
        
        // Validar entradas
        if (empty($valor) || empty($etiqueta) || empty($prefijo)) {
            $error = 'Todos los campos son obligatorios.';
        } elseif (preg_match('/[^a-z0-9_]/', $valor)) {
            $error = 'El valor solo puede contener letras minúsculas, números y guiones bajos.';
        } elseif (isset($tiposEquipo[$valor])) {
            $error = 'Ya existe un tipo de equipo con ese valor.';
        } else {
            // Agregar nuevo tipo con los campos seleccionados
            $tiposEquipo[$valor] = [
                'label' => $etiqueta,
                'prefijo' => $prefijo,
                'campos' => $camposSeleccionados // Guardar los campos seleccionados
            ];
            
            // Guardar en archivo
            guardarTiposEquipo($tiposEquipo);
            
            $mensaje = 'Tipo de equipo agregado correctamente con sus campos configurados.';
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

// Lista de todos los campos posibles para mostrar en el formulario
$todosCampos = [
    'marca' => 'Marca',
    'modelo' => 'Modelo',
    'serie' => 'Serie',
    'placa' => 'Placa',
    'procesador' => 'Procesador',
    'memoria' => 'Memoria',
    'disco' => 'Disco',
    'pantalla' => 'Pantalla',
    'observaciones' => 'Observaciones',
    'costo' => 'Costo',
    'sistema' => 'Sistema',
    'ubicacion' => 'Ubicación',
    'tipo' => 'Tipo (Específico)'
];

$gruposCampos = [
    'grupo1' => ['marca', 'modelo', 'serie', 'placa'],
    'grupo2' => ['procesador', 'memoria', 'disco', 'pantalla'],
    'grupo3' => ['observaciones', 'costo', 'sistema', 'ubicacion'],
    'grupo4' => ['tipo']
];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="…">
    <title>Gestionar Tipos de Equipo - Mapache Security</title>
    <style>
        /* CSS para Mapache Security - Gestionar Tipos de Equipo */

        /* Variables de colores actualizadas */
        :root {
            --azul-principal: #2573f9;
            --naranja: #f9ab25;
            --verde: #27ae60;
            --verde-hover: #219150;
            --gris-claro: #f1f2f2;
            --azul-claro: #f7faff;
            --azul-marino: #002255;
            --blanco: #ffffff;
            --texto-oscuro: #333333;
            --border-color: #e0e6ed;
        }

        /* Reset y configuración base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gris-claro);
            min-height: 100vh;
            color: var(--texto-oscuro);
            line-height: 1.6;
        }

        /* Header principal - REDUCIDO */
        .main-header {
            background: var(--azul-marino);
            color: var(--blanco);
            text-align: center;
            padding: 1rem 2rem; /* Reducido de 2rem a 1rem */
            margin-bottom: 2rem;
            position: relative;
        }

        .main-header h1 {
            font-size: 2rem; /* Reducido de 2.5rem a 2rem */
            font-weight: 700;
        }

        /* Icono de casa en la esquina superior derecha */
        .home-icon {
        position: absolute;
        top: 10px; /* Reducido de 15px a 10px */
        right: 20px;
        text-decoration: none;
        transition: background-color 0.3s ease;
        }
        .home-icon .fas {
        color: white;      /* relleno completamente blanco */
        font-size: 20px; /* Reducido de 24px a 20px */
        }
        .home-icon:hover {
        background: rgba(255, 255, 255, 0.2);
        }


        /* Container principal */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Título de página */
        .page-title {
            text-align: center;
            color: var(--azul-marino);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }

        /* Navegación centrada */
        nav {
            background: var(--blanco);
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        nav ul {
            list-style: none;
            display: inline-flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        nav li a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        /* Botón "Volver al inicio" en naranja */
        nav li:first-child a {
            background: var(--naranja);
            color: var(--blanco);
        }

        nav li:first-child a:hover {
            background: #e8941f;
            transform: translateY(-2px);
        }

        /* Botón "Registrar Equipo" en azul */
        nav li:last-child a {
            background: var(--azul-principal);
            color: var(--blanco);
        }

        nav li:last-child a:hover {
            background: #1a5fd4;
            transform: translateY(-2px);
        }

        /* Títulos de sección */
        h2 {
            color: var(--azul-marino);
            font-size: 1.5rem;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--naranja);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            background: var(--blanco);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        table thead {
            background: var(--azul-marino);
        }

        table th {
            padding: 1rem;
            text-align: left;
            color: var(--blanco);
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        table tbody tr:nth-child(odd) {
            background: var(--blanco);
        }

        table tbody tr:nth-child(even) {
            background: var(--gris-claro);
        }

        table tbody tr:hover {
            background: var(--azul-claro);
        }

        /* Prefijos en la tabla */
        .prefijo-badge {
            background: var(--naranja);
            color: var(--blanco);
            padding: 0.3rem 0.7rem;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.8rem;
        }

        /* Acciones centradas */
        .acciones-col {
            text-align: center;
        }

        /* Formularios */
        .form-container {
            background: var(--blanco);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 2px solid #e3f2fd;
        }

        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            align-items: end;
        }

        .form-group {
            margin-bottom: 1.5rem;
            flex: 1;
        }

        .form-group.half {
            flex: 1;
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--azul-marino);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--blanco);
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--azul-principal);
            box-shadow: 0 0 0 3px rgba(37, 115, 249, 0.1);
        }

        small {
            display: block;
            color: #666;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            font-style: italic;
        }

        /* Botones */
        button, input[type="submit"] {
            background: var(--azul-principal);
            color: var(--blanco);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        button:hover, input[type="submit"]:hover {
            background: #1a5fd4;
            transform: translateY(-2px);
        }

        /* Botón de agregar en verde */
        .btn-agregar {
            background: var(--verde) !important;
            color: var(--blanco) !important;
            padding: 1rem 2rem !important;
            font-size: 1.1rem !important;
            margin-top: 1rem;
        }

        .btn-agregar:hover {
            background: var(--verde-hover) !important;
        }

        button.accion {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            text-transform: none;
            letter-spacing: normal;
        }

        .btn-configurar {
            background: var(--azul-principal);
        }

        .btn-configurar:hover {
            background: #1a5fd4;
        }

        .btn-eliminar {
            background: #dc3545;
        }

        .btn-eliminar:hover {
            background: #c82333;
        }

        /* Sección de campos - Estilo de cuadrícula */
        .campos-section {
            background: var(--azul-claro);
            border: 2px solid var(--azul-principal);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .campos-section h3 {
            color: var(--azul-marino);
            margin-bottom: 1rem;
            font-size: 1.2rem;
            text-align: center;
        }

        .campos-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .campo-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: var(--blanco);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .campo-item:hover {
            background: var(--gris-claro);
            border-color: var(--azul-principal);
        }

        .campo-item input[type="checkbox"] {
            margin: 0;
            transform: scale(1.2);
            accent-color: var(--azul-principal);
        }

        .campo-item label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
            font-size: 0.9rem;
        }

        /* Botón centrado */
        .button-center {
            text-align: center;
            margin-top: 1.5rem;
        }

        /* Mensajes de alerta */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Footer */
       .footer {
          width: 100%; 
          background:rgb(0, 0, 0);
          color: white;
          text-align: center;
          padding: 15px 0;
          margin-top: 50px;
          font-size: 14px;
        }


        .campos-grupo {
            margin-right: 20px;
            margin-bottom: 15px;
        }

        #formulario-campos {
            margin-top: 2rem;
            background: var(--azul-claro);
        }

        #formulario-campos h2 {
            color: var(--azul-marino);
            text-align: center;
            margin-bottom: 1rem;
        }

        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .main-header h1 {
                font-size: 1.5rem; /* Reducido de 2rem a 1.5rem */
            }
            
            .main-header {
                padding: 0.75rem; /* Reducido de 1.5rem a 0.75rem */
            }
            
            .home-icon {
                position: static;
                display: block;
                margin: 1rem auto 0;
            }
            
            nav {
                padding: 1rem;
            }
            
            nav ul {
                flex-direction: column;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            table th, table td {
                padding: 0.8rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .campos-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-container {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .campos-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script>
        function mostrarFormularioCampos(tipo, label) {
            document.getElementById('formulario-campos').style.display = 'block';
            document.getElementById('tipo_editar').value = tipo;
            document.getElementById('titulo-campos').textContent = 'Configurar campos para: ' + label;
            
            // Desmarcar todos los checkboxes del formulario de edición
            const checkboxes = document.querySelectorAll('#formulario-campos input[name="campos[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Marcar los checkboxes asociados a este tipo si existen
            const tiposEquipo = <?php echo json_encode($tiposEquipo); ?>;
            if (tiposEquipo[tipo] && tiposEquipo[tipo].campos) {
                tiposEquipo[tipo].campos.forEach(campo => {
                    // Buscar específicamente en el formulario de edición
                    const checkbox = document.querySelector(`#formulario-campos input[name="campos[]"][value="${campo}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
            
            // Scroll al formulario
            document.getElementById('formulario-campos').scrollIntoView({ behavior: 'smooth' });
        }

        // Mostrar alertas al cargar la página si hay mensajes
        window.onload = function() {
            <?php if (!empty($mensaje)): ?>
                showAlert("✅ <?= addslashes($mensaje) ?>", 'success');
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                showAlert("⚠️ <?= addslashes($error) ?>", 'error');
            <?php endif; ?>
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Remover después de 5 segundos
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>
</head>
<body>
    <div class="main-header">
        <h1>Mapache Security</h1>
         <a href="../home.php" class="home-icon">
        <i class="fas fa-home"></i>
        </a>
    </div>
    
    <div class="container">
        <h1 class="page-title">Gestión de Tipos de Equipo</h1>
        
        <nav>
            <ul>
                <li><a href="../home.php">VOLVER AL INICIO</a></li>
                <li><a href="../equipos/crearEquipos.php">REGISTRAR EQUIPO</a></li>
            </ul>
        </nav>
        
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
                <?php if (empty($tiposEquipo)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: #666;">
                            No hay tipos de equipo configurados
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tiposEquipo as $valor => $datos): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($valor) ?></strong></td>
                            <td><?= htmlspecialchars($datos['label']) ?></td>
                            <td><span class="prefijo-badge"><?= htmlspecialchars($datos['prefijo']) ?></span></td>
                            <td>
                                <?php 
                                    if(isset($datos['campos']) && is_array($datos['campos'])) {
                                        $camposTexto = array_map(function($campo) use ($todosCampos) {
                                            return isset($todosCampos[$campo]) ? $todosCampos[$campo] : $campo;
                                        }, $datos['campos']);
                                        echo '<small>' . htmlspecialchars(implode(', ', $camposTexto)) . '</small>';
                                    } else {
                                        echo '<em style="color: #999;">(Sin campos configurados)</em>';
                                    }
                                ?>
                            </td>
                            <td class="acciones-col">
                                <button class="accion btn-configurar" onclick="mostrarFormularioCampos('<?= htmlspecialchars($valor) ?>', '<?= htmlspecialchars($datos['label']) ?>')">⚙️ Configurar</button>
                                
                                <?php if (!in_array($valor, ['pc', 'portatil', 'impresora', 'monitor', 'otro'])): ?>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este tipo de equipo?');">
                                        <button type="submit" name="eliminar_tipo" value="<?= htmlspecialchars($valor) ?>" class="accion btn-eliminar">🗑️ Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    
        <h2>Agregar Nuevo Tipo de Equipo</h2>
        <div class="form-container">
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group half">
                        <label for="valor">Valor (identificativo único):</label>
                        <input type="text" id="valor" name="valor" pattern="[a-z0-9_]+" 
                               title="Solo letras minúsculas, números o guiones bajos" required>
                        <small>Este valor se usa internamente. Solo use letras minúsculas, números y guiones bajos, sin espacios.</small>
                    </div>
                    
                    <div class="form-group half">
                        <label for="etiqueta">Etiqueta (nombre visible):</label>
                        <input type="text" id="etiqueta" name="etiqueta" required>
                        <small>Este es el nombre que se mostrará a los usuarios.</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="prefijo">Prefijo (para códigos de equipo):</label>
                    <input type="text" id="prefijo" name="prefijo" required maxlength="5">
                    <small>Código corto usado para generar el número de equipo (máx. 5 caracteres).</small>
                </div>
                    <div class="form-container" id="camposNuevoTipo">
                <h3>Seleccione los campos para este tipo de equipo:</h3>
                
                <div class="campos-grid">
                    <?php foreach ($gruposCampos as $grupo => $campos): ?>
                    <div class="campos-grupo">
                        <?php foreach ($campos as $campo): ?>
                        <div class="campo-item">
                            <input type="checkbox" name="campos[]" value="<?= htmlspecialchars($campo) ?>" id="nuevo_<?= htmlspecialchars($campo) ?>">
                            <label for="nuevo_<?= htmlspecialchars($campo) ?>"><?= htmlspecialchars($todosCampos[$campo]) ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

                <div class="button-center">
                    <button type="submit" name="agregar_tipo" class="btn-agregar">AGREGAR TIPO DE EQUIPO</button>
                </div>
            </form>
        </div>
    </div>


<div class="form-container" id="formulario-campos" style="display: none;">
    <h2 id="titulo-campos">Configurar campos para tipo</h2>
    <form method="post" action="">
        <input type="hidden" id="tipo_editar" name="tipo_editar" value="">
        
        <p>Seleccione los campos que se mostrarán para este tipo de equipo:</p>
        
        <div class="campos-grid">
            <?php foreach ($gruposCampos as $grupo => $campos): ?>
            <div class="campos-grupo">
                <?php foreach ($campos as $campo): ?>
                <div class="campo-item">
                    <input type="checkbox" name="campos[]" value="<?= htmlspecialchars($campo) ?>" id="edit_<?= htmlspecialchars($campo) ?>">
                    <label for="edit_<?= htmlspecialchars($campo) ?>"><?= htmlspecialchars($todosCampos[$campo]) ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="button-center">
            <button type="submit" name="guardar_campos" class="btn-agregar">GUARDAR CONFIGURACIÓN DE CAMPOS</button>
        </div>
    </form>
</div>


     <footer class="footer">
            @Copyright 2025
    </footer>
</body>
</html>