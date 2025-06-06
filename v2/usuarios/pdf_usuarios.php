<?php
require_once(__DIR__ . '/../libs/fpdf/fpdf.php');
session_start();

if (!isset($_SESSION["idUsuario"])) {
    header("Location: ../login.php");
    exit;
}

try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
        'Mapapli',
        '9R%d5cf62'
    );
    $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $u = $bd->prepare("SELECT permiso FROM Usuarios WHERE idUsuarios = ?");
    $u->execute([$_SESSION['idUsuario']]);
    $usuario = $u->fetch();
    $permiso = strtolower($usuario['permiso'] ?? '');

    $filtroPermiso = $_GET['permiso'] ?? 'todos';
    if ($filtroPermiso !== 'todos') {
        $query = $bd->prepare("SELECT * FROM Usuarios WHERE permiso = ?");
        $query->execute([$filtroPermiso]);
    } else {
        $query = $bd->query("SELECT * FROM Usuarios");
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(37, 115, 250);
        $this->Cell(0, 10, mb_convert_encoding('Listado de Usuarios - Mapache Security', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(3);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150);
        $this->Cell(0, 10, mb_convert_encoding('Página ' . $this->PageNo(), 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    }
}

$pdf = new PDF('P');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);
$pdf->SetDrawColor(180, 180, 255);

while ($r = $query->fetch()) {
    $pdf->SetFillColor(37, 115, 250);
    $pdf->SetTextColor(255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 8, mb_convert_encoding("Usuario ID #" . $r['idUsuarios'], 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

    $pdf->SetTextColor(0);
    $pdf->SetFont('Arial', '', 8);

    $campos = [
        'Usuario' => $r['usuario'],
        'Correo' => $r['correo'],
        'Permiso' => $r['permiso'],
    ];

    if (strtolower($r['permiso']) === 'cliente') {
        $campos = array_merge($campos, [
            'CP Fiscal' => $r['cpFiscal'],
            'Provincia Fiscal' => $r['provinciaFiscal'],
            'Localidad Fiscal' => $r['localidadFiscal'],
            'Dirección Fiscal' => $r['direccionFiscal'],
            'CP1' => $r['cp1'],
            'Provincia1' => $r['provincia1'],
            'Localidad1' => $r['localidad1'],
            'Dirección1' => $r['direccion1'],
            'CP2' => $r['cp2'],
            'Provincia2' => $r['provincia2'],
            'Localidad2' => $r['localidad2'],
            'Dirección2' => $r['direccion2'],
        ]);
    }

    foreach ($campos as $key => $value) {
        $pdf->Cell(50, 6, mb_convert_encoding($key . ":", 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
        $pdf->Cell(0, 6, mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8'), 1, 1);
    }

    $pdf->Ln(4);
}

$pdf->Output('I', 'Listado_Usuarios.pdf');
?>
