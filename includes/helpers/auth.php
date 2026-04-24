<?php
/**
 * includes/helpers/auth.php
 * Authentication helper functions
 */

/**
 * Authenticate user with username and password
 * Returns array with user info or null if failed
 */
function authenticateUser($username, $password)
{
    try {
        $users = require __DIR__ . '/../users_config.php';
        
        if (!isset($users[$username])) {
            return null;
        }
        
        $user = $users[$username];
        
        // Check if user is active
        if (!$user['active']) {
            return null;
        }
        
        // Verify password using bcrypt
        if (!password_verify($password, $user['password'])) {
            return null;
        }
        
        // Return user info (excluding password)
        return [
            'username' => $username,
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'email' => $user['email']
        ];
    } catch (Exception $e) {
        error_log("Auth Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if user has required role
 */
function hasRole($requiredRole)
{
    if (!isset($_SESSION['admin_role'])) {
        return false;
    }
    
    $userRole = $_SESSION['admin_role'];
    $requiredRoles = is_array($requiredRole) ? $requiredRole : [$requiredRole];
    
    return in_array($userRole, $requiredRoles);
}

/**
 * Check if user is admin
 */
function isAdmin()
{
    return hasRole('admin');
}

/**
 * Check if user is staff
 */
function isStaff()
{
    return hasRole('staff');
}

/**
 * Require specific role, redirect to login if not authorized
 */
function requireRole($requiredRole)
{
    if (!hasRole($requiredRole)) {
        http_response_code(403);
        die('Access denied. Required role: ' . (is_array($requiredRole) ? implode(', ', $requiredRole) : $requiredRole));
    }
}

/**
 * Get all users from config
 */
function getAllUsers()
{
    try {
        return require __DIR__ . '/../users_config.php';
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get user by username
 */
function getUserByUsername($username)
{
    $users = getAllUsers();
    return isset($users[$username]) ? $users[$username] : null;
}

?>
