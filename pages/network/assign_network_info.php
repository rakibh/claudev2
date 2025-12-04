<?php
// ==================== pages/network/assign_network_info.php ====================
// Folder: pages/network/
// File: assign_network_info.php
// Purpose: Assign network info to equipment

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

$stmt = $pdo->query("SELECT * FROM network_info WHERE equipment_id IS NULL ORDER BY INET_ATON(ip_address)");
$unassigned_network = $stmt->fetchAll();

$stmt = $pdo->query("SELECT e.* FROM equipments e LEFT JOIN network_info n ON e.id = n.equipment_id WHERE n.equipment_id IS NULL ORDER BY e.label");
$unassigned_equipment = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $network_id = (int)($_POST['network_id'] ?? 0);
    $equipment_id = (int)($_POST['equipment_id'] ?? 0);
    
    if ($network_id > 0 && $equipment_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE network_info SET equipment_id = ? WHERE id = ?");
            $stmt->execute([$equipment_id, $network_id]);
            
            $_SESSION['success_message'] = 'Network assigned successfully';
            header('Location: list_network_info.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error';
        }
    }
}

define('PAGE_TITLE', 'Assign Network');
include '../../includes/header.php';
?>

<h1 class="h2">Assign Network to Equipment</h1>
<form method="POST">
    <div class="row g-3">
        <div class="col-md-6">
            <label>Select Network Info</label>
            <select class="form-select" name="network_id" required>
                <option value="">Select</option>
                <?php foreach ($unassigned_network as $n): ?>
                <option value="<?php echo $n['id']; ?>"><?php echo htmlspecialchars($n['ip_address']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label>Select Equipment</label>
            <select class="form-select" name="equipment_id" required>
                <option value="">Select</option>
                <?php foreach ($unassigned_equipment as $e): ?>
                <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-success mt-3">Assign</button>
</form>

<?php include '../../includes/footer.php'; ?>
