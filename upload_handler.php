<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['action']) && $_POST['action'] === 'upload') {
        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            exit;
        }

        $file = $_FILES['file'];
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size exceeds the 10MB limit']);
            exit;
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prop_') . '.' . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            echo json_encode(['success' => true, 'filepath' => $target]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload failed']);
        }
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $filepath = $_POST['filepath'];
        // Basic security check: ensure the path is within the uploads directory
        if (strpos($filepath, 'uploads/') === 0 && file_exists($filepath)) {
            if (unlink($filepath)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Delete failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file path']);
        }
        exit;
    }
}
