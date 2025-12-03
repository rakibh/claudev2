<?php
// Folder: root
// File: generate_hash.php
// Purpose: Generate correct password hash for Admin@123

$password = 'Admin@123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: {$password}\n";
echo "Hash: {$hash}\n\n";
echo "Run this SQL:\n";
echo "UPDATE users SET password = '{$hash}' WHERE employee_id = 'E-001';\n";
?>