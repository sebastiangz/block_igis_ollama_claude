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
 * Settings for the Ollama Claude AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Header for General settings
    $settings->add(new admin_setting_heading(
        'block_igis_ollama_claude/generalsettings',
        get_string('generalsettings', 'block_igis_ollama_claude'),
        ''
    ));
    
    // Default API service
    $apioptions = array(
        'ollama' => get_string('ollamaapi', 'block_igis_ollama_claude'),
        'claude' => get_string('claudeapi', 'block_igis_ollama_claude')
    );
    
    $settings->add(new admin_setting_configselect(
        'block_igis_ollama_claude/defaultapi',
        get_string('defaultapi', 'block_igis_ollama_claude'),
        get_string('defaultapihelp', 'block_igis_ollama_claude'),
        'ollama',
        $apioptions
    ));
    
    // Allow users to select API
    $settings->add(new admin_setting_configcheckbox(
        'block_igis_ollama_claude/allowapiselection',
        get_string('allowapiselection', 'block_igis_ollama_claude'),
        get_string('allowapiselectionhelp', 'block_igis_ollama_claude'),
        1
    ));
    
    // Restrict to logged-in users
    $settings->add(new admin_setting_configcheckbox(
        'block_igis_ollama_claude/loggedinonly',
        get_string('loggedinonly', 'block_igis_ollama_claude'),
        get_string('loggedonlyhelp', 'block_igis_ollama_claude'),
        1
    ));
    
    // Header for Ollama API settings
    $settings->add(new admin_setting_heading(
        'block_igis_ollama_claude/ollamaapisettings',
        get_string('ollamaapisettings', 'block_igis_ollama_claude'),
        ''
    ));

    // Ollama API URL
    $settings->add(new admin_setting_configtext(
        'block_igis_ollama_claude/ollamaapiurl',
        get_string('ollamaapiurl', 'block_igis_ollama_claude'),
        get_string('ollamaapiurlhelp', 'block_igis_ollama_claude'),
        'http://localhost:11434',
        PARAM_URL
    ));

    // Ollama Model selection
    $settings->add(new admin_setting_configtext(
        'block_igis_ollama_claude/ollamamodel',
        get_string('ollamamodel', 'block_igis_ollama_claude'),
        get_string('ollamamodelhelp', 'block_igis_ollama_claude'),
        'claude',
        PARAM_TEXT
    ));
    
    // Header for Claude API settings
    $settings->add(new admin_setting_heading(
        'block_igis_ollama_claude/claudeapisettings',
        get_string('claudeapisettings', 'block_igis_ollama_claude'),
        ''
    ));
    
    // Claude API Key
    $settings->add(new admin_setting_configpasswordunmask(
        'block_igis_ollama_claude/claudeapikey',
        get_string('claudeapikey', 'block_igis_ollama_claude'),
        get_string('claudeapikeyhelp', 'block_igis_ollama_claude'),
        '',
        PARAM_RAW
    ));
    
    // Claude API URL
    $settings->add(new admin_setting_configtext(
        'block_igis_ollama_claude/claudeapiurl',
        get_string('claudeapiurl', 'block_igis_ollama_claude'),
        get_string('claudeapiurlhelp', 'block_igis_ollama_claude'),
        'https://api.anthropic.com/v1/messages',
        PARAM_URL
    ));
    
    // Claude Model selection
    $claudemodels = array(
        'claude-3-opus-20240229' => 'Claude 3 Opus',
        'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
        'claude-3-haiku-20240307' => 'Claude 3 Haiku',
        'claude-3.5-sonnet-20240620' => 'Claude 3.5 Sonnet',
        'claude-3.7-sonnet-20250219' => 'Claude 3.7 Sonnet'
    );
    
    $settings->add(new admin_setting_configselect(
        'block_igis_ollama_claude/claudemodel',
        get_string('claudemodel', 'block_igis_ollama_claude'),
        get_string('claudemodelhelp', 'block_igis_ollama_claude'),
        'claude-3-haiku-20240307',
        $claudemodels
    ));

    // Header for UI settings
    $settings->add(new admin_setting_heading(
        'block_igis_ollama_claude/uisettings',
        get_string('uisettings', 'block_igis_ollama_claude'),
        ''
    ));

    // Assistant name
    $settings->add(new admin_setting_configtext(
        'block_igis_ollama_claude/assistant_name',
        get_string('assistantname', 'block_igis_ollama_claude'),
        get_string('assistantnamehelp', 'block_igis_ollama_claude'),
        get_string('defaultassistantname', 'block_igis_ollama_claude'),
        PARAM_TEXT
    ));

    // User name
    $settings->add(new admin_setting_configtext(
        'block_igis_ollama_claude/user_name',
        get_string('username', 'block_igis_ollama_claude'),
        get_string('usernamehelp', 'block_igis_ollama_claude'),
        get_string('defaultusername', 'block_igis_ollama_claude'),
        PARAM_TEXT
    ));

    // Enable logging
    $settings->add(new admin_setting_configcheckbox(
        'block_igis_ollama_claude/enablelogging',
        get_string('enablelogging', 'block_igis_ollama_claude'),
        get_string('enablelogginghelp', 'block_igis_ollama_claude'),
        0
    ));

    // Header for prompt settings
    $settings->add(new admin_setting_heading(
        'block_igis_ollama_claude/promptsettings',
        get_string('promptsettings', 'block_igis_ollama_claude'),
        ''
    ));

    // Completion prompt (system prompt)
    $settings->add(new admin_setting_configtextarea(
        'block_igis_ollama_claude/completion_prompt',
        get_string('completionprompt', 'block_igis_ollama_claude'),
        get_string('completionprompthelp', 'block_igis_ollama_claude'),
        get_string('defaultcompletionprompt', 'block_igis_ollama_claude'),
        PARAM_TEXT
    ));

    // Source of truth
    $settings->add(new admin_setting_configtextarea(
        'block_igis_ollama_claude/sourceoftruth',
        get_string('sourceoftruth', 'block_igis_ollama_claude'),
        get_string('sourceoftruthhelp', 'block_igis_ollama_claude'),
        '',
        PARAM_TEXT
    ));

    // Header for advanced settings
    $settings->add(new admin_setting_heading(
        'block_igis_ollama_claude/advancedsettings',
        get_string('advancedsettings', 'block_igis_ollama_claude'),
        get_string('advancedsettingshelp', 'block_igis_ollama_claude')
    ));

    // Enable instance-level settings
    $settings->add(new admin_setting_configcheckbox(
        'block_igis_ollama_claude/instancesettings',
        get_string('instancesettings', 'block_igis_ollama_claude'),
        get_string('instancesettingshelp', 'block_igis_ollama_claude'),
        0
    ));

    // Temperature
    $settings->add(new admin_setting_configtext(
        'block_igis_ollama_claude/temperature',
        get_string('temperature', 'block_igis_ollama_claude'),
        get_string('temperaturehelp', 'block_igis_ollama_claude'),
        '0.7',
        PARAM_FLOAT
    ));

    // Max tokens
    $settings->add(new admin_setting_configtext(
        'block_igis_ollama_claude/max_tokens',
        get_string('maxtokens', 'block_igis_ollama_claude'),
        get_string('maxtokenshelp', 'block_igis_ollama_claude'),
        '1024',
        PARAM_INT
    ));
}