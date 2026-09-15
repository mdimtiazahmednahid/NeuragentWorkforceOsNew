<?php
/**
 * Laravel Shared Hosting Entry Point
 * 
 * This file prevents the "403 Forbidden" error by providing 
 * the default index.php file the server is looking for in the root directory.
 * It simply forwards the request to Laravel's actual public/index.php.
 */
require_once __DIR__.'/public/index.php';
