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
 * Upgrade script for the Ollama Claude AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for the Ollama Claude AI Chat Block
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_block_igis_ollama_claude_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025030900) {
        // Define table block_igis_ollama_claude_logs to be created
        $table = new xmldb_table('block_igis_ollama_claude_logs');

        // Adding fields to table block_igis_ollama_claude_logs
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('sourceoftruth', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('prompt', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('model', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_igis_ollama_claude_logs
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('contextid', XMLDB_KEY_FOREIGN, ['contextid'], 'context', ['id']);

        // Adding indexes to table block_igis_ollama_claude_logs
        $table->add_index('instanceid', XMLDB_INDEX_NOTUNIQUE, ['instanceid']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create a web service token for the Ollama Claude API
        // This is used for authenticated AJAX calls
        if (!get_config('block_igis_ollama_claude', 'wstoken')) {
            // Code to create a web service token could go here
            // but for simplicity we'll skip this and use Moodle's built-in
            // token handling for AJAX calls
        }

        // Set default values for settings
        if (!get_config('block_igis_ollama_claude', 'ollamaapiurl')) {
            set_config('ollamaapiurl', 'http://localhost:11434', 'block_igis_ollama_claude');
        }
        if (!get_config('block_igis_ollama_claude', 'ollamamodel')) {
            set_config('ollamamodel', 'claude', 'block_igis_ollama_claude');
        }
        if (!get_config('block_igis_ollama_claude', 'assistant_name')) {
            set_config('assistant_name', 'Claude', 'block_igis_ollama_claude');
        }
        if (!get_config('block_igis_ollama_claude', 'user_name')) {
            set_config('user_name', 'You', 'block_igis_ollama_claude');
        }
        if (!get_config('block_igis_ollama_claude', 'temperature')) {
            set_config('temperature', '0.7', 'block_igis_ollama_claude');
        }
        if (!get_config('block_igis_ollama_claude', 'max_tokens')) {
            set_config('max_tokens', '1024', 'block_igis_ollama_claude');
        }
        if (!get_config('block_igis_ollama_claude', 'completion_prompt')) {
            $default_prompt = 'You are a helpful assistant for a Moodle learning platform. You provide concise, accurate information to help students with their questions. If you don\'t know the answer, admit it rather than guessing.';
            set_config('completion_prompt', $default_prompt, 'block_igis_ollama_claude');
        }

        // Register external services
        require_once($CFG->dirroot . '/blocks/igis_ollama_claude/classes/external.php');
        \core\task\manager::clear_static_caches();

        // IGIS Ollama Claude savepoint reached
        upgrade_block_savepoint(true, 2025030900, 'igis_ollama_claude');
    }

    if ($oldversion < 2025032500) {
        // Adding the api field to the logs table for tracking which service was used
        $table = new xmldb_table('block_igis_ollama_claude_logs');
        $field = new xmldb_field('api', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'model');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Set default values for new API selection settings
        if (!get_config('block_igis_ollama_claude', 'defaultapi')) {
            set_config('defaultapi', 'ollama', 'block_igis_ollama_claude');
        }
        if (!get_config('block_igis_ollama_claude', 'allowapiselection')) {
            set_config('allowapiselection', '1', 'block_igis_ollama_claude');
        }
        if (!get_config('block_igis_ollama_claude', 'claudeapiurl')) {
            set_config('claudeapiurl', 'https://api.anthropic.com/v1/messages', 'block_igis_ollama_claude');
        }
        if (!get_config('block_igis_ollama_claude', 'claudemodel')) {
            set_config('claudemodel', 'claude-3-haiku-20240307', 'block_igis_ollama_claude');
        }

        // IGIS Ollama Claude savepoint reached
        upgrade_block_savepoint(true, 2025032500, 'igis_ollama_claude');
    }

    return true;
}