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
 * Form for editing Multi-provider AI Chat Block settings
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_igis_ollama_claude_edit_form extends block_edit_form {

    /**
     * Extends the configuration form for the block
     *
     * @param MoodleQuickForm $mform The form being built
     */
    protected function specific_definition($mform) {
        global $CFG;
        
        // Section header title
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Block title
        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_igis_ollama_claude'));
        $mform->setType('config_title', PARAM_TEXT);
        
        // Show labels
        $mform->addElement('advcheckbox', 'config_showlabels', get_string('showlabels', 'block_igis_ollama_claude'));
        $mform->setDefault('config_showlabels', 1);
        
        // Source of truth
        $mform->addElement('textarea', 'config_sourceoftruth', get_string('sourceoftruth', 'block_igis_ollama_claude'));
        $mform->setType('config_sourceoftruth', PARAM_TEXT);
        $mform->addHelpButton('config_sourceoftruth', 'sourceoftruth', 'block_igis_ollama_claude');
        
        // Check if instance-level settings are allowed
        if (get_config('block_igis_ollama_claude', 'instancesettings')) {
            // API Selection
            $apiOptions = array();
            
            // Only add options for configured APIs
            if (!empty(get_config('block_igis_ollama_claude', 'ollamaapiurl'))) {
                $apiOptions['ollama'] = get_string('ollamaapi', 'block_igis_ollama_claude');
            }
            
            if (!empty(get_config('block_igis_ollama_claude', 'claudeapikey'))) {
                $apiOptions['claude'] = get_string('claudeapi', 'block_igis_ollama_claude');
            }
            
            if (!empty(get_config('block_igis_ollama_claude', 'openaikey'))) {
                $apiOptions['openai'] = get_string('openaiapi', 'block_igis_ollama_claude');
            }
            
            if (!empty(get_config('block_igis_ollama_claude', 'geminikey'))) {
                $apiOptions['gemini'] = get_string('geminiapi', 'block_igis_ollama_claude');
            }
            
            if (!empty($apiOptions)) {
                $mform->addElement('select', 'config_defaultapi', get_string('defaultapi', 'block_igis_ollama_claude'), $apiOptions);
                $mform->setDefault('config_defaultapi', get_config('block_igis_ollama_claude', 'defaultapi'));
            }
            
            // Common settings
            $mform->addElement('text', 'config_assistant_name', get_string('assistantname', 'block_igis_ollama_claude'));
            $mform->setType('config_assistant_name', PARAM_TEXT);
            
            $mform->addElement('text', 'config_user_name', get_string('username', 'block_igis_ollama_claude'));
            $mform->setType('config_user_name', PARAM_TEXT);
            
            $mform->addElement('textarea', 'config_completion_prompt', get_string('completionprompt', 'block_igis_ollama_claude'));
            $mform->setType('config_completion_prompt', PARAM_TEXT);
            
            // Ollama specific settings
            if (!empty(get_config('block_igis_ollama_claude', 'ollamaapiurl'))) {
                $mform->addElement('header', 'config_ollama_header', get_string('ollamaapisettings', 'block_igis_ollama_claude'));
                
                $mform->addElement('text', 'config_ollamamodel', get_string('ollamamodel', 'block_igis_ollama_claude'));
                $mform->setType('config_ollamamodel', PARAM_TEXT);
                $mform->setDefault('config_ollamamodel', get_config('block_igis_ollama_claude', 'ollamamodel'));
            }
            
            // Claude specific settings
            if (!empty(get_config('block_igis_ollama_claude', 'claudeapikey'))) {
                $mform->addElement('header', 'config_claude_header', get_string('claudeapisettings', 'block_igis_ollama_claude'));
                
                // Claude model selection
                $claudemodels = array(
                    'claude-3-opus-20240229' => 'Claude 3 Opus',
                    'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                    'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                    'claude-3.5-sonnet-20240620' => 'Claude 3.5 Sonnet',
                    'claude-3.7-sonnet-20250219' => 'Claude 3.7 Sonnet'
                );
                
                $mform->addElement('select', 'config_claudemodel', get_string('claudemodel', 'block_igis_ollama_claude'), $claudemodels);
                $mform->setDefault('config_claudemodel', get_config('block_igis_ollama_claude', 'claudemodel'));
            }
            
            // OpenAI specific settings
            if (!empty(get_config('block_igis_ollama_claude', 'openaikey'))) {
                $mform->addElement('header', 'config_openai_header', get_string('openaisettings', 'block_igis_ollama_claude'));
                
                // OpenAI model selection
                $openaimodels = array(
                    'gpt-4o' => 'GPT-4o',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-4' => 'GPT-4',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
                );
                
                $mform->addElement('select', 'config_openaimodel', get_string('openaimodel', 'block_igis_ollama_claude'), $openaimodels);
                $mform->setDefault('config_openaimodel', get_config('block_igis_ollama_claude', 'openaimodel'));
            }
            
            // Gemini specific settings
            if (!empty(get_config('block_igis_ollama_claude', 'geminikey'))) {
                $mform->addElement('header', 'config_gemini_header', get_string('geminisettings', 'block_igis_ollama_claude'));
                
                // Gemini model selection
                $geminimodels = array(
                    'gemini-pro' => 'Gemini Pro',
                    'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                    'gemini-1.5-flash' => 'Gemini 1.5 Flash'
                );
                
                $mform->addElement('select', 'config_geminimodel', get_string('geminimodel', 'block_igis_ollama_claude'), $geminimodels);
                $mform->setDefault('config_geminimodel', get_config('block_igis_ollama_claude', 'geminimodel'));
            }
            
            // Advanced settings
            $mform->addElement('header', 'config_advanced_header', get_string('advancedsettings', 'block_igis_ollama_claude'));
            
            $mform->addElement('text', 'config_temperature', get_string('temperature', 'block_igis_ollama_claude'));
            $mform->setType('config_temperature', PARAM_FLOAT);
            $mform->setDefault('config_temperature', get_config('block_igis_ollama_claude', 'temperature'));
            
            $mform->addElement('text', 'config_max_tokens', get_string('maxtokens', 'block_igis_ollama_claude'));
            $mform->setType('config_max_tokens', PARAM_INT);
            $mform->setDefault('config_max_tokens', get_config('block_igis_ollama_claude', 'max_tokens'));
        }
    }
}