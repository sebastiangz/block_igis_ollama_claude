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
 * Block igis_ollama_claude is defined here.
 *
 * @package     block_igis_ollama_claude
 * @copyright   2025 Sebastián González Zepeda sgonzalez@infraestructuragis.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Claude AI chat block for Moodle through Ollama.
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_igis_ollama_claude extends block_base {

    /**
     * Initializes block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_igis_ollama_claude');
    }

    /**
     * Gets the block contents.
     *
     * @return string The block HTML.
     */
    public function get_content() {
        global $CFG, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = new stdClass();
            $this->content->text = '';
            $this->content->footer = '';
            return $this->content;
        }

        // Check if only logged-in users can use the chat.
        if (get_config('block_igis_ollama_claude', 'restrict_to_logged_in_users') && !isloggedin()) {
            $this->content = new stdClass();
            $this->content->text = get_string('must_be_logged_in', 'block_igis_ollama_claude');
            $this->content->footer = '';
            return $this->content;
        }

        // Check if the API endpoint is configured.
        if (empty(get_config('block_igis_ollama_claude', 'api_endpoint'))) {
            $this->content = new stdClass();
            $this->content->text = get_string('api_endpoint_not_set', 'block_igis_ollama_claude');
            $this->content->footer = '';
            return $this->content;
        }

        $this->page->requires->js_call_amd('block_igis_ollama_claude/chat', 'init', [
            'contextid' => $this->context->id,
            'instanceid' => $this->instance->id,
            'logging' => get_config('block_igis_ollama_claude', 'enable_logging'),
            'assistant_name' => $this->get_assistant_name(),
            'user_name' => $this->get_user_name(),
            'show_labels' => $this->config->show_labels ?? true,
            'persist_conversations' => $this->get_persist_conversations(),
        ]);

        $this->content = new stdClass();
        $this->content->text = $this->render_chat_ui();
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Render the chat UI
     *
     * @return string HTML for the chat interface
     */
    private function render_chat_ui() {
        global $OUTPUT;

        $assistant_name = $this->get_assistant_name();
        $user_name = $this->get_user_name();
        $logging = get_config('block_igis_ollama_claude', 'enable_logging');

        $data = [
            'assistant_name' => $assistant_name,
            'user_name' => $user_name,
            'logging' => $logging,
        ];

        return $OUTPUT->render_from_template('block_igis_ollama_claude/chat', $data);
    }

    /**
     * Get the assistant name from config
     *
     * @return string Assistant name
     */
    private function get_assistant_name() {
        // Check if instance-level settings are enabled and if an instance-level setting exists
        if (get_config('block_igis_ollama_claude', 'instance_level_settings') && 
            !empty($this->config->assistant_name)) {
            return $this->config->assistant_name;
        }
        
        // Fall back to the global setting
        return get_config('block_igis_ollama_claude', 'assistant_name') ?: 'Claude';
    }

    /**
     * Get the user name from config
     *
     * @return string User name
     */
    private function get_user_name() {
        // Check if instance-level settings are enabled and if an instance-level setting exists
        if (get_config('block_igis_ollama_claude', 'instance_level_settings') && 
            !empty($this->config->user_name)) {
            return $this->config->user_name;
        }
        
        // Fall back to the global setting
        return get_config('block_igis_ollama_claude', 'user_name') ?: 'Usuario';
    }

    /**
     * Get the persist_conversations setting
     *
     * @return bool Whether to persist conversations
     */
    private function get_persist_conversations() {
        // Check if instance-level settings are enabled and if an instance-level setting exists
        if (get_config('block_igis_ollama_claude', 'instance_level_settings') && 
            isset($this->config->persist_conversations)) {
            return (bool)$this->config->persist_conversations;
        }
        
        // Fall back to the global setting
        return (bool)get_config('block_igis_ollama_claude', 'persist_conversations');
    }

    /**
     * Defines configuration data.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return boolean True if the global configuration is enabled.
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats() {
        return array(
            'all' => true,
        );
    }
}
