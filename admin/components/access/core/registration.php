<?php
/**
 * Access Component - Registration Functions
 * Handles user registration, approval workflow, account creation
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/authentication.php';

/**
 * Create registration request
 * @param array $registrationData Registration data
 * @return array ['success' => bool, 'registration_id' => int|null, 'message' => string]
 */
function access_create_registration($registrationData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Validate account type
    $accountType = access_get_account_type($registrationData['account_type_id']);
    if (!$accountType || !$accountType['is_active']) {
        return ['success' => false, 'message' => 'Invalid account type'];
    }
    
    // Validate email
    if (!access_validate_email($registrationData['email'])) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    // Check if email already exists
    if (access_get_user_by_email($registrationData['email'])) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Store submitted data as JSON
    $submittedData = json_encode($registrationData);
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_registrations (account_type_id, email, submitted_data, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iss", $registrationData['account_type_id'], $registrationData['email'], $submittedData);
        
        if ($stmt->execute()) {
            $registrationId = $conn->insert_id;
            $stmt->close();
            
            // If auto-approve, process immediately
            if ($accountType['auto_approve']) {
                return access_approve_registration($registrationId);
            }
            
            return [
                'success' => true,
                'registration_id' => $registrationId,
                'message' => 'Registration submitted. Awaiting approval.'
            ];
        }
        
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to create registration'];
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error creating registration: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Approve registration
 * @param int $registrationId Registration ID
 * @param int $approvedBy User ID who approved
 * @return array ['success' => bool, 'account_id' => int|null, 'user_id' => int|null, 'message' => string]
 */
function access_approve_registration($registrationId, $approvedBy = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Get registration
    $registration = access_get_registration($registrationId);
    if (!$registration) {
        return ['success' => false, 'message' => 'Registration not found'];
    }
    
    if ($registration['status'] !== 'pending') {
        return ['success' => false, 'message' => 'Registration already processed'];
    }
    
    $submittedData = json_decode($registration['submitted_data'], true);
    $accountType = access_get_account_type($registration['account_type_id']);
    
    // Create user
    $passwordHash = access_hash_password($submittedData['password'] ?? access_generate_token(16));
    $userId = access_create_user([
        'email' => $registration['email'],
        'username' => $submittedData['username'] ?? null,
        'password_hash' => $passwordHash,
        'first_name' => $submittedData['first_name'] ?? null,
        'last_name' => $submittedData['last_name'] ?? null,
        'phone' => $submittedData['phone'] ?? null,
        'status' => 'pending_verification'
    ]);
    
    if (!$userId) {
        return ['success' => false, 'message' => 'Failed to create user'];
    }
    
    // Create account
    $accountId = access_create_account([
        'account_type_id' => $registration['account_type_id'],
        'account_name' => $submittedData['account_name'] ?? $submittedData['first_name'] . ' ' . $submittedData['last_name'],
        'email' => $registration['email'],
        'phone' => $submittedData['phone'] ?? null,
        'status' => 'active',
        'approved_at' => date('Y-m-d H:i:s'),
        'approved_by' => $approvedBy
    ]);
    
    if (!$accountId) {
        // Rollback user creation
        access_delete_user($userId);
        return ['success' => false, 'message' => 'Failed to create account'];
    }
    
    // Save custom field data
    $fields = access_get_account_type_fields($registration['account_type_id']);
    foreach ($fields as $field) {
        if (isset($submittedData[$field['field_name']])) {
            access_set_account_field_value($accountId, $field['field_name'], $submittedData[$field['field_name']]);
        }
    }
    
    // Add user to account with default role
    $defaultRole = access_get_role_by_slug('user');
    if ($defaultRole) {
        access_add_user_to_account($userId, $accountId, $defaultRole['id'], true);
    }
    
    // Update registration status
    $stmt = $conn->prepare("UPDATE access_registrations SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $approvedBy, $registrationId);
    $stmt->execute();
    $stmt->close();
    
    // Generate email verification token if required
    if (access_get_parameter('Registration', 'require_email_verification', 'yes') === 'yes') {
        access_generate_email_verification_token($userId);
    }
    
    return [
        'success' => true,
        'account_id' => $accountId,
        'user_id' => $userId,
        'message' => 'Registration approved successfully'
    ];
}

/**
 * Reject registration
 * @param int $registrationId Registration ID
 * @param string $reason Rejection reason
 * @param int $rejectedBy User ID who rejected
 * @return bool Success
 */
function access_reject_registration($registrationId, $reason, $rejectedBy = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE access_registrations SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("isi", $rejectedBy, $reason, $registrationId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error rejecting registration: " . $e->getMessage());
        return false;
    }
}

/**
 * Get registration by ID
 * @param int $registrationId Registration ID
 * @return array|null Registration data or null
 */
function access_get_registration($registrationId) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_registrations WHERE id = ?");
        $stmt->bind_param("i", $registrationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $registration = $result->fetch_assoc();
        $stmt->close();
        return $registration;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting registration: " . $e->getMessage());
        return null;
    }
}

/**
 * List registrations with filters
 * @param array $filters Filters (status, account_type_id, limit, offset)
 * @return array Registrations list
 */
function access_list_registrations($filters = []) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['account_type_id'])) {
        $where[] = "account_type_id = ?";
        $params[] = (int)$filters['account_type_id'];
        $types .= 'i';
    }
    
    $sql = "SELECT r.*, at.name as account_type_name FROM access_registrations r LEFT JOIN access_account_types at ON r.account_type_id = at.id";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY r.created_at DESC";
    
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT ?";
        $params[] = (int)$filters['limit'];
        $types .= 'i';
        
        if (!empty($filters['offset'])) {
            $sql .= " OFFSET ?";
            $params[] = (int)$filters['offset'];
            $types .= 'i';
        }
    }
    
    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $registrations = [];
        while ($row = $result->fetch_assoc()) {
            $registrations[] = $row;
        }
        $stmt->close();
        return $registrations;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error listing registrations: " . $e->getMessage());
        return [];
    }
}

