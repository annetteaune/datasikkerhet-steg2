<?php

/**
 * Configuration Bootstrap
 *
 * This file bootstraps the configuration by loading environment
 * variables and defining constants.
 *
 * PHP version 7.4
 *
 * @category   Configuration
 * @package    CleanSteg1
 * @subpackage Core
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */

require_once __DIR__ . '/EnvLoader.php';
require_once __DIR__ . '/Constants.php';

use function CleanSteg1\Config\loadEnv;
use function CleanSteg1\Config\defineConstants;

// Load environment variables
$envPath = __DIR__ . '/../.env';
try {
    loadEnv($envPath);
    defineConstants();
} catch (\Exception $e) {
    die('Error loading configuration: ' . $e->getMessage());
}
