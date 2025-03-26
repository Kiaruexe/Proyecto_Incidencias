<?php
try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com:3306;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');
    $sqlUsuarios = "SELECT idUsuarios, usuario,
                           cpFiscal, provinciaFiscal, localidadFiscal, direccionFiscal,
                           cp1, provincia1, localidad1, direccion1,
                           cp2, provincia2, localidad2, direccion2
                    FROM Usuarios";
    $stmtUsr = $bd->prepare($sqlUsuarios);
    $stmtUsr->execute();
    $listaUsuarios = $stmtUsr->fetchAll();

} catch (PDOException $e) {
    echo "<p style='color:red;'>Error al cargar usuarios: " . $e->getMessage() . "</p>";
    $listaUsuarios = [];
}
if (isset($_POST['registrar'])) {
    $numEquipo          = $_POST['numEquipo']          ?? null;
    $fechaAlta          = date('Y-m-d'); 
    $descripcion        = $_POST['descripcion']        ?? null;
    $tipoMantenimiento  = $_POST['tipoMantenimiento']  ?? null;  
    $cp                 = $_POST['cp']                 ?? null;
    $provincia          = $_POST['provincia']          ?? null;
    $localidad          = $_POST['localidad']          ?? null;
    $direccion          = $_POST['direccion']          ?? null; 
    $idUsuario          = $_POST['idUsuario']          ?? null;
    if (
        !$numEquipo || !$descripcion || !$tipoMantenimiento || !$cp || !$provincia || !$localidad || !$direccion || !$idUsuario) {
        echo "<p style='color:red;'>Por favor, complete todos los campos.</p>";
    } else {
        try {
            $bdEquipos = new PDO('mysql:host=PMYSQL168.dns-servicio.com:3306;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');
            $bdEquipos->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sqlInsert = "INSERT INTO Equipos
                (numEquipo, fechaAlta, descripcion, tipoMantenimiento,
                 cp, provincia, localidad, direccion, idUsuario)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmtEq = $bdEquipos->prepare($sqlInsert);
            $stmtEq->execute([
                $numEquipo,
                $fechaAlta,
                $descripcion,
                $tipoMantenimiento,
                $cp,
                $provincia,
                $localidad,
                $direccion,
                $idUsuario
            ]);

            echo "<p style='color:green;'>Equipo registrado con éxito.</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Error al registrar equipo: " . $e->getMessage() . "</p>";
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
        document.getElementById('cp').value         = "";
        document.getElementById('provincia').value  = "";
        document.getElementById('localidad').value  = "";
        document.getElementById('direccion').value  = "";
        return;
      }

      const cpFiscal        = selectedOption.getAttribute('data-cpfiscal')        || "";
      const provinciaFiscal = selectedOption.getAttribute('data-provinciafiscal')|| "";
      const localidadFiscal = selectedOption.getAttribute('data-localidadfiscal')|| "";
      const direccionFiscal = selectedOption.getAttribute('data-direccionfiscal')|| "";

      const cp1        = selectedOption.getAttribute('data-cp1')        || "";
      const provincia1 = selectedOption.getAttribute('data-provincia1') || "";
      const localidad1 = selectedOption.getAttribute('data-localidad1') || "";
      const direccion1 = selectedOption.getAttribute('data-direccion1') || "";

      const cp2        = selectedOption.getAttribute('data-cp2')        || "";
      const provincia2 = selectedOption.getAttribute('data-provincia2') || "";
      const localidad2 = selectedOption.getAttribute('data-localidad2') || "";
      const direccion2 = selectedOption.getAttribute('data-direccion2') || "";

      let cpValue         = "";
      let provinciaValue  = "";
      let localidadValue  = "";
      let direccionValue  = "";

      if (dirType === "fiscal") {
        cpValue         = cpFiscal;
        provinciaValue  = provinciaFiscal;
        localidadValue  = localidadFiscal;
        direccionValue  = direccionFiscal;
      } else if (dirType === "1") {
        cpValue         = cp1;
        provinciaValue  = provincia1;
        localidadValue  = localidad1;
        direccionValue  = direccion1;
      } else if (dirType === "2") {
        cpValue         = cp2;
        provinciaValue  = provincia2;
        localidadValue  = localidad2;
        direccionValue  = direccion2;
      }

      document.getElementById('cp').value         = cpValue;
      document.getElementById('provincia').value  = provinciaValue;
      document.getElementById('localidad').value  = localidadValue;
      document.getElementById('direccion').value  = direccionValue;
    }
    </script>
</head>
<body>
<h1>Registrar nuevo equipo</h1>

<form method="post" action="">
    <label>numEquipo:</label><br>
    <input type="text" name="numEquipo" required><br><br>

    <label for="descripcion">Descripción:</label><br>
    <textarea name="descripcion" id="descripcion" required></textarea><br><br>

    <label>Tipo de Mantenimiento:</label><br>
    <select name="tipoMantenimiento" id="tipoMantenimiento" required>
        <option value="">-- Seleccione --</option>
        <option value="mantenimientoCompleto">Completo</option>
        <option value="mantenimientoManoObra">Mano de Obra</option>
    </select><br><br>

    <label>Seleccione el Usuario:</label><br>
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
    <br><br>

    <label>Tipo de dirección:</label><br>
    <select id="tipoDireccion" onchange="autocompletarDireccion()" required>
        <option value="fiscal">Fiscal</option>
        <option value="1">Dirección 1</option>
        <option value="2">Dirección 2</option>
    </select>
    <br><br>

    <label>CP:</label><br>
    <input type="number" id="cp" name="cp" required><br><br>

    <label>Provincia:</label><br>
    <input type="text" id="provincia" name="provincia" required><br><br>

    <label>Localidad:</label><br>
    <input type="text" id="localidad" name="localidad" required><br><br>

    <label>Dirección:</label><br>
    <input type="text" id="direccion" name="direccion" required><br><br>

    <input type="submit" name="registrar" value="Registrar Equipo">
</form>
</body>
</html>
