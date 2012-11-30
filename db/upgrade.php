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
 * This file keeps track of upgrades to the qv module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod
 * @subpackage qv
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute qv upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_qv_upgrade($oldversion=0) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2007092500) {
        /// Define and launch lang field format to be added to qv
        $table = new xmldb_table('qv');
        $field = new xmldb_field('assessmentlang');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, null, 'ca', 'skin');

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2008051500) {
        /// Define fields ordersections, orderitems, sectionorder, itemorder
        $table1 = new xmldb_table('qv');

        $field1 = new xmldb_field('ordersections');
        $field1->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0', 'showinteraction');
        $dbman->add_field($table1, $field1);

        $field2 = new xmldb_field('orderitems');
        $field2->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0', 'ordersections');
        $dbman->add_field($table1, $field2);


        $table2 = new xmldb_table('qv_assignments');

        $field3 = new xmldb_field('sectionorder');
        $field3->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'userid');
        $dbman->add_field($table2, $field3);

        $field4 = new xmldb_field('itemorder');
        $field4->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'sectionorder');
        $dbman->add_field($table2, $field4);
    }
    
    if ($oldversion < 2008052600) {
        /// Define field time in qv_sections
        $table3 = new xmldb_table('qv_sections');

        $field5 = new xmldb_field('time');
        $field5->setAttributes(XMLDB_TYPE_CHAR, '8', null, XMLDB_NOTNULL, null, null, null, '00:00:00', 'state');
        $dbman->add_field($table3, $field5);
    }
	
    if ($oldversion < 2008060300) {
        /// Define field time in qv_sections
        $field6 = new xmldb_field('pending_scores');
        $field6->setAttributes(XMLDB_TYPE_TEXT, null, null, null, null, null, null, '', 'scores');
        $dbman->add_field($table3, $field6);
    }

    if ($oldversion < 2008061100) {
        // @Albert Llastarri
        if ($qv_sections=get_records('qv_sections')){
            foreach($qv_sections as $qv_section){
                $qv_section->pending_scores=$qv_section->scores;
                update_record("qv_sections", $qv_section);
            }
        }
    }


    if ($oldversion < 2012112100) {
        /// Define field introformat to be added to qv
        $table = new xmldb_table('qv');
        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'name');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'intro');
        }
        
        $field = new xmldb_field('introformat');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

        /// Launch add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // conditionally migrate to html format in intro
        if ($CFG->texteditors !== 'textarea') {
            $rs = $DB->get_recordset('qv', array('introformat'=>FORMAT_MOODLE), '', 'id,intro,introformat');
            foreach ($rs as $f) {
                $f->intro       = text_to_html($f->intro, false, false, true);
                $f->introformat = FORMAT_HTML;
                $DB->update_record('qv', $f);
                upgrade_set_timeout();
            }
            $rs->close();
        }

        /// qv savepoint reached
        upgrade_mod_savepoint(true, 2012112100, 'qv');
    }

//===== 1.9.0 upgrade line ======//

    if ($oldversion < 2012112101) {

        require_once("$CFG->dirroot/mod/qv/db/upgradelib.php");
        // Add upgrading code from 1.9 (+ new file storage system)
        // @TODO: test it!!!!
        qv_migrate_files();
        
        // Rename field maxgrade to grade on table qv
        $table = new xmldb_table('qv');
        $field = new xmldb_field('maxgrade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'target');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'grade');
        }

        // Add fields timeavailable and timedue on table qv
        $field = new xmldb_field('timeavailable', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0', 'exiturl');
        $dbman->add_field($table, $field);

        $field = new xmldb_field('timedue', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0', 'timeavailable');
        $dbman->add_field($table, $field);
        
        // qv savepoint reached
        upgrade_mod_savepoint(true, 2012112101, 'qv');
    }
    
    return true;
}