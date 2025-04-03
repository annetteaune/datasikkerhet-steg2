<?php

/**
 * Database Constants
 *
 * This file defines database-related constants.
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

declare(strict_types=1);

namespace CleanSteg1\Config;

/**
 * Define database constants from environment variables
 */
function defineConstants(): void
{
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));
    define('DB_NAME', getenv('DB_NAME'));
}
