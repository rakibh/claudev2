<?php
// Folder: pages/equipment/
// File: get_equipment_fields.php (Complete Version)
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
            ['name' => 'motherboard', 'label' => 'Motherboard', 'type' => 'text', 'placeholder' => 'e.g., ASUS Prime B450M-A'],
            ['name' => 'cpu', 'label' => 'CPU', 'type' => 'text', 'placeholder' => 'e.g., Intel Core i5-10400 / AMD Ryzen 5 3600'],
            ['name' => 'ram', 'label' => 'RAM', 'type' => 'textarea', 'placeholder' => 'Enter RAM details (e.g., 16GB DDR4 2666MHz Kingston, 8GB DDR4 3200MHz Corsair)'],
            ['name' => 'ssd', 'label' => 'SSD', 'type' => 'textarea', 'placeholder' => 'Enter SSD details (e.g., 512GB Samsung 970 EVO, 256GB Kingston A2000)'],
            ['name' => 'hdd', 'label' => 'HDD', 'type' => 'textarea', 'placeholder' => 'Enter HDD details (e.g., 1TB WD Blue 7200RPM, 2TB Seagate Barracuda)'],
            ['name' => 'graphics_card', 'label' => 'Graphics Card', 'type' => 'text', 'placeholder' => 'Model + Size + Serial (e.g., NVIDIA RTX 3060 12GB SN123456)'],
            ['name' => 'os', 'label' => 'Operating System', 'type' => 'text', 'placeholder' => 'e.g., Windows 11 Pro, Ubuntu 22.04'],
            ['name' => 'monitor', 'label' => 'Monitor', 'type' => 'text', 'placeholder' => 'Manual entry or link to existing Monitor']
        ];
        break;
        
    case 'Laptop':
        $fields = [
            ['name' => 'motherboard', 'label' => 'Motherboard', 'type' => 'text', 'placeholder' => 'Integrated motherboard model'],
            ['name' => 'cpu', 'label' => 'CPU', 'type' => 'text', 'placeholder' => 'e.g., Intel Core i7-11800H'],
            ['name' => 'ram', 'label' => 'RAM', 'type' => 'textarea', 'placeholder' => 'Enter RAM details (e.g., 16GB DDR4 3200MHz)'],
            ['name' => 'ssd', 'label' => 'SSD', 'type' => 'textarea', 'placeholder' => 'Enter SSD details (e.g., 512GB NVMe PCIe 3.0)'],
            ['name' => 'hdd', 'label' => 'HDD', 'type' => 'textarea', 'placeholder' => 'Enter HDD details (if applicable)'],
            ['name' => 'graphics_card', 'label' => 'Graphics Card', 'type' => 'text', 'placeholder' => 'e.g., NVIDIA GTX 1650 4GB'],
            ['name' => 'os', 'label' => 'Operating System', 'type' => 'text', 'placeholder' => 'e.g., Windows 11 Home'],
            ['name' => 'display_size', 'label' => 'Display Size', 'type' => 'text', 'placeholder' => 'e.g., 15.6 inch FHD'],
            ['name' => 'battery', 'label' => 'Battery', 'type' => 'text', 'placeholder' => 'e.g., 56Wh Li-ion']
        ];
        break;
        
    case 'Monitor':
        $fields = [
            ['name' => 'display_size', 'label' => 'Display Size', 'type' => 'text', 'placeholder' => 'e.g., 24 inch, 27 inch'],
            ['name' => 'resolution', 'label' => 'Resolution', 'type' => 'text', 'placeholder' => 'e.g., 1920x1080 (FHD), 2560x1440 (QHD)'],
            ['name' => 'panel_type', 'label' => 'Panel Type', 'type' => 'select', 'options' => ['IPS', 'TN', 'VA', 'OLED']],
            ['name' => 'refresh_rate', 'label' => 'Refresh Rate', 'type' => 'text', 'placeholder' => 'e.g., 60Hz, 144Hz'],
            ['name' => 'color', 'label' => 'Color', 'type' => 'text', 'placeholder' => 'e.g., Black, White, Silver'],
            ['name' => 'ports', 'label' => 'Ports', 'type' => 'select-multiple', 'options' => ['VGA', 'HDMI', 'DisplayPort', 'DVI', 'USB-C', 'Thunderbolt']]
        ];
        break;
        
    case 'UPS':
        $fields = [
            ['name' => 'battery_capacity', 'label' => 'Battery Capacity', 'type' => 'text', 'placeholder' => 'e.g., 1000VA, 1500VA'],
            ['name' => 'output_wattage', 'label' => 'Output Wattage', 'type' => 'text', 'placeholder' => 'e.g., 600W, 900W'],
            ['name' => 'backup_time', 'label' => 'Backup Time', 'type' => 'text', 'placeholder' => 'e.g., 15 minutes at full load'],
            ['name' => 'number_of_outlets', 'label' => 'Number of Outlets', 'type' => 'text', 'placeholder' => 'e.g., 4, 6, 8']
        ];
        break;
        
    case 'Web Camera':
        $fields = [
            ['name' => 'resolution', 'label' => 'Resolution', 'type' => 'text', 'placeholder' => 'e.g., 1080p, 720p, 4K'],
            ['name' => 'frame_rate', 'label' => 'Frame Rate', 'type' => 'text', 'placeholder' => 'e.g., 30fps, 60fps'],
            ['name' => 'connection_type', 'label' => 'Connection Type', 'type' => 'select', 'options' => ['USB 2.0', 'USB 3.0', 'USB-C', 'Wireless']],
            ['name' => 'features', 'label' => 'Features', 'type' => 'textarea', 'placeholder' => 'e.g., Auto-focus, Built-in microphone, LED light']
        ];
        break;
        
    case 'SSD':
        $fields = [
            ['name' => 'storage_capacity', 'label' => 'Storage Capacity', 'type' => 'text', 'placeholder' => 'e.g., 256GB, 512GB, 1TB'],
            ['name' => 'interface', 'label' => 'Interface', 'type' => 'select', 'options' => ['SATA III', 'M.2 SATA', 'M.2 NVMe', 'PCIe 3.0', 'PCIe 4.0']],
            ['name' => 'form_factor', 'label' => 'Form Factor', 'type' => 'select', 'options' => ['2.5"', 'M.2 2280', 'M.2 2242', 'M.2 22110']],
            ['name' => 'read_speed', 'label' => 'Read Speed', 'type' => 'text', 'placeholder' => 'e.g., 3500 MB/s'],
            ['name' => 'write_speed', 'label' => 'Write Speed', 'type' => 'text', 'placeholder' => 'e.g., 3000 MB/s']
        ];
        break;
        
    case 'HDD':
        $fields = [
            ['name' => 'storage_capacity', 'label' => 'Storage Capacity', 'type' => 'text', 'placeholder' => 'e.g., 1TB, 2TB, 4TB'],
            ['name' => 'interface', 'label' => 'Interface', 'type' => 'select', 'options' => ['SATA III', 'SATA II', 'IDE', 'SAS']],
            ['name' => 'form_factor', 'label' => 'Form Factor', 'type' => 'select', 'options' => ['3.5"', '2.5"']],
            ['name' => 'rpm', 'label' => 'RPM', 'type' => 'select', 'options' => ['5400 RPM', '7200 RPM', '10000 RPM', '15000 RPM']],
            ['name' => 'cache', 'label' => 'Cache', 'type' => 'text', 'placeholder' => 'e.g., 64MB, 256MB']
        ];
        break;
        
    case 'RAM':
        $fields = [
            ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'text', 'placeholder' => 'e.g., 8GB, 16GB, 32GB'],
            ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => ['DDR3', 'DDR4', 'DDR5']],
            ['name' => 'speed', 'label' => 'Speed', 'type' => 'text', 'placeholder' => 'e.g., 2666MHz, 3200MHz, 3600MHz'],
            ['name' => 'form_factor', 'label' => 'Form Factor', 'type' => 'select', 'options' => ['DIMM', 'SO-DIMM']],
            ['name' => 'cl_latency', 'label' => 'CL Latency', 'type' => 'text', 'placeholder' => 'e.g., CL16, CL18']
        ];
        break;
        
    case 'Printer':
        $fields = [
            ['name' => 'printer_type', 'label' => 'Printer Type', 'type' => 'select', 'options' => ['Inkjet', 'Laser', 'Dot Matrix', 'Thermal']],
            ['name' => 'output_color', 'label' => 'Output Color', 'type' => 'select', 'options' => ['Color', 'B&W', 'Both']],
            ['name' => 'print_speed', 'label' => 'Print Speed', 'type' => 'text', 'placeholder' => 'e.g., 20 ppm (pages per minute)'],
            ['name' => 'connectivity', 'label' => 'Connectivity', 'type' => 'select-multiple', 'options' => ['USB', 'Ethernet', 'WiFi', 'Bluetooth']],
            ['name' => 'paper_size', 'label' => 'Paper Size', 'type' => 'text', 'placeholder' => 'e.g., A4, Letter, Legal']
        ];
        break;
        
    case 'Scanner':
        $fields = [
            ['name' => 'scanner_type', 'label' => 'Scanner Type', 'type' => 'select', 'options' => ['Flatbed', 'Sheet-fed', 'Handheld', 'Drum']],
            ['name' => 'resolution', 'label' => 'Resolution', 'type' => 'text', 'placeholder' => 'e.g., 1200 DPI, 2400 DPI'],
            ['name' => 'scan_speed', 'label' => 'Scan Speed', 'type' => 'text', 'placeholder' => 'e.g., 25 ppm'],
            ['name' => 'connectivity', 'label' => 'Connectivity', 'type' => 'select-multiple', 'options' => ['USB', 'Ethernet', 'WiFi']],
            ['name' => 'color_depth', 'label' => 'Color Depth', 'type' => 'text', 'placeholder' => 'e.g., 48-bit']
        ];
        break;
        
    case 'Network Switch':
        $fields = [
            ['name' => 'switch_type', 'label' => 'Type', 'type' => 'select', 'options' => ['Managed', 'Unmanaged', 'Smart']],
            ['name' => 'ports', 'label' => 'Number of Ports', 'type' => 'text', 'placeholder' => 'e.g., 8, 16, 24, 48'],
            ['name' => 'port_speed', 'label' => 'Port Speed', 'type' => 'select', 'options' => ['10/100 Mbps', '10/100/1000 Mbps (Gigabit)', '10 Gbps']],
            ['name' => 'poe_support', 'label' => 'PoE Support', 'type' => 'select', 'options' => ['No', 'PoE', 'PoE+', 'PoE++']],
            ['name' => 'data_rate', 'label' => 'Data Transmission Rate', 'type' => 'text', 'placeholder' => 'e.g., 1Gbps, 10Gbps'],
            ['name' => 'sfp_ports', 'label' => 'SFP Ports', 'type' => 'text', 'placeholder' => 'e.g., 2, 4']
        ];
        break;
        
    case 'WiFi Router':
        $fields = [
            ['name' => 'wifi_standard', 'label' => 'WiFi Standard', 'type' => 'select', 'options' => ['WiFi 4 (802.11n)', 'WiFi 5 (802.11ac)', 'WiFi 6 (802.11ax)', 'WiFi 6E']],
            ['name' => 'frequency_bands', 'label' => 'Frequency Bands', 'type' => 'select-multiple', 'options' => ['2.4 GHz', '5 GHz', '6 GHz']],
            ['name' => 'max_speed', 'label' => 'Max Speed', 'type' => 'text', 'placeholder' => 'e.g., AC1900, AX3000'],
            ['name' => 'ethernet_ports', 'label' => 'Ethernet Ports', 'type' => 'text', 'placeholder' => 'e.g., 4x Gigabit LAN'],
            ['name' => 'antennas', 'label' => 'Number of Antennas', 'type' => 'text', 'placeholder' => 'e.g., 4, 6, 8']
        ];
        break;
        
    case 'Server':
        $fields = [
            ['name' => 'form_factor', 'label' => 'Form Factor', 'type' => 'select', 'options' => ['Tower', 'Rack (1U)', 'Rack (2U)', 'Rack (4U)', 'Blade']],
            ['name' => 'cpu', 'label' => 'CPU', 'type' => 'text', 'placeholder' => 'e.g., Intel Xeon E5-2690 v4 x2'],
            ['name' => 'ram', 'label' => 'RAM', 'type' => 'text', 'placeholder' => 'e.g., 128GB DDR4 ECC'],
            ['name' => 'storage', 'label' => 'Storage', 'type' => 'textarea', 'placeholder' => 'e.g., 4x 2TB SAS HDD RAID 10, 2x 480GB SSD'],
            ['name' => 'raid_controller', 'label' => 'RAID Controller', 'type' => 'text', 'placeholder' => 'e.g., Dell PERC H730'],
            ['name' => 'network_ports', 'label' => 'Network Ports', 'type' => 'text', 'placeholder' => 'e.g., 4x 1GbE, 2x 10GbE SFP+'],
            ['name' => 'os', 'label' => 'Operating System', 'type' => 'text', 'placeholder' => 'e.g., Windows Server 2022, Ubuntu Server 22.04']
        ];
        break;
        
    case 'KVM':
        $fields = [
            ['name' => 'number_of_ports', 'label' => 'Number of Ports', 'type' => 'text', 'placeholder' => 'e.g., 2, 4, 8, 16'],
            ['name' => 'connection_type', 'label' => 'Connection Type', 'type' => 'select-multiple', 'options' => ['VGA', 'HDMI', 'DVI', 'DisplayPort', 'USB']],
            ['name' => 'resolution_support', 'label' => 'Max Resolution', 'type' => 'text', 'placeholder' => 'e.g., 1920x1080, 4K']
        ];
        break;
        
    case 'Projector':
        $fields = [
            ['name' => 'projector_type', 'label' => 'Type', 'type' => 'select', 'options' => ['LCD', 'DLP', 'LED', 'Laser']],
            ['name' => 'resolution', 'label' => 'Resolution', 'type' => 'select', 'options' => ['XGA (1024x768)', 'WXGA (1280x800)', 'Full HD (1920x1080)', '4K UHD (3840x2160)']],
            ['name' => 'brightness', 'label' => 'Brightness', 'type' => 'text', 'placeholder' => 'e.g., 3000 lumens, 4500 lumens'],
            ['name' => 'contrast_ratio', 'label' => 'Contrast Ratio', 'type' => 'text', 'placeholder' => 'e.g., 10000:1, 20000:1'],
            ['name' => 'connectivity', 'label' => 'Connectivity', 'type' => 'select-multiple', 'options' => ['HDMI', 'VGA', 'USB', 'Ethernet', 'WiFi', 'Bluetooth']]
        ];
        break;
        
    case 'Speaker/Headphones':
        $fields = [
            ['name' => 'device_type', 'label' => 'Type', 'type' => 'select', 'options' => ['Desktop Speakers', 'Headphones', 'Earphones', 'Wireless Headphones', 'Bluetooth Speakers']],
            ['name' => 'connectivity', 'label' => 'Connectivity', 'type' => 'select-multiple', 'options' => ['3.5mm Jack', 'USB', 'Bluetooth', 'Wireless 2.4GHz']],
            ['name' => 'power_output', 'label' => 'Power Output', 'type' => 'text', 'placeholder' => 'e.g., 10W RMS']
        ];
        break;
        
    case 'CCTV Camera':
        $fields = [
            ['name' => 'camera_type', 'label' => 'Type', 'type' => 'select', 'options' => ['Bullet', 'Dome', 'PTZ', 'IP Camera']],
            ['name' => 'resolution', 'label' => 'Resolution', 'type' => 'select', 'options' => ['720p', '1080p (2MP)', '4MP', '5MP', '4K (8MP)']],
            ['name' => 'lens', 'label' => 'Lens', 'type' => 'text', 'placeholder' => 'e.g., 2.8mm, 3.6mm, Varifocal 2.8-12mm'],
            ['name' => 'night_vision', 'label' => 'Night Vision Range', 'type' => 'text', 'placeholder' => 'e.g., 20m, 30m IR'],
            ['name' => 'connectivity', 'label' => 'Connectivity', 'type' => 'select', 'options' => ['Analog', 'IP/Ethernet', 'WiFi', 'PoE']]
        ];
        break;
        
    case 'NVR/DVR':
        $fields = [
            ['name' => 'device_type', 'label' => 'Type', 'type' => 'select', 'options' => ['NVR (Network Video Recorder)', 'DVR (Digital Video Recorder)', 'Hybrid']],
            ['name' => 'channels', 'label' => 'Number of Channels', 'type' => 'text', 'placeholder' => 'e.g., 4, 8, 16, 32'],
            ['name' => 'max_resolution', 'label' => 'Max Recording Resolution', 'type' => 'select', 'options' => ['720p', '1080p', '4MP', '5MP', '4K']],
            ['name' => 'storage', 'label' => 'Storage', 'type' => 'text', 'placeholder' => 'e.g., 2TB HDD, 4TB HDD'],
            ['name' => 'network_ports', 'label' => 'Network Ports', 'type' => 'text', 'placeholder' => 'e.g., 1x Gigabit Ethernet, 8x PoE ports']
        ];
        break;
        
    case 'Graphics Card':
        $fields = [
            ['name' => 'model', 'label' => 'Model', 'type' => 'text', 'placeholder' => 'e.g., NVIDIA RTX 3060, AMD RX 6700 XT'],
            ['name' => 'memory_size', 'label' => 'Memory Size', 'type' => 'text', 'placeholder' => 'e.g., 8GB, 12GB, 16GB'],
            ['name' => 'memory_type', 'label' => 'Memory Type', 'type' => 'select', 'options' => ['GDDR5', 'GDDR6', 'GDDR6X']],
            ['name' => 'interface', 'label' => 'Interface', 'type' => 'select', 'options' => ['PCIe 3.0 x16', 'PCIe 4.0 x16']],
            ['name' => 'power_consumption', 'label' => 'TDP (Power)', 'type' => 'text', 'placeholder' => 'e.g., 170W, 220W']
        ];
        break;
        
    case 'Custom Type':
        $fields = [
            ['name' => 'custom_field_1', 'label' => 'Field 1', 'type' => 'text'],
            ['name' => 'custom_field_2', 'label' => 'Field 2', 'type' => 'text'],
            ['name' => 'custom_field_3', 'label' => 'Field 3', 'type' => 'textarea']
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