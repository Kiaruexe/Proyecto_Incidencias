<?php 
session_start();

if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../login.php");
    exit;
}

try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error de conexión: " . $e->getMessage() . "</p>";
    exit;
}
if (!isset($_GET['id'])) {
    try {
        $sql = "SELECT numEquipo FROM Equipos";
        $stmt = $bd->prepare($sql);
        $stmt->execute();
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
    </head>
    <body>
        <h1>Seleccionar Equipo a Modificar</h1>
        <form method="get" action="">
            <label for="id">Equipo:</label>
            <select name="id" id="id" required>
                <option value="">-- Seleccione un equipo --</option>
                <?php foreach ($equipos as $eq): ?>
                    <option value="<?= htmlspecialchars($eq['numEquipo']); ?>">
                        <?= htmlspecialchars($eq['numEquipo']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>
            <input type="submit" value="Modificar Equipo">
        </form>
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

if (isset($_POST['modificar'])) {
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
            idUsuario = ?
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
            $numEquipoModificar
        ]);
        echo "<p style='color:green;'>Equipo (" . htmlspecialchars($equipoData['numEquipo']) . ") modificado con éxito.</p>";
        $equipoData = array_merge($equipoData, [
            'tipoEquipo' => $tipoEquipo,
            'marca' => $marca,
            'modelo' => $modelo,
            'procesador' => $procesador,
            'memoria' => $memoria,
            'disco' => $disco,
            'tipo' => $tipo,
            'placa' => $placa,
            'serie' => $serie,
            'ubicacion' => $ubicacion,
            'costo' => $costo,
            'sistema' => $sistema,
            'pantalla' => $pantalla,
            'observaciones' => $observaciones,
            'tipoMantenimiento' => $tipoMantenimiento,
            'cp' => $cp,
            'provincia' => $provincia,
            'localidad' => $localidad,
            'direccion' => $direccion,
            'idUsuario' => $idUsuario
        ]);
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error al modificar equipo: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Modificar Equipo</title>
    <link rel="stylesheet" href="../css/style.css">
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
        const cpFiscal = selectedOption.getAttribute('data-cpfiscal') || "";
        const provinciaFiscal = selectedOption.getAttribute('data-provinciaFiscal') || "";
        const localidadFiscal = selectedOption.getAttribute('data-localidadFiscal') || "";
        const direccionFiscal = selectedOption.getAttribute('data-direccionFiscal') || "";
        const cp1 = selectedOption.getAttribute('data-cp1') || "";
        const provincia1 = selectedOption.getAttribute('data-provincia1') || "";
        const localidad1 = selectedOption.getAttribute('data-localidad1') || "";
        const direccion1 = selectedOption.getAttribute('data-direccion1') || "";
        const cp2 = selectedOption.getAttribute('data-cp2') || "";
        const provincia2 = selectedOption.getAttribute('data-provincia2') || "";
        const localidad2 = selectedOption.getAttribute('data-localidad2') || "";
        const direccion2 = selectedOption.getAttribute('data-direccion2') || "";
        document.getElementById('cp').value = cpFiscal;
        document.getElementById('provincia').value = provinciaFiscal;
        document.getElementById('localidad').value = localidadFiscal;
        document.getElementById('direccion').value = direccionFiscal;
      }

      function actualizarCampos() {
        const select = document.getElementsByName('tipoEquipo[]')[0];
        let values = Array.from(select.selectedOptions).map(opt => opt.value);
        const grupos = [
          'grupo-marca', 'grupo-modelo', 'grupo-serie', 'grupo-placa', 'grupo-procesador',
          'grupo-memoria', 'grupo-disco', 'grupo-pantalla', 'grupo-observaciones',
          'grupo-costo', 'grupo-sistema', 'grupo-ubicacion', 'grupo-tipo'
        ];
        grupos.forEach(function(id) {
          const elem = document.getElementById(id);
          if (elem) { elem.style.display = 'none'; }
        });
        let tipoEquipo = values.length ? values[0] : "";
        if (tipoEquipo === "pc") {
          ['grupo-marca','grupo-modelo','grupo-serie','grupo-placa',
           'grupo-procesador','grupo-memoria','grupo-disco','grupo-observaciones',
           'grupo-costo','grupo-sistema','grupo-ubicacion']
          .forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        } else if (tipoEquipo === "portatil") {
          ['grupo-marca','grupo-modelo','grupo-serie','grupo-procesador',
           'grupo-memoria','grupo-disco','grupo-pantalla','grupo-observaciones',
           'grupo-costo','grupo-sistema','grupo-ubicacion']
          .forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        } else if (tipoEquipo === "impresora") {
          ['grupo-marca','grupo-modelo','grupo-serie','grupo-observaciones',
           'grupo-ubicacion','grupo-costo']
          .forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        } else if (tipoEquipo === "monitor") {
          ['grupo-marca','grupo-modelo','grupo-serie','grupo-observaciones',
           'grupo-ubicacion','grupo-costo']
          .forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        } else if (tipoEquipo === "otro") {
          ['grupo-tipo','grupo-marca','grupo-modelo','grupo-serie',
           'grupo-observaciones','grupo-ubicacion','grupo-costo']
          .forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        } else if (["teclado","raton","router","sw","sai"].indexOf(tipoEquipo) > -1) {
          ['grupo-marca','grupo-modelo','grupo-serie','grupo-observaciones',
           'grupo-costo','grupo-ubicacion']
          .forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        }
      }
    </script>
</head>
<body onload="actualizarCampos()">
    <h1>Modificar Equipo</h1>
    <p><strong>Número de Equipo:</strong> <?= htmlspecialchars($equipoData['numEquipo']); ?></p>
    <p><strong>Fecha de Alta:</strong> <?= htmlspecialchars($equipoData['fechaAlta']); ?></p>
    <form method="post" action="">
        <label>Tipo de Equipo (selecciona al menos uno):</label><br/>
        <select name="tipoEquipo[]" multiple onchange="actualizarCampos()" required>
            <?php 
              $opciones = [
                  "pc" => "PC",
                  "portatil" => "Portátil",
                  "impresora" => "Impresora",
                  "monitor" => "Monitor",
                  "otro" => "Otro",
                  "teclado" => "Teclado",
                  "raton" => "Ratón",
                  "router" => "Router",
                  "sw" => "Switch",
                  "sai" => "SAI"
              ];
              foreach ($opciones as $valor => $texto):
                  $selected = ($valor === $equipoData['tipoEquipo']) ? 'selected' : '';
            ?>
            <option value="<?= htmlspecialchars($valor); ?>" <?= $selected; ?>>
                <?= htmlspecialchars($texto); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <br/><br/>

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

        <label>Tipo de Mantenimiento:</label><br/>
        <select name="tipoMantenimiento" required>
            <option value="">-- Seleccione --</option>
            <option value="mantenimientoCompleto" <?= $equipoData['tipoMantenimiento']=='mantenimientoCompleto' ? 'selected' : ''; ?>>Completo</option>
            <option value="mantenimientoManoObra" <?= $equipoData['tipoMantenimiento']=='mantenimientoManoObra' ? 'selected' : ''; ?>>Mano de Obra</option>
            <option value="mantenimientoFacturable" <?= $equipoData['tipoMantenimiento']=='mantenimientoFacturable' ? 'selected' : ''; ?>>Facturable</option>
            <option value="mantenimientoFacturable" <?= $equipoData['tipoMantenimiento']=='mantenimientoFacturable' ? 'selected' : ''; ?>>Preventivo</option>
        </select>
        <br/><br/>

        <label>Código Postal:</label><br/>
        <input type="number" id="cp" name="cp" value="<?= htmlspecialchars($equipoData['cp']); ?>" required><br/><br/>

        <label>Provincia:</label><br/>
        <input type="text" id="provincia" name="provincia" value="<?= htmlspecialchars($equipoData['provincia']); ?>" required><br/><br/>

        <label>Localidad:</label><br/>
        <input type="text" id="localidad" name="localidad" value="<?= htmlspecialchars($equipoData['localidad']); ?>" required><br/><br/>

        <label>Dirección:</label><br/>
        <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($equipoData['direccion']); ?>" required><br/><br/>

        <label>Seleccione el Usuario:</label><br/>
        <select id="idUsuario" name="idUsuario" onchange="autocompletarDireccion()" required>
            <option value="">-- Selecciona un usuario --</option>
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
        <select id="tipoDireccion" onchange="autocompletarDireccion()" required>
            <option value="fiscal">Fiscal</option>
            <option value="1">Dirección 1</option>
            <option value="2">Dirección 2</option>
        </select>
        <br/><br/>

        <input type="submit" name="modificar" value="Modificar Equipo">
    </form>
</body>
</html>
