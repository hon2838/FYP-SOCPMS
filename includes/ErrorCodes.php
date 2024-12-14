<?php
class ErrorCodes {
    // Authentication Errors (1000-1099)
    const AUTH_INVALID_CREDENTIALS = 1001;
    const AUTH_SESSION_EXPIRED = 1002;
    const AUTH_INSUFFICIENT_PERMISSIONS = 1003;
    const AUTH_RATE_LIMIT_EXCEEDED = 1004;
    const AUTH_ACCOUNT_LOCKED = 1005;

    // User Management Errors (1100-1199)
    const USER_NOT_FOUND = 1100;
    const USER_CREATE_FAILED = 1101;
    const USER_UPDATE_FAILED = 1102;
    const USER_DELETE_FAILED = 1103;
    const USER_EMAIL_EXISTS = 1104;
    const USER_INVALID_ROLE = 1105;

    // Permission Errors (1200-1299)
    const PERMISSION_DENIED = 1200;
    const PERMISSION_INVALID = 1201;
    const PERMISSION_ASSIGNMENT_FAILED = 1202;
    const PERMISSION_ROLE_NOT_FOUND = 1203;

    // Paperwork Errors (1300-1399)
    const PAPERWORK_NOT_FOUND = 1300;
    const PAPERWORK_CREATE_FAILED = 1301;
    const PAPERWORK_UPDATE_FAILED = 1302;
    const PAPERWORK_DELETE_FAILED = 1303;
    const PAPERWORK_INVALID_STATUS = 1304;

    // File Operation Errors (1400-1499)
    const FILE_UPLOAD_FAILED = 1400;
    const FILE_TYPE_NOT_ALLOWED = 1401;
    const FILE_SIZE_EXCEEDED = 1402;
    const FILE_NOT_FOUND = 1403;
    const FILE_DELETE_FAILED = 1404;

    // Database Errors (1500-1599)
    const DB_CONNECTION_FAILED = 1500;
    const DB_QUERY_FAILED = 1501;
    const DB_TRANSACTION_FAILED = 1502;
    const DB_CONSTRAINT_VIOLATION = 1503;
    const DB_DUPLICATE_ENTRY = 1504;

    // Input Validation Errors (1600-1699)
    const INPUT_REQUIRED_MISSING = 1600;
    const INPUT_INVALID_FORMAT = 1601;
    const INPUT_EXCEEDS_LENGTH = 1602;
    const INPUT_CONTAINS_INVALID_CHARS = 1603;

    public static $ERROR_MESSAGES = [
        // Authentication messages
        1001 => 'Invalid email or password',
        1002 => 'Your session has expired. Please login again',
        1003 => 'You do not have permission to perform this action',
        1004 => 'Too many login attempts. Please try again later',
        1005 => 'Account is locked. Please contact administrator',

        // User management messages
        1100 => 'User not found',
        1101 => 'Failed to create new user',
        1102 => 'Failed to update user information',
        1103 => 'Failed to delete user',
        1104 => 'Email address already exists',
        1105 => 'Invalid role selected',

        // Permission messages
        1200 => 'Access denied',
        1201 => 'Invalid permission type',
        1202 => 'Failed to assign permissions',
        1203 => 'Role not found',

        // Paperwork messages
        1300 => 'Paperwork not found',
        1301 => 'Failed to create paperwork',
        1302 => 'Failed to update paperwork',
        1303 => 'Failed to delete paperwork',
        1304 => 'Invalid paperwork status',

        // File operation messages
        1400 => 'File upload failed',
        1401 => 'File type not allowed',
        1402 => 'File size exceeds limit',
        1403 => 'File not found',
        1404 => 'Failed to delete file',

        // Database messages
        1500 => 'Database connection failed',
        1501 => 'Database query failed',
        1502 => 'Database transaction failed',
        1503 => 'Database constraint violation',
        1504 => 'Duplicate entry found',

        // Input validation messages
        1600 => 'Required field is missing',
        1601 => 'Invalid input format',
        1602 => 'Input exceeds maximum length',
        1603 => 'Input contains invalid characters'
    ];

    public static function getMessage($code) {
        return self::$ERROR_MESSAGES[$code] ?? 'Unknown error';
    }

    public static function getAllMessages() {
        return self::$ERROR_MESSAGES;
    }
}