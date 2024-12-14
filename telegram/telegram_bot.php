<?php
// telegram_bot.php

class TelegramBot {
    private $botToken;
    private $chatId;
    private const TELEGRAM_API = 'https://api.telegram.org/bot';
    private const MAX_RETRIES = 3;

    public function __construct($botToken, $chatId) {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
    }

    // Send text message with optional formatting
    public function sendMessage($message, $parse_mode = 'HTML', $disable_notification = false) {
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => $parse_mode,
            'disable_notification' => $disable_notification
        ];
        
        return $this->makeRequest('sendMessage', $data);
    }

    // Send alert for new paperwork submission
    public function sendPaperworkAlert($ppw_id, $user_name, $title) {
        $message = "🔔 *New Paperwork Submission*\n\n" .
                  "ID: `$ppw_id`\n" .
                  "From: $user_name\n" .
                  "Title: $title\n" .
                  "Time: " . date('Y-m-d H:i:s');
        
        return $this->sendMessage($message, 'Markdown');
    }

    // Send login failure alert
    public function sendLoginAlert($email, $ip, $attempt_count) {
        $message = "⚠️ *Failed Login Attempt*\n\n" .
                  "Email: `$email`\n" .
                  "IP: `$ip`\n" .
                  "Attempt #: $attempt_count\n" .
                  "Time: " . date('Y-m-d H:i:s');
        
        return $this->sendMessage($message, 'Markdown');
    }

    // Send system error alert
    public function sendErrorAlert($error_type, $message, $file = null, $line = null) {
        $alert = "🚨 *System Error*\n\n" .
                "Type: `$error_type`\n" .
                "Message: `$message`\n";
        
        if ($file && $line) {
            $alert .= "Location: `$file:$line`\n";
        }
        
        $alert .= "Time: " . date('Y-m-d H:i:s');
        
        return $this->sendMessage($alert, 'Markdown', false);
    }

    // Make HTTP request to Telegram API with retry mechanism
    private function makeRequest($method, $data, $attempts = 0) {
        $url = self::TELEGRAM_API . $this->botToken . '/' . $method;
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ];

        try {
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            if ($result === false && $attempts < self::MAX_RETRIES) {
                sleep(1); // Wait before retry
                return $this->makeRequest($method, $data, $attempts + 1);
            }
            
            return json_decode($result, true);
        } catch (Exception $e) {
            error_log("Telegram API Error: " . $e->getMessage());
            return false;
        }
    }
}