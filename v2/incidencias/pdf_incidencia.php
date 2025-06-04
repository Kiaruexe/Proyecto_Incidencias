<?php
require_once(__DIR__ . '/../libs/fpdf/fpdf.php');
session_start();

if (!isset($_SESSION["idUsuario"])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID de incidencia no válido.";
    exit;
}

$id = intval($_GET['id']);

try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
        'Mapapli',
        '9R%d5cf62'
    );
    $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $bd->prepare("
        SELECT i.*, u.usuario, e.ubicacion 
        FROM Incidencias i
        LEFT JOIN Usuarios u ON i.idUsuario = u.idUsuarios
        LEFT JOIN Equipos e ON i.numEquipo = e.numEquipo
        WHERE i.idIncidencias = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        echo "No se encontró la incidencia.";
        exit;
    }

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',12);
        $this->SetTextColor(37, 115, 250);
        $this->Cell(0,10,mb_convert_encoding('Incidencia Detallada - Mapache Security', 'ISO-8859-1','UTF-8'),0,1,'C');
        $this->Ln(3);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(150);
        $this->Cell(0,10,mb_convert_encoding('Página '.$this->PageNo(), 'ISO-8859-1','UTF-8'),0,0,'C');
    }
}

$pdf = new PDF('P');
$pdf->AddPage();
$pdf->SetFont('Arial','',9);
$pdf->SetDrawColor(180,180,255);

// Cabecera
$pdf->SetFillColor(37, 115, 250);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,8,mb_convert_encoding("Incidencia #" . $row['idIncidencias'], 'ISO-8859-1','UTF-8'),1,1,'C',true);

$pdf->SetTextColor(0);
$pdf->SetFont('Arial','',9);

$campos = [
    'Fecha' => substr($row['fecha'], 0, 10),
    'Cliente' => $row['usuario'],
    'Nombre' => $row['nombre'],
    'Número' => $row['numero'],
    'Correo' => $row['correo'],
    'Ubicación' => $row['ubicacion'] ?: 'N/A',
    'Incidencia' => $row['incidencia'],
    'Observaciones' => $row['observaciones'],
    'Tiempo Desplazamiento (min)' => $row['TDesplazamiento'] ?: 'pendiente',
    'Tiempo Intervención (min)' => $row['TIntervencion'] ?: 'pendiente',
    'Técnico' => $row['tecnicoAsignado'],
    'Estado' => $row['estado'] ? 'Cerrado' : 'Abierto',
    'Nº Equipo' => $row['numEquipo'],
];

foreach ($campos as $key => $value) {
    $pdf->Cell(55,7,mb_convert_encoding($key . ':', 'ISO-8859-1','UTF-8'),1,0,'R');
    $pdf->Cell(0,7,mb_convert_encoding($value, 'ISO-8859-1','UTF-8'),1,1);
}

// Agregar firma si existe
if (!empty($row['firma']) && str_starts_with($row['firma'], 'data:image')) {
    $base64 = explode(',', $row['firma'])[1];
    $binary = base64_decode($base64);

    // Guardar temporalmente
    $tmpFile = tempnam(sys_get_temp_dir(), 'firma_') . '.png';
    file_put_contents($tmpFile, $binary);

    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,7,mb_convert_encoding('Firma del cliente:', 'ISO-8859-1','UTF-8'),0,1);

    // Insertar imagen de firma
    $pdf->Image($tmpFile, $pdf->GetX(), $pdf->GetY(), 60, 25); // Ajusta tamaño según necesites

    // Borrar archivo temporal al final del script
    register_shutdown_function(function () use ($tmpFile) {
        if (file_exists($tmpFile)) unlink($tmpFile);
    });
} else {
    $pdf->Ln(5);
    $pdf->Cell(0,7,mb_convert_encoding('Firma del cliente: (Sin firmar)', 'ISO-8859-1','UTF-8'),0,1);
}

$pdf->Output('I', 'Incidencia_'.$row['idIncidencias'].'.pdf');
?>
