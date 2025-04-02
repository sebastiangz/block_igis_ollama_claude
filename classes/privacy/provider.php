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
 * Privacy Subsystem implementation for Multi-provider AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_igis_ollama_claude\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for the Multi-provider AI Chat Block
 *
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns metadata about this plugin's privacy practices.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'block_igis_ollama_claude_logs',
            [
                'userid' => 'privacy:metadata:block_igis_ollama_claude_logs:userid',
                'courseid' => 'privacy:metadata:block_igis_ollama_claude_logs:courseid',
                'contextid' => 'privacy:metadata:block_igis_ollama_claude_logs:contextid',
                'instanceid' => 'privacy:metadata:block_igis_ollama_claude_logs:instanceid',
                'message' => 'privacy:metadata:block_igis_ollama_claude_logs:message',
                'response' => 'privacy:metadata:block_igis_ollama_claude_logs:response',
                'timecreated' => 'privacy:metadata:block_igis_ollama_claude_logs:timecreated'
            ],
            'privacy:metadata:block_igis_ollama_claude_logs'
        );

        $collection->add_external_location_link(
            'ollama_api',
            [
                'message' => 'privacy:metadata:ollama_api:message',
                'prompt' => 'privacy:metadata:ollama_api:prompt',
                'sourceoftruth' => 'privacy:metadata:ollama_api:sourceoftruth'
            ],
            'privacy:metadata:ollama_api'
        );
        
        $collection->add_external_location_link(
            'claude_api',
            [
                'message' => 'privacy:metadata:claude_api:message',
                'prompt' => 'privacy:metadata:claude_api:prompt',
                'sourceoftruth' => 'privacy:metadata:claude_api:sourceoftruth'
            ],
            'privacy:metadata:claude_api'
        );
        
        $collection->add_external_location_link(
            'openai_api',
            [
                'message' => 'privacy:metadata:openai_api:message',
                'prompt' => 'privacy:metadata:openai_api:prompt',
                'sourceoftruth' => 'privacy:metadata:openai_api:sourceoftruth'
            ],
            'privacy:metadata:openai_api'
        );
        
        $collection->add_external_location_link(
            'gemini_api',
            [
                'message' => 'privacy:metadata:gemini_api:message',
                'prompt' => 'privacy:metadata:gemini_api:prompt',
                'sourceoftruth' => 'privacy:metadata:gemini_api:sourceoftruth'
            ],
            'privacy:metadata:gemini_api'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Add contexts from log entries.
        $sql = "SELECT contextid 
                  FROM {block_igis_ollama_claude_logs}
                 WHERE userid = :userid";
        $params = ['userid' => $userid];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        
        $sql = "SELECT cl.id, cl.courseid, cl.contextid, cl.instanceid, cl.message, cl.response, 
                       cl.sourceoftruth, cl.prompt, cl.api, cl.model, cl.timecreated, c.fullname as coursename,
                       bi.blockname
                  FROM {block_igis_ollama_claude_logs} cl
                  JOIN {course} c ON c.id = cl.courseid
                  JOIN {block_instances} bi ON bi.id = cl.instanceid
                 WHERE cl.userid = :userid AND cl.contextid {$contextsql}
              ORDER BY cl.timecreated ASC";
        
        $params = array_merge(['userid' => $user->id], $contextparams);
        $logs = $DB->get_records_sql($sql, $params);

        // Export data organized by context.
        $contextdata = [];
        foreach ($logs as $log) {
            if (!isset($contextdata[$log->contextid])) {
                $contextdata[$log->contextid] = [];
            }
            
            $contextdata[$log->contextid][] = [
                'coursename' => $log->coursename,
                'blockname' => $log->blockname,
                'message' => $log->message,
                'response' => $log->response,
                'model' => $log->model,
                'api' => $log->api,
                'timecreated' => transform::datetime($log->timecreated)
            ];
        }
        
        // Write the data for each context.
        foreach ($contextdata as $contextid => $data) {
            $context = \context::instance_by_id($contextid);
            writer::with_context($context)->export_data(
                ['block_igis_ollama_claude'],
                (object)['interactions' => $data]
            );
        }
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        
        $DB->delete_records('block_igis_ollama_claude_logs', ['contextid' => $context->id]);
    }

    /**
     * Delete all personal data for the specified user in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        
        if (empty($contextlist->count())) {
            return;
        }
        
        $userid = $contextlist->get_user()->id;
        
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        
        $params = array_merge(['userid' => $userid], $contextparams);
        
        $DB->delete_records_select(
            'block_igis_ollama_claude_logs',
            "userid = :userid AND contextid {$contextsql}",
            $params
        );
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        
        $sql = "SELECT userid 
                  FROM {block_igis_ollama_claude_logs}
                 WHERE contextid = :contextid";
        
        $params = ['contextid' => $context->id];
        
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        
        $context = $userlist->get_context();
        
        list($usersql, $userparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        
        $params = array_merge(['contextid' => $context->id], $userparams);
        
        $DB->delete_records_select(
            'block_igis_ollama_claude_logs',
            "contextid = :contextid AND userid {$usersql}",
            $params
        );
    }
}