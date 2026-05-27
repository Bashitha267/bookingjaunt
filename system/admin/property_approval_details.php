<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['admin']);

$current_role = 'admin';
$sidebar_path = 'sidebar.php';

require_once '../shared/property_approval_details_core.php';
?>
