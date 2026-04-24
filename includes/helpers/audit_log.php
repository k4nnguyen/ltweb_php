<?php
/**
 * includes/helpers/audit_log.php
 * System Audit Log - File-based logging for admin actions
 */

// Constants for log types
const LOG_ACTION_CREATE = 'CREATE';
const LOG_ACTION_UPDATE = 'UPDATE';
const LOG_ACTION_DELETE = 'DELETE';
const LOG_ACTION_VIEW = 'VIEW';
const LOG_ACTION_LOGIN = 'LOGIN';
const LOG_ACTION_LOGOUT = 'LOGOUT';
const LOG_ACTION_SEARCH = 'SEARCH';

/**
 * Get log file path
 */
function getLogFilePath()
{
    $logDir = __DIR__ . '/../../logs';
    
    // Create logs directory if not exists
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    return $logDir . '/system_audit.log';
}

/**
 * Write log entry to file
 * Format: [2024-04-23 14:30:45] [admin01] [DELETE] Đã xóa hồ sơ HS005
 */
function logAction($action, $description, $targetId = '')
{
    try {
        // Get current user info
        $username = $_SESSION['admin_id'] ?? 'unknown';
        $userRole = $_SESSION['admin_role'] ?? 'unknown';
        
        // Format: [DATETIME] [USERNAME] [ROLE] [ACTION] Description | TargetId
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] [%s] [%s] [%s] %s %s\n",
            $timestamp,
            $username,
            strtoupper($userRole),
            $action,
            $description,
            $targetId ? "| ID: " . $targetId : ''
        );
        
        $logFile = getLogFilePath();
        
        // Append to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        return true;
    } catch (Exception $e) {
        error_log("Audit Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all log entries as array
 */
function getAllLogs($limit = 500)
{
    try {
        $logFile = getLogFilePath();
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Get last N entries (reverse order - newest first)
        $lines = array_reverse($lines);
        $logs = array_slice($lines, 0, $limit);
        
        $parsedLogs = [];
        foreach ($logs as $line) {
            $parsed = parseLogLine($line);
            if ($parsed) {
                $parsedLogs[] = $parsed;
            }
        }
        
        return $parsedLogs;
    } catch (Exception $e) {
        error_log("Get Logs Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Parse a log line into structured data
 */
function parseLogLine($line)
{
    // Pattern: [DATETIME] [USERNAME] [ROLE] [ACTION] Description | ID: xxx
    if (preg_match('/\[(.*?)\]\s+\[(.*?)\]\s+\[(.*?)\]\s+\[(.*?)\]\s+(.*)/i', $line, $matches)) {
        return [
            'timestamp' => $matches[1],
            'username' => $matches[2],
            'role' => $matches[3],
            'action' => $matches[4],
            'description' => trim($matches[5]),
            'raw_line' => $line
        ];
    }
    
    return null;
}

/**
 * Get logs filtered by action
 */
function getLogsByAction($action, $limit = 100)
{
    $logs = getAllLogs($limit * 2); // Get more to account for filtering
    
    return array_filter($logs, function($log) use ($action) {
        return strtoupper($log['action']) === strtoupper($action);
    });
}

/**
 * Get logs filtered by username
 */
function getLogsByUsername($username, $limit = 100)
{
    $logs = getAllLogs($limit * 2);
    
    return array_filter($logs, function($log) use ($username) {
        return $log['username'] === $username;
    });
}

/**
 * Get logs for a specific date
 */
function getLogsByDate($date, $limit = 500)
{
    $logs = getAllLogs($limit);
    
    return array_filter($logs, function($log) use ($date) {
        return strpos($log['timestamp'], $date) === 0;
    });
}

/**
 * Clear log file (only for admin)
 */
function clearLogs()
{
    if (!isAdmin()) {
        return false;
    }
    
    try {
        $logFile = getLogFilePath();
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Export logs to CSV format
 */
function exportLogsAsCSV($filename = null)
{
    if (!isAdmin()) {
        return false;
    }
    
    try {
        $logs = getAllLogs(10000);
        
        if (!$filename) {
            $filename = 'audit_log_' . date('Y-m-d_H-i-s') . '.csv';
        }
        
        $output = "Thời gian,Tài khoản,Vai trò,Hành động,Mô tả\n";
        
        foreach ($logs as $log) {
            $output .= sprintf(
                '"%s","%s","%s","%s","%s"' . "\n",
                $log['timestamp'],
                $log['username'],
                $log['role'],
                $log['action'],
                str_replace('"', '""', $log['description'])
            );
        }
        
        return $output;
    } catch (Exception $e) {
        return false;
    }
}

?>
