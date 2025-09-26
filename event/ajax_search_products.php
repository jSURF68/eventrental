<?php
require_once '../../../main.inc.php';

// Security check
if (!$user->rights->eventrental->event->read) {
    http_response_code(403);
    echo json_encode(['error' => 'Access forbidden']);
    exit;
}

$search = GETPOST('search', 'alpha');
$limit = GETPOST('limit', 'int') ?: 20;

if (strlen($search) < 2) {
    echo json_encode(['products' => []]);
    exit;
}

$products = array();

// Recherche dans les produits
$sql = "SELECT p.rowid, p.ref_product, p.label, p.category_event, p.prix_location_jour, p.qty_disponible,";
$sql .= " p.dimensions, p.poids, p.sub_category";
$sql .= " FROM ".MAIN_DB_PREFIX."eventrental_product p";
$sql .= " WHERE p.statut = 1";
$sql .= " AND (";
$sql .= " p.ref_product LIKE '%".$db->escape($search)."%'";
$sql .= " OR p.label LIKE '%".$db->escape($search)."%'";
$sql .= " OR p.sub_category LIKE '%".$db->escape($search)."%'";
$sql .= " OR p.category_event LIKE '%".$db->escape($search)."%'";
$sql .= ")";
$sql .= " ORDER BY ";
$sql .= " CASE WHEN p.ref_product LIKE '".$db->escape($search)."%' THEN 1 ELSE 2 END,"; // Priorité références qui commencent par
$sql .= " CASE WHEN p.label LIKE '".$db->escape($search)."%' THEN 1 ELSE 2 END,"; // Priorité libellés qui commencent par
$sql .= " p.qty_disponible DESC, p.label";
$sql .= " LIMIT " . ((int) $limit);

$resql = $db->query($sql);

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $category_icons = array(
            'son' => '🎵',
            'eclairage' => '💡',
            'scene' => '🎪',
            'mobilier' => '🪑',
            'decoration' => '🎨',
            'technique' => '🔧'
        );
        
        $icon = isset($category_icons[$obj->category_event]) ? $category_icons[$obj->category_event] : '📦';
        
        $availability_class = '';
        $availability_text = '';
        
        if ($obj->qty_disponible <= 0) {
            $availability_class = 'unavailable';
            $availability_text = 'Indisponible';
        } else if ($obj->qty_disponible <= 2) {
            $availability_class = 'low-stock';
            $availability_text = $obj->qty_disponible . ' disponible(s)';
        } else {
            $availability_class = 'available';
            $availability_text = $obj->qty_disponible . ' disponible(s)';
        }
        
        $products[] = array(
            'id' => $obj->rowid,
            'ref' => $obj->ref_product,
            'label' => $obj->label,
            'category' => $obj->category_event,
            'sub_category' => $obj->sub_category ?: '',
            'icon' => $icon,
            'price' => $obj->prix_location_jour,
            'qty_available' => $obj->qty_disponible,
            'availability_class' => $availability_class,
            'availability_text' => $availability_text,
            'dimensions' => $obj->dimensions ?: '',
            'weight' => $obj->poids ?: 0,
            'display_text' => $obj->ref_product . ' - ' . $obj->label
        );
    }
}

header('Content-Type: application/json');
echo json_encode(['products' => $products]);
?>
