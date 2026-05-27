-- 1. Extend users role ENUM
ALTER TABLE users MODIFY COLUMN role ENUM('admin','owner','manager','site_staff','staff','user') DEFAULT 'user';

-- 2. Staff-Manager relationship table
CREATE TABLE IF NOT EXISTS staff_manager (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  manager_id INT NULL,       -- NULL means they report directly to admin
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY (staff_id)      -- a staff belongs to exactly one manager
);

-- 3. Manager invite tokens
CREATE TABLE IF NOT EXISTS manager_invite_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  token VARCHAR(64) NOT NULL UNIQUE,
  created_by INT NOT NULL,       -- admin user_id
  used_by INT DEFAULT NULL,      -- manager user_id after registration
  used_at TIMESTAMP NULL,
  expires_at TIMESTAMP NULL,     -- NULL = no expiry
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Booking cancellation log (with reason, who cancelled)
CREATE TABLE IF NOT EXISTS booking_cancellations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  cancelled_by INT NOT NULL,     -- user_id of who cancelled
  reason TEXT NOT NULL,
  notified_manager TINYINT(1) DEFAULT 0,
  notified_admin TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Internal notifications
CREATE TABLE IF NOT EXISTS internal_notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  recipient_id INT NOT NULL,     -- user_id to notify
  type VARCHAR(50) NOT NULL,     -- 'booking_cancelled', 'edit_approved', etc.
  message TEXT NOT NULL,
  related_id INT DEFAULT NULL,   -- booking_id, property_id, etc.
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Role background settings (per user)
ALTER TABLE users ADD COLUMN IF NOT EXISTS manager_bg VARCHAR(255) DEFAULT NULL;
