<?php

declare(strict_types=1);

$_ENV['PROJECT_ROOT'] = $_SERVER['PROJECT_ROOT'] = $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__);
define('TEST_PROJECT_DIR', $_SERVER['PROJECT_ROOT']);

$loader = require TEST_PROJECT_DIR . '/vendor/autoload.php';
