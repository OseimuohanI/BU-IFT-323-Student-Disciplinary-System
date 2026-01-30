<?php
// Bridge config file for legacy includes.
// Loads the actual configuration from config/config.php.

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}
