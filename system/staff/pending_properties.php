<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['staff']);

$current_role = 'staff';
$sidebar_path = 'sidebar.php';

require_once '../shared/pending_properties_core.php';
?>
