<?php
// includes/logger.php - Advanced Logging System

class Logger
{
    private static $logDir = __DIR__ . '/../logs';

    /**
     * Log webhook API requests and responses
     */
    public static function logWebhook($action, $data, $response = null, $error = null)
    {
        $logFile = self::$logDir . '/webhook_debug_' . date('Y-m-d') . '.log';

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'data' => $data,
            'response' => $response,
            'error' => $error,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ];

        $logLine = date('[Y-m-d H:i:s] ') . strtoupper($action) . PHP_EOL;
        $logLine .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
        $logLine .= "REQUEST DATA:" . PHP_EOL;
        $logLine .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

        if ($response) {
            $logLine .= PHP_EOL . "RESPONSE:" . PHP_EOL;
            $logLine .= json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }

        if ($error) {
            $logLine .= PHP_EOL . "⚠️ ERROR:" . PHP_EOL;
            $logLine .= json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }

        $logLine .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL . PHP_EOL;

        file_put_contents($logFile, $logLine, FILE_APPEND);
    }

    /**
     * Log Evolution API calls
     */
    public static function logEvolutionAPI($endpoint, $method, $requestData, $httpCode, $responseBody, $curlError = null)
    {
        $logFile = self::$logDir . '/evolution_api_' . date('Y-m-d') . '.log';

        $logLine = date('[Y-m-d H:i:s] ') . "$method $endpoint" . PHP_EOL;
        $logLine .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
        $logLine .= "HTTP CODE: $httpCode" . PHP_EOL;

        if ($curlError) {
            $logLine .= "⚠️ CURL ERROR: $curlError" . PHP_EOL;
        }

        $logLine .= PHP_EOL . "REQUEST:" . PHP_EOL;
        $logLine .= json_encode($requestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

        $logLine .= PHP_EOL . "RESPONSE:" . PHP_EOL;
        if (is_array($responseBody)) {
            $logLine .= json_encode($responseBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        } else {
            $logLine .= $responseBody . PHP_EOL;
        }

        $logLine .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL . PHP_EOL;

        file_put_contents($logFile, $logLine, FILE_APPEND);
    }

    /**
     * Log PHP errors
     */
    public static function logError($message, $context = [])
    {
        $logFile = self::$logDir . '/error_' . date('Y-m-d') . '.log';

        $logLine = date('[Y-m-d H:i:s] ') . "ERROR: $message" . PHP_EOL;
        if (!empty($context)) {
            $logLine .= "Context: " . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
        $logLine .= "Stack trace: " . PHP_EOL;
        $logLine .= print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), true) . PHP_EOL;
        $logLine .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;

        file_put_contents($logFile, $logLine, FILE_APPEND);
    }

    /**
     * Log general info
     */
    public static function logInfo($message, $data = null)
    {
        $logFile = self::$logDir . '/info_' . date('Y-m-d') . '.log';

        $logLine = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
        if ($data) {
            $logLine .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }

        file_put_contents($logFile, $logLine, FILE_APPEND);
    }
}

// Ensure logs directory exists
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}
