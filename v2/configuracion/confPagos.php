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

// Función para generar valor automáticamente desde la etiqueta
function generarValor($etiqueta) {
    // Convertir a minúsculas
    $valor = strtolower($etiqueta);
    
    // Reemplazar espacios y caracteres especiales con guiones bajos
    $valor = preg_replace('/[^a-z0-9]+/', '_', $valor);
    
    // Eliminar guiones bajos al inicio y final
    $valor = trim($valor, '_');
    
    // Si queda vacío, usar un valor por defecto
    if (empty($valor)) {
        $valor = 'tipo_servicio';
    }
    
    return $valor;
}

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
    error_log("Usando tipos de servicio por defecto");
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
    $error = 'Error al cargar los tipos de servicio. Se ha inicializado con un conjunto vacío.';
}

// Procesar formulario de agregar nuevo tipo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_tipo'])) {
        $etiqueta = isset($_POST['etiqueta']) ? trim($_POST['etiqueta']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        
        // Generar valor automáticamente
        $valor = generarValor($etiqueta);
        
        // Validar entradas
        if (empty($etiqueta)) {
            $error = 'El campo Etiqueta es obligatorio.';
        } else {
            // Verificar si ya existe un tipo con el mismo valor generado
            $valorOriginal = $valor;
            $contador = 1;
            while (isset($tiposMantenimiento[$valor])) {
                $valor = $valorOriginal . '_' . $contador;
                $contador++;
            }
            
            // Agregar nuevo tipo
            $tiposMantenimiento[$valor] = [
                'label' => $etiqueta,
                'descripcion' => $descripcion
            ];
            
            // Guardar en archivo
            guardarTiposMantenimiento($tiposMantenimiento);
            
            $mensaje = 'Tipo de Servicio agregado correctamente.';
        }
    } elseif (isset($_POST['eliminar_tipo'])) {
        $valorEliminar = $_POST['eliminar_tipo'];
        
        // No permitir eliminar tipos básicos
        $tiposBasicos = ['mantenimientoCompleto', 'mantenimientoManoObra', 'mantenimientoFacturable', 'mantenimientoGarantia'];
        if (in_array($valorEliminar, $tiposBasicos)) {
            $error = 'No se pueden eliminar los tipos básicos de servicio.';
        } else {
            // Eliminar tipo
            unset($tiposMantenimiento[$valorEliminar]);
            
            // Guardar en archivo
            guardarTiposMantenimiento($tiposMantenimiento);
            
            $mensaje = 'Tipo de servicio eliminado correctamente.';
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
            
            $mensaje = 'Tipo de Servicio actualizado correctamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tipos de Servicio - Mapache Security</title>
    <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="…">
    <style>
        /* Reset y configuración general */
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }

        body {
          font-family: Arial, sans-serif;
          background: #f0f2f5;
          margin: 0;
          padding: 0;
        }

        /* Header azul con Mapache Security centrado */
        .header-mapache {
          background: #002255;
          color: white;
          padding: 15px 0;
          text-align: center;
          position: relative;
        }

        .header-mapache h1 {
          font-size: 32px;
          font-weight: bold;
          margin: 0;
        }

        /* Icono de casa en la esquina superior derecha */
        .home-icon {
        position: absolute;
        top: 15px;
        right: 20px;
        text-decoration: none;
        transition: background-color 0.3s ease;
        }
        .home-icon .fas {
        color: white;      /* relleno completamente blanco */
        font-size: 24px;
        }
        .home-icon:hover {
        background: rgba(255, 255, 255, 0.2);
        }

        /* Contenedor principal */
        .main-container {
          max-width: 1200px;
          margin: 0 auto;
          padding: 40px 20px;
          text-align: center;
        }

        /* Título de la página */
        .page-title {
          font-size: 28px;
          color: #2c3e50;
          margin-bottom: 30px;
          font-weight: bold;
        }

        /* Contenedor de botones */
        .button-container {
          margin-bottom: 40px;
        }

        /* Botón Volver al Inicio (naranja) */
        .btn-volver {
          background: #f39c12;
          color: white;
          padding: 12px 24px;
          border: none;
          border-radius: 25px;
          font-size: 14px;
          font-weight: bold;
          text-decoration: none;
          display: inline-block;
          margin: 0 10px;
          cursor: pointer;
          transition: background-color 0.3s ease;
        }

        .btn-volver:hover {
          background: #e67e22;
        }

        /* Botón Registrar Equipo (verde) */
        .btn-registrar {
          background: #27ae60;
          color: white;
          padding: 12px 24px;
          border: none;
          border-radius: 25px;
          font-size: 14px;
          font-weight: bold;
          text-decoration: none;
          display: inline-block;
          margin: 0 10px;
          cursor: pointer;
          transition: background-color 0.3s ease;
        }

        .btn-registrar:hover {
          background: #219150;
        }

        /* Tabla de tipos de servicio */
        .tabla-container {
          background: white;
          border-radius: 8px;
          overflow: hidden;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
          margin-bottom: 40px;
          border: 1px solid #ddd;
        }

        .tabla-tipos {
          width: 100%;
          border-collapse: collapse;
        }

        .tabla-tipos thead {
          background: #4a90e2;
          color: white;
        }

        .tabla-tipos th {
          padding: 15px;
          text-align: left;
          font-weight: bold;
          font-size: 16px;
        }

        .tabla-tipos td {
          padding: 15px;
          border-bottom: 1px solid #e0e0e0;
          background: white;
        }

        .tabla-tipos tbody tr:hover {
          background: #f8f9fa;
        }

        /* Botones de acción en la tabla */
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-right: 8px;
            font-weight: bold;
        }

        .edit-btn {
            background-color: #4a90e2;
            color: white;
        }

        .edit-btn:hover {
            background-color: #357abd;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        /* Sección Agregar Nuevo Tipo de Servicio - Estilo similar a la tabla */
        .agregar-section {
          background: #f7faff;
          border-radius: 15px;
          padding: 30px;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
          max-width: 800px;
          margin: 0 auto 30px;
          border: 1px solid #ddd;
        }

        .agregar-section h2 {
          font-size: 24px;
          color: #2c3e50;
          margin-bottom: 30px;
          font-weight: bold;
        }

        /* Formulario simplificado */
        .form-row {
          display: flex;
          gap: 20px;
          margin-bottom: 20px;
          align-items: flex-start;
        }

        .form-group {
          flex: 1;
        }

        .form-group label {
          display: block;
          font-weight: bold;
          color: #2c3e50;
          margin-bottom: 5px;
          font-size: 14px;
        }

        .form-group input[type="text"] {
          width: 100%;
          padding: 12px;
          border: 2px solid #ddd;
          border-radius: 6px;
          font-size: 14px;
          transition: border-color 0.3s ease;
        }

        .form-group input[type="text"]:focus {
          outline: none;
          border-color: #4a90e2;
        }

        .form-group small {
          display: block;
          margin-top: 5px;
          color: #666;
          font-size: 12px;
        }

        /* Campo de descripción */
        .form-group-full {
          margin-bottom: 20px;
        }

        .form-group-full label {
          display: block;
          font-weight: bold;
          color: #2c3e50;
          margin-bottom: 5px;
          text-align: left;
        }

        .form-group-full textarea {
          width: 100%;
          padding: 12px;
          border: 2px solid #ddd;
          border-radius: 6px;
          font-size: 14px;
          min-height: 80px;
          resize: vertical;
          font-family: Arial, sans-serif;
          transition: border-color 0.3s ease;
        }

        .form-group-full textarea:focus {
          outline: none;
          border-color: #4a90e2;
        }

        .form-group-full .help-text {
          font-size: 12px;
          color: #666;
          margin-top: 5px;
        }

        /* Botón Agregar Tipo de SERVICIO */
        .btn-agregar {
          background: #27ae60;
          color: white;
          padding: 12px 30px;
          border: none;
          border-radius: 25px;
          font-size: 14px;
          font-weight: bold;
          cursor: pointer;
          transition: background-color 0.3s ease;
          display: block;
          margin: 30px auto 0;
        }

        .btn-agregar:hover {
          background: #219150;
        }

        /* Formulario de edición */
        .edit-form {
            display: none;
            background: #f7faff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto 30px;
            border: 1px solid #ddd;
        }

        .edit-form h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: bold;
            text-align: center;
        }

        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 16px;
        }

        /* Footer */
        .footer {
          background:rgb(0, 0, 0);
          color: white;
          text-align: center;
          padding: 15px 0;
          margin-top: 50px;
          font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
          .form-row {
            flex-direction: column;
          }
          
          .header-mapache h1 {
            font-size: 24px;
          }
          
          .page-title {
            font-size: 22px;
          }
          
          .button-container {
            flex-direction: column;
            gap: 10px;
          }
          
          .btn-volver, .btn-registrar {
            margin: 5px 0;
          }

          .main-container {
            padding: 20px 15px;
          }

          .agregar-section, .edit-form {
            padding: 20px;
          }

          .tabla-tipos {
            font-size: 14px;
          }

          .tabla-tipos th, .tabla-tipos td {
            padding: 10px 8px;
          }
        }
    </style>
    <script>
        function mostrarFormularioEdicion(valor, etiqueta, descripcion) {
            document.getElementById('formulario-edicion').style.display = 'block';
            document.getElementById('tipo_editar').value = valor;
            document.getElementById('nueva_etiqueta').value = etiqueta;
            document.getElementById('nueva_descripcion').value = descripcion;
            document.getElementById('titulo-edicion').textContent = 'Editar tipo de Servicio: ' + etiqueta;
            
            // Scroll al formulario
            document.getElementById('formulario-edicion').scrollIntoView();
        }
    </script>
</head>
<body>
    <!-- Header -->
    <div class="header-mapache">
        <h1>Mapache Security</h1>
       <a href="../home.php" class="home-icon">
        <i class="fas fa-home"></i>
        </a>
    </div>

    <!-- Contenido principal -->
    <div class="main-container">
        <h1 class="page-title">Gestión de Tipos de Servicio</h1>
        
        <!-- Botones -->
        <div class="button-container">
            <a href="../home.php" class="btn-volver">VOLVER AL INICIO</a>
            <a href="../equipos/crearEquipos.php" class="btn-registrar">REGISTRAR EQUIPO</a>
        </div>

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

        <!-- Tabla -->
        <div class="tabla-container">
            <?php if (empty($tiposMantenimiento)): ?>
                <div class="empty-state">
                    <p>No hay tipos de Servicios definidos. Por favor, agregue uno a continuación.</p>
                </div>
            <?php else: ?>
                <table border="1" class="tabla-tipos">
                    <thead>
                        <tr>
                            <th>Etiqueta</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiposMantenimiento as $valor => $datos): ?>
                            <tr>
                                <td><?= htmlspecialchars($datos['label']) ?></td>
                                <td><?= htmlspecialchars($datos['descripcion'] ?? '') ?></td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="mostrarFormularioEdicion('<?= htmlspecialchars(addslashes($valor)) ?>', '<?= htmlspecialchars(addslashes($datos['label'])) ?>', '<?= htmlspecialchars(addslashes($datos['descripcion'] ?? '')) ?>')">Editar</button>
                                    
                                    <?php if (!in_array($valor, ['mantenimientoCompleto', 'mantenimientoManoObra', 'mantenimientoFacturable', 'mantenimientoGarantia'])): ?>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este tipo de Servicio?');">
                                            <button type="submit" name="eliminar_tipo" value="<?= htmlspecialchars($valor) ?>" class="action-btn delete-btn">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Formulario Agregar -->
        <div class="agregar-section">
            <h2>Agregar Nuevo Tipo de Servicio</h2>
            
            <form method="post" action="" id="agregar-form" onsubmit="return validarFormulario()">
                <div class="form-group">
                    <label for="etiqueta">Nombre del Servicio</label>
                    <input type="text" id="etiqueta" name="etiqueta" required>
                    <small>Este es el nombre que se mostrará a los clientes. El identificador se generará automáticamente.</small>
                </div>
                
                <div class="form-group-full">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" placeholder="Descripción detallada del tipo de servicio"></textarea>
                    <div class="help-text">Descripción detallada del tipo de Servicio.</div>
                </div>
                
                <button type="submit" name="agregar_tipo" class="btn-agregar">AGREGAR TIPO DE SERVICIO</button>
            </form>
        </div>

        <!-- Formulario de Edición -->
        <div id="formulario-edicion" class="edit-form">
            <h3 id="titulo-edicion">Editar tipo de Servicio</h3>
            
            <form method="post" action="" id="editar-form" onsubmit="return validarFormularioEdicion()">
                <input type="hidden" id="tipo_editar" name="tipo_editar" value="">
                
                <div class="form-group">
                    <label for="nueva_etiqueta">Etiqueta</label>
                    <input type="text" id="nueva_etiqueta" name="nueva_etiqueta" required>
                </div>
                
                <div class="form-group-full">
                    <label for="nueva_descripcion">Descripción</label>
                    <textarea id="nueva_descripcion" name="nueva_descripcion"></textarea>
                </div>
                <button type="submit" name="editar_tipo" class="btn-agregar">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer"><p>&copy;  <?php echo date('Y'); ?> Todos los derechos reservados.</p></div>

    <script>
        // Validación del formulario de agregar
        function validarFormulario() {
            var etiqueta = document.getElementById('etiqueta').value.trim();
            
            if (etiqueta === '') {
                alert('El campo Nombre del Servicio es obligatorio.');
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