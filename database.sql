-- Create the database
CREATE DATABASE IF NOT EXISTS bookingjaunt;
USE bookingjaunt;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(20),
    whatsapp_number VARCHAR(20),
    nic_passport VARCHAR(50),
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255) NOT NULL,
    address TEXT,
    country VARCHAR(100),
    role ENUM('admin', 'owner', 'staff', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Properties table (Hotels / Reception Halls)
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    business_type ENUM('hotel', 'reception_hall') NOT NULL,
    hotel_category ENUM('budget_friendly', 'luxury', 'super_luxury') NULL,
    property_name VARCHAR(255) NOT NULL,
    description TEXT,
    street_address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    google_map_location TEXT,
    contact_number VARCHAR(20),
    whatsapp_number VARCHAR(20),
    business_email VARCHAR(100),
    
    -- Manager details (if not owner)
    manager_name VARCHAR(100),
    manager_email VARCHAR(100),
    manager_phone VARCHAR(20),
    
    -- Business Rules
    payout_percentage DECIMAL(5,2) DEFAULT 80.00,
    commission_percentage DECIMAL(5,2) DEFAULT 20.00,
    allow_payout_requests BOOLEAN DEFAULT TRUE,
    min_payout_amount DECIMAL(10,2) DEFAULT 0.00,
    
    -- Policies
    currency VARCHAR(10) DEFAULT 'USD',
    vat_percentage DECIMAL(5,2) DEFAULT 0.00,
    service_charge_percentage DECIMAL(5,2) DEFAULT 0.00,
    check_in_time TIME,
    check_out_time TIME,
    id_required BOOLEAN DEFAULT TRUE,
    cancellation_policy TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Property Amenities
CREATE TABLE IF NOT EXISTS property_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    amenity_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Extra Services
CREATE TABLE IF NOT EXISTS extra_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    pricing_type VARCHAR(50),
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Bank Details
CREATE TABLE IF NOT EXISTS property_bank_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    bank_name VARCHAR(255),
    account_number VARCHAR(100),
    account_holder_name VARCHAR(255),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Property Staff
CREATE TABLE IF NOT EXISTS property_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    user_id INT NOT NULL, -- Links to users table with role 'staff'
    staff_role VARCHAR(50),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
