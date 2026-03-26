<?php

/**
 * Root index.php — Plesk Bridge File
 *
 * Plesk document root points to httpdocs/ (project root).
 * This file routes all requests through public/index.php
 * so Laravel works correctly without changing the document root.
 */

define('LARAVEL_START', microtime(true));

// Adjust server variables so Laravel resolves paths correctly
$publicPath = __DIR__ . '/public';

$_SERVER['SCRIPT_FILENAME'] = $publicPath . '/index.php';
$_SERVER['DOCUMENT_ROOT']   = $publicPath;

chdir($publicPath);

require $publicPath . '/index.php';
