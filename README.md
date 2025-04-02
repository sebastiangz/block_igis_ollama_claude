# Multi-provider AI Chat Block para Moodle

Este bloque permite a los usuarios de Moodle obtener soporte 24/7 a través de varios proveedores de IA:

- **Ollama** (local) - Para ejecutar modelos localmente como Claude, Llama, Mistral, etc.
- **Claude** (nube) - Utilizando la API de Anthropic
- **OpenAI** (nube) - Utilizando la API de OpenAI
- **Gemini** (nube) - Utilizando la API de Google

El bloque ofrece múltiples opciones para personalizar el comportamiento del asistente de IA, incluida la posibilidad de proporcionar una "fuente de verdad" personalizada para influir en las respuestas.

## Características principales

- **Múltiples proveedores de IA**: Integración con Ollama, Claude, OpenAI y Gemini
- **Selección dinámica de API**: Los usuarios pueden seleccionar qué proveedor de IA utilizar
- **Fuente de verdad personalizada**: Añade preguntas y respuestas específicas para influir en la IA
- **Configuración a nivel de instancia**: Personaliza cada bloque individualmente
- **Registro de interacciones**: Opcionalmente registra todas las conversaciones
- **Interfaz de chat intuitiva**: Diseño limpio y fácil de usar con soporte para markdown
- **Soporte multilingüe**: Disponible en inglés y español

## Requisitos

- Moodle 4.1 o superior
- PHP 7.4 o superior
- Al menos uno de los siguientes:
  - Ollama instalado en un servidor accesible
  - Una cuenta de Anthropic con clave API para Claude
  - Una cuenta de OpenAI con clave API
  - Una cuenta de Google Cloud con clave API para Gemini

## Instalación

1. Descarga el código del plugin
2. Descomprime el archivo en la carpeta `/blocks/` de tu instalación de Moodle
3. Renombra la carpeta a `igis_ollama_claude` si es necesario
4. Visita la página de notificaciones de administración para completar la instalación

## Configuración

### Configuración global

1. Accede a "Administración del sitio > Plugins > Bloques > Multi-provider AI Chat"
2. Configura las opciones generales:
   - API por defecto
   - Permitir selección de API
   - Restricción a usuarios conectados
   - Habilitar registro de actividad
   - Nombre del asistente y usuario
   - Prompt de completado y fuente de verdad global

3. Configura al menos un proveedor de IA:
   - **Ollama**: URL de la API (por defecto: http://localhost:11434)
   - **Claude**: Clave API y modelo a utilizar
   - **OpenAI**: Clave API y modelo GPT a utilizar
   - **Gemini**: Clave API y modelo a utilizar

4. Configura opciones avanzadas si es necesario:
   - Permitir configuración a nivel de instancia
   - Temperatura
   - Tokens máximos

### Añadir el bloque a un curso

1. Activa el modo de edición en el curso
2. Haz clic en "Añadir un bloque" y selecciona "Multi-provider AI Chat"
3. Configura el bloque según tus necesidades:
   - Título del bloque
   - Mostrar etiquetas
   - Fuente de verdad específica para este bloque
   
4. Si la configuración a nivel de instancia está habilitada, también podrás configurar:
   - API por defecto para este bloque
   - Modelos específicos para cada proveedor
   - Prompt personalizado
   - Configuración avanzada

## Uso de la "Fuente de verdad"

La fuente de verdad permite proporcionar información específica que la IA utilizará para responder preguntas. Es útil para:
- Responder preguntas específicas del curso
- Proporcionar información personalizada sobre la institución
- Corregir posibles errores o sesgos en las respuestas de la IA

Formato recomendado:
```
P: ¿Cuál es la fecha límite para el proyecto final?
R: La fecha límite para el proyecto final es el 15 de diciembre.

P: ¿Dónde puedo encontrar los recursos adicionales?
R: Los recursos adicionales están disponibles en la sección "Materiales complementarios" del curso.
```

## Proveedores de IA soportados

### Ollama (local)
Ollama permite ejecutar modelos de IA en local. Es ideal para instituciones con restricciones de privacidad o acceso a internet limitado.
- [Sitio web de Ollama](https://ollama.ai/)
- Modelos compatibles: Claude, Llama, Mistral, Gemma, etc.

### Claude (nube)
Claude de Anthropic es un asistente de IA avanzado con un fuerte enfoque en el comportamiento seguro y útil.
- [API de Claude](https://www.anthropic.com/claude)
- Se requiere una clave API de Anthropic

### OpenAI (nube)
OpenAI proporciona modelos GPT de alta calidad con amplias capacidades.
- [API de OpenAI](https://openai.com/api/)
- Se requiere una clave API de OpenAI

### Gemini (nube)
Gemini de Google es un modelo multimodal avanzado con excelentes capacidades de razonamiento.
- [API de Gemini](https://ai.google.dev/gemini-api)
- Se requiere una clave API de Google

## Registro y privacidad

Cuando el registro está habilitado, se guarda la siguiente información:
- Usuario que envió el mensaje
- Mensaje del usuario
- Respuesta del asistente IA
- Curso y contexto donde ocurrió la interacción
- Modelo y proveedor de IA utilizado
- Timestamp

Esta información se puede exportar y eliminar a través de las herramientas de privacidad de Moodle (GDPR).

## Licencia

Este plugin es software libre: se puede redistribuir y/o modificar bajo los términos de la Licencia Pública General GNU versión 3 o posterior.

## Créditos

Desarrollado por Sebastián González Zepeda (sgonzalez@infraestructuragis.com)

Inspirado en el bloque [OpenAI Chat Block](https://github.com/Limekiller/moodle-block_openai_chat) desarrollado por Bryce Yoder.