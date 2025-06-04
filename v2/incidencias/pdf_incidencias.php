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

    // Obtener el permiso del usuario
    $u = $bd->prepare("SELECT permiso FROM Usuarios WHERE idUsuarios = ?");
    $u->execute([$_SESSION['idUsuario']]);
    $usuario = $u->fetch();
    $permiso = strtolower($usuario['permiso'] ?? '');
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

$filterField = $_GET['filter_field'] ?? '';
$filterValue = trim($_GET['filter_value'] ?? '');

$allowedFields = [
    'numIncidencia' => 'Incidencias.numIncidencia',
    'fecha' => 'Incidencias.fecha',
    'nombre' => 'Incidencias.nombre',
    'numero' => 'Incidencias.numero',
    'ubicacion' => 'Equipos.ubicacion',
    'correo' => 'Incidencias.correo',
    'incidencia' => 'Incidencias.incidencia',
    'observaciones' => 'Incidencias.observaciones',
    'TDesplazamiento' => 'Incidencias.TDesplazamiento',
    'TIntervencion' => 'Incidencias.TIntervencion',
    'tecnicoAsignado' => 'Incidencias.tecnicoAsignado',
    'usuario' => 'Usuarios.usuario',
    'numEquipo' => 'Incidencias.numEquipo',
    'estado' => 'Incidencias.estado',
    'firma' => 'Incidencias.firma'
];

$params = [];
$filterClause = '';
if (isset($allowedFields[$filterField]) && $filterValue !== '') {
    if ($filterField === 'firma') {
        $fv = strtolower($filterValue);
        if (in_array($fv, ['true', 'firmado'])) {
            $filterClause = " AND Incidencias.firma IS NOT NULL AND Incidencias.firma <> ''";
        } elseif (in_array($fv, ['false', 'sin firmar'])) {
            $filterClause = " AND (Incidencias.firma IS NULL OR Incidencias.firma = '')";
        }
    } elseif ($filterField === 'estado') {
        if ($filterValue === 'cerrado') {
            $filterClause = " AND Incidencias.estado = ?";
            $params[] = 1;
        } elseif ($filterValue === 'abierto') {
            $filterClause = " AND Incidencias.estado = ?";
            $params[] = 0;
        }
    } else {
        $filterClause = " AND {$allowedFields[$filterField]} = ?";
        $params[] = $filterValue;
    }
}

$sql = "SELECT Incidencias.*, Usuarios.usuario, Equipos.ubicacion
        FROM Incidencias
        LEFT JOIN Usuarios ON Incidencias.idUsuario = Usuarios.idUsuarios
        LEFT JOIN Equipos ON Incidencias.numEquipo = Equipos.numEquipo
        WHERE 1=1 $filterClause";

if ($permiso === 'cliente') {
    $sql .= " AND Incidencias.idUsuario = ?";
    $params[] = $_SESSION['idUsuario'];
}

$stmt = $bd->prepare($sql);
$stmt->execute($params);

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(37, 115, 250);
        $this->Cell(0, 10, mb_convert_encoding('Listado de Incidencias - Mapache Security', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(3);
    }

    function Footer()
    {
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

$num = 1;
while ($r = $stmt->fetch()) {
    $pdf->SetFillColor(37, 115, 250);
    $pdf->SetTextColor(255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 8, mb_convert_encoding("Incidencia #" . $r['numIncidencia'], 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

    $pdf->SetTextColor(0);
    $pdf->SetFont('Arial', '', 8);

    $campos = [
        'Fecha' => substr($r['fecha'], 0, 10),
        'Cliente' => $r['usuario'],
        'Nombre' => $r['nombre'],
        'Número' => $r['numero'],
        'Correo' => $r['correo'],
        'Ubicación' => $r['ubicacion'] ?: 'N/A',
        'Incidencia' => $r['incidencia'],
        'Observaciones' => $r['observaciones'],
        'Tiempo Desplazamiento (min)' => ($r['TDesplazamiento'] ?: 'pendiente'),
        'Tiempo Intervención (min)' => ($r['TIntervencion'] ?: 'pendiente'),
        'Técnico' => $r['tecnicoAsignado'],
        'Estado' => $r['estado'] ? 'Cerrado' : 'Abierto',
        'Nº Equipo' => $r['numEquipo'],
        'Firma' => $r['firma'] ? 'Firmado' : 'Sin firmar'
    ];

    foreach ($campos as $key => $value) {
        $pdf->Cell(50, 6, mb_convert_encoding($key . ":", 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
        $pdf->Cell(0, 6, mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8'), 1, 1);
    }

    $pdf->Ln(4); 
}

$pdf->Output('I', 'Listado_Incidencias.pdf');
?>
