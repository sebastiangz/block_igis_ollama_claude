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
 * Language strings for Ollama Claude AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Ollama Claude AI Chat';
$string['igis_ollama_claude:addinstance'] = 'Add a new Ollama Claude AI Chat block';
$string['igis_ollama_claude:myaddinstance'] = 'Add a new Ollama Claude AI Chat block to Dashboard';
$string['igis_ollama_claude:viewlogs'] = 'View Ollama Claude AI Chat logs';

// Block configuration
$string['blocktitle'] = 'Block title';
$string['showlabels'] = 'Show labels';
$string['showlabelshelp'] = 'Show assistant and user labels in the chat interface';
$string['sourceoftruth'] = 'Source of truth';
$string['sourceoftruthhelp'] = 'Add a list of questions and answers that the AI should use to accurately respond to queries. The format should be Q: (question) followed by A: (answer).';
$string['completion_prompt'] = 'Completion prompt';

// API settings
$string['apisettings'] = 'API Settings';
$string['apiurl'] = 'Ollama API URL';
$string['apiurlhelp'] = 'The URL of your Ollama API service, including the port (e.g., http://localhost:11434)';
$string['model'] = 'Claude model';
$string['modelhelp'] = 'The name of the Claude model to use (e.g., claude, claude-instant)';
$string['loggedinonly'] = 'Restrict chat usage to logged-in users';
$string['loggedonlyhelp'] = 'If checked, only logged-in users will be able to use the chat box';

// UI settings
$string['uisettings'] = 'User Interface Settings';
$string['assistantname'] = 'Assistant name';
$string['assistantnamehelp'] = 'The name that will be displayed for the AI assistant in the chat';
$string['defaultassistantname'] = 'Claude';
$string['username'] = 'User name';
$string['usernamehelp'] = 'The name that will be displayed for the user in the chat';
$string['defaultusername'] = 'You';
$string['enablelogging'] = 'Enable logging';
$string['enablelogginghelp'] = 'If checked, all messages sent by users along with the AI responses will be recorded';

// Prompt settings
$string['promptsettings'] = 'Prompt Settings';
$string['completionprompt'] = 'Completion prompt';
$string['completionprompthelp'] = 'The text added to the top of the conversation to influence the AI\'s persona and responses';
$string['defaultcompletionprompt'] = 'You are a helpful assistant for a Moodle learning platform. You provide concise, accurate information to help students with their questions. If you don\'t know the answer, admit it rather than guessing.';

// Advanced settings
$string['advancedsettings'] = 'Advanced Settings';
$string['advancedsettingshelp'] = 'These are extra parameters to adjust the behavior of the model';
$string['instancesettings'] = 'Instance-level settings';
$string['instancesettingshelp'] = 'If checked, this will allow anybody that can add a block to adjust settings at a per-block level';
$string['temperature'] = 'Temperature';
$string['temperaturehelp'] = 'Controls randomness. Lower values (e.g., 0.2) make the output more focused and deterministic, while higher values (e.g., 0.8) make it more creative (0.0-1.0)';
$string['maxtokens'] = 'Maximum tokens';
$string['maxtokenshelp'] = 'The maximum number of tokens that the AI can generate in its response';

// Chat interface
$string['typemessage'] = 'Type your message...';
$string['sendmessage'] = 'Send';
$string['chatbeingrecorded'] = 'This chat is being recorded';
$string['chathistory'] = 'Chat history';
$string['clearconversation'] = 'Clear conversation';
$string['loadingresponse'] = 'Thinking...';
$string['erroroccurred'] = 'An error occurred while processing your request. Please try again.';
$string['logintochat'] = 'Please log in to use the chat feature.';
$string['noapiurlsetup'] = 'This chat block has not been configured yet. Please contact the site administrator.';
$string['noapiurlsetupadmin'] = 'Ollama Claude has not been configured yet. <a href="{$a}">Set up the API URL</a> to get started.';

// Strings para privacidad
$string['privacy:metadata:block_igis_ollama_claude_logs'] = 'Information about user interactions with the Claude AI assistant';
$string['privacy:metadata:block_igis_ollama_claude_logs:userid'] = 'The ID of the user who sent the message';
$string['privacy:metadata:block_igis_ollama_claude_logs:courseid'] = 'The course ID where the interaction occurred';
$string['privacy:metadata:block_igis_ollama_claude_logs:contextid'] = 'The context ID where the interaction occurred';
$string['privacy:metadata:block_igis_ollama_claude_logs:instanceid'] = 'The block instance ID where the interaction occurred';
$string['privacy:metadata:block_igis_ollama_claude_logs:message'] = 'The message sent by the user';
$string['privacy:metadata:block_igis_ollama_claude_logs:response'] = 'The response from the AI assistant';
$string['privacy:metadata:block_igis_ollama_claude_logs:timecreated'] = 'The time when the interaction occurred';
$string['privacy:metadata:ollama_api'] = 'To integrate with Ollama, some data needs to be sent to the Ollama API service';
$string['privacy:metadata:ollama_api:message'] = 'The message sent by the user';
$string['privacy:metadata:ollama_api:prompt'] = 'The system prompt used to guide the AI behavior';
$string['privacy:metadata:ollama_api:sourceoftruth'] = 'The source of truth data used to provide accurate information';

// Strings adicionales para la interfaz
$string['welcomemessage'] = 'Hola, soy {$a->name}. ¿En qué puedo ayudarte hoy?';
$string['logs'] = 'Registros de chat';
$string['report'] = 'Informe de chat de Claude AI';
$string['usermessage'] = 'Mensaje del usuario';
$string['aimessage'] = 'Respuesta de la IA';
$string['conversationcleared'] = 'Conversación borrada';
$string['nologs'] = 'No hay registros de chat para mostrar';
$string['from'] = 'Desde';
$string['to'] = 'Hasta';
$string['filter'] = 'Filtrar';
$string['user'] = 'Usuario';
$string['course'] = 'Curso';
$string['date'] = 'Fecha';
$string['exportlogs'] = 'Exportar registros';
$string['viewlog'] = 'Ver registro completo';
$string['settings'] = 'Configuración';