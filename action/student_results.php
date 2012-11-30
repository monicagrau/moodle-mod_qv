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
 * Prints results from a specific session
 *
 * @package    mod
 * @subpackage qv
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/../config.php');
require_once(dirname(__FILE__).'/../locallib.php');
    
$id = optional_param('id', 0, PARAM_INT); // course_module ID, or

if ($id) {
    $cm         = get_coursemodule_from_id('qv', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $qv      = $DB->get_record('qv', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/qv:view', $context);


$strqvs = get_string("modulenameplural", "qv");
$strstarttime  = get_string("starttime", "qv");
$strscore  = get_string("score", "qv");
$strtotaltime  = get_string("totaltime", "qv");
$strtotals  = get_string("totals", "qv");
$strdone  = get_string("activitydone", "qv");
$stractivitysolved  = get_string("activitysolved", "qv");
$strattempts  = get_string("attempts", "qv");
$strlastaccess  = get_string("lastaccess", "qv");
$strmsgnosessions  = get_string("msg_nosessions", "qv");

$stractivity = get_string("activity", "qv");
$strsolved = get_string("solved", "qv");
$stractions = get_string("actions", "qv");
$strtime = get_string("time", "qv");
$stryes = get_string("yes");
$strno = get_string("no");


$PAGE->set_url('/mod/qv/action/student_results.php', array('id' => $cm->id));
$PAGE->set_title(format_string($course->fullname.' - '.$qv->name));
//$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
echo $OUTPUT->header();

$sessions = qv_get_sessions($qv->id, $USER->id);

if (sizeof($sessions)>0){
    $PAGE->requires->js('/mod/qv/qv.js');
    $table = new html_table();
    $table->head = array($strstarttime, $strscore, $strtotaltime, get_string('solveddone', 'qv'), $strattempts);
    
    // Print session data
    foreach($sessions as $session){
        $starttime='<a href="#" onclick="showSessionActivities(\''.$session->session_id.'\');">'.date('d/m/Y H:i', strtotime($session->starttime)).'</a>';
        $table->data[] = array($starttime, $session->score.'%', $session->totaltime, $session->solved.' / '.$session->done,$session->attempts.($qv->maxattempts>0?'/'.$qv->maxattempts:''));
        // Print activities for each session
        $session_activities_html= qv_get_session_activities_html($session->session_id);
        $cell = new html_table_cell();
        $cell->text = $session_activities_html;
        $cell->colspan = 5;
        $row = new html_table_row();
        $row->id = 'session_'.$session->session_id;
        $row->attributes = array('class' => 'qv-session-activities-hidden') ;
        $row->cells[] = $cell;     
        $table->data[] = $row;
    }
    
    if (sizeof($sessions)>1){
        $sessions_summary = qv_get_sessions_summary($qv->id,$USER->id);                
        $table->data[] = array('<b>'.$strtotals.'</b>', '<b>'.$sessions_summary->score.'%</b>', '<b>'.$sessions_summary->totaltime.'</b>','<b>'.$sessions_summary->solved.' / '.$sessions_summary->done.'</b>','<b>'.$sessions_summary->attempts.'</b>');
    }
    echo html_writer::table($table);
} else{
  echo '<br><center>'.$strmsgnosessions.'</center>';
}

echo $OUTPUT->footer();