<?php
session_start();
// Mostrar todos los errores (en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Variables para mensajes
$mensaje = '';
$tipoMensaje = '';
$redirigir = false; // Nueva variable para controlar redirección

try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
    $sqlUsuarios = "SELECT idUsuarios, usuario,
                           cpFiscal, provinciaFiscal, localidadFiscal, direccionFiscal,
                           cp1, provincia1, localidad1, direccion1,
                           cp2, provincia2, localidad2, direccion2
                    FROM Usuarios
                    WHERE permiso = 'cliente'";
    $stmtUsr = $bd->prepare($sqlUsuarios);
    $stmtUsr->execute();
    $listaUsuarios = $stmtUsr->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al conectar con la base de datos de usuarios: " . htmlspecialchars($e->getMessage());
    $tipoMensaje = 'error';
}

function leerTiposEquipo() {
  // Función sin cambios
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
    // Función sin cambios
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

$tiposEquipo = leerTiposEquipo();
$tiposMantenimiento = leerTiposMantenimiento();

$errors = [];
$success = false;
$data = [
    'tipoEquipo'        => [],
    'marca'             => '',
    'modelo'            => '',
    'procesador'        => '',
    'memoria'           => '',
    'disco'             => '',
    'tipo'              => '',
    'placa'             => '',
    'serie'             => '',
    'ubicacion'         => '',
    'costo'             => '',
    'sistema'           => '',
    'pantalla'          => '',
    'observaciones'     => '',
    'tipoMantenimiento' => '',
    'fechaCompra'       => '',
    'cp'                => '',
    'provincia'         => '',
    'localidad'         => '',
    'direccion'         => '',
    'idUsuario'         => '',
    'tipoDireccion'     => 'fiscal',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recogida de datos
    foreach ($data as $key => &$val) {
        if (isset($_POST[$key])) {
            $val = is_string($val) ? trim($_POST[$key]) : $_POST[$key];
        }
    }
    unset($val);

    // Validaciones básicas
    if (empty($data['tipoEquipo'])) {
        $errors['tipoEquipo'] = 'Seleccione al menos un tipo de equipo.';
    }
    if (empty($data['tipoMantenimiento'])) {
        $errors['tipoMantenimiento'] = 'Seleccione tipo de mantenimiento.';
    }
    if (empty($data['cp']) || strlen($data['cp']) < 5) {
        $errors['cp'] = 'El código postal debe tener al menos 5 dígitos.';
    }
    if (empty($data['provincia'])) {
        $errors['provincia'] = 'Provincia requerida.';
    }
    if (empty($data['localidad'])) {
        $errors['localidad'] = 'Localidad requerida.';
    }
    if (empty($data['direccion'])) {
        $errors['direccion'] = 'Dirección requerida.';
    }
    if (empty($data['idUsuario'])) {
        $errors['idUsuario'] = 'Seleccione un usuario.';
    }
    if (empty($data['fechaCompra'])) {
        $errors['fechaCompra'] = 'Fecha de compra requerida.';
    }
    
    // Validación de garantía en servidor
    if (empty($errors) && $data['tipoMantenimiento'] === 'mantenimientoGarantia') {
        $fc = new DateTime($data['fechaCompra']);
        $hoy = new DateTime();
        $diff = $hoy->diff($fc);
        if ($diff->y >= 3) {
            $errors['tipoMantenimiento'] = 'La garantía sólo es válida hasta 3 años desde la fecha de compra.';
        }
    }

    // Insert en DB si no hay errores
    if (empty($errors)) {
        try {
            $bdEquipos = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
            // Verificar si la columna fechaCompra existe en la tabla
            $checkColumn = $bdEquipos->query("SHOW COLUMNS FROM Equipos LIKE 'fechaCompra'");
            $columnExists = $checkColumn->rowCount() > 0;
            
            // Contador genérico
            $stmtCount = $bdEquipos->query("SELECT COUNT(*) AS total FROM Equipos");
            $row = $stmtCount->fetch();
            $counter = $row['total'] + 1;
            
            // Prefijo
            $tipoEquipoPrim = $data['tipoEquipo'][0];
            $prefix = "EQ"; // Valor por defecto
            if (isset($tiposEquipo[$tipoEquipoPrim]) && !empty($tiposEquipo[$tipoEquipoPrim]['prefijo'])) {
                $prefix = $tiposEquipo[$tipoEquipoPrim]['prefijo'];
            }
            $numEquipo = $prefix . sprintf("%03d", $counter);
            
            // Preparamos los campos y valores según si existe la columna fechaCompra
            if ($columnExists) {
                $sqlInsert = "INSERT INTO Equipos (
                        numEquipo, fechaAlta, fechaCompra, tipoEquipo, marca, modelo, procesador, memoria, disco, tipo,
                        placa, serie, ubicacion, costo, sistema, pantalla, observaciones, tipoMantenimiento,
                        cp, provincia, localidad, direccion, idUsuario
                    ) VALUES (
                        ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?
                    )";
                $params = [
                    $numEquipo,
                    $data['fechaCompra'] ?: null,
                    $tipoEquipoPrim,
                    $data['marca'] ?: null,
                    $data['modelo'] ?: null,
                    $data['procesador'] ?: null,
                    $data['memoria'] ?: null, 
                    $data['disco'] ?: null,
                    $data['tipo'] ?: null,
                    $data['placa'] ?: null,
                    $data['serie'] ?: null,
                    $data['ubicacion'] ?: null,
                    $data['costo'] ?: null,
                    $data['sistema'] ?: null,
                    $data['pantalla'] ?: null,
                    $data['observaciones'] ?: null,
                    $data['tipoMantenimiento'],
                    $data['cp'],
                    $data['provincia'],
                    $data['localidad'],
                    $data['direccion'],
                    $data['idUsuario']
                ];
            } else {
                // Sin la columna fechaCompra
                $sqlInsert = "INSERT INTO Equipos (
                        numEquipo, fechaAlta, tipoEquipo, marca, modelo, procesador, memoria, disco, tipo,
                        placa, serie, ubicacion, costo, sistema, pantalla, observaciones, tipoMantenimiento,
                        cp, provincia, localidad, direccion, idUsuario
                    ) VALUES (
                        ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?
                    )";
                $params = [
                    $numEquipo,
                    $tipoEquipoPrim,
                    $data['marca'] ?: null,
                    $data['modelo'] ?: null,
                    $data['procesador'] ?: null,
                    $data['memoria'] ?: null, 
                    $data['disco'] ?: null,
                    $data['tipo'] ?: null,
                    $data['placa'] ?: null,
                    $data['serie'] ?: null,
                    $data['ubicacion'] ?: null,
                    $data['costo'] ?: null,
                    $data['sistema'] ?: null,
                    $data['pantalla'] ?: null,
                    $data['observaciones'] ?: null,
                    $data['tipoMantenimiento'],
                    $data['cp'],
                    $data['provincia'],
                    $data['localidad'],
                    $data['direccion'],
                    $data['idUsuario']
                ];
            }
            
            $stmtEq = $bdEquipos->prepare($sqlInsert);
            $stmtEq->execute($params);
            
            // Indicar éxito
            $success = true;
            $mensaje = "Equipo $numEquipo registrado correctamente.";
            $tipoMensaje = 'success';
            
            // Establecer que hay que redirigir después de mostrar la alerta
            $redirigir = true;
            
            // También almacenamos los datos en sesión como respaldo si JavaScript está desactivado
            $_SESSION['mensaje_equipo'] = $mensaje;
            $_SESSION['tipo_mensaje_equipo'] = $tipoMensaje;
            
            // Ya no redirigimos inmediatamente - se hará por JavaScript después de mostrar la alerta
        } catch (PDOException $e) {
            $mensaje = 'Error al registrar equipo: ' . htmlspecialchars($e->getMessage());
            $tipoMensaje = 'error';
        }
    } else {
        // Si hay errores de validación, preparar mensaje
        $mensaje = "Hay errores en el formulario. Por favor revise los campos marcados.";
        $tipoMensaje = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Equipos</title>
  <link rel="icon" href="../multimedia/logo-mapache.png" type="image/png">
  <link rel="stylesheet" href="../css/style.css">
  <script>
    // Función para mostrar alertas
    function mostrarAlerta(mensaje, tipo, redirigir = false) {
      if (!mensaje) return;
      
      // Crear alerta según el tipo
      if (tipo === 'success') {
        alert('✅ ' + mensaje);
      } else if (tipo === 'error') {
        alert('⚠️ ' + mensaje);
      } else {
        alert(mensaje);
      }
      
      // Si hay que redirigir, hacerlo después de que se cierre la alerta
      if (redirigir) {
        window.location.href = '../home.php';
      }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      // Mostrar alerta si hay mensaje y redirigir si es necesario
      <?php if ($mensaje): ?>
      mostrarAlerta(
        "<?= addslashes($mensaje) ?>", 
        "<?= $tipoMensaje ?>",
        <?= $redirigir ? 'true' : 'false' ?>
      );
      <?php endif; ?>
      
      const tipoPago = document.getElementById('tipoMantenimiento');
      const fechaCompra = document.getElementById('fechaCompra');
      // Al iniciar, deshabilitar select de tipo de pago
      tipoPago.disabled = true;

      // Función para actualizar estado del select y la opción garantía
      function actualizarTipoPago() {
        const valFecha = fechaCompra.value;
        if (!valFecha) {
          tipoPago.disabled = true;
          return;
        }
        tipoPago.disabled = false;
        // Calcular diferencia en años
        const fc = new Date(valFecha);
        const hoy = new Date();
        const diffAnios = hoy.getFullYear() - fc.getFullYear() - 
            ( (hoy.getMonth() < fc.getMonth() || 
              (hoy.getMonth() === fc.getMonth() && hoy.getDate() < fc.getDate())) ? 1 : 0 );
        // Deshabilitar opción garantía si >=3 años
        const optGarantia = tipoPago.querySelector('option[value="mantenimientoGarantia"]');
        if (optGarantia) {
          optGarantia.disabled = (diffAnios >= 3);
          // Si estaba seleccionada y ahora es inválida, reiniciar selección
          if (optGarantia.disabled && tipoPago.value === 'mantenimientoGarantia') {
            tipoPago.value = '';
            alert('Han pasado más de 3 años desde la compra: la garantía ya no está disponible.');
          }
        }
      }

      fechaCompra.addEventListener('change', actualizarTipoPago);
      // Si ya había valor al recargar (en edición), aplicar chequeo
      actualizarTipoPago();
    });
    
    function validarCP(input) {
      // Validar que el CP tenga al menos 5 dígitos
      if(input.value.length < 5) {
        input.setCustomValidity('El código postal debe tener al menos 5 dígitos');
      } else {
        input.setCustomValidity('');
      }
    }
    
    function autocompletarDireccion() {
      const selectUser = document.getElementById('idUsuario');
      const selectedOption = selectUser.options[selectUser.selectedIndex];
      const dirType = document.getElementById('tipoDireccion').value;
      
      // Limpiar campos primero
      document.getElementById('cp').value = '';
      document.getElementById('provincia').value = '';
      document.getElementById('localidad').value = '';
      document.getElementById('direccion').value = '';
      
      // Si no hay usuario seleccionado, no hacer nada más
      if (!selectedOption.value) return;
      
      // Definir qué atributos usar según el tipo de dirección
      let cpAttr, provinciaAttr, localidadAttr, direccionAttr;
      
      if (dirType === 'fiscal') {
        cpAttr = selectedOption.getAttribute('data-cpfiscal');
        provinciaAttr = selectedOption.getAttribute('data-provinciafiscal');
        localidadAttr = selectedOption.getAttribute('data-localidadfiscal');
        direccionAttr = selectedOption.getAttribute('data-direccionfiscal');
      } else if (dirType === '1') {
        cpAttr = selectedOption.getAttribute('data-cp1');
        provinciaAttr = selectedOption.getAttribute('data-provincia1');
        localidadAttr = selectedOption.getAttribute('data-localidad1');
        direccionAttr = selectedOption.getAttribute('data-direccion1');
      } else if (dirType === '2') {
        cpAttr = selectedOption.getAttribute('data-cp2');
        provinciaAttr = selectedOption.getAttribute('data-provincia2');
        localidadAttr = selectedOption.getAttribute('data-localidad2');
        direccionAttr = selectedOption.getAttribute('data-direccion2');
      }
      
      // Establecer valores
      document.getElementById('cp').value = cpAttr || '';
      document.getElementById('provincia').value = provinciaAttr || '';
      document.getElementById('localidad').value = localidadAttr || '';
      document.getElementById('direccion').value = direccionAttr || '';
      
      // Validar CP después de autocompletar
      validarCP(document.getElementById('cp'));
    }
    
    function actualizarCampos() {
      const select = document.getElementsByName('tipoEquipo[]')[0];
      const values = Array.from(select.selectedOptions).map(opt=>opt.value);
      const grupos = [
        'grupo-marca','grupo-modelo','grupo-serie','grupo-placa','grupo-procesador',
        'grupo-memoria','grupo-disco','grupo-pantalla','grupo-observaciones',
        'grupo-costo','grupo-sistema','grupo-ubicacion','grupo-tipo'
      ];
      grupos.forEach(id=>document.getElementById(id).style.display='none');
      
      const tipoEquipo = values[0] || '';
      
      // Cargar tipos de equipo desde PHP
      const tiposEquipo = <?= json_encode($tiposEquipo) ?>;
      
      // Obtener los campos para el tipo seleccionado
      let camposAMostrar = [];
      
      if (tiposEquipo[tipoEquipo] && tiposEquipo[tipoEquipo].campos) {
        // Si tiene campos definidos
        camposAMostrar = tiposEquipo[tipoEquipo].campos;
      } else {
        // Si no tiene campos definidos, no mostrar nada
        camposAMostrar = [];
      }
      
      // Mostrar los campos correspondientes
      camposAMostrar.forEach(campo => {
        const elemento = document.getElementById('grupo-'+campo);
        if (elemento) elemento.style.display = 'block';
      });
    }
    
    // Función que se ejecuta al cargar la página
    function inicializar() {
      actualizarCampos();
      // Si hay un usuario ya seleccionado, autocompletar sus direcciones
      if (document.getElementById('idUsuario').value) {
        autocompletarDireccion();
      }
    }
    
    // Validar formulario antes de enviar
    function validarFormulario() {
      const cp = document.getElementById('cp');
      if(cp.value.length < 5) {
        alert('El código postal debe tener al menos 5 dígitos');
        cp.focus();
        return false;
      }
      return true;
    }
  </script>
</head>
<body onload="inicializar()">
  <h1>Registrar nuevo equipo</h1>

  <form method="post" action="" onsubmit="return validarFormulario()">
    <label>Tipo de Equipo:</label><br/>
    <select name="tipoEquipo[]" multiple onchange="actualizarCampos()" required>
      <option value="">-- Seleccione --</option>
      <?php foreach ($tiposEquipo as $val => $info): ?>
        <option value="<?= htmlspecialchars($val) ?>"
          <?= in_array($val, $data['tipoEquipo']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($info['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['tipoEquipo'])): ?><br/><span style="color:red;"><?= $errors['tipoEquipo'] ?></span><?php endif; ?>
    <br/><br/>

    <div id="grupo-marca" style="display:none;">
      <label>Marca:</label><br/>
      <input type="text" name="marca" value="<?= htmlspecialchars($data['marca']) ?>"><br/><br/>
    </div>
    <div id="grupo-modelo" style="display:none;">
      <label>Modelo:</label><br/>
      <input type="text" name="modelo" value="<?= htmlspecialchars($data['modelo']) ?>"><br/><br/>
    </div>
    <div id="grupo-serie" style="display:none;">
      <label>Serie:</label><br/>
      <input type="text" name="serie" value="<?= htmlspecialchars($data['serie']) ?>"><br/><br/>
    </div>
    <div id="grupo-placa" style="display:none;">
      <label>Placa:</label><br/>
      <input type="text" name="placa" value="<?= htmlspecialchars($data['placa']) ?>"><br/><br/>
    </div>
    <div id="grupo-procesador" style="display:none;">
      <label>Procesador:</label><br/>
      <input type="text" name="procesador" value="<?= htmlspecialchars($data['procesador']) ?>"><br/><br/>
    </div>
    <div id="grupo-memoria" style="display:none;">
      <label>Memoria:</label><br/>
      <input type="text" name="memoria" value="<?= htmlspecialchars($data['memoria']) ?>"><br/><br/>
    </div>
    <div id="grupo-disco" style="display:none;">
      <label>Disco:</label><br/>
      <input type="text" name="disco" value="<?= htmlspecialchars($data['disco']) ?>"><br/><br/>
    </div>
    <div id="grupo-pantalla" style="display:none;">
      <label>Pantalla:</label><br/>
      <input type="text" name="pantalla" value="<?= htmlspecialchars($data['pantalla']) ?>"><br/><br/>
    </div>
    <div id="grupo-observaciones" style="display:none;">
      <label>Observaciones:</label><br/>
      <textarea name="observaciones"><?= htmlspecialchars($data['observaciones']) ?></textarea><br/><br/>
    </div>
    <div id="grupo-costo" style="display:none;">
      <label>Costo:</label><br/>
      <input type="number" step="0.01" name="costo" value="<?= htmlspecialchars($data['costo']) ?>"><br/><br/>
    </div>
    <div id="grupo-sistema" style="display:none;">
      <label>Sistema:</label><br/>
      <input type="text" name="sistema" value="<?= htmlspecialchars($data['sistema']) ?>"><br/><br/>
    </div>
    <div id="grupo-ubicacion" style="display:none;">
      <label>Ubicación:</label><br/>
      <input type="text" name="ubicacion" value="<?= htmlspecialchars($data['ubicacion']) ?>"><br/><br/>
    </div>
    <div id="grupo-tipo" style="display:none;">
      <label>Tipo (Especifique):</label><br/>
      <input type="text" name="tipo" value="<?= htmlspecialchars($data['tipo']) ?>"><br/><br/>
    </div>

    <label>Fecha de Compra:</label><br/>
    <input type="date" id="fechaCompra" name="fechaCompra"
         value="<?= htmlspecialchars($data['fechaCompra']) ?>" required>
    <?php if (!empty($errors['fechaCompra'])): ?>
      <br/><span style="color:red;"><?= $errors['fechaCompra'] ?></span>
    <?php endif; ?>
    <br/><br/>

    <label>Tipo de Pago:</label><br/>
    <select id="tipoMantenimiento" name="tipoMantenimiento" required>
        <option value="">-- Seleccione --</option>
        <?php foreach ($tiposMantenimiento as $val => $info): ?>
          <option value="<?= htmlspecialchars($val) ?>" 
                  <?= $data['tipoMantenimiento'] === $val ? 'selected' : '' ?>>
            <?= htmlspecialchars($info['label']) ?>
          </option>
        <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['tipoMantenimiento'])): ?>
      <br/><span style="color:red;"><?= $errors['tipoMantenimiento'] ?></span>
    <?php endif; ?>
    <br/><br/>

    <label>Seleccione un Cliente:</label><br/>
    <select id="idUsuario" name="idUsuario" onchange="autocompletarDireccion()" required>
        <option value="">-- Selecciona un Cliente --</option>
        <?php foreach ($listaUsuarios as $usr): ?>
            <option 
              value="<?= htmlspecialchars($usr['idUsuarios'] ?? '') ?>"
              data-cpfiscal="<?= htmlspecialchars($usr['cpFiscal'] ?? '') ?>"
              data-provinciafiscal="<?= htmlspecialchars($usr['provinciaFiscal'] ?? '') ?>"
              data-localidadfiscal="<?= htmlspecialchars($usr['localidadFiscal'] ?? '') ?>"
              data-direccionfiscal="<?= htmlspecialchars($usr['direccionFiscal'] ?? '') ?>"
              data-cp1="<?= htmlspecialchars($usr['cp1'] ?? '') ?>"
              data-provincia1="<?= htmlspecialchars($usr['provincia1'] ?? '') ?>"
              data-localidad1="<?= htmlspecialchars($usr['localidad1'] ?? '') ?>"
              data-direccion1="<?= htmlspecialchars($usr['direccion1'] ?? '') ?>"
              data-cp2="<?= htmlspecialchars($usr['cp2'] ?? '') ?>"
              data-provincia2="<?= htmlspecialchars($usr['provincia2'] ?? '') ?>"
              data-localidad2="<?= htmlspecialchars($usr['localidad2'] ?? '') ?>"
              data-direccion2="<?= htmlspecialchars($usr['direccion2'] ?? '') ?>"
              <?= $data['idUsuario']==$usr['idUsuarios'] ? 'selected' : '' ?>
            >
              <?= htmlspecialchars($usr['usuario'] . " (ID: " . $usr['idUsuarios'] . ")") ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['idUsuario'])): ?><br/><span style="color:red;"><?= $errors['idUsuario'] ?></span><?php endif; ?>
    <br/><br/>

    <label>Tipo de dirección:</label><br/>
    <select id="tipoDireccion" name="tipoDireccion" onchange="autocompletarDireccion()" required>
        <option value="fiscal" <?= $data['tipoDireccion']==='fiscal'?'selected':'' ?>>Fiscal</option>
        <option value="1" <?= $data['tipoDireccion']==='1'?'selected':'' ?>>Dirección 1</option>
        <option value="2" <?= $data['tipoDireccion']==='2'?'selected':'' ?>>Dirección 2</option>
    </select>
    <br/><br/>

    <label>CP:</label><br/>
    <input type="text" id="cp" name="cp" 
           value="<?= htmlspecialchars($data['cp']) ?>" 
           required 
           minlength="5" 
           oninput="validarCP(this)" 
           onblur="validarCP(this)">
    <?php if (!empty($errors['cp'])): ?><br/><span style="color:red;"><?= $errors['cp'] ?></span><?php endif; ?>
    <br/><br/>

    <label>Provincia:</label><br/>
    <input type="text" id="provincia" name="provincia" value="<?= htmlspecialchars($data['provincia']) ?>" required>
    <?php if (!empty($errors['provincia'])): ?><br/><span style="color:red;"><?= $errors['provincia'] ?></span><?php endif; ?>
    <br/><br/>
    <label>Localidad:</label><br/>
    <input type="text" id="localidad" name="localidad" value="<?= htmlspecialchars($data['localidad']) ?>" required>
    <?php if (!empty($errors['localidad'])): ?><br/><span style="color:red;"><?= $errors['localidad'] ?></span><?php endif; ?>
    <br/><br/>

    <label>Dirección:</label><br/>
    <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($data['direccion']) ?>" required>
    <?php if (!empty($errors['direccion'])): ?><br/><span style="color:red;"><?= $errors['direccion'] ?></span><?php endif; ?>
    <br/><br/>

    <input type="submit" name="registrar" value="Registrar Equipo">
</form>
</body>
</html>