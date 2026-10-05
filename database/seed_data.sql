-- ============================================================
-- Hotel Management System
-- Seed Data
-- File: database/seed_data.sql
--
-- WARNING:
-- These are DEVELOPMENT / DEMO credentials.
-- Change all passwords before using in production.
--
-- Demo password:
-- Password@123
-- ============================================================

USE `hotel_management`;

SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
-- USERS
-- ============================================================

INSERT INTO `users` (
    `id`,
    `first_name`,
    `last_name`,
    `email`,
    `phone`,
    `password`,
    `role`,
    `status`,
    `email_verified_at`,
    `password_changed_at`
) VALUES
(
    1,
    'System',
    'Administrator',
    'admin@hotel.test',
    '+10000000001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC7Q3ZQqYQq6fYpQW7uG',
    'admin',
    'active',
    NOW(),
    NOW()
),
(
    2,
    'John',
    'Manager',
    'manager@hotel.test',
    '+10000000002',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC7Q3ZQqYQq6fYpQW7uG',
    'manager',
    'active',
    NOW(),
    NOW()
),
(
    3,
    'Sarah',
    'Receptionist',
    'reception@hotel.test',
    '+10000000003',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC7Q3ZQqYQq6fYpQW7uG',
    'receptionist',
    'active',
    NOW(),
    NOW()
),
(
    4,
    'Michael',
    'Accountant',
    'accountant@hotel.test',
    '+10000000004',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC7Q3ZQqYQq6fYpQW7uG',
    'accountant',
    'active',
    NOW(),
    NOW()
),
(
    5,
    'David',
    'Staff',
    'staff@hotel.test',
    '+10000000005',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC7Q3ZQqYQq6fYpQW7uG',
    'staff',
    'active',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    `first_name` = VALUES(`first_name`),
    `last_name` = VALUES(`last_name`),
    `role` = VALUES(`role`),
    `status` = VALUES(`status`);


-- ============================================================
-- ROOMS
-- ============================================================

INSERT INTO `rooms` (
    `id`,
    `room_number`,
    `room_type`,
    `name`,
    `description`,
    `capacity`,
    `max_guests`,
    `price_per_night`,
    `currency`,
    `status`,
    `is_available`,
    `floor`,
    `bed_type`,
    `bed_count`,
    `bathroom_type`,
    `amenities`
) VALUES
(
    1,
    '101',
    'Standard',
    'Standard Single',
    'Comfortable room suitable for one or two guests.',
    2,
    2,
    85.00,
    'USD',
    'available',
    1,
    1,
    'Single',
    1,
    'Private',
    JSON_ARRAY(
        'WiFi',
        'TV',
        'Air Conditioning',
        'Desk'
    )
),
(
    2,
    '102',
    'Standard',
    'Standard Double',
    'Spacious double room with modern amenities.',
    2,
    2,
    110.00,
    'USD',
    'available',
    1,
    1,
    'Double',
    1,
    'Private',
    JSON_ARRAY(
        'WiFi',
        'TV',
        'Air Conditioning',
        'Mini Fridge'
    )
),
(
    3,
    '201',
    'Deluxe',
    'Deluxe King',
    'Large king room with upgraded furnishings.',
    2,
    2,
    160.00,
    'USD',
    'available',
    1,
    2,
    'King',
    1,
    'Private',
    JSON_ARRAY(
        'WiFi',
        'Smart TV',
        'Air Conditioning',
        'Mini Bar',
        'Room Service'
    )
),
(
    4,
    '202',
    'Deluxe',
    'Deluxe Twin',
    'Deluxe twin room suitable for business and leisure guests.',
    2,
    2,
    155.00,
    'USD',
    'available',
    1,
    2,
    'Twin',
    2,
    'Private',
    JSON_ARRAY(
        'WiFi',
        'Smart TV',
        'Air Conditioning',
        'Mini Fridge',
        'Desk'
    )
),
(
    5,
    '301',
    'Suite',
    'Executive Suite',
    'Premium suite with separate living area.',
    4,
    4,
    250.00,
    'USD',
    'available',
    1,
    3,
    'King',
    1,
    'Private',
    JSON_ARRAY(
        'WiFi',
        'Smart TV',
        'Air Conditioning',
        'Mini Bar',
        'Living Room',
        'Room Service',
        'Safe'
    )
),
(
    6,
    '302',
    'Suite',
    'Family Suite',
    'Large suite designed for families.',
    5,
    5,
    285.00,
    'USD',
    'available',
    1,
    3,
    'King + Twin',
    2,
    'Private',
    JSON_ARRAY(
        'WiFi',
        'Smart TV',
        'Air Conditioning',
        'Mini Fridge',
        'Living Room',
        'Room Service'
    )
),
(
    7,
    '401',
    'Presidential',
    'Presidential Suite',
    'Luxury suite with premium hotel services.',
    4,
    4,
    500.00,
    'USD',
    'available',
    1,
    4,
    'King',
    1,
    'Luxury',
    JSON_ARRAY(
        'WiFi',
        'Smart TV',
        'Air Conditioning',
        'Mini Bar',
        'Living Room',
        'Dining Area',
        'Jacuzzi',
        'Room Service',
        'Safe'
    )
),
(
    8,
    '402',
    'Standard',
    'Accessible Room',
    'Accessible room with suitable facilities.',
    2,
    2,
    100.00,
    'USD',
    'available',
    1,
    4,
    'Double',
    1,
    'Accessible',
    JSON_ARRAY(
        'WiFi',
        'TV',
        'Air Conditioning',
        'Accessible Bathroom'
    )
)
ON DUPLICATE KEY UPDATE
    `room_type` = VALUES(`room_type`),
    `name` = VALUES(`name`),
    `price_per_night` = VALUES(`price_per_night`),
    `status` = VALUES(`status`),
    `is_available` = VALUES(`is_available`);


-- ============================================================
-- GUESTS
-- ============================================================

INSERT INTO `guests` (
    `id`,
    `first_name`,
    `last_name`,
    `email`,
    `phone`,
    `date_of_birth`,
    `gender`,
    `nationality`,
    `id_type`,
    `id_number`,
    `address`,
    `city`,
    `state`,
    `country`,
    `postal_code`,
    `emergency_contact_name`,
    `emergency_contact_phone`,
    `notes`
) VALUES
(
    1,
    'Robert',
    'Johnson',
    'robert.johnson@example.com',
    '+14155550101',
    '1985-04-12',
    'Male',
    'American',
    'Passport',
    'P100001',
    '123 Main Street',
    'San Francisco',
    'California',
    'USA',
    '94105',
    'Emily Johnson',
    '+14155550102',
    'Business traveler'
),
(
    2,
    'Emily',
    'Williams',
    'emily.williams@example.com',
    '+14155550103',
    '1990-08-21',
    'Female',
    'American',
    'Passport',
    'P100002',
    '456 Oak Avenue',
    'Los Angeles',
    'California',
    'USA',
    '90001',
    'James Williams',
    '+14155550104',
    'Frequent guest'
),
(
    3,
    'Daniel',
    'Brown',
    'daniel.brown@example.com',
    '+14155550105',
    '1978-11-03',
    'Male',
    'British',
    'Passport',
    'P100003',
    '78 High Street',
    'London',
    NULL,
    'United Kingdom',
    'SW1A 1AA',
    'Sarah Brown',
    '+44205550106',
    NULL
),
(
    4,
    'Sophia',
    'Davis',
    'sophia.davis@example.com',
    '+14155550107',
    '1995-02-17',
    'Female',
    'Canadian',
    'Passport',
    'P100004',
    '90 King Street',
    'Toronto',
    'Ontario',
    'Canada',
    'M5H 1J9',
    'Michael Davis',
    '+14155550108',
    NULL
),
(
    5,
    'James',
    'Miller',
    'james.miller@example.com',
    '+14155550109',
    '1982-06-30',
    'Male',
    'Australian',
    'Passport',
    'P100005',
    '15 George Street',
    'Sydney',
    'NSW',
    'Australia',
    '2000',
    'Laura Miller',
    '+61255501010',
    'VIP guest'
)
ON DUPLICATE KEY UPDATE
    `first_name` = VALUES(`first_name`),
    `last_name` = VALUES(`last_name`),
    `phone` = VALUES(`phone`);


-- ============================================================
-- STAFF
-- ============================================================

INSERT INTO `staff` (
    `id`,
    `user_id`,
    `employee_number`,
    `first_name`,
    `last_name`,
    `email`,
    `phone`,
    `department`,
    `position`,
    `employment_type`,
    `hire_date`,
    `status`,
    `salary`,
    `salary_currency`
) VALUES
(
    1,
    2,
    'EMP-0001',
    'John',
    'Manager',
    'manager@hotel.test',
    '+10000000002',
    'Management',
    'Hotel Manager',
    'full_time',
    '2023-01-15',
    'active',
    65000.00,
    'USD'
),
(
    2,
    3,
    'EMP-0002',
    'Sarah',
    'Receptionist',
    'reception@hotel.test',
    '+10000000003',
    'Front Desk',
    'Receptionist',
    'full_time',
    '2024-02-01',
    'active',
    42000.00,
    'USD'
),
(
    3,
    4,
    'EMP-0003',
    'Michael',
    'Accountant',
    'accountant@hotel.test',
    '+10000000004',
    'Finance',
    'Accountant',
    'full_time',
    '2023-08-10',
    'active',
    52000.00,
    'USD'
),
(
    4,
    5,
    'EMP-0004',
    'David',
    'Staff',
    'staff@hotel.test',
    '+10000000005',
    'Housekeeping',
    'Housekeeping Staff',
    'full_time',
    '2024-05-20',
    'active',
    36000.00,
    'USD'
)
ON DUPLICATE KEY UPDATE
    `user_id` = VALUES(`user_id`),
    `first_name` = VALUES(`first_name`),
    `last_name` = VALUES(`last_name`),
    `status` = VALUES(`status`);


-- ============================================================
-- BOOKINGS
-- ============================================================

INSERT INTO `bookings` (
    `id`,
    `booking_reference`,
    `guest_id`,
    `room_id`,
    `check_in`,
    `check_out`,
    `number_of_guests`,
    `adults`,
    `children`,
    `number_of_nights`,
    `room_rate`,
    `subtotal`,
    `discount`,
    `tax`,
    `total_amount`,
    `currency`,
    `status`,
    `payment_status`,
    `source`,
    `special_requests`,
    `notes`
) VALUES
(
    1,
    'BK-20260001',
    1,
    3,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY),
    DATE_ADD(CURDATE(), INTERVAL 5 DAY),
    2,
    2,
    0,
    3,
    160.00,
    480.00,
    0.00,
    48.00,
    528.00,
    'USD',
    'confirmed',
    'paid',
    'website',
    'Late check-in requested.',
    'Demo confirmed booking.'
),
(
    2,
    'BK-20260002',
    2,
    5,
    DATE_ADD(CURDATE(), INTERVAL 7 DAY),
    DATE_ADD(CURDATE(), INTERVAL 10 DAY),
    2,
    2,
    0,
    3,
    250.00,
    750.00,
    50.00,
    70.00,
    770.00,
    'USD',
    'confirmed',
    'partial',
    'reception',
    'High-floor room preferred.',
    'Demo partial-payment booking.'
),
(
    3,
    'BK-20260003',
    3,
    1,
    DATE_SUB(CURDATE(), INTERVAL 10 DAY),
    DATE_SUB(CURDATE(), INTERVAL 7 DAY),
    1,
    1,
    0,
    3,
    85.00,
    255.00,
    0.00,
    25.50,
    280.50,
    'USD',
    'completed',
    'paid',
    'website',
    NULL,
    'Completed historical booking.'
),
(
    4,
    'BK-20260004',
    4,
    2,
    DATE_ADD(CURDATE(), INTERVAL 14 DAY),
    DATE_ADD(CURDATE(), INTERVAL 17 DAY),
    2,
    2,
    0,
    3,
    110.00,
    330.00,
    0.00,
    33.00,
    363.00,
    'USD',
    'pending',
    'unpaid',
    'phone',
    NULL,
    'Pending demo booking.'
),
(
    5,
    'BK-20260005',
    5,
    7,
    DATE_SUB(CURDATE(), INTERVAL 20 DAY),
    DATE_SUB(CURDATE(), INTERVAL 17 DAY),
    2,
    2,
    0,
    3,
    500.00,
    1500.00,
    100.00,
    140.00,
    1540.00,
    'USD',
    'completed',
    'paid',
    'website',
    'Airport transfer requested.',
    'VIP historical booking.'
)
ON DUPLICATE KEY UPDATE
    `status` = VALUES(`status`),
    `payment_status` = VALUES(`payment_status`),
    `total_amount` = VALUES(`total_amount`);


-- ============================================================
-- PAYMENTS
-- ============================================================

INSERT INTO `payments` (
    `id`,
    `payment_reference`,
    `booking_id`,
    `guest_id`,
    `amount`,
    `currency`,
    `payment_method`,
    `status`,
    `transaction_id`,
    `gateway`,
    `refunded_amount`,
    `invoice_number`,
    `receipt_number`,
    `description`,
    `paid_at`
) VALUES
(
    1,
    'PAY-20260001',
    1,
    1,
    528.00,
    'USD',
    'card',
    'completed',
    'DEMO-TXN-0001',
    'demo_gateway',
    0.00,
    'INV-20260001',
    'REC-20260001',
    'Full payment for booking BK-20260001',
    NOW()
),
(
    2,
    'PAY-20260002',
    2,
    2,
    385.00,
    'USD',
    'card',
    'completed',
    'DEMO-TXN-0002',
    'demo_gateway',
    0.00,
    'INV-20260002',
    'REC-20260002',
    'Partial payment for booking BK-20260002',
    NOW()
),
(
    3,
    'PAY-20260003',
    3,
    3,
    280.50,
    'USD',
    'cash',
    'completed',
    NULL,
    NULL,
    0.00,
    'INV-20260003',
    'REC-20260003',
    'Full cash payment for completed booking',
    DATE_SUB(NOW(), INTERVAL 7 DAY)
),
(
    4,
    'PAY-20260004',
    5,
    5,
    1540.00,
    'USD',
    'bank_transfer',
    'completed',
    'DEMO-TXN-0004',
    'demo_bank',
    0.00,
    'INV-20260004',
    'REC-20260004',
    'Full payment for VIP booking',
    DATE_SUB(NOW(), INTERVAL 17 DAY)
)
ON DUPLICATE KEY UPDATE
    `status` = VALUES(`status`),
    `amount` = VALUES(`amount`),
    `refunded_amount` = VALUES(`refunded_amount`);


-- ============================================================
-- RESET AUTO_INCREMENT VALUES
-- ============================================================

ALTER TABLE `users`
    AUTO_INCREMENT = 6;

ALTER TABLE `guests`
    AUTO_INCREMENT = 6;

ALTER TABLE `rooms`
    AUTO_INCREMENT = 9;

ALTER TABLE `staff`
    AUTO_INCREMENT = 5;

ALTER TABLE `bookings`
    AUTO_INCREMENT = 6;

ALTER TABLE `payments`
    AUTO_INCREMENT = 5;


-- ============================================================
-- RE-ENABLE FOREIGN KEYS
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- SEED COMPLETE
-- ============================================================

SELECT
    'Hotel Management System seed data inserted successfully.'
    AS `message`;

SELECT
    COUNT(*) AS `users`
FROM `users`;

SELECT
    COUNT(*) AS `rooms`
FROM `rooms`;

SELECT
    COUNT(*) AS `guests`
FROM `guests`;

SELECT
    COUNT(*) AS `staff`
FROM `staff`;

SELECT
    COUNT(*) AS `bookings`
FROM `bookings`;

SELECT
    COUNT(*) AS `payments`
FROM `payments`;
