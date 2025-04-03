<?php

/**
 * API Constants
 *
 * This file contains constants used by the API.
 *
 * PHP version 7.4
 *
 * @category   API
 * @package    CleanSteg1
 * @subpackage API
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */

declare(strict_types=1);

namespace CleanSteg1\API;

// Configure more restrictive limits for API endpoints
const API_MAX_REQUESTS = 30;  // max requests per window
const API_TIME_WINDOW = 60;   // time in seconds
const API_BLOCK_TIME = 300;   // block time in seconds, 300s=5min

// Override default rate limit constants for API endpoints
const MAX_REQUESTS = API_MAX_REQUESTS;
const TIME_WINDOW = API_TIME_WINDOW;
const BLOCK_TIME = API_BLOCK_TIME;
