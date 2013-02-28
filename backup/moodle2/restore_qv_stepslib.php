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
 *
 * Define all the restore steps that will be used by the restore_qv_activity_task
 * 
 * @package    mod
 * @subpackage qv
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one qv activity
 */
class restore_qv_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('qv', '/activity/qv');
        if ($userinfo) {
            $paths[] = new restore_path_element('qv_session', '/activity/qv/sessions/session');
            $paths[] = new restore_path_element('qv_session_activity', '/activity/qv/sessions/session/sessionactivities/sessionactivity');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_qv($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timeavailable = $this->apply_date_offset($data->timeavailable);
        $data->timedue = $this->apply_date_offset($data->timedue);

        // insert the qv record
        $newitemid = $DB->insert_record('qv', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_qv_session($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->qvid = $this->get_new_parentid('qv');
        $data->user_id = $this->get_mappingid('user', $data->user_id);

        $data->session_datetime = date('Y-m-d h:i:s', $this->apply_date_offset(strtotime($data->session_datetime)));
        $data->session_id = time();
        $newitemid = $DB->insert_record('qv_sessions', $data);
        $data->id = $newitemid;
        $data->session_id = $newitemid;
        $DB->update_record('qv_sessions', $data);
        $this->set_mapping('qv_session', $oldid, $newitemid);
    }

    protected function process_qv_session_activity($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $oldsessionid = $data->session_id;
        $data->session_id = $this->get_mappingid('qv_session', $oldsessionid);
        $newitemid = $DB->insert_record('qv_activities', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    protected function after_execute() {
        // Add qv related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_qv', 'intro', null);
        $this->add_related_files('mod_qv', 'package', null);
    }
}
