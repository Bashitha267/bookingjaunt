<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['manager']);

$current_role = 'manager';
$sidebar_path = 'sidebar.php';

require_once '../shared/property_approval_details_core.php';
?>
