<?php
try {
    $bd = new PDO('mysql:host=PMYSQL168.dns-servicio.com:3306;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');
    $sqlUsuarios = "SELECT idUsuarios, usuario,
                           cpFiscal, provinciaFiscal, localidadFiscal,
                           cp1, provincia1, localidad1,
                           cp2, provincia2, localidad2
                    FROM Usuarios";
    $stmtUsr = $bd->prepare($sqlUsuarios);
    $stmtUsr->execute();
    $listaUsuarios = $stmtUsr->fetchAll();

} catch (PDOException $e) {
    echo "<p style='color:red;'>Error al cargar usuarios: " . $e->getMessage() . "</p>";
    $listaUsuarios = [];
}

if (isset($_POST['registrar'])) {
    $numEquipo   = $_POST['numEquipo']   ?? null;
    $fechaAlta   = date('Y-m-d');
    $descripcion = $_POST['descripcion'] ?? null;
    $cp          = $_POST['cp']          ?? null;
    $provincia   = $_POST['provincia']   ?? null;
    $localidad   = $_POST['localidad']   ?? null;
    $idUsuario   = $_POST['idUsuario']   ?? null;

    if (!$numEquipo || !$descripcion || !$cp || !$provincia || !$localidad || !$idUsuario) {
        echo "<p style='color:red;'>Por favor, complete todos los campos.</p>";
    } else {
        try {
            $bdEquipos = new PDO('mysql:host=PMYSQL168.dns-servicio.com:3306;dbname=9981336_aplimapa', 'Mapapli', '9R%d5cf62');
            $sqlInsert = "INSERT INTO Equipos (numEquipo, fechaAlta, descripcion, cp, provincia, localidad, idUsuario)
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtEq = $bdEquipos->prepare($sqlInsert);
            $stmtEq->execute([$numEquipo, $fechaAlta, $descripcion, $cp, $provincia, $localidad, $idUsuario]);

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
        document.getElementById('cp').value = "";
        document.getElementById('provincia').value = "";
        document.getElementById('localidad').value = "";
        return;
      }
      const cpFiscal         = selectedOption.getAttribute('data-cpfiscal');
      const provinciaFiscal  = selectedOption.getAttribute('data-provinciafiscal');
      const localidadFiscal  = selectedOption.getAttribute('data-localidadfiscal');

      const cp1             = selectedOption.getAttribute('data-cp1');
      const provincia1      = selectedOption.getAttribute('data-provincia1');
      const localidad1      = selectedOption.getAttribute('data-localidad1');

      const cp2             = selectedOption.getAttribute('data-cp2');
      const provincia2      = selectedOption.getAttribute('data-provincia2');
      const localidad2      = selectedOption.getAttribute('data-localidad2');

      let cpValue = "";
      let provinciaValue = "";
      let localidadValue = "";

      if (dirType === "fiscal") {
        cpValue        = cpFiscal        || "";
        provinciaValue = provinciaFiscal || "";
        localidadValue = localidadFiscal || "";
      } else if (dirType === "1") {
        cpValue        = cp1        || "";
        provinciaValue = provincia1 || "";
        localidadValue = localidad1 || "";
      } else if (dirType === "2") {
        cpValue        = cp2        || "";
        provinciaValue = provincia2 || "";
        localidadValue = localidad2 || "";
      }

      document.getElementById('cp').value         = cpValue;
      document.getElementById('provincia').value  = provinciaValue;
      document.getElementById('localidad').value  = localidadValue;
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

    <label>Seleccione el Usuario:</label><br>
    <select id="idUsuario" name="idUsuario" onchange="autocompletarDireccion()" required>
        <option value="">-- Selecciona un usuario --</option>
        <?php foreach ($listaUsuarios as $usr): ?>
            <option 
              value="<?php echo $usr['idUsuarios']; ?>"
              data-cpfiscal="<?php echo $usr['cpFiscal']; ?>"
              data-provinciafiscal="<?php echo $usr['provinciaFiscal']; ?>"
              data-localidadfiscal="<?php echo $usr['localidadFiscal']; ?>"

              data-cp1="<?php echo $usr['cp1']; ?>"
              data-provincia1="<?php echo $usr['provincia1']; ?>"
              data-localidad1="<?php echo $usr['localidad1']; ?>"

              data-cp2="<?php echo $usr['cp2']; ?>"
              data-provincia2="<?php echo $usr['provincia2']; ?>"
              data-localidad2="<?php echo $usr['localidad2']; ?>"
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

    <input type="submit" name="registrar" value="Registrar Equipo">
</form>

</body>
</html>
