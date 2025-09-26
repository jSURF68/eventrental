<?php
require_once '../../../main.inc.php';
require_once '../class/eventrental_event.class.php';
require_once '../class/eventrental_assignment.class.php';

$langs->loadLangs(array("eventrental@eventrental", "other"));

$event_id = GETPOST('event_id', 'int');

// Load event
$event = new EventRental($db);
if ($event_id > 0) {
    $event->fetch($event_id);
    $event->fetch_thirdparty();
}

// Récupération des assignations
$assignments = EventRentalAssignment::getEventAssignments($event_id, $db);

// Force le téléchargement PDF (avec conversion HTML)
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Fiche_Sortie_'.$event->ref_event.'.pdf"');

// Si wkhtmltopdf ou dompdf disponible, on peut faire une vraie conversion
// Sinon, on génère du HTML optimisé pour impression
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fiche de Sortie - <?php echo $event->ref_event; ?></title>
    <style>
        @page { 
            margin: 20mm; 
            size: A4;
        }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .event-info {
            background: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .material-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .material-table th, .material-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        .material-table th {
            background: #e0e0e0;
            font-weight: bold;
        }
        .category-header {
            background: #d0d0d0 !important;
            font-weight: bold;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .signature-block {
            width: 45%;
            border: 2px solid #333;
            padding: 15px;
            height: 100px;
        }
        .checkbox {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 2px solid #333;
            margin-right: 5px;
        }
        .important {
            text-align: center;
            font-style: italic;
            margin-top: 20px;
            padding: 10px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FICHE DE SORTIE MATÉRIEL</h1>
        <h2>Événementiel Professionnel</h2>
    </div>

    <div class="event-info">
        <h3>📋 INFORMATIONS ÉVÉNEMENT</h3>
        <table style="width: 100%;">
            <tr>
                <td style="width: 150px;"><strong>Référence :</strong></td>
                <td><strong><?php echo $event->ref_event; ?></strong></td>
                <td style="width: 100px;"><strong>Date :</strong></td>
                <td><?php echo dol_print_date($event->date_debut, 'day'); ?></td>
            </tr>
            <tr>
                <td><strong>Événement :</strong></td>
                <td><?php echo $event->nom_evenement; ?></td>
                <td><strong>Heure :</strong></td>
                <td><?php echo dol_print_date($event->date_debut, 'hour'); ?></td>
            </tr>
            <tr>
                <td><strong>Client :</strong></td>
                <td><?php echo $event->thirdparty->name; ?></td>
                <td><strong>Type :</strong></td>
                <td><?php echo $event->type_evenement; ?></td>
            </tr>
            <?php if (!empty($event->lieu_evenement)): ?>
            <tr>
                <td><strong>Lieu :</strong></td>
                <td colspan="3"><?php echo $event->lieu_evenement; ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <h3>🛠️ MATÉRIEL ASSIGNÉ (<?php echo count($assignments); ?> unités)</h3>
    
    <table class="material-table">
        <thead>
            <tr>
                <th style="width: 15%;">Référence</th>
                <th style="width: 35%;">Produit</th>
                <th style="width: 20%;">N° Série</th>
                <th style="width: 20%;">QR Code</th>
                <th style="width: 10%;">Contrôle</th>
            </tr>
        </thead>
        <tbody>
            <?php
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

            foreach ($assignments_by_category as $category => $category_assignments):
                $icon = isset($category_icons[$category]) ? $category_icons[$category] : '📦';
            ?>
                <tr class="category-header">
                    <td colspan="5"><?php echo $icon; ?> <strong><?php echo strtoupper($category); ?></strong></td>
                </tr>
                <?php foreach ($category_assignments as $assignment): ?>
                <tr>
                    <td><strong><?php echo $assignment->ref_product; ?></strong></td>
                    <td><?php echo $assignment->product_label; ?></td>
                    <td><code><?php echo $assignment->numero_serie; ?></code></td>
                    <td><code><?php echo $assignment->qr_code; ?></code></td>
                    <td style="text-align: center;">
                        <span class="checkbox"></span>OK 
                        <span class="checkbox"></span>Défaut
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-block">
            <h4>👨‍🔧 SIGNATURE TECHNICIEN</h4>
            <p>Nom : _________________________</p>
            <p>Date : ________________________</p>
            <p>Signature :</p>
            <div style="height: 40px;"></div>
        </div>
        
        <div class="signature-block">
            <h4>👤 SIGNATURE CLIENT</h4>
            <p>Nom : _________________________</p>
            <p>Date : ________________________</p>
            <p>Signature :</p>
            <div style="height: 40px;"></div>
        </div>
    </div>

    <div class="important">
        <strong>⚠️ IMPORTANT :</strong> Vérifiez l'état du matériel avant utilisation. 
        Signalez immédiatement tout défaut ou problème.<br>
        <strong>Contact urgence :</strong> <?php echo $conf->global->MAIN_INFO_PHONE ?: '01 XX XX XX XX'; ?>
    </div>

    <div style="text-align: center; margin-top: 20px; font-size: 10px; color: #666;">
        Document généré le <?php echo dol_print_date(dol_now(), 'dayhour'); ?> - 
        Référence: <?php echo $event->ref_event; ?>
    </div>

    <script>
        // Auto-print si demandé
        if (window.location.search.includes('print=1')) {
            window.print();
        }
    </script>
</body>
</html>