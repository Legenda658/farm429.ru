<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
function logDebug($message, $type = 'INFO') {
    try {
        $logFile = __DIR__ . '/../debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $logMessage = "[$timestamp] [$type] [IP: $ip] [User: $user] [URL: $url] $message\n";
        if (is_writable(dirname($logFile))) {
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    } catch (Exception $e) {
    }
}
function logUserAction($message) {
    try {
        $logDir = __DIR__ . '/../logs';
        if (!file_exists($logDir) && is_writable(dirname($logDir))) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/user_actions.log';
        if (!is_writable($logDir)) {
            return;
        }
        $timestamp = date('Y-m-d H:i:s');
        $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Гость';
        $ip = $_SERVER['REMOTE_ADDR'];
        $logMessage = "[$timestamp] [$ip] [$user] $message" . PHP_EOL;
        error_log($logMessage, 3, $logFile);
    } catch (Exception $e) {
    }
}
function logError($message) {
    try {
        $logDir = __DIR__ . '/../logs';
        if (!file_exists($logDir) && is_writable(dirname($logDir))) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/errors.log';
        if (!is_writable($logDir)) {
            return;
        }
        $timestamp = date('Y-m-d H:i:s');
        $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Гость';
        $ip = $_SERVER['REMOTE_ADDR'];
        $logMessage = "[$timestamp] [$ip] [$user] $message" . PHP_EOL;
        error_log($logMessage, 3, $logFile);
    } catch (Exception $e) {
    }
}
function logSQL($query, $params = []) {
    try {
        $message = "SQL Query: $query";
        if (!empty($params)) {
            $message .= " Params: " . json_encode($params);
        }
        logDebug($message, 'SQL');
    } catch (Exception $e) {
    }
}
$logDir = __DIR__ . '/../logs';
if (!file_exists($logDir) && is_writable(dirname($logDir))) {
    try {
        mkdir($logDir, 0755, true);
    } catch (Exception $e) {
    }
}
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    try {
        logError("Ошибка [$errno]: $errstr в файле $errfile на строке $errline");
    } catch (Exception $e) {
    }
    return false;
});
set_exception_handler(function($exception) {
    try {
        logError("Необработанное исключение: " . $exception->getMessage() . 
                 " в файле " . $exception->getFile() . 
                 " на строке " . $exception->getLine());
    } catch (Exception $e) {
    }
});
try {
    logDebug("Request started");
} catch (Exception $e) {
} 