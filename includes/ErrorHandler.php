<?php
class ErrorHandler {
    private static $instance = null;
    private $logFile;
    private $errorStats = [];
    
    private function __construct() {
        $this->logFile = 'errors.log';
        $this->initErrorStats();
    }
    
    private function initErrorStats() {
        if (!isset($_SESSION['error_stats'])) {
            $_SESSION['error_stats'] = [
                'count' => 0,
                'types' => [],
                'last_error' => null
            ];
        }
        $this->errorStats = &$_SESSION['error_stats'];
    }
    
    private function updateErrorStats($errorData) {
        $this->errorStats['count']++;
        $this->errorStats['types'][$errorData['code']] = 
            ($this->errorStats['types'][$errorData['code']] ?? 0) + 1;
        $this->errorStats['last_error'] = [
            'timestamp' => $errorData['timestamp'],
            'code' => $errorData['code']
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ErrorHandler();
        }
        return self::$instance;
    }
    
    public function handleError($error, $redirectUrl = null) {
        $errorData = $this->formatError($error);
        $this->logError($errorData);
        $this->updateErrorStats($errorData);
        
        // Alert admin if error threshold exceeded
        if ($this->errorStats['count'] > 10) {
            $this->alertAdmin();
        }
        
        return $this->sendResponse($errorData, $redirectUrl);
    }
    
    private function formatError($error) {
        if ($error instanceof Exception) {
            return [
                'error' => true,
                'code' => $error->getCode() ?: ErrorCodes::DB_QUERY_FAILED,
                'message' => $error->getMessage(),
                'details' => ErrorCodes::getMessage($error->getCode()),
                'timestamp' => date('Y-m-d H:i:s'),
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)
            ];
        }
        
        return [
            'error' => true,
            'code' => ErrorCodes::DB_QUERY_FAILED,
            'message' => is_string($error) ? $error : 'An unexpected error occurred',
            'details' => ErrorCodes::getMessage(ErrorCodes::DB_QUERY_FAILED),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function logError($errorData) {
        error_log(json_encode($errorData, JSON_PRETTY_PRINT) . "\n", 3, $this->logFile);
    }
    
    private function handleJsonResponse($errorData) {
        header('Content-Type: application/json');
        return json_encode($errorData);
    }
    
    private function sendResponse($errorData, $redirectUrl) {
        if (headers_sent()) {
            return $this->handleJsonResponse($errorData);
        }
        
        if ($redirectUrl) {
            $_SESSION['error_message'] = $errorData['message'];
            header("Location: $redirectUrl");
            exit;
        }
        
        return $this->handleJsonResponse($errorData);
    }
    
    private function alertAdmin() {
        // Code to alert admin
    }
}