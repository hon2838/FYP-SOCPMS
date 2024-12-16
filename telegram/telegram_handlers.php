<?php
require_once 'telegram_bot.php';

function initTelegramBot() {
    $bot = new TelegramBot(
        getenv('TELEGRAM_BOT_TOKEN') ?: 'your_bot_token_here',
        getenv('TELEGRAM_CHAT_ID') ?: 'your_chat_id_here'
    );
    return $bot;
}

// Paperwork notification handler
function notifyPaperworkSubmission($ppw_id, $user_name, $title) {
    if (getenv('TELEGRAM_NOTIFICATION_ENABLED') !== 'false') {
        $bot = initTelegramBot();
        return $bot->sendPaperworkAlert($ppw_id, $user_name, $title);
    }
    return false;
}

// Login failure handler
function notifyLoginFailure($email, $ip, $attempt_count) {
    if (getenv('TELEGRAM_NOTIFICATION_ENABLED') !== 'false') {
        $bot = initTelegramBot();
        return $bot->sendLoginAlert($email, $ip, $attempt_count);
    }
    return false;
}

// System error handler
function notifySystemError($error_type, $message, $file = null, $line = null) {
    if (getenv('TELEGRAM_NOTIFICATION_ENABLED') !== 'false' && 
        getenv('TELEGRAM_ALERT_LEVEL') === 'error') {
        $bot = initTelegramBot();
        return $bot->sendErrorAlert($error_type, $message, $file, $line);
    }
    return false;
}