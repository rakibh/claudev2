<?php
// Folder: pages/equipment/
// File: get_equipment_fields.php
// Purpose: AJAX endpoint to return type-specific fields for equipment forms

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';

require_login();

header('Content-Type: application/json');

$type_id = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;

if ($type_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid type ID']);
    exit;
}

// Get equipment type info
$stmt = $pdo->prepare("SELECT * FROM equipment_types WHERE id = ?");
$stmt->execute([$type_id]);
$type = $stmt->fetch();

if (!$type) {
    echo json_encode(['success' => false, 'message' => 'Equipment type not found']);
    exit;
}

$fields = [];

// Define type-specific fields based on equipment type
switch ($type['type_name']) {
    case 'Desktop PC':
        $fields = [
            ['name' => 'motherboard', 'label' => 'Motherboard', 'type' => 'text'],
            ['name' => 'cpu', 'label' => 'CPU', 'type' => 'text'],
            ['name' => 'ram', 'label' => 'RAM', 'type' => 'textarea', 'placeholder' => 'Enter RAM details (e.g., 16GB DDR4, 8GB DDR4)'],
            ['name' => 'ssd', 'label' => 'SSD', 'type' => 'textarea', 'placeholder' => 'Enter SSD details (e.g., 512GB Samsung, 256GB Kingston)'],
            ['name' => 'hdd', 'label' => 'HDD', 'type' => 'textarea', 'placeholder' => 'Enter HDD details (e.g., 1TB WD Blue, 2TB Seagate)'],
            ['name' => 'graphics_card', 'label' => 'Graphics Card', 'type' => 'text', 'placeholder' => 'Model + Size + Serial (e.g., NVIDIA RTX 3060 12GB SN123456)'],
            ['name' => 'os', 'label' => 'Operating System', 'type' => 'text'],
            ['name' => 'monitor', 'label' => 'Monitor', 'type' => 'text', 'placeholder' => 'Manual entry or link to existing Monitor']
        ];
        break;
        
    case 'Laptop':
        $fields = [
            ['name' => 'motherboard', 'label' => 'Motherboard', 'type' => 'text'],
            ['name' => 'cpu', 'label' => 'CPU', 'type' => 'text'],
            ['name' => 'ram', 'label' => 'RAM', 'type' => 'textarea', 'placeholder' => 'Enter RAM details'],
            ['name' => 'ssd', 'label' => 'SSD', 'type' => 'textarea', 'placeholder' => 'Enter SSD details'],
            ['name' => 'hdd', 'label' => 'HDD', 'type' => 'textarea', 'placeholder' => 'Enter HDD details'],
            ['name' => 'graphics_card', 'label' => 'Graphics Card', 'type' => 'text'],
            ['name' => 'os', 'label' => 'Operating System', 'type' => 'text']
        ];
        break;
        
    case 'Monitor':
        $fields = [
            ['name' => 'display_size', 'label' => 'Display Size', 'type' => 'text', 'placeholder' => 'e.g., 24 inch'],
            ['name' => 'color', 'label' => 'Color', 'type' => 'text'],
            ['name' => 'ports', 'label' => 'Ports', 'type' => 'select-multiple', 'options' => [
                'VGA', 'HDMI', 'DisplayPort', 'DVI', 'USB-C', 'Thunderbolt'
            ]]
        ];
        break;
        
    case 'UPS':
        $fields = [
            ['name' => 'battery_capacity', 'label' => 'Battery Capacity', 'type' => 'text', 'placeholder' => 'e.g., 1000VA']
        ];
        break;
        
    case 'SSD':
    case 'HDD':
        $fields = [
            ['name' => 'storage_capacity', 'label' => 'Storage Capacity', 'type' => 'text', 'placeholder' => 'e.g., 512GB, 1TB']
        ];
        break;
        
    case 'Printer':
        $fields = [
            ['name' => 'output_color', 'label' => 'Output Color', 'type' => 'select', 'options' => ['Color', 'B&W']]
        ];
        break;
        
    case 'Network Switch':
        $fields = [
            ['name' => 'switch_type', 'label' => 'Type', 'type' => 'text', 'placeholder' => 'e.g., Managed, Unmanaged'],
            ['name' => 'ports', 'label' => 'Number of Ports', 'type' => 'text', 'placeholder' => 'e.g., 24, 48'],
            ['name' => 'data_rate', 'label' => 'Data Transmission Rate', 'type' => 'text', 'placeholder' => 'e.g., 1Gbps, 10Gbps']
        ];
        break;
        
    case 'Graphics Card':
        $fields = [
            ['name' => 'model', 'label' => 'Model', 'type' => 'text'],
            ['name' => 'size', 'label' => 'Size', 'type' => 'text', 'placeholder' => 'e.g., 8GB, 12GB']
        ];
        break;
}

echo json_encode([
    'success' => true,
    'type_name' => $type['type_name'],
    'has_network' => (bool)$type['has_network'],
    'fields' => $fields
]);
?>