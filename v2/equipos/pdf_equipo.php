<?php
// Evitar warnings y errores que interfieran con el PDF
error_reporting(0);
ini_set('display_errors', 0);

session_start();
if (!isset($_SESSION["idUsuario"])) {
    header("Location: ../login.php");
    exit;
}

// Verificar que se recibió el ID del equipo
if (!isset($_GET['numEquipo']) || empty($_GET['numEquipo'])) {
    header("Location: javascript:history.back()");
    exit;
}

$numEquipo = $_GET['numEquipo'];

// Incluir la librería FPDF
require_once(__DIR__ . '/../libs/fpdf/fpdf.php');

try {
    $bd = new PDO(
        'mysql:host=PMYSQL168.dns-servicio.com;dbname=9981336_aplimapa;charset=utf8',
        'Mapapli',
        '9R%d5cf62'
    );
    $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta corregida - usando los nombres correctos de las columnas
    $stmt = $bd->prepare("
        SELECT e.*, u.usuario, u.correo as correoUsuario
        FROM Equipos e
        LEFT JOIN Usuarios u ON e.idUsuario = u.idUsuarios
        WHERE e.numEquipo = ?
    ");
    $stmt->execute([$numEquipo]);
    $equipo = $stmt->fetch();

    if (!$equipo) {
        header("Location: javascript:history.back()");
        exit;
    }

} catch (PDOException $e) {
    // No mostrar errores que interfieran con el PDF
    header("Location: javascript:history.back()");
    exit;
}

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',16);
        $this->SetTextColor(0, 34, 90);
        $this->Cell(0,12,mb_convert_encoding('Ficha Técnica del Equipo', 'ISO-8859-1','UTF-8'),0,1,'C');
        $this->SetFont('Arial','B',12);
        $this->SetTextColor(37, 115, 250);
        $this->Cell(0,8,mb_convert_encoding('Mapache Security', 'ISO-8859-1','UTF-8'),0,1,'C');
        $this->Ln(5);
        
        // Línea separadora
        $this->SetDrawColor(37, 115, 250);
        $this->Line(10, 30, 200, 30);
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(100);
        $this->SetDrawColor(37, 115, 250);
        $this->Line(10, -25, 200, -25);
        $this->Ln(2);
        $this->Cell(0,10,mb_convert_encoding('Página '.$this->PageNo().' - Generado el '.date('d/m/Y H:i'), 'ISO-8859-1','UTF-8'),0,0,'C');
        $this->Ln(5);
        $this->SetFont('Arial','I',7);
        $this->Cell(0,5,mb_convert_encoding('© '.date('Y').' Mapache Security - Documento confidencial', 'ISO-8859-1','UTF-8'),0,0,'C');
    }
    
    function SectionTitle($title) {
        $this->Ln(6);
        $this->SetFillColor(37, 115, 250);
        $this->SetTextColor(255);
        $this->SetFont('Arial','B',11);
        $this->Cell(0,8,mb_convert_encoding($title, 'ISO-8859-1','UTF-8'),1,1,'L',true);
        $this->SetTextColor(0);
        $this->SetFont('Arial','',9);
    }
    
    function DataRow($label, $value, $fullWidth = false) {
        $value = $value ?: 'No especificado';
        if ($fullWidth) {
            $this->Cell(0,7,mb_convert_encoding($label . ': ' . $value, 'ISO-8859-1','UTF-8'),1,1,'L');
        } else {
            $this->SetFont('Arial','B',9);
            $this->Cell(60,7,mb_convert_encoding($label . ':', 'ISO-8859-1','UTF-8'),1,0,'R');
            $this->SetFont('Arial','',9);
            $this->Cell(0,7,mb_convert_encoding($value, 'ISO-8859-1','UTF-8'),1,1,'L');
        }
    }
}

// Función para verificar si existe una columna en el array
function verificarCampo($array, $campo) {
    return isset($array[$campo]) ? $array[$campo] : null;
}

// Función para formatear fechas
function formatearFecha($fecha) {
    if (empty($fecha) || $fecha === '0000-00-00') return 'No especificada';
    return date('d/m/Y', strtotime($fecha));
}

// Función para formatear estado
function formatearEstado($estado) {
    return $estado == 1 ? 'Activo' : 'Inactivo';
}

// Función para formatear costo
function formatearCosto($costo) {
    if (empty($costo) || $costo == 0) return 'No especificado';
    return '€ ' . number_format($costo, 2, ',', '.');
}

$pdf = new PDF('P');
$pdf->AddPage();
$pdf->SetDrawColor(200,200,200);

// Título principal del equipo
$pdf->SetFillColor(249, 171, 37);
$pdf->SetTextColor(0);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,12,mb_convert_encoding("EQUIPO #" . $equipo['numEquipo'], 'ISO-8859-1','UTF-8'),1,1,'C',true);

// Información básica del equipo
$pdf->SectionTitle('INFORMACIÓN GENERAL');

$pdf->DataRow('Número de Equipo', $equipo['numEquipo']);
$pdf->DataRow('Fecha de Alta', formatearFecha($equipo['fechaAlta']));

// Solo mostrar estado si la columna existe
if (isset($equipo['estado'])) {
    $pdf->DataRow('Estado', formatearEstado($equipo['estado']));
}

// Solo mostrar campos que tienen valor
if (!empty($equipo['tipoMantenimiento'])) {
    $pdf->DataRow('Tipo de Mantenimiento', $equipo['tipoMantenimiento']);
}

// Información técnica del equipo
$pdf->SectionTitle('ESPECIFICACIONES TÉCNICAS');

$campos_tecnicos = [
    'Tipo de Equipo' => verificarCampo($equipo, 'tipoEquipo'),
    'Marca' => verificarCampo($equipo, 'marca'),
    'Modelo' => verificarCampo($equipo, 'modelo'),
    'Número de Serie' => verificarCampo($equipo, 'serie'),
    'Procesador' => verificarCampo($equipo, 'procesador'),
    'Memoria' => verificarCampo($equipo, 'memoria'),
    'Disco' => verificarCampo($equipo, 'disco'),
    'Pantalla' => verificarCampo($equipo, 'pantalla'),
    'Sistema' => verificarCampo($equipo, 'sistema'),
    'Placa' => verificarCampo($equipo, 'placa'),
    'Tipo' => verificarCampo($equipo, 'tipo')
];

foreach ($campos_tecnicos as $label => $value) {
    if (!empty($value)) {
        $pdf->DataRow($label, $value);
    }
}

// Información económica
if (!empty(verificarCampo($equipo, 'costo')) && verificarCampo($equipo, 'costo') > 0) {
    $pdf->SectionTitle('INFORMACIÓN ECONÓMICA');
    $pdf->DataRow('Costo', formatearCosto(verificarCampo($equipo, 'costo')));
    if (!empty(verificarCampo($equipo, 'fechaCompra'))) {
        $pdf->DataRow('Fecha de Compra', formatearFecha(verificarCampo($equipo, 'fechaCompra')));
    }
}

// Ubicación
$pdf->SectionTitle('UBICACIÓN');

$pdf->DataRow('Código Postal', verificarCampo($equipo, 'cp'));
$pdf->DataRow('Provincia', verificarCampo($equipo, 'provincia'));
$pdf->DataRow('Localidad', verificarCampo($equipo, 'localidad'));
$pdf->DataRow('Dirección', verificarCampo($equipo, 'direccion'));

if (!empty(verificarCampo($equipo, 'ubicacion'))) {
    $pdf->DataRow('Ubicación Específica', verificarCampo($equipo, 'ubicacion'), true);
}

// Información del cliente - CORREGIDO
$pdf->SectionTitle('CLIENTE ASOCIADO');

$pdf->DataRow('Cliente', verificarCampo($equipo, 'usuario'));
if (!empty(verificarCampo($equipo, 'correoUsuario'))) {
    $pdf->DataRow('Correo', verificarCampo($equipo, 'correoUsuario'));
}

// Observaciones del equipo si existen
if (!empty(verificarCampo($equipo, 'observaciones'))) {
    $pdf->SectionTitle('OBSERVACIONES');
    $pdf->SetFont('Arial','',9);
    
    $observaciones = mb_convert_encoding(verificarCampo($equipo, 'observaciones'), 'ISO-8859-1','UTF-8');
    $pdf->MultiCell(0, 6, $observaciones, 1, 'L');
}

// Información del sistema (metadatos)
$pdf->SectionTitle('INFORMACIÓN DEL SISTEMA');

$pdf->DataRow('ID Usuario Asociado', verificarCampo($equipo, 'idUsuario'));

// Solo mostrar fechas de sistema si existen
if (!empty(verificarCampo($equipo, 'fechaCreacion'))) {
    $pdf->DataRow('Fecha de Creación', date('d/m/Y H:i', strtotime(verificarCampo($equipo, 'fechaCreacion'))));
}
if (!empty(verificarCampo($equipo, 'fechaModificacion'))) {
    $pdf->DataRow('Última Modificación', date('d/m/Y H:i', strtotime(verificarCampo($equipo, 'fechaModificacion'))));
}

// Generar el PDF
$nombreArchivo = 'Equipo_' . $equipo['numEquipo'] . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('I', $nombreArchivo);
?>