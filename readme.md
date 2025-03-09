# Ollama Claude AI Chat Block for Moodle

Este bloque permite a los usuarios de Moodle obtener soporte de chat 24/7 mediante Claude AI a través de Ollama. El bloque ofrece múltiples opciones para personalizar la persona de la IA y el prompt que se le proporciona, con el fin de influir en el texto que genera.

## Características

- Integración con Ollama para ejecutar modelos Claude localmente
- Soporte para fuentes de verdad personalizadas para respuestas más precisas
- Personalización de prompts a nivel de instancia y global
- Registro de interacciones (opcional)
- Interfaz de chat intuitiva con soporte para Markdown
- Persistencia de conversaciones entre cargas de página
- Configuración avanzada de parámetros del modelo (temperatura, tokens máximos)
- Soporte completo para multilenguaje

## Requisitos

- Moodle 4.0 o superior
- Servidor Ollama ejecutándose con un modelo Claude instalado
- PHP 7.4 o superior
- Permisos JavaScript adecuados en el navegador para la interfaz de chat

## Instalación

1. Descarga o clona este repositorio
2. Coloca los archivos en el directorio `blocks/igis_ollama_claude` de tu instalación de Moodle
3. Navega a "Administración del sitio" > "Notificaciones" para completar la instalación
4. Configura la URL de la API de Ollama en "Administración del sitio" > "Plugins" > "Bloques" > "Ollama Claude AI Chat Block"

## Configuración

### Configuración global

La configuración global del bloque se puede encontrar en "Administración del sitio" > "Plugins" > "Bloques" > "Ollama Claude AI Chat Block". Las opciones son:

- **URL de API de Ollama**: La URL de tu servidor Ollama, incluyendo el puerto (por defecto: http://localhost:11434)
- **Modelo Claude**: El nombre del modelo Claude a utilizar en Ollama
- **Restringir uso del chat a usuarios conectados**: Si está marcada, solo los usuarios que hayan iniciado sesión podrán usar el chat
- **Nombre del asistente**: La IA usará este nombre para sí misma en la conversación
- **Nombre del usuario**: La IA usará este nombre para el usuario en la conversación
- **Habilitar registro**: Marcar esta casilla registrará todos los mensajes enviados por los usuarios junto con la respuesta de la IA
- **Prompt de completado**: Aquí puedes editar el texto añadido al inicio de la conversación para influir en la personalidad y respuestas de la IA
- **Fuente de verdad**: Aquí puedes añadir una lista de preguntas y respuestas que la IA utilizará para responder con precisión a las consultas

### Configuración avanzada

- **Configuración a nivel de instancia**: Marcar esta casilla permitirá que cualquiera que pueda añadir un bloque ajuste la configuración a nivel de bloque individual
- **Temperatura**: Controla la aleatoriedad de las respuestas (0.0-1.0)
- **Tokens máximos**: El número máximo de tokens que la IA puede generar en su respuesta

### Configuración individual del bloque

Hay algunas configuraciones que se pueden cambiar a nivel de bloque individual. Puedes acceder a estas configuraciones entrando en el modo de edición en tu sitio y haciendo clic en el engranaje del bloque, y luego yendo a "Configurar bloque Ollama Claude AI Chat"

- **Título del bloque**: El título para este bloque
- **Mostrar etiquetas**: Si se deben mostrar los nombres elegidos para "Nombre del asistente" y "Nombre del usuario" en la interfaz de chat
- **Fuente de verdad**: Aquí puedes añadir una lista de preguntas y respuestas que la IA utilizará para responder con precisión a las consultas a nivel de instancia de bloque

Si "Configuración a nivel de instancia" está marcada en la configuración global del bloque, las siguientes configuraciones adicionales también estarán disponibles:

- **Nombre del asistente**: Personalizado para esta instancia
- **Nombre del usuario**: Personalizado para esta instancia
- **Prompt de completado**: Permite establecer un prompt de completado por bloque
- **Avanzado**: Parámetros adicionales para ajustar el comportamiento del modelo

## Fuente de verdad

Aunque la IA es muy capaz de fábrica, si no conoce la respuesta a una pregunta, es más probable que dé información incorrecta con confianza que negarse a responder. El plugin proporciona un área de texto tanto a nivel de instancia de bloque como a nivel de plugin donde los profesores o administradores pueden incluir una lista de preguntas y respuestas que la IA procesará antes de generar una respuesta.

Formato ejemplo:

```
P: ¿Cuándo es la fecha límite para el proyecto final?
R: La fecha límite para el proyecto final es el 15 de mayo de 2025.

P: ¿Quién es el profesor de este curso?
R: El profesor de este curso es Dr. González.
```

## Soporte

Para obtener soporte, por favor contacta a [sgonzalez@infraestructuragis.com](mailto:sgonzalez@infraestructuragis.com).

## Licencia

Este plugin está licenciado bajo la [GNU GPL v3 o posterior](http://www.gnu.org/copyleft/gpl.html).

## Créditos

Desarrollado por Sebastián González Zepeda.
