<?php
require_once '../../../main.inc.php';
require_once '../class/eventrental_event.class.php';
require_once '../class/eventrental_assignment.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
dol_include_once('/core/lib/company.lib.php');

$langs->loadLangs(array("eventrental@eventrental", "other"));

// Security check
if (!$user->rights->eventrental->event->read) {
    accessforbidden();
}

$event_id = GETPOST('event_id', 'int');

// Load event
$event = new EventRental($db);
if ($event_id > 0) {
    $event->fetch($event_id);
    $event->fetch_thirdparty();
}

// Récupération des assignations
$assignments = EventRentalAssignment::getEventAssignments($event_id, $db);

if (empty($assignments)) {
    setEventMessages('Aucune unité assignée. Assignez d\'abord les unités.', null, 'errors');
    header('Location: generate_sheet.php?event_id='.$event_id);
    exit;
}

// Génération PDF simple avec TCPDF
require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php';

// Création du PDF
$pdf = new TCPDF();
$pdf->SetCreator('Dolibarr');
$pdf->SetAuthor($user->getFullName($langs));
$pdf->SetTitle('Fiche de Sortie - '.$event->ref_event);
$pdf->SetSubject('Fiche de sortie matériel événementiel');

// Configuration
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->setImageScale(1.25);

// Ajout page
$pdf->AddPage();

// En-tête
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'FICHE DE SORTIE MATÉRIEL', 0, 1, 'C');
$pdf->Ln(5);

// Informations événement
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'INFORMATIONS ÉVÉNEMENT', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(50, 6, 'Référence :', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $event->ref_event, 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(50, 6, 'Nom événement :', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $event->nom_evenement, 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(50, 6, 'Client :', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $event->thirdparty->name, 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(50, 6, 'Date début :', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, dol_print_date($event->date_debut, 'dayhour'), 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

if (!empty($event->lieu_evenement)) {
    $pdf->Cell(50, 6, 'Lieu :', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, $event->lieu_evenement, 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
}

$pdf->Ln(10);

// Matériel assigné
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'MATÉRIEL ASSIGNÉ ('.count($assignments).' unités)', 0, 1, 'L');
$pdf->Ln(2);

// En-têtes tableau
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(40, 6, 'Référence', 1, 0, 'C', 1);
$pdf->Cell(60, 6, 'Produit', 1, 0, 'C', 1);
$pdf->Cell(30, 6, 'N° Série', 1, 0, 'C', 1);
$pdf->Cell(30, 6, 'QR Code', 1, 0, 'C', 1);
$pdf->Cell(20, 6, 'État', 1, 1, 'C', 1);

// Groupement par catégorie
$assignments_by_category = array();
foreach ($assignments as $assignment) {
    $category = $assignment->category_event ?: 'autre';
    if (!isset($assignments_by_category[$category])) {
        $assignments_by_category[$category] = array();
    }
    $assignments_by_category[$category][] = $assignment;
}

$category_icons = array(
    'son' => '🎵',
    'eclairage' => '💡',
    'scene' => '🎪',
    'mobilier' => '🪑',
    'decoration' => '🎨',
    'technique' => '🔧',
    'autre' => '📦'
);

$pdf->SetFont('helvetica', '', 9);

foreach ($assignments_by_category as $category => $category_assignments) {
    // Titre catégorie
    $icon = isset($category_icons[$category]) ? $category_icons[$category] : '📦';
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell(0, 8, $icon . ' ' . strtoupper($category), 1, 1, 'L', 1);
    
    $pdf->SetFont('helvetica', '', 9);
    
    foreach ($category_assignments as $assignment) {
        $pdf->Cell(40, 6, $assignment->ref_product, 1, 0, 'L');
        $pdf->Cell(60, 6, substr($assignment->product_label, 0, 35), 1, 0, 'L');
        $pdf->Cell(30, 6, $assignment->numero_serie, 1, 0, 'C');
        $pdf->Cell(30, 6, $assignment->qr_code, 1, 0, 'C');
        $pdf->Cell(20, 6, '☐ OK ☐', 1, 1, 'C'); // Cases à cocher
    }
    
    $pdf->Ln(2);
}

$pdf->Ln(10);

// Blocs signatures
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'CONTRÔLES ET SIGNATURES', 0, 1, 'C');
$pdf->Ln(5);

// Signature technicien (gauche)
$pdf->Rect(10, $pdf->GetY(), 85, 35);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(85, 6, 'SIGNATURE TECHNICIEN', 0, 0, 'C');
$pdf->Cell(10, 6, '', 0, 0); // Espacement
$pdf->Cell(85, 6, 'SIGNATURE CLIENT', 0, 1, 'C');

$y_signatures = $pdf->GetY();

$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, $y_signatures + 5);
$pdf->Cell(75, 4, 'Nom: ________________________', 0, 1, 'L');
$pdf->SetX(15);
$pdf->Cell(75, 4, 'Date: _______________________', 0, 1, 'L');
$pdf->SetX(15);
$pdf->Cell(75, 4, 'Signature:', 0, 1, 'L');

// Signature client (droite)  
$pdf->Rect(105, $y_signatures, 85, 35);
$pdf->SetXY(110, $y_signatures + 5);
$pdf->Cell(75, 4, 'Nom: ________________________', 0, 1, 'L');
$pdf->SetX(110);
$pdf->Cell(75, 4, 'Date: _______________________', 0, 1, 'L');
$pdf->SetX(110);
$pdf->Cell(75, 4, 'Signature:', 0, 1, 'L');

$pdf->Ln(40);

// Pied de page avec informations importantes
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 4, 'IMPORTANT: Vérifiez l\'état du matériel avant utilisation. Signalez immédiatement tout défaut.', 0, 1, 'C');
$pdf->Cell(0, 4, 'Contact urgence: ' . (empty($conf->global->MAIN_INFO_PHONE) ? 'À définir' : $conf->global->MAIN_INFO_PHONE), 0, 1, 'C');

// Génération et téléchargement
$filename = 'Fiche_Sortie_' . $event->ref_event . '_' . date('Y-m-d') . '.pdf';

// Output du PDF
$pdf->Output($filename, 'D'); // D = Download
exit;
?>
