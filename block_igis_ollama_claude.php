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
 * Ollama Claude AI Chat Block for Moodle
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Ollama Claude AI Chat Block class
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_igis_ollama_claude extends block_base {

    /**
     * Block initialization
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_igis_ollama_claude');
    }

    /**
     * Block has config
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Allow instance configuration
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Get content
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER, $COURSE, $OUTPUT, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        // If the user is not logged in and the config requires login for chat
        if (!isloggedin() && get_config('block_igis_ollama_claude', 'loggedinonly')) {
            $this->content = new stdClass();
            $this->content->text = get_string('logintochat', 'block_igis_ollama_claude');
            $this->content->footer = '';
            return $this->content;
        }

        // If the API URL isn't set, show an error message
        if (empty(get_config('block_igis_ollama_claude', 'apiurl'))) {
            $this->content = new stdClass();
            if (has_capability('moodle/site:config', context_system::instance())) {
                $settingsurl = new moodle_url('/admin/settings.php', ['section' => 'blocksettingigis_ollama_claude']);
                $this->content->text = get_string('noapiurlsetupadmin', 'block_igis_ollama_claude', $settingsurl->out());
            } else {
                $this->content->text = get_string('noapiurlsetup', 'block_igis_ollama_claude');
            }
            $this->content->footer = '';
            return $this->content;
        }

        $this->content = new stdClass();

        // Get the renderer
        $renderer = $this->page->get_renderer('block_igis_ollama_claude');

        // Load the main chat interface
        $data = new stdClass();
        $data->blocktitle = $this->title;
        $data->assistant_name = !empty($this->config->assistant_name) ? 
                                $this->config->assistant_name : 
                                get_config('block_igis_ollama_claude', 'assistant_name');
        $data->user_name = !empty($this->config->user_name) ? 
                           $this->config->user_name : 
                           get_config('block_igis_ollama_claude', 'user_name');
        $data->showlabels = isset($this->config->showlabels) ? $this->config->showlabels : 1;
        $data->instanceid = $this->instance->id;
        $data->logging = get_config('block_igis_ollama_claude', 'enablelogging');
        $data->contextid = $this->context->id;
        $data->uniqid = uniqid(); // For unique DOM IDs
        
        // Get custom completion prompt for this instance if it exists
        $data->customprompt = isset($this->config->completion_prompt) ? $this->config->completion_prompt : '';
        
        // Get source of truth, combining global and instance if both exist
        $data->sourceoftruth = '';
        $globalsot = get_config('block_igis_ollama_claude', 'sourceoftruth');
        $instancesot = isset($this->config->sourceoftruth) ? $this->config->sourceoftruth : '';
        
        if (!empty($globalsot) && !empty($instancesot)) {
            $data->sourceoftruth = $globalsot . "\n\n" . $instancesot;
        } else if (!empty($globalsot)) {
            $data->sourceoftruth = $globalsot;
        } else if (!empty($instancesot)) {
            $data->sourceoftruth = $instancesot;
        }

        // Render the chat template
        $this->content->text = $renderer->render_chat($data);
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Applicable formats
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'all' => true
        );
    }

    /**
     * HTML attributes for the block
     */
    function html_attributes() {
        $attributes = parent::html_attributes();
        $attributes['class'] .= ' block_igis_ollama_claude';
        return $attributes;
    }
}