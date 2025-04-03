<?php

/**
 * API Helper Functions
 *
 * This file contains helper functions for the API endpoints.
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

/**
 * Check if the user is authenticated
 *
 * @return bool True if user is authenticated, false otherwise
 */
function isAuthenticated(): bool
{
    session_start();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Get messages for a course
 *
 * @param \mysqli $conn   Database connection
 * @param array   $params Request parameters
 *
 * @return array Response array with status and data/error message
 */
function getMessages(\mysqli $conn, array $params): array
{
    try {
        // Validate required parameters
        if (!isset($params['emne_kode']) || !isset($params['pin_kode'])) {
            return [
                'status' => 'error',
                'message' => 'Missing required parameters (emne_kode and pin_kode)'
            ];
        }

        // Get emne_id based on emne_kode
        $stmt = $conn->prepare("SELECT emne_id FROM emner WHERE emne_kode = ?");
        if (!$stmt) {
            throw new \Exception("Error preparing course query: " . $conn->error);
        }

        $stmt->bind_param("s", $params['emne_kode']);
        if (!$stmt->execute()) {
            throw new \Exception("Error executing course query: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $emne = $result->fetch_assoc();
        $stmt->close();

        if (!$emne) {
            return [
                'status' => 'error',
                'message' => 'Course not found'
            ];
        }

        // Call the procedure with emne_id and pin_kode
        $stmt = $conn->prepare("CALL get_course_messages(?, ?)");
        if (!$stmt) {
            throw new \Exception("Error preparing messages procedure: " . $conn->error);
        }

        $stmt->bind_param("is", $emne['emne_id'], $params['pin_kode']);
        if (!$stmt->execute()) {
            throw new \Exception("Error executing messages procedure: " . $stmt->error);
        }

        // First result is status
        $result = $stmt->get_result();
        $status = $result->fetch_assoc();

        if ($status['result'] === 'ERROR') {
            return [
                'status' => 'error',
                'message' => $status['message']
            ];
        }

        // Next result is messages
        $stmt->next_result();
        $result = $stmt->get_result();
        $messages = [];

        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'melding_id' => $row['melding_id'],
                'melding_innhold' => $row['melding_innhold'],
                'melding_tidspunkt' => $row['melding_tidspunkt'],
                'student_navn' => 'Anonymous student',
                'emne_navn' => $row['emne_navn'],
                'emne_kode' => $row['emne_kode'],
                'svar' => $row['svar_id'] ? [
                    'svar_id' => $row['svar_id'],
                    'innhold' => $row['svar_innhold'],
                    'tidspunkt' => $row['svar_tidspunkt'],
                    'foreleser_navn' => $row['foreleser_fornavn'] . ' ' . $row['foreleser_etternavn']
                ] : null
            ];
        }

        $stmt->close();

        return [
            'status' => 'success',
            'data' => $messages
        ];
    } catch (\Exception $e) {
        error_log("Error in getMessages: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'An error occurred while fetching messages: ' . $e->getMessage()
        ];
    }
}
