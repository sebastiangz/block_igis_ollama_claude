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
 * Language strings for Multi-provider AI Chat Block (English)
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Multi-provider AI Chat';
$string['igis_ollama_claude:addinstance'] = 'Add a new Multi-provider AI Chat block';
$string['igis_ollama_claude:myaddinstance'] = 'Add a new Multi-provider AI Chat block to the Dashboard';
$string['igis_ollama_claude:viewlogs'] = 'View Multi-provider AI Chat logs';

// General settings
$string['generalsettings'] = 'General Settings';
$string['defaultapi'] = 'Default API service';
$string['defaultapihelp'] = 'Select which AI service to use by default';
$string['ollamaapi'] = 'Ollama (local)';
$string['claudeapi'] = 'Claude (cloud)';
$string['openaiapi'] = 'OpenAI (cloud)';
$string['geminiapi'] = 'Gemini (cloud)';
$string['allowapiselection'] = 'Allow API selection';
$string['allowapiselectionhelp'] = 'If enabled, users can select which AI service to use';
$string['selectapi'] = 'Select AI service';

// Block settings
$string['blocktitle'] = 'Block title';
$string['showlabels'] = 'Show labels';
$string['showlabelshelp'] = 'Show assistant and user labels in the chat interface';
$string['sourceoftruth'] = 'Source of truth';
$string['sourceoftruthhelp'] = 'Add a list of questions and answers that the AI should use to answer accurately. The format should be Q: (question) followed by A: (answer).';
$string['completion_prompt'] = 'Completion prompt';

// Ollama API settings
$string['ollamaapisettings'] = 'Ollama API Settings';
$string['ollamaapiurl'] = 'Ollama API URL';
$string['ollamaapiurlhelp'] = 'The URL of your Ollama API service, including port (e.g., http://localhost:11434)';
$string['ollamamodel'] = 'Ollama Model';
$string['ollamamodelhelp'] = 'The name of the model to use in Ollama (e.g., claude, llama2, mistral, etc.)';

// Claude API settings
$string['claudeapisettings'] = 'Claude API Settings';
$string['claudeapikey'] = 'Claude API Key';
$string['claudeapikeyhelp'] = 'Your Anthropic Claude API key to access the cloud service';
$string['claudeapiurl'] = 'Claude API URL';
$string['claudeapiurlhelp'] = 'The URL for the Claude API (usually https://api.anthropic.com/v1/messages)';
$string['claudemodel'] = 'Claude Model';
$string['claudemodelhelp'] = 'The specific Claude model version to use (e.g., claude-3-opus, claude-3-sonnet)';

// OpenAI API settings
$string['openaisettings'] = 'OpenAI API Settings';
$string['openaikey'] = 'OpenAI API Key';
$string['openaikeyhelp'] = 'Your OpenAI API key to access the cloud service';
$string['openaimodel'] = 'OpenAI Model';
$string['openaimodelhelp'] = 'The OpenAI model to use (e.g., gpt-4, gpt-3.5-turbo)';

// Gemini API settings
$string['geminisettings'] = 'Gemini API Settings';
$string['geminikey'] = 'Gemini API Key';
$string['geminikeyhelp'] = 'Your Google Gemini API key to access the cloud service';
$string['geminimodel'] = 'Gemini Model';
$string['geminimodelhelp'] = 'The Gemini model to use (e.g., gemini-pro, gemini-1.5-pro)';

// Access restriction settings
$string['loggedinonly'] = 'Restrict chat usage to logged-in users';
$string['loggedonlyhelp'] = 'If checked, only logged-in users will be able to use the chat box';

// User interface settings
$string['uisettings'] = 'User Interface Settings';
$string['assistantname'] = 'Assistant name';
$string['assistantnamedesc'] = 'The name to display for the AI assistant in the chat';
$string['defaultassistantname'] = 'AI Assistant';
$string['username'] = 'User name';
$string['usernamedesc'] = 'The name to display for the user in the chat';
$string['defaultusername'] = 'You';
$string['enablelogging'] = 'Enable logging';
$string['enablelogginghelp'] = 'If checked, all messages sent by users along with the AI responses will be logged';

// Prompt settings
$string['promptsettings'] = 'Prompt Settings';
$string['completionprompt'] = 'Completion prompt';
$string['completionprompthelp'] = 'The text added at the beginning of the conversation to influence the AI\'s personality and responses';
$string['defaultcompletionprompt'] = 'You are a helpful assistant for a Moodle learning platform. You provide concise, accurate information to help students with their questions. If you don\'t know the answer, admit it rather than guessing.';

// Advanced settings
$string['advancedsettings'] = 'Advanced Settings';
$string['advancedsettingshelp'] = 'These are additional parameters to adjust the behavior of the model';
$string['instancesettings'] = 'Instance-level settings';
$string['instancesettingshelp'] = 'If checked, it will allow anyone who can add a block to adjust settings at an individual block level';
$string['temperature'] = 'Temperature';
$string['temperaturehelp'] = 'Controls randomness. Lower values (e.g., 0.2) make the output more focused and deterministic, while higher values (e.g., 0.8) make it more creative (0.0-1.0)';
$string['maxtokens'] = 'Maximum tokens';
$string['maxtokenshelp'] = 'The maximum number of tokens the AI can generate in its response';

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
$string['noapiurlsetupadmin'] = 'Multi-provider AI Chat has not been configured yet. <a href="{$a}">Set up the API configuration</a> to get started.';

// Privacy strings
$string['privacy:metadata:block_igis_ollama_claude_logs'] = 'Information about user interactions with the AI assistant';
$string['privacy:metadata:block_igis_ollama_claude_logs:userid'] = 'The ID of the user who sent the message';
$string['privacy:metadata:block_igis_ollama_claude_logs:courseid'] = 'The ID of the course where the interaction occurred';
$string['privacy:metadata:block_igis_ollama_claude_logs:contextid'] = 'The ID of the context where the interaction occurred';
$string['privacy:metadata:block_igis_ollama_claude_logs:instanceid'] = 'The ID of the block instance where the interaction occurred';
$string['privacy:metadata:block_igis_ollama_claude_logs:message'] = 'The message sent by the user';
$string['privacy:metadata:block_igis_ollama_claude_logs:response'] = 'The response from the AI assistant';
$string['privacy:metadata:block_igis_ollama_claude_logs:timecreated'] = 'The time when the interaction occurred';
$string['privacy:metadata:block_igis_ollama_claude_logs:api'] = 'The API service used to process the request';
$string['privacy:metadata:ollama_api'] = 'To integrate with Ollama, some data needs to be sent to the Ollama API service';
$string['privacy:metadata:ollama_api:message'] = 'The message sent by the user';
$string['privacy:metadata:ollama_api:prompt'] = 'The system prompt used to guide the AI\'s behavior';
$string['privacy:metadata:ollama_api:sourceoftruth'] = 'The source of truth data used to provide accurate information';
$string['privacy:metadata:claude_api'] = 'To integrate with Claude, some data needs to be sent to the Claude API service';
$string['privacy:metadata:claude_api:message'] = 'The message sent by the user';
$string['privacy:metadata:claude_api:prompt'] = 'The system prompt used to guide the AI\'s behavior';
$string['privacy:metadata:claude_api:sourceoftruth'] = 'The source of truth data used to provide accurate information';
$string['privacy:metadata:openai_api'] = 'To integrate with OpenAI, some data needs to be sent to the OpenAI API service';
$string['privacy:metadata:openai_api:message'] = 'The message sent by the user';
$string['privacy:metadata:openai_api:prompt'] = 'The system prompt used to guide the AI\'s behavior';
$string['privacy:metadata:openai_api:sourceoftruth'] = 'The source of truth data used to provide accurate information';
$string['privacy:metadata:gemini_api'] = 'To integrate with Gemini, some data needs to be sent to the Gemini API service';
$string['privacy:metadata:gemini_api:message'] = 'The message sent by the user';
$string['privacy:metadata:gemini_api:prompt'] = 'The system prompt used to guide the AI\'s behavior';
$string['privacy:metadata:gemini_api:sourceoftruth'] = 'The source of truth data used to provide accurate information';

// Additional interface strings
$string['welcomemessage'] = 'Hello, I\'m {$a->name}. How can I help you today?';
$string['logs'] = 'Chat logs';
$string['report'] = 'AI Chat Report';
$string['usermessage'] = 'User message';
$string['aimessage'] = 'AI response';
$string['conversationcleared'] = 'Conversation cleared';
$string['nologs'] = 'No chat logs to display';
$string['from'] = 'From';
$string['to'] = 'To';
$string['filter'] = 'Filter';
$string['user'] = 'User';
$string['course'] = 'Course';
$string['date'] = 'Date';
$string['exportlogs'] = 'Export logs';
$string['viewlog'] = 'View full log';
$string['settings'] = 'Settings';