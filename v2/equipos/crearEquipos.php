<?php
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
    $listaUsuarios = $stmtUsr->fetchAll();
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error al cargar usuarios: " . $e->getMessage() . "</p>";
    $listaUsuarios = [];
}

if (isset($_POST['registrar'])) {
    $fechaAlta         = date('Y-m-d'); 
    $tipoEquipoArray   = $_POST['tipoEquipo'] ?? [];
    if(empty($tipoEquipoArray)){
        echo "<p style='color:red;'>Por favor, seleccione al menos un tipo de equipo.</p>";
    } else {
        // Usamos el primer valor seleccionado para el INSERT en "tipoEquipo"
        $tipoEquipoPrim = $tipoEquipoArray[0];
    
        $marca             = $_POST['marca']             ?? null;
        $modelo            = $_POST['modelo']            ?? null;
        $procesador        = $_POST['procesador']        ?? null;
        $memoria           = $_POST['memoria']           ?? null;
        $disco             = $_POST['disco']             ?? null;
        $tipo              = $_POST['tipo']              ?? null; 
        $placa             = $_POST['placa']             ?? null;
        $serie             = $_POST['serie']             ?? null;
        $ubicacion         = $_POST['ubicacion']         ?? null;
        $costo             = $_POST['costo']             ?? null;
        $sistema           = $_POST['sistema']           ?? null;
        $pantalla          = $_POST['pantalla']          ?? null;
        $observaciones     = $_POST['observaciones']     ?? null;
        $tipoMantenimiento = $_POST['tipoMantenimiento'] ?? null;
        $cp                = $_POST['cp']                ?? null;
        $provincia         = $_POST['provincia']         ?? null;
        $localidad         = $_POST['localidad']         ?? null;
        $direccion         = $_POST['direccion']         ?? null;
        $idUsuario         = $_POST['idUsuario']         ?? null;
        
        if (
            !$tipoEquipoPrim || !$tipoMantenimiento ||
            !$cp || !$provincia || !$localidad || !$direccion || !$idUsuario
        ) {
            echo "<p style='color:red;'>Por favor, complete los campos obligatorios.</p>";
        } else {
            try {
                $bdEquipos = new PDO('mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8', 'Mapapli', '9R%d5cf62');
                // Se obtiene el contador genérico (total de registros + 1)
                $stmtCount = $bdEquipos->query("SELECT COUNT(*) AS total FROM Equipos");
                $row = $stmtCount->fetch(PDO::FETCH_ASSOC);
                $counter = $row['total'] + 1;
                // Se determina el prefijo según el primer tipo seleccionado
                switch ($tipoEquipoPrim) {
                    case 'pc':       $prefix = "PC";   break;
                    case 'portatil': $prefix = "port"; break;
                    case 'impresora':$prefix = "imp";  break;
                    case 'monitor':  $prefix = "mon";  break;
                    case 'otro':     $prefix = "ot";   break;
                    case 'teclado':  $prefix = "tecl"; break;
                    case 'raton':    $prefix = "rat";  break;
                    case 'router':   $prefix = "rou";  break;
                    case 'sw':       $prefix = "sw";   break;
                    case 'sai':      $prefix = "sai";  break;
                    default:         $prefix = "EQ";   break;
                }
                // Se genera el código con 3 dígitos (ej. port001)
                $numEquipo = $prefix . sprintf("%03d", $counter);

                $sqlInsert = "INSERT INTO Equipos (
                    numEquipo, fechaAlta, tipoEquipo, marca, modelo, procesador, memoria, disco, tipo,
                    placa, serie, ubicacion, costo, sistema, pantalla, observaciones, tipoMantenimiento,
                    cp, provincia, localidad, direccion, idUsuario
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtEq = $bdEquipos->prepare($sqlInsert);
                $stmtEq->execute([
                    $numEquipo,
                    $fechaAlta,
                    $tipoEquipoPrim, // Se guarda solo el primer valor seleccionado
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
                    $idUsuario
                ]);
                echo "<p style='color:green;'>Equipo ($numEquipo) registrado con éxito.</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red;'>Error al registrar equipo: " . $e->getMessage() . "</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registro de Equipos</title>
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
        const provinciaFiscal = selectedOption.getAttribute('data-provinciafiscal') || "";
        const localidadFiscal = selectedOption.getAttribute('data-localidadfiscal') || "";
        const direccionFiscal = selectedOption.getAttribute('data-direccionfiscal') || "";
        const cp1 = selectedOption.getAttribute('data-cp1') || "";
        const provincia1 = selectedOption.getAttribute('data-provincia1') || "";
        const localidad1 = selectedOption.getAttribute('data-localidad1') || "";
        const direccion1 = selectedOption.getAttribute('data-direccion1') || "";
        const cp2 = selectedOption.getAttribute('data-cp2') || "";
        const provincia2 = selectedOption.getAttribute('data-provincia2') || "";
        const localidad2 = selectedOption.getAttribute('data-localidad2') || "";
        const direccion2 = selectedOption.getAttribute('data-direccion2') || "";
        let cpValue = "", provinciaValue = "", localidadValue = "", direccionValue = "";
        if (dirType === "fiscal") {
          cpValue = cpFiscal;
          provinciaValue = provinciaFiscal;
          localidadValue = localidadFiscal;
          direccionValue = direccionFiscal;
        } else if (dirType === "1") {
          cpValue = cp1;
          provinciaValue = provincia1;
          localidadValue = localidad1;
          direccionValue = direccion1;
        } else if (dirType === "2") {
          cpValue = cp2;
          provinciaValue = provincia2;
          localidadValue = localidad2;
          direccionValue = direccion2;
        }
        document.getElementById('cp').value = cpValue;
        document.getElementById('provincia').value = provinciaValue;
        document.getElementById('localidad').value = localidadValue;
        document.getElementById('direccion').value = direccionValue;
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
          ['grupo-marca', 'grupo-modelo', 'grupo-serie', 'grupo-placa',
           'grupo-procesador', 'grupo-memoria', 'grupo-disco', 'grupo-observaciones',
           'grupo-costo', 'grupo-sistema', 'grupo-ubicacion'].forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        } else if (tipoEquipo === "portatil") {
          ['grupo-marca', 'grupo-modelo', 'grupo-serie', 'grupo-procesador',
           'grupo-memoria', 'grupo-disco', 'grupo-pantalla', 'grupo-observaciones',
           'grupo-costo', 'grupo-sistema', 'grupo-ubicacion'].forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        } else if (tipoEquipo === "impresora") {
          ['grupo-marca', 'grupo-modelo', 'grupo-serie',
           'grupo-observaciones', 'grupo-ubicacion', 'grupo-costo'].forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        } else if (tipoEquipo === "monitor") {
          ['grupo-marca', 'grupo-modelo', 'grupo-serie',
           'grupo-observaciones', 'grupo-ubicacion', 'grupo-costo'].forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        } else if (tipoEquipo === "otro") {
          ['grupo-tipo', 'grupo-marca', 'grupo-modelo', 'grupo-serie',
           'grupo-observaciones', 'grupo-ubicacion', 'grupo-costo'].forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        } else if (["teclado", "raton", "router", "sw", "sai"].indexOf(tipoEquipo) > -1) {
          ['grupo-marca', 'grupo-modelo', 'grupo-serie',
           'grupo-observaciones', 'grupo-costo', 'grupo-ubicacion'].forEach(function(id) {
            document.getElementById(id).style.display = 'block';
          });
        }
      }
    </script>
</head>
<body onload="actualizarCampos()">
<h1>Registrar nuevo equipo</h1>
<form method="post" action="">
    <label>Tipo de Equipo (puedes seleccionar varias):</label><br/>
    <select name="tipoEquipo[]" multiple onchange="actualizarCampos()" required>
        <option value="">-- Seleccione --</option>
        <option value="pc">PC</option>
        <option value="portatil">Portátil</option>
        <option value="impresora">Impresora</option>
        <option value="monitor">Monitor</option>
        <option value="otro">Otro</option>
        <option value="teclado">Teclado</option>
        <option value="raton">Ratón</option>
        <option value="router">Router</option>
        <option value="sw">Switch</option>
        <option value="sai">SAI</option>
    </select><br/><br/>

    <div id="grupo-marca" style="display:none;">
      <label>Marca:</label><br/>
      <input type="text" name="marca"><br/><br/>
    </div>

    <div id="grupo-modelo" style="display:none;">
      <label>Modelo:</label><br/>
      <input type="text" name="modelo"><br/><br/>
    </div>

    <div id="grupo-serie" style="display:none;">
      <label>Serie:</label><br/>
      <input type="text" name="serie"><br/><br/>
    </div>

    <div id="grupo-placa" style="display:none;">
      <label>Placa:</label><br/>
      <input type="text" name="placa"><br/><br/>
    </div>

    <div id="grupo-procesador" style="display:none;">
      <label>Procesador:</label><br/>
      <input type="text" name="procesador"><br/><br/>
    </div>

    <div id="grupo-memoria" style="display:none;">
      <label>Memoria:</label><br/>
      <input type="text" name="memoria"><br/><br/>
    </div>

    <div id="grupo-disco" style="display:none;">
      <label>Disco:</label><br/>
      <input type="text" name="disco"><br/><br/>
    </div>

    <div id="grupo-pantalla" style="display:none;">
      <label>Pantalla:</label><br/>
      <input type="text" name="pantalla"><br/><br/>
    </div>

    <div id="grupo-observaciones" style="display:none;">
      <label>Observaciones:</label><br/>
      <textarea name="observaciones"></textarea><br/><br/>
    </div>

    <div id="grupo-costo" style="display:none;">
      <label>Costo:</label><br/>
      <input type="number" step="0.01" name="costo"><br/><br/>
    </div>

    <div id="grupo-sistema" style="display:none;">
      <label>Sistema:</label><br/>
      <input type="text" name="sistema"><br/><br/>
    </div>

    <div id="grupo-ubicacion" style="display:none;">
      <label>Ubicación:</label><br/>
      <input type="text" name="ubicacion"><br/><br/>
    </div>

    <div id="grupo-tipo" style="display:none;">
      <label>Tipo (Especifique):</label><br/>
      <input type="text" name="tipo"><br/><br/>
    </div>

    <label>Tipo de Mantenimiento:</label><br/>
    <select name="tipoMantenimiento" required>
        <option value="">-- Seleccione --</option>
        <option value="mantenimientoCompleto">Completo</option>
        <option value="mantenimientoManoObra">Mano de Obra</option>
    </select><br/><br/>

    <label>Seleccione el Usuario:</label><br/>
    <select id="idUsuario" name="idUsuario" onchange="autocompletarDireccion()" required>
        <option value="">-- Selecciona un usuario --</option>
        <?php foreach ($listaUsuarios as $usr): ?>
            <option 
              value="<?php echo $usr['idUsuarios']; ?>"
              data-cpfiscal="<?php echo $usr['cpFiscal']; ?>"
              data-provinciafiscal="<?php echo $usr['provinciaFiscal']; ?>"
              data-localidadfiscal="<?php echo $usr['localidadFiscal']; ?>"
              data-direccionfiscal="<?php echo $usr['direccionFiscal']; ?>"
              data-cp1="<?php echo $usr['cp1']; ?>"
              data-provincia1="<?php echo $usr['provincia1']; ?>"
              data-localidad1="<?php echo $usr['localidad1']; ?>"
              data-direccion1="<?php echo $usr['direccion1']; ?>"
              data-cp2="<?php echo $usr['cp2']; ?>"
              data-provincia2="<?php echo $usr['provincia2']; ?>"
              data-localidad2="<?php echo $usr['localidad2']; ?>"
              data-direccion2="<?php echo $usr['direccion2']; ?>"
            >
              <?php echo $usr['usuario'] . " (ID: " . $usr['idUsuarios'] . ")"; ?>
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

    <label>CP:</label><br/>
    <input type="number" id="cp" name="cp" required><br/><br/>

    <label>Provincia:</label><br/>
    <input type="text" id="provincia" name="provincia" required><br/><br/>

    <label>Localidad:</label><br/>
    <input type="text" id="localidad" name="localidad" required><br/><br/>

    <label>Dirección:</label><br/>
    <input type="text" id="direccion" name="direccion" required><br/><br/>

    <input type="submit" name="registrar" value="Registrar Equipo">
</form>
</body>
</html>
