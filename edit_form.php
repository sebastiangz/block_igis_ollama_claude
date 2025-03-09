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
 * Form for editing Ollama Claude AI Chat Block settings
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Form for editing Ollama Claude AI Chat Block settings
 */
class block_igis_ollama_claude_edit_form extends block_edit_form {

    /**
     * Adds form fields to the block edit form
     *
     * @param moodleform $mform The form being built
     */
    protected function specific_definition($mform) {
        global $CFG;

        // Section header title.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Block title.
        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_igis_ollama_claude'));
        $mform->setType('config_title', PARAM_TEXT);
        
        // Show labels.
        $mform->addElement('advcheckbox', 'config_showlabels', get_string('showlabels', 'block_igis_ollama_claude'));
        $mform->setDefault('config_showlabels', 1);
        $mform->setType('config_showlabels', PARAM_BOOL);
        $mform->addHelpButton('config_showlabels', 'showlabels', 'block_igis_ollama_claude');
        
        // Source of truth.
        $mform->addElement('textarea', 'config_sourceoftruth', get_string('sourceoftruth', 'block_igis_ollama_claude'), 
                           array('rows' => 5, 'cols' => 50));
        $mform->setType('config_sourceoftruth', PARAM_RAW);
        $mform->addHelpButton('config_sourceoftruth', 'sourceoftruth', 'block_igis_ollama_claude');
        
        // Instance-level settings are enabled.
        if (get_config('block_igis_ollama_claude', 'instancesettings')) {
            // API settings section
            $mform->addElement('header', 'apisettingsheader', get_string('generalsettings', 'block_igis_ollama_claude'));
            
            // Default API service for this block instance
            $apioptions = array(
                'ollama' => get_string('ollamaapi', 'block_igis_ollama_claude'),
                'claude' => get_string('claudeapi', 'block_igis_ollama_claude')
            );
            
            $mform->addElement('select', 'config_defaultapi', get_string('defaultapi', 'block_igis_ollama_claude'), $apioptions);
            $mform->setDefault('config_defaultapi', get_config('block_igis_ollama_claude', 'defaultapi'));
            
            // Ollama settings section
            $mform->addElement('header', 'ollamasettingsheader', get_string('ollamaapisettings', 'block_igis_ollama_claude'));
            
            // Ollama model
            $mform->addElement('text', 'config_ollamamodel', get_string('ollamamodel', 'block_igis_ollama_claude'));
            $mform->setType('config_ollamamodel', PARAM_TEXT);
            $mform->setDefault('config_ollamamodel', get_config('block_igis_ollama_claude', 'ollamamodel'));
            $mform->addHelpButton('config_ollamamodel', 'ollamamodel', 'block_igis_ollama_claude');
            
            // Claude settings section
            $mform->addElement('header', 'claudesettingsheader', get_string('claudeapisettings', 'block_igis_ollama_claude'));
            
            // Claude model
            $claudemodels = array(
                'claude-3-opus-20240229' => 'Claude 3 Opus',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                'claude-3.5-sonnet-20240620' => 'Claude 3.5 Sonnet',
                'claude-3.7-sonnet-20250219' => 'Claude 3.7 Sonnet',
            );
            
            $mform->addElement('select', 'config_claudemodel', get_string('claudemodel', 'block_igis_ollama_claude'), $claudemodels);
            $mform->setDefault('config_claudemodel', get_config('block_igis_ollama_claude', 'claudemodel'));
            $mform->addHelpButton('config_claudemodel', 'claudemodel', 'block_igis_ollama_claude');
            
            // UI settings section
            $mform->addElement('header', 'uisettingsheader', get_string('uisettings', 'block_igis_ollama_claude'));
            
            // Assistant name
            $mform->addElement('text', 'config_assistant_name', get_string('assistantname', 'block_igis_ollama_claude'));
            $mform->setType('config_assistant_name', PARAM_TEXT);
            $mform->setDefault('config_assistant_name', get_config('block_igis_ollama_claude', 'assistant_name'));
            $mform->addHelpButton('config_assistant_name', 'assistantname', 'block_igis_ollama_claude');
            
            // User name
            $mform->addElement('text', 'config_user_name', get_string('username', 'block_igis_ollama_claude'));
            $mform->setType('config_user_name', PARAM_TEXT);
            $mform->setDefault('config_user_name', get_config('block_igis_ollama_claude', 'user_name'));
            $mform->addHelpButton('config_user_name', 'username', 'block_igis_ollama_claude');
            
            // Prompt settings section
            $mform->addElement('header', 'promptsettingsheader', get_string('promptsettings', 'block_igis_ollama_claude'));
            
            // Completion prompt
            $mform->addElement('textarea', 'config_completion_prompt', get_string('completionprompt', 'block_igis_ollama_claude'), 
                               array('rows' => 5, 'cols' => 50));
            $mform->setType('config_completion_prompt', PARAM_RAW);
            $mform->setDefault('config_completion_prompt', get_config('block_igis_ollama_claude', 'completion_prompt'));
            $mform->addHelpButton('config_completion_prompt', 'completionprompt', 'block_igis_ollama_claude');
            
            // Advanced settings section
            $mform->addElement('header', 'advancedsettingsheader', get_string('advancedsettings', 'block_igis_ollama_claude'));
            
            // Temperature
            $mform->addElement('text', 'config_temperature', get_string('temperature', 'block_igis_ollama_claude'));
            $mform->setType('config_temperature', PARAM_FLOAT);
            $mform->setDefault('config_temperature', get_config('block_igis_ollama_claude', 'temperature'));
            $mform->addRule('config_temperature', null, 'numeric', null, 'client');
            $mform->addHelpButton('config_temperature', 'temperature', 'block_igis_ollama_claude');
            
            // Max tokens
            $mform->addElement('text', 'config_max_tokens', get_string('maxtokens', 'block_igis_ollama_claude'));
            $mform->setType('config_max_tokens', PARAM_INT);
            $mform->setDefault('config_max_tokens', get_config('block_igis_ollama_claude', 'max_tokens'));
            $mform->addRule('config_max_tokens', null, 'numeric', null, 'client');
            $mform->addHelpButton('config_max_tokens', 'maxtokens', 'block_igis_ollama_claude');
        }
    }
}
