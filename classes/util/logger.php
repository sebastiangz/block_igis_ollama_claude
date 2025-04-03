<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Error logging utility for Multi-provider AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_igis_ollama_claude\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Logger utility class
 */
class logger {
    /**
     * Log API requests and responses for debugging
     *
     * @param string $api API name (ollama, claude, openai, gemini)
     * @param string $action Action being performed
     * @param mixed $data Data to log
     * @param bool $is_error Whether this is an error log
     */
    public static function log_api($api, $action, $data, $is_error = false) {
        global $CFG;
        
        // If debugging is not enabled, don't log
        if (!$CFG->debugdeveloper && !$is_error) {
            return;
        }
        
        // Format data for logging
        if (is_array($data) || is_object($data)) {
            $data_str = json_encode($data, JSON_PRETTY_PRINT);
        } else {
            $data_str = (string)$data;
        }
        
        // Truncate if too long
        if (strlen($data_str) > 2000) {
            $data_str = substr($data_str, 0, 2000) . '... [truncated]';
        }
        
        // Create log message
        $message = "[$api] $action: $data_str";
        
        // Log using Moodle's debugging function
        if ($is_error) {
            debugging($message, DEBUG_NORMAL);
        } else {
            debugging($message, DEBUG_DEVELOPER);
        }
        
        // Also write to a special log file if it exists
        $log_dir = $CFG->dataroot . '/ai_chat_logs';
        if (!file_exists($log_dir)) {
            // Try to create directory
            @mkdir($log_dir, 0777, true);
        }
        
        if (is_dir($log_dir) && is_writable($log_dir)) {
            $log_file = $log_dir . '/' . date('Y-m-d') . '.log';
            $timestamp = date('Y-m-d H:i:s');
            @file_put_contents(
                $log_file, 
                "[$timestamp] $message\n", 
                FILE_APPEND
            );
        }
    }
    
    /**
     * Log an API error
     *
     * @param string $api API name
     * @param \Exception $exception Exception that occurred
     * @param array $context Additional context information
     */
    public static function log_error($api, $exception, $context = []) {
        // Format context data
        $context_str = '';
        if (!empty($context)) {
            $context_str = json_encode($context);
        }
        
        $error_data = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context_str
        ];
        
        self::log_api($api, 'ERROR', $error_data, true);
    }
}