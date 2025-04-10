<?php

/**
 * Bootstrap File for Library
 *
 * This file handles loading dependencies for the library components.
 *
 * PHP version 7.4
 *
 * @category   Bootstrap
 * @package    CleanSteg1
 * @subpackage Core
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */

declare(strict_types=1);

require_once __DIR__ . '/security_headers.php';


require_once __DIR__ . '/db.php';


date_default_timezone_set('Europe/Oslo');


header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../api/includes/bootstrap.php';
