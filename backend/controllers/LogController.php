<?php

class LogController {
    private $logDir;
    
    public function __construct() {
        $this->logDir = __DIR__ . '/../logs';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    public function writeLog() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input']);
                return;
            }
            
            $level = $input['level'] ?? 'info';
            $message = $input['message'] ?? '';
            $data = $input['data'] ?? [];
            $timestamp = $input['timestamp'] ?? date('c');
            $url = $input['url'] ?? '';
            $userAgent = $input['userAgent'] ?? '';
            
            // Create log entry
            $logEntry = [
                'timestamp' => $timestamp,
                'level' => strtoupper($level),
                'message' => $message,
                'url' => $url,
                'userAgent' => $userAgent,
                'data' => $data
            ];
            
            // Format log line
            $logLine = sprintf(
                "[%s] %s: %s | URL: %s | Data: %s\n",
                $logEntry['timestamp'],
                $logEntry['level'],
                $logEntry['message'],
                $logEntry['url'],
                json_encode($logEntry['data'])
            );
            
            // Write to daily log file
            $logFile = $this->logDir . '/frontend_' . date('Y-m-d') . '.log';
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            
            // Also write to level-specific log file
            $levelLogFile = $this->logDir . '/frontend_' . strtolower($level) . '_' . date('Y-m-d') . '.log';
            file_put_contents($levelLogFile, $logLine, FILE_APPEND | LOCK_EX);
            
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Log written successfully']);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to write log: ' . $e->getMessage()]);
        }
    }
    
    public function getLogs() {
        try {
            $date = $_GET['date'] ?? date('Y-m-d');
            $level = $_GET['level'] ?? 'all';
            
            if ($level === 'all') {
                $logFile = $this->logDir . '/frontend_' . $date . '.log';
            } else {
                $logFile = $this->logDir . '/frontend_' . strtolower($level) . '_' . $date . '.log';
            }
            
            if (!file_exists($logFile)) {
                http_response_code(404);
                echo json_encode(['error' => 'Log file not found']);
                return;
            }
            
            $logs = file_get_contents($logFile);
            
            header('Content-Type: text/plain');
            echo $logs;
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to read logs: ' . $e->getMessage()]);
        }
    }
}