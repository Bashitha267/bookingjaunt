<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require specific roles to access a page.
 * Redirects to login page if user does not have one of the required roles.
 *
 * @param array $roles Array of allowed roles, e.g., ['admin', 'manager']
 * @param string $redirect_url URL to redirect to if unauthorized
 */
function requireRole($roles, $redirect_url = '../../login.php') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: " . $redirect_url);
        exit();
    }
    
    if (!in_array($_SESSION['role'], $roles)) {
        header("Location: " . $redirect_url);
        exit();
    }
}

/**
 * Check if current user has the 'admin' role.
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if current user has the 'manager' role.
 */
function isManager() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'manager';
}

/**
 * Check if current user has the 'site_staff' role.
 */
function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'site_staff';
}

/**
 * Generic check if current user has a specific role.
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Get unread notification count for a user.
 */
function getUnreadNotificationsCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM internal_notifications WHERE recipient_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get recent notifications for a user.
 */
function getRecentNotifications($pdo, $user_id, $limit = 5) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM internal_notifications WHERE recipient_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Record a booking cancellation in the log.
 */
function logBookingCancellation($pdo, $booking_id, $user_id, $reason) {
    try {
        $stmt = $pdo->prepare("INSERT INTO booking_cancellations (booking_id, cancelled_by, reason) VALUES (?, ?, ?)");
        return $stmt->execute([$booking_id, $user_id, $reason]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Notify admins (and optionally manager) about a cancellation.
 */
function notifyAdminsOfCancellation($pdo, $booking_id, $cancelled_by_id, $reason) {
    try {
        // Find all admins
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Find if user is staff and has a manager
        $manager_id = null;
        $stmtManager = $pdo->prepare("SELECT manager_id FROM staff_manager WHERE staff_id = ?");
        $stmtManager->execute([$cancelled_by_id]);
        $row = $stmtManager->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['manager_id']) {
            $manager_id = $row['manager_id'];
        }
        
        $cancellerName = "Staff/Manager (ID: $cancelled_by_id)";
        $stmtName = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $stmtName->execute([$cancelled_by_id]);
        if ($nameRow = $stmtName->fetch(PDO::FETCH_ASSOC)) {
            $cancellerName = trim($nameRow['first_name'] . ' ' . $nameRow['last_name']);
        }

        $message = "Booking #$booking_id cancelled by $cancellerName. Reason: $reason";
        
        $insertStmt = $pdo->prepare("INSERT INTO internal_notifications (recipient_id, type, message, related_id) VALUES (?, 'booking_cancelled', ?, ?)");
        
        // Notify admins
        foreach ($admins as $admin) {
            $insertStmt->execute([$admin['id'], $message, $booking_id]);
        }
        
        // Notify manager if applicable
        if ($manager_id) {
            $insertStmt->execute([$manager_id, $message, $booking_id]);
            
            // update log
            $updateLog = $pdo->prepare("UPDATE booking_cancellations SET notified_manager = 1, notified_admin = 1 WHERE booking_id = ?");
            $updateLog->execute([$booking_id]);
        } else {
            $updateLog = $pdo->prepare("UPDATE booking_cancellations SET notified_admin = 1 WHERE booking_id = ?");
            $updateLog->execute([$booking_id]);
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>
