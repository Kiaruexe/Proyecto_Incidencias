<?php
ob_clean();
require_once(__DIR__ . '/../libs/fpdf/fpdf.php');
session_start();

if (!isset($_SESSION["idUsuario"])) {
  header("Location: ../login.php");
  exit;
}

function fmtMantenimiento($clave)
{
  $map = [
    'mantenimientoCompleto' => 'Completo',
    'mantenimientoManoObra' => 'Mano de Obra',
    'mantenimientoFacturable' => 'Facturable',
    'mantenimientoGarantia' => 'Garantía'
  ];
  return $map[$clave] ?? $clave;
}

function fmtTipoEquipo($clave)
{
  $map = [
    'pc' => 'PC',
    'portatil' => 'Portátil',
    'impresora' => 'Impresora',
    'monitor' => 'Monitor',
    'otro' => 'Otro',
    'teclado' => 'Teclado',
    'raton' => 'Ratón',
    'router' => 'Router',
    'sw' => 'Switch',
    'sai' => 'SAI'
  ];
  return $map[$clave] ?? $clave;
}

try {
  $bd = new PDO(
    'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
    'Mapapli',
    '9R%d5cf62'
  );
  $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Obtener el permiso del usuario
  $u = $bd->prepare("SELECT permiso FROM Usuarios WHERE idUsuarios = ?");
  $u->execute([$_SESSION['idUsuario']]);
  $usuario = $u->fetch();
  $permiso = strtolower($usuario['permiso'] ?? '');
} catch (PDOException $e) {
  exit("Error de conexión a la base de datos.");
}

// Recoger filtros
$fTipo = $_GET['tipoEquipo'] ?? 'todos';
$fMaint = $_GET['tipoMantenimiento'] ?? '';
$fCP = $_GET['cp'] ?? '';
$fProv = $_GET['provincia'] ?? '';
$fLocal = $_GET['localidad'] ?? '';
$fUser = $_GET['usuario'] ?? '';
$orderBy = $_GET['ordenarPor'] ?? 'numEquipo';
$orderDir = (($_GET['orden'] ?? 'ASC') === 'DESC') ? 'DESC' : 'ASC';

$validCols = ['numEquipo', 'fechaAlta', 'fechaCompra', 'usuario', 'costo'];
if (!in_array($orderBy, $validCols)) {
  $orderBy = 'numEquipo';
}

$orderBySql = ($orderBy === 'usuario') ? 'u.usuario' : "e.$orderBy";

$sql = "SELECT e.*, u.usuario
        FROM Equipos e
        LEFT JOIN Usuarios u ON e.idUsuario = u.idUsuarios
        WHERE 1=1";

$params = [];

// Aplicar filtros si existen
if ($fTipo !== 'todos') {
  $sql .= " AND e.tipoEquipo = ?";
  $params[] = $fTipo;
}
if ($fMaint !== '') {
  $sql .= " AND e.tipoMantenimiento = ?";
  $params[] = $fMaint;
}
foreach (['cp' => $fCP, 'provincia' => $fProv, 'localidad' => $fLocal] as $campo => $valor) {
  if ($valor !== '') {
    $sql .= " AND e.$campo LIKE ?";
    $params[] = "%$valor%";
  }
}
if ($fUser !== '') {
  $sql .= " AND u.usuario LIKE ?";
  $params[] = "%$fUser%";
}

// Filtro por cliente si aplica
if ($permiso === 'cliente') {
  $sql .= " AND e.idUsuario = ?";
  $params[] = $_SESSION['idUsuario'];
}

$sql .= " ORDER BY $orderBySql $orderDir";

$stmt = $bd->prepare($sql);
$stmt->execute($params);

class PDF extends FPDF {
  function Header() {
    $this->SetFont('Arial', 'B', 12);
    $this->SetTextColor(37, 115, 250);
    $this->Cell(0, 10, mb_convert_encoding('Listado de Equipos - Mapache Security', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    $this->Ln(3);
  }

  function Footer() {
    $this->SetY(-15);
    $this->SetFont('Arial', 'I', 8);
    $this->SetTextColor(150);
    $this->Cell(0, 10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $this->PageNo(), 0, 0, 'C');
  }
}

$pdf = new PDF('P'); // Vertical
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);

while ($r = $stmt->fetch()) {
  $pdf->SetFillColor(37, 115, 250);
  $pdf->SetTextColor(255);
  $pdf->SetFont('Arial', 'B', 10);
  $pdf->Cell(0, 8, mb_convert_encoding("Equipo #" . $r['numEquipo'], 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

  $pdf->SetFont('Arial', '', 8);
  $pdf->SetTextColor(0);

  $campos = [
    'Fecha Alta' => $r['fechaAlta'],
    'Fecha Compra' => $r['fechaCompra'],
    'Tipo' => fmtTipoEquipo($r['tipoEquipo']),
    'Mantenimiento' => fmtMantenimiento($r['tipoMantenimiento']),
    'CP' => $r['cp'],
    'Provincia' => $r['provincia'],
    'Localidad' => $r['localidad'],
    'Cliente' => $r['usuario'],
    'Marca' => $r['marca'],
    'Modelo' => $r['modelo'],
    'Serie' => $r['serie'],
    'Observaciones' => $r['observaciones'],
    'Costo (euros)' => $r['costo']
  ];

  foreach ($campos as $key => $val) {
    $pdf->Cell(50, 6, mb_convert_encoding("$key:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
    $pdf->Cell(0, 6, mb_convert_encoding($val ?: '-', 'ISO-8859-1', 'UTF-8'), 1, 1);
  }

  $pdf->Ln(4); // espacio entre fichas
}

$pdf->Output('I', 'Listado_Equipos.pdf');
