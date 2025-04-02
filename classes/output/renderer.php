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
 * Renderer for the Multi-provider AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_igis_ollama_claude\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use renderable;
use stdClass;

/**
 * Renderer class for Multi-provider AI Chat Block
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the chat interface
     *
     * @param stdClass $data The data for the template
     * @return string HTML string
     */
    public function render_chat(stdClass $data) {
        global $CFG;
        
        // Set up the template data
        $templatedata = new stdClass();
        $templatedata->blocktitle = $data->blocktitle;
        $templatedata->assistant_name = $data->assistant_name;
        $templatedata->user_name = $data->user_name;
        $templatedata->showlabels = $data->showlabels;
        $templatedata->instanceid = $data->instanceid;
        $templatedata->contextid = $data->contextid;
        $templatedata->uniqid = $data->uniqid;
        $templatedata->logging = $data->logging;
        $templatedata->sourceoftruth = $data->sourceoftruth;
        $templatedata->customprompt = $data->customprompt;
        
        // API Selection data
        $templatedata->allowapiselection = $data->allowapiselection;
        $templatedata->defaultapi = $data->defaultapi;
        $templatedata->defaultapi_ollama = $data->defaultapi_ollama;
        $templatedata->defaultapi_claude = $data->defaultapi_claude;
        $templatedata->defaultapi_openai = $data->defaultapi_openai;
        $templatedata->defaultapi_gemini = $data->defaultapi_gemini;
        $templatedata->ollamaapiavailable = $data->ollamaapiavailable;
        $templatedata->claudeapiavailable = $data->claudeapiavailable;
        $templatedata->openaiapiavailable = $data->openaiapiavailable;
        $templatedata->geminiapiavailable = $data->geminiapiavailable;
        
        // Model information
        $templatedata->ollamamodel = $data->ollamamodel;
        $templatedata->claudemodel = $data->claudemodel;
        $templatedata->openaimodel = $data->openaimodel;
        $templatedata->geminimodel = $data->geminimodel;
        
        // Add URLs for web service calls
        $templatedata->wwwroot = $CFG->wwwroot;
        
        // Add JavaScript initialization
        $this->page->requires->js_call_amd('block_igis_ollama_claude/lib', 'init', [
            [
                'blockId' => $data->instanceid,
                'uniqueId' => $data->uniqid,
                'contextId' => $data->contextid,
                'sourceOfTruth' => $data->sourceoftruth,
                'customPrompt' => $data->customprompt,
                'defaultApi' => $data->defaultapi
            ]
        ]);
        
        // Render the template
        return $this->render_from_template('block_igis_ollama_claude/chat', $templatedata);
    }
}