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

// Configuración general
$string['generalsettings'] = 'Configuración General';
$string['defaultapi'] = 'Servicio API predeterminado';
$string['defaultapihelp'] = 'Seleccione qué servicio de IA usar por defecto';
$string['ollamaapi'] = 'Ollama (local)';
$string['claudeapi'] = 'Claude (nube)';
$string['allowapiselection'] = 'Permitir selección de API';
$string['allowapiselectionhelp'] = 'Si está habilitado, los usuarios pueden seleccionar qué servicio de IA utilizar';
$string['selectapi'] = 'Seleccionar servicio de IA';

// Configuración del bloque
$string['blocktitle'] = 'Título del bloque';
$string['showlabels'] = 'Mostrar etiquetas';
$string['showlabelshelp'] = 'Mostrar etiquetas del asistente y usuario en la interfaz de chat';
$string['sourceoftruth'] = 'Fuente de verdad';
$string['sourceoftruthhelp'] = 'Añadir una lista de preguntas y respuestas que la IA debe usar para responder con precisión. El formato debe ser P: (pregunta) seguido de R: (respuesta).';
$string['completion_prompt'] = 'Prompt de completado';

// Configuración API - Ollama
$string['ollamaapisettings'] = 'Configuración API Ollama';
$string['ollamaapiurl'] = 'URL API Ollama';
$string['ollamaapiurlhelp'] = 'La URL de su servicio API Ollama, incluyendo el puerto (p.ej., http://localhost:11434)';
$string['ollamamodel'] = 'Modelo Ollama';
$string['ollamamodelhelp'] = 'El nombre del modelo Claude a utilizar en Ollama (p.ej., claude, claude-instant)';

// Configuración API - Claude
$string['claudeapisettings'] = 'Configuración API Claude';
$string['claudeapikey'] = 'Clave API Claude';
$string['claudeapikeyhelp'] = 'Su clave API de Anthropic Claude para acceder al servicio en la nube';
$string['claudeapiurl'] = 'URL API Claude';
$string['claudeapiurlhelp'] = 'La URL para la API de Claude (normalmente https://api.anthropic.com/v1/messages)';
$string['claudemodel'] = 'Modelo Claude';
$string['claudemodelhelp'] = 'La versión específica del modelo Claude a utilizar (p.ej., claude-3-opus, claude-3-sonnet)';

// Configuración de restricción de acceso
$string['loggedinonly'] = 'Restringir uso del chat a usuarios conectados';
$string['loggedonlyhelp'] = 'Si está marcado, solo los usuarios que hayan iniciado sesión podrán usar el chat';

// Configuración de interfaz de usuario
$string['uisettings'] = 'Configuración de Interfaz de Usuario';
$string['assistantname'] = 'Nombre del asistente';
$string['assistantnamehelp'] = 'El nombre que se mostrará para el asistente IA en el chat';
$string['defaultassistantname'] = 'Claude';
$string['username'] = 'Nombre de usuario';
$string['usernamehelp'] = 'El nombre que se mostrará para el usuario en el chat';
$string['defaultusername'] = 'Tú';
$string['enablelogging'] = 'Habilitar registro de actividad';
$string['enablelogginghelp'] = 'Si está marcado, se registrarán todos los mensajes enviados por los usuarios junto con las respuestas de la IA';

// Configuración de prompts
$string['promptsettings'] = 'Configuración de Prompts';
$string['completionprompt'] = 'Prompt de completado';
$string['completionprompthelp'] = 'El texto añadido al inicio de la conversación para influir en la personalidad y respuestas de la IA';
$string['defaultcompletionprompt'] = 'Eres un asistente útil para una plataforma de aprendizaje Moodle. Proporcionas información concisa y precisa para ayudar a los estudiantes con sus preguntas. Si no conoces la respuesta, admítelo en lugar de adivinar.';

// Configuración avanzada
$string['advancedsettings'] = 'Configuración Avanzada';
$string['advancedsettingshelp'] = 'Estos son parámetros adicionales para ajustar el comportamiento del modelo';
$string['instancesettings'] = 'Configuración a nivel de instancia';
$string['instancesettingshelp'] = 'Si está marcado, permitirá que cualquiera que pueda añadir un bloque ajuste la configuración a nivel de bloque individual';
$string['temperature'] = 'Temperatura';
$string['temperaturehelp'] = 'Controla la aleatoriedad. Valores más bajos (p.ej., 0.2) hacen que la salida sea más enfocada y determinista, mientras que valores más altos (p.ej., 0.8) la hacen más creativa (0.0-1.0)';
$string['maxtokens'] = 'Tokens máximos';
$string['maxtokenshelp'] = 'El número máximo de tokens que la IA puede generar en su respuesta';

// Interfaz de chat
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

// Strings para privacidad
$string['privacy:metadata:block_igis_ollama_claude_logs'] = 'Información sobre las interacciones de los usuarios con el asistente IA Claude';
$string['privacy:metadata:block_igis_ollama_claude_logs:userid'] = 'El ID del usuario que envió el mensaje';
$string['privacy:metadata:block_igis_ollama_claude_logs:courseid'] = 'El ID del curso donde ocurrió la interacción';
$string['privacy:metadata:block_igis_ollama_claude_logs:contextid'] = 'El ID del contexto donde ocurrió la interacción';
$string['privacy:metadata:block_igis_ollama_claude_logs:instanceid'] = 'El ID de la instancia del bloque donde ocurrió la interacción';
$string['privacy:metadata:block_igis_ollama_claude_logs:message'] = 'El mensaje enviado por el usuario';
$string['privacy:metadata:block_igis_ollama_claude_logs:response'] = 'La respuesta del asistente IA';
$string['privacy:metadata:block_igis_ollama_claude_logs:timecreated'] = 'El momento en que ocurrió la interacción';
$string['privacy:metadata:block_igis_ollama_claude_logs:api'] = 'El servicio API utilizado para procesar la petición';
$string['privacy:metadata:ollama_api'] = 'Para integrarse con Ollama, algunos datos deben enviarse al servicio API Ollama';
$string['privacy:metadata:ollama_api:message'] = 'El mensaje enviado por el usuario';
$string['privacy:metadata:ollama_api:prompt'] = 'El prompt del sistema utilizado para guiar el comportamiento de la IA';
$string['privacy:metadata:ollama_api:sourceoftruth'] = 'Los datos de la fuente de verdad utilizados para proporcionar información precisa';
$string['privacy:metadata:claude_api'] = 'Para integrarse con Claude, algunos datos deben enviarse al servicio API Claude';
$string['privacy:metadata:claude_api:message'] = 'El mensaje enviado por el usuario';
$string['privacy:metadata:claude_api:prompt'] = 'El prompt del sistema utilizado para guiar el comportamiento de la IA';
$string['privacy:metadata:claude_api:sourceoftruth'] = 'Los datos de la fuente de verdad utilizados para proporcionar información precisa';

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