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
 * Language strings for Ollama Claude AI Chat Block (Spanish)
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Chat IA Claude Ollama';
$string['igis_ollama_claude:addinstance'] = 'Añadir un nuevo bloque de Chat IA Claude Ollama';
$string['igis_ollama_claude:myaddinstance'] = 'Añadir un nuevo bloque de Chat IA Claude Ollama al Tablero';
$string['igis_ollama_claude:viewlogs'] = 'Ver registros del Chat IA Claude Ollama';

// Block configuration
$string['blocktitle'] = 'Título del bloque';
$string['showlabels'] = 'Mostrar etiquetas';
$string['showlabelshelp'] = 'Mostrar etiquetas del asistente y usuario en la interfaz de chat';
$string['sourceoftruth'] = 'Fuente de verdad';
$string['sourceoftruthhelp'] = 'Añadir una lista de preguntas y respuestas que la IA debe usar para responder con precisión. El formato debe ser P: (pregunta) seguido de R: (respuesta).';
$string['completion_prompt'] = 'Prompt de completado';

// API settings
$string['apisettings'] = 'Configuración de API';
$string['apiurl'] = 'URL de API Ollama';
$string['apiurlhelp'] = 'La URL de tu servicio API Ollama, incluyendo el puerto (p.ej., http://localhost:11434)';
$string['model'] = 'Modelo Claude';
$string['modelhelp'] = 'El nombre del modelo Claude a utilizar (p.ej., claude, claude-instant)';
$string['loggedinonly'] = 'Restringir uso del chat a usuarios conectados';
$string['loggedonlyhelp'] = 'Si está marcado, solo los usuarios que hayan iniciado sesión podrán usar el chat';

// UI settings
$string['uisettings'] = 'Configuración de Interfaz de Usuario';
$string['assistantname'] = 'Nombre del asistente';
$string['assistantnamehelp'] = 'El nombre que se mostrará para el asistente IA en el chat';
$string['defaultassistantname'] = 'Claude';
$string['username'] = 'Nombre de usuario';
$string['usernamehelp'] = 'El nombre que se mostrará para el usuario en el chat';
$string['defaultusername'] = 'Tú';
$string['enablelogging'] = 'Habilitar registro de actividad';
$string['enablelogginghelp'] = 'Si está marcado, se registrarán todos los mensajes enviados por los usuarios junto con las respuestas de la IA';

// Prompt settings
$string['promptsettings'] = 'Configuración de Prompts';
$string['completionprompt'] = 'Prompt de completado';
$string['completionprompthelp'] = 'El texto añadido al inicio de la conversación para influir en la personalidad y respuestas de la IA';
$string['defaultcompletionprompt'] = 'Eres un asistente útil para una plataforma de aprendizaje Moodle. Proporcionas información concisa y precisa para ayudar a los estudiantes con sus preguntas. Si no conoces la respuesta, admítelo en lugar de adivinar.';

// Advanced settings
$string['advancedsettings'] = 'Configuración Avanzada';
$string['advancedsettingshelp'] = 'Estos son parámetros adicionales para ajustar el comportamiento del modelo';
$string['instancesettings'] = 'Configuración a nivel de instancia';
$string['instancesettingshelp'] = 'Si está marcado, permitirá que cualquiera que pueda añadir un bloque ajuste la configuración a nivel de bloque individual';
$string['temperature'] = 'Temperatura';
$string['temperaturehelp'] = 'Controla la aleatoriedad. Valores más bajos (p.ej., 0.2) hacen que la salida sea más enfocada y determinista, mientras que valores más altos (p.ej., 0.8) la hacen más creativa (0.0-1.0)';
$string['maxtokens'] = 'Tokens máximos';
$string['maxtokenshelp'] = 'El número máximo de tokens que la IA puede generar en su respuesta';

// Chat interface
$string['typemessage'] = 'Escribe tu mensaje...';
$string['sendmessage'] = 'Enviar';
$string['chatbeingrecorded'] = 'Este chat está siendo registrado';
$string['chathistory'] = 'Historial de chat';
$string['clearconversation'] = 'Borrar conversación';
$string['loadingresponse'] = 'Pensando...';
$string['erroroccurred'] = 'Ocurrió un error al procesar tu solicitud. Por favor, inténtalo de nuevo.';
$string['logintochat'] = 'Por favor, inicia sesión para usar la función de chat.';
$string['noapiurlsetup'] = 'Este bloque de chat aún no ha sido configurado. Por favor, contacta al administrador del sitio.';
$string['noapiurlsetupadmin'] = 'Claude Ollama no ha sido configurado aún. <a href="{$a}">Configura la URL de la API</a> para comenzar.';

<?php
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
