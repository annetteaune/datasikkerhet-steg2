<?php

/**
 * Environment Loader
 *
 * This file contains functions for loading environment variables.
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
 * Loads environment variables from .env file
 *
 * @param string $path Path to .env file
 *
 * @return void
 * @throws \Exception If .env file not found
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        throw new \Exception('.env file not found');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
            }
        }
    }
}
