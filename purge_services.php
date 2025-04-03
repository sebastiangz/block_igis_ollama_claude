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
 * Script to purge and reinstall web services
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

// Verificar que el usuario tenga permisos de administrador
if (!is_siteadmin()) {
    redirect(new moodle_url('/'), get_string('accessdenied', 'admin'));
}

// Check for confirmation
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$PAGE->set_url(new moodle_url('/blocks/igis_ollama_claude/purge_services.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Multi-provider AI Chat Block - Purgar servicios web');
$PAGE->set_heading('Purgar y reinstalar servicios web');

echo $OUTPUT->header();
echo $OUTPUT->heading('Purgar y reinstalar servicios web');

if (!$confirm) {
    // Show confirmation page
    echo '<div class="alert alert-warning">';
    echo '<p>Esta herramienta eliminará y volverá a registrar los servicios web del plugin Multi-provider AI Chat Block.</p>';
    echo '<p>Esto puede ayudar a solucionar problemas con las llamadas AJAX cuando no se están enviando correctamente los mensajes a las IAs.</p>';
    echo '<p>¿Estás seguro de que deseas continuar?</p>';
    echo '</div>';
    
    $continue_url = new moodle_url('/blocks/igis_ollama_claude/purge_services.php', ['confirm' => 1]);
    $cancel_url = new moodle_url('/admin/settings.php', ['section' => 'blocksettingigis_ollama_claude']);
    
    echo '<div class="mt-3">';
    echo '<a href="' . $continue_url->out() . '" class="btn btn-danger">Sí, purgar y reinstalar servicios</a> ';
    echo '<a href="' . $cancel_url->out() . '" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';
} else {
    // Perform the purge and reinstall
    echo '<div class="alert alert-info">Ejecutando purga y reinstalación de servicios web...</div>';
    
    // 1. Delete existing external functions
    $functions = [
        'block_igis_ollama_claude_get_chat_response',
        'block_igis_ollama_claude_clear_conversation'
    ];
    
    foreach ($functions as $function) {
        $DB->delete_records('external_functions', ['name' => $function]);
        echo "<div>Eliminada función: $function</div>";
    }
    
    // 2. Delete the service
    $service_shortname = 'igis_ollama_claude_service';
    $DB->delete_records('external_services', ['shortname' => $service_shortname]);
    echo "<div>Eliminado servicio: $service_shortname</div>";
    
    // 3. Delete existing service functions mappings
    $DB->execute("DELETE FROM {external_services_functions} WHERE functionname IN ('block_igis_ollama_claude_get_chat_response', 'block_igis_ollama_claude_clear_conversation')");
    echo "<div>Eliminadas asignaciones de funciones</div>";
    
    // 4. Purge all caches
    purge_all_caches();
    echo "<div>Purgadas todas las cachés</div>";
    
    // 5. Re-include the services.php file to register the service and functions
    require_once($CFG->dirroot . '/blocks/igis_ollama_claude/db/services.php');
    echo "<div>Recargado archivo services.php</div>";
    
    // 6. Manually register the functions if needed
    $existing_functions = $DB->get_records_menu('external_functions', null, '', 'name, id');
    foreach ($functions as $function) {
        if (!isset($existing_functions[$function])) {
            $new_function = new stdClass();
            $new_function->name = $function;
            $new_function->classname = 'block_igis_ollama_claude_external';
            $new_function->methodname = str_replace('block_igis_ollama_claude_', '', $function);
            $new_function->description = 'Function for Multi-provider AI Chat Block';
            $DB->insert_record('external_functions', $new_function);
            echo "<div>Registrada manualmente función: $function</div>";
        } else {
            echo "<div>Función ya registrada: $function</div>";
        }
    }
    
    // 7. Manually register the service
    $existing_service = $DB->get_record('external_services', ['shortname' => $service_shortname]);
    if (!$existing_service) {
        $service = new stdClass();
        $service->name = 'Multi-provider AI Chat Services';
        $service->shortname = $service_shortname;
        $service->enabled = 1;
        $service->restrictedusers = 0;
        $service->downloadfiles = 0;
        $service->uploadfiles = 0;
        $service_id = $DB->insert_record('external_services', $service);
        echo "<div>Registrado servicio con ID: $service_id</div>";
    } else {
        $service_id = $existing_service->id;
        echo "<div>Servicio ya registrado con ID: $service_id</div>";
    }
    
    // 8. Manually register the functions for the service
    foreach ($functions as $function) {
        $exists = $DB->record_exists('external_services_functions', [
            'externalserviceid' => $service_id,
            'functionname' => $function
        ]);
        
        if (!$exists) {
            $DB->insert_record('external_services_functions', [
                'externalserviceid' => $service_id,
                'functionname' => $function
            ]);
            echo "<div>Registrada función $function para el servicio</div>";
        } else {
            echo "<div>Función $function ya registrada para el servicio</div>";
        }
    }
    
    // 9. Update the plugin version in the DB to force a refresh
    $current_version = $DB->get_field('config_plugins', 'value', ['plugin' => 'block_igis_ollama_claude', 'name' => 'version']);
    if ($current_version) {
        $DB->set_field('config_plugins', 'value', $current_version + 1, ['plugin' => 'block_igis_ollama_claude', 'name' => 'version']);
        echo "<div>Actualizada versión del plugin</div>";
    }
    
    echo '<div class="alert alert-success mt-3">';
    echo 'Servicios web purgados y reinstalados correctamente.';
    echo '</div>';
    
    // 10. Provide links to next steps
    echo '<div class="mt-3">';
    echo '<p>Próximos pasos recomendados:</p>';
    echo '<ol>';
    echo '<li>Recarga esta página o vuelve a la configuración del plugin</li>';
    echo '<li>Abre la página de diagnóstico para verificar el registro de los servicios</li>';
    echo '<li>Prueba el envío de mensajes en el bloque de chat</li>';
    echo '</ol>';
    echo '</div>';
    
    $settings_url = new moodle_url('/admin/settings.php', ['section' => 'blocksettingigis_ollama_claude']);
    $diagnostics_url = new moodle_url('/blocks/igis_ollama_claude/diagnostics.php');
    
    echo '<div class="mt-3">';
    echo '<a href="' . $settings_url->out() . '" class="btn btn-primary">Volver a la configuración</a> ';
    echo '<a href="' . $diagnostics_url->out() . '" class="btn btn-info">Ver diagnósticos</a>';
    echo '</div>';
}

echo $OUTPUT->footer();