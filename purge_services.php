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
    
    try {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        
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
        $service = $DB->get_record('external_services', ['shortname' => $service_shortname]);
        if ($service) {
            $service_id = $service->id;
            
            // First, delete all service function mappings for this service
            $DB->delete_records('external_services_functions', ['externalserviceid' => $service_id]);
            
            // Then, delete the service itself
            $DB->delete_records('external_services', ['id' => $service_id]);
            echo "<div>Eliminado servicio: $service_shortname (ID: $service_id)</div>";
        } else {
            echo "<div>No se encontró el servicio: $service_shortname</div>";
        }
        
        // 3. Delete any remaining service functions mappings for our functions
        $DB->execute("DELETE FROM {external_services_functions} 
                      WHERE functionname IN ('block_igis_ollama_claude_get_chat_response', 
                                           'block_igis_ollama_claude_clear_conversation')");
        echo "<div>Eliminadas asignaciones de funciones</div>";
        
        // 4. Purge all caches
        purge_all_caches();
        echo "<div>Purgadas todas las cachés</div>";
        
        // 5. Re-include the services.php file to register the service and functions
        global $CFG;
        require_once($CFG->dirroot . '/blocks/igis_ollama_claude/db/services.php');
        echo "<div>Recargado archivo services.php</div>";
        
        // 6. Manually register the functions
        $functionsConfig = [
            'block_igis_ollama_claude_get_chat_response' => [
                'classname' => 'block_igis_ollama_claude_external',
                'methodname' => 'get_chat_response',
                'description' => 'Get a response from the selected AI provider'
            ],
            'block_igis_ollama_claude_clear_conversation' => [
                'classname' => 'block_igis_ollama_claude_external',
                'methodname' => 'clear_conversation',
                'description' => 'Clear the conversation history'
            ]
        ];
        
        foreach ($functionsConfig as $function => $config) {
            // Check if the function already exists (in case the include added it)
            if (!$DB->record_exists('external_functions', ['name' => $function])) {
                $new_function = new stdClass();
                $new_function->name = $function;
                $new_function->classname = $config['classname'];
                $new_function->methodname = $config['methodname'];
                $new_function->description = $config['description'];
                
                try {
                    $DB->insert_record('external_functions', $new_function, false, true);
                    echo "<div>Registrada manualmente función: $function</div>";
                } catch (Exception $e) {
                    echo "<div class='alert alert-warning'>Error al registrar función $function: " . $e->getMessage() . "</div>";
                    // Continue with the next function
                }
            } else {
                echo "<div>La función $function ya existe</div>";
            }
        }
        
        // 7. Check if the service already exists after the include
        $service = $DB->get_record('external_services', ['shortname' => $service_shortname]);
        
        if (!$service) {
            // Service doesn't exist, create it manually
            $service_obj = new stdClass();
            $service_obj->name = 'Multi-provider AI Chat Services';
            $service_obj->shortname = $service_shortname;
            $service_obj->enabled = 1;
            $service_obj->restrictedusers = 0;
            $service_obj->downloadfiles = 0;
            $service_obj->uploadfiles = 0;
            
            try {
                $service_id = $DB->insert_record('external_services', $service_obj, true);
                echo "<div>Registrado servicio con ID: $service_id</div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-warning'>Error al registrar servicio: " . $e->getMessage() . "</div>";
                // Continue anyway to try the function mappings
                $service = $DB->get_record('external_services', ['shortname' => $service_shortname]);
                if ($service) {
                    $service_id = $service->id;
                } else {
                    throw new Exception("No se pudo crear el servicio ni encontrar uno existente.");
                }
            }
        } else {
            $service_id = $service->id;
            echo "<div>El servicio ya existe con ID: $service_id</div>";
        }
        
        // 8. Register the functions for the service
        foreach ($functions as $function) {
            // Check if the mapping already exists
            $exists = $DB->record_exists('external_services_functions', [
                'externalserviceid' => $service_id,
                'functionname' => $function
            ]);
            
            if (!$exists) {
                try {
                    $DB->insert_record('external_services_functions', [
                        'externalserviceid' => $service_id,
                        'functionname' => $function
                    ]);
                    echo "<div>Registrada función $function para el servicio</div>";
                } catch (Exception $e) {
                    echo "<div class='alert alert-warning'>Error al registrar función $function para el servicio: " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<div>La función $function ya está registrada para el servicio</div>";
            }
        }
        
        // 9. Update the plugin version to force Moodle to refresh the services
        $current_version = $DB->get_field('config_plugins', 'value', ['plugin' => 'block_igis_ollama_claude', 'name' => 'version']);
        if ($current_version) {
            $DB->set_field('config_plugins', 'value', $current_version + 1, ['plugin' => 'block_igis_ollama_claude', 'name' => 'version']);
            echo "<div>Actualizada versión del plugin a " . ($current_version + 1) . "</div>";
        }
        
        // Commit the transaction
        $transaction->allow_commit();
        
        echo '<div class="alert alert-success mt-3">';
        echo 'Servicios web purgados y reinstalados correctamente.';
        echo '</div>';
        
        // Provide links to next steps
        echo '<div class="mt-3">';
        echo '<p>Próximos pasos recomendados:</p>';
        echo '<ol>';
        echo '<li>Purga todas las cachés de Moodle desde Administración > Desarrollo > Purgar todas las cachés</li>';
        echo '<li>Verifica los servicios web registrados en Administración > Plugins > Servicios web > Servicios externos</li>';
        echo '<li>Prueba el envío de mensajes en el bloque de chat</li>';
        echo '</ol>';
        echo '</div>';
        
    } catch (Exception $e) {
        // Handle transaction rollback
        if (isset($transaction) && $transaction instanceof \core\dml\sql_transaction) {
            $transaction->rollback(new \Exception('Error durante la purga de servicios: ' . $e->getMessage()));
        }
        
        echo '<div class="alert alert-danger mt-3">';
        echo '<h4>Error durante la purga de servicios:</h4>';
        echo '<p>' . $e->getMessage() . '</p>';
        
        // Provide technical details for admin
        echo '<div class="mt-2 p-3 bg-light">';
        echo '<h5>Detalles técnicos:</h5>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
        echo '</div>';
        
        // Suggest fixes
        echo '<h5 class="mt-3">Posibles soluciones:</h5>';
        echo '<ol>';
        echo '<li>Verifica que tienes permisos de escritura en la base de datos.</li>';
        echo '<li>Comprueba que no hay problemas de bloqueo de tablas en la base de datos.</li>';
        echo '<li>Intenta purgar todas las cachés desde Administración > Desarrollo > Purgar todas las cachés.</li>';
        echo '<li>Si el problema persiste, intenta reinstalar manualmente el plugin o contacta con el administrador del sistema.</li>';
        echo '</ol>';
        
        echo '</div>';
    }
    
    $settings_url = new moodle_url('/admin/settings.php', ['section' => 'blocksettingigis_ollama_claude']);
    $diagnostics_url = new moodle_url('/blocks/igis_ollama_claude/diagnostics.php');
    $purge_cache_url = new moodle_url('/admin/purgecaches.php', ['confirm' => 1, 'sesskey' => sesskey()]);
    
    echo '<div class="mt-3">';
    echo '<a href="' . $settings_url->out() . '" class="btn btn-primary">Volver a la configuración</a> ';
    echo '<a href="' . $diagnostics_url->out() . '" class="btn btn-info">Ver diagnósticos</a> ';
    echo '<a href="' . $purge_cache_url->out() . '" class="btn btn-warning">Purgar todas las cachés</a>';
    echo '</div>';
}

echo $OUTPUT->footer();