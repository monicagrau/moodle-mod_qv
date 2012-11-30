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
 * Define all the backup steps that will be used by the backup_qv_activity_task
 * 
 * @package    mod
 * @subpackage qv
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete qv structure for backup, with file and id annotations
 */     
class backup_qv_activity_structure_step extends backup_activity_structure_step {
 
    protected function define_structure() {
 
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');
 
        // Define each element separated
        $qv = new backup_nested_element('qv', array('id'), array(
            'name', 'intro', 'introformat', 'url', 'skin', 'maxattempts', 
            'width', 'height', 'avaluation', 'maxgrade', 'grade', 'lang', 
            'exiturl', 'timeavailable', 'timedue'));
 
        $sessions = new backup_nested_element('sessions');
 
        $session = new backup_nested_element('session', array('id'), array(
            'session_id', 'user_id', 'session_datetime', 'project_name',
            'session_key', 'session_code', 'session_context'));
 
        $activities = new backup_nested_element('sessionactivities');
 
        $activity = new backup_nested_element('sessionactivity', array('id'), array(
            'session_id', 'activity_id', 'activity_name', 'num_actions', 'score',
            'activity_solved', 'qualification', 'total_time', 'activity_code'));
        
        // Build the tree
        $qv->add_child($sessions);
        $sessions->add_child($session);
        $session->add_child($activities);
        $activities->add_child($activity);
 
        // Define sources
        $qv->set_source_table('qv', array('id' => backup::VAR_ACTIVITYID));
  
        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $session->set_source_table('qv_sessions', array('qvid' => backup::VAR_PARENTID));
            $activity->set_source_table('qv_activities', array('session_id' => '../../session_id'));
        }
        
        // Define id annotations
        $qv->annotate_ids('scale', 'grade');
        $session->annotate_ids('user', 'user_id');

        // Define file annotations
        $qv->annotate_files('mod_qv', 'intro', null);     // This file area hasn't itemid
        $qv->annotate_files('mod_qv', 'content', null);   // This file area hasn't itemid
 
        // Return the root element (qv), wrapped into standard activity structure
        return $this->prepare_activity_structure($qv);
    }
}