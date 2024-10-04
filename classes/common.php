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

namespace block_readaloudstudent;

use block_readaloudstudent\constants;
use block_readaloudstudent\flower;

defined('MOODLE_INTERNAL') || die();


/**
 *
 * This is a class containing constants and static functions for general use around the plugin
 *
 * @package   block_newtemplate
 * @since      Moodle 3.4
 * @copyright  2018 Justin Hunt (https://poodll,com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class common {


    // this is a helper function to prepare data to be passed to the something_happened event
    public static function fetch_event_data($blockid=0) {
        global $USER;
        $config = self::fetch_best_config($blockid);

        if($blockid == 0) {
            $eventdata = [
                'context' => \context_system::instance(0),
            'userid' => 0,
            'relateduserid' => 0,
            'other' => $config->sometext,
            ];
        }else{

            $eventdata = [
            'context' => \context_block::instance($blockid),
            'userid' => $USER->id,
            'relateduserid' => 0,
            'other' => $config->sometext,
            ];
        }
        return $eventdata;
    }

    // this merges the local config and admin config settings to make it easy to assume there is a setting
    // and to get it.
    public static function fetch_best_config($blockid=0) {
        global $DB;

        $config = get_config(constants::M_COMP);
        $localconfig = false;
        if($blockid > 0) {
            $configdata = $DB->get_field('block_instances', 'configdata', ['id' => $blockid]);
            if($configdata){
                $localconfig = unserialize(base64_decode($configdata));
            }

            if($localconfig){
                $localvars = get_object_vars($localconfig);
                foreach($localvars as $prop => $value){
                    $config->{$prop} = $value;
                }
            }
        }
        return $config;
    }

    public static function fetch_showcourses_options() {
        $options = [
            constants::M_THISCOURSE => get_string('thiscourse', constants::M_COMP),
            constants::M_ENROLLEDCOURSES => get_string('enrolledcourses', constants::M_COMP),
            constants::M_ACTIVECOURSES => get_string('activecourses', constants::M_COMP),
        ];
        return $options;
    }

    public static function fetch_forcesequence_options() {
        $options = [
            constants::M_FORCESEQUENCE => get_string('forcesequenceyes', constants::M_COMP),
            constants::M_DONTFORCESEQUENCE => get_string('forcesequenceno', constants::M_COMP),
        ];
        return $options;
    }

    public static function fetch_showreadings_options() {
        $options = [
            constants::M_SHOWALLREADINGS => get_string('showallreadings', constants::M_COMP),
            constants::M_SHOWMYREADINGS => get_string('showmyreadingsonly', constants::M_COMP),
        ];
        return $options;
    }

    /*
     * Fetch all the courses for which a user has readaloud attempts
     *
     */
    public static function fetch_courses_with_myattempts($userid) {
        global $DB;

        $sql = 'SELECT DISTINCT c.id, c.fullname ';
        $sql .= ' FROM {' . constants::M_ATTEMPTTABLE . '} a';
        $sql .= ' INNER JOIN {course} c ON a.courseid=c.id';
        $sql .= ' WHERE a.userid =?';
        $records = $DB->get_records_sql($sql, [$userid]);
        return $records;
    }

    /*
    * Fetch all the courses for which there are readalouds AND the user is enrolled
    *
    */
    public static function fetch_courses_myenrolled($userid) {
        global $DB;

        $sql = 'SELECT DISTINCT c.id, c.fullname ';
        $sql .= ' FROM {' . constants::M_RSTABLE . '} rs';
        $sql .= ' INNER JOIN {course} c ON rs.course=c.id';
        $sql .= ' INNER JOIN {enrol} e ON e.courseid=c.id';
        $sql .= ' INNER JOIN {user_enrolments} ue ON e.id=ue.enrolid';
        $sql .= ' WHERE ue.userid=?';
        $records = $DB->get_records_sql($sql, [$userid]);
        return $records;
    }

    /*
     * Fetch all the users attempts on readalouds in a particular course
     *
     */
    public static function fetch_next_reading($userid, $courseid, $readings) {
        $course = get_course($courseid);
        $modinfo = get_fast_modinfo($course, $userid);

        $completedreadings = [];
        foreach($readings as $reading){
            if($reading->qscore != null) {
                $completedreadings[] = $reading->readaloudid;
            }
        }

        foreach($modinfo->cms as $cm) {
            // Exclude activities which are not visible or available or are not readaloud or are completed.
            if (!$cm->uservisible) {
                continue;
            }
            if ($cm->deletioninprogress) {
                continue;
            }
            if (!($cm->modname == constants::M_RSTABLE)) {
                continue;
            }
            if (in_array($cm->instance, $completedreadings, false)) {
                continue;
            }
            // return constants::M_RSURL . $cm->instance;
            return  $cm->instance;
        }
        return false;
    }

    /*
    * Fetch a single user attempt on readaloud
    *
    */
    public static function fetch_single_user_attempt($attemptid) {
        global $DB, $USER;
        $sql = 'SELECT rsai.*';
        $sql .= ' FROM {' . constants::M_ATTEMPTTABLE . '} rsa';
        $sql .= ' INNER JOIN {' . constants::M_AITABLE . '} rsai on rsai.attemptid = rsa.id';
        $sql .= ' WHERE rsa.userid = :rsauserid AND rsai.attemptid = :rsaiattemptid';
        $record = $DB->get_record_sql($sql, ['rsauserid' => $USER->id, 'rsaiattemptid' => $attemptid]);
        return $record;
    }

    /*
     * Fetch all the users attempts on readalouds in a particular course
     *
     */
    public static function fetch_user_readings($userid, $courseid, $context) {
        global $DB;
        // This is the original SQL which shows all attempts
        /*
        $sql ='SELECT rsa.id as attemptid,rsa.readaloudid,rsa.courseid,rsa.wpm as h_wpm,rsa.sessionscore as h_sessionscore,rsa.qscore,rsa.flowerid,rsa.timemodified,';
        $sql .=' rsai.wpm as ai_wpm,rsai.sessionscore as ai_sessionscore, rs.name,rs.passagepicture, cm.id as cmid';
        $sql .=' FROM {' . constants::M_ATTEMPTTABLE . '} rsa INNER JOIN {' . constants::M_RSTABLE . '} rs';
        $sql .=' on rs.id=rsa.readaloudid';
        $sql .=' INNER JOIN {' . constants::M_AITABLE . '} rsai on rsai.attemptid = rsa.id';
        $sql .=' INNER JOIN {modules} m on m.name="readaloud" INNER JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = rsa.readaloudid';
        $sql .=' WHERE rsa.userid = ? AND rsa.courseid = ?';
        $sql .=' ORDER BY timemodified DESC';
        $records = $DB->get_records_sql($sql,array($userid,$courseid));
        */

        // this is the new SQL which eliminates superceded attempts
        $sql = 'SELECT rsa.id as attemptid,rsa.readaloudid,rsa.courseid,rsa.wpm as h_wpm,rsa.sessionscore as h_sessionscore,rsa.qscore,rsa.flowerid,rsa.timemodified,';
        $sql .= ' rsai.wpm as ai_wpm,rsai.sessionscore as ai_sessionscore, rs.name,rs.passagepicture, cm.id as cmid,';
        $sql .= ' rsai.accuracy as ai_accuracy, rsa.accuracy as h_accuracy, ';
        $sql .= ' (rsai.sessionendword - rsai.errorcount) as ai_totalwordsread,  (rsa.sessionendword - rsa.errorcount) as h_totalwordsread';
        $sql .= ' FROM {' . constants::M_ATTEMPTTABLE . '} rsa INNER JOIN {' . constants::M_RSTABLE . '} rs';
        $sql .= ' on rs.id=rsa.readaloudid';
        $sql .= ' INNER JOIN {' . constants::M_AITABLE . '} rsai on rsai.attemptid = rsa.id';
        $sql .= ' INNER JOIN {modules} m on m.name="readaloud" INNER JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = rsa.readaloudid';
        $sql .= ' WHERE rsa.userid = :userid AND rsa.courseid = :courseid';
        $sql .= ' AND rsa.id IN( SELECT MAX(att.id) FROM {' . constants::M_ATTEMPTTABLE . '}  att';
        $sql .= ' WHERE att.userid = :useridinner AND att.courseid = :courseidinner GROUP BY att.readaloudid)';
        $sql .= ' ORDER BY timemodified DESC';
        $records = $DB->get_records_sql($sql, ['userid' => $userid, 'courseid' => $courseid, 'useridinner' => $userid, 'courseidinner' => $courseid]);

        // it would be more elegant to use $gradenow = new \mod_readaloud\gradenow($latestattempt->id,$modulecontext->id);
        // to get grade data, but it would be heaps more DB calls so we fetch all the data in the single SQL above
        $flower = new flower($context);
        if($records){
            foreach ($records as $record){
                $record->flower = $flower->get_flower($record->flowerid);
            }
        }
        return $records;
    }

    /*
     * Fetch all the readings for a course
     */
    public static function fetch_course_readings($courseid) {
        $course = get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $ret = [];

        foreach($modinfo->cms as $cm) {
            if (!($cm->modname == constants::M_RSTABLE)) {
                continue;
            }
            if (!$cm->uservisible) {
                continue;
            }
            if ($cm->deletioninprogress) {
                continue;
            }
            $ret[$cm->id] = [
                'name' => $cm->get_formatted_name(),
                'instance' => $cm->instance,
                'id' => $cm->id,
                'courseid' => $courseid,
                'uservisible' => $cm->uservisible,
            ];
        }
        return $ret;
    }

    /**
     * If a human has entered a grade to override the AI WPM grade it will be in h_wpm.
     * Use it if present and ignore the AI one.
     * @param object $attemptdata
     * @return object
     */
    public static function override_with_human_grade_if_present($attemptdata) {
        foreach ($attemptdata as $attempt) {
            if ($attempt->h_wpm) {
                $attempt->ai_wpm = $attempt->h_wpm;
                $attempt->ai_accuracy = $attempt->h_accuracy;
                $attempt->ai_totalwordread = $attempt->h_totalwordsread;
            }
        }
        return $attemptdata;
    }


    /*
     * Fetch all the readings for a course
     * If a userid is passed in(studentblock) then
     * the item will include a link to the user's attempt (if any) for that reading.
     * Otherwise (teacher block) then
     * the item leads to the grading page for that reading
     *
     */
    public static function old_fetch_course_readings($courseid, $userid=false) {
        global $DB;
        $sql = 'SELECT rsa.id as attemptid,rsa.readaloudid,rsa.courseid,rsa.wpm,rsa.qscore,rsa.flowerid,rsa.timemodified,rs.name,rs.passagepicture, cm.id as cmid';
        $sql .= ' FROM {' . constants::M_ATTEMPTTABLE . '} rsa INNER JOIN {' . constants::M_RSTABLE . '} rs';
        $sql .= ' on rs.id=rsa.readaloudid';
        $sql .= ' INNER JOIN {modules} m on m.name="readaloud" INNER JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = rsa.readaloudid';
        $sql .= ' WHERE rsa.userid = ?';
        $records = $DB->get_records_sql($sql, [$userid]);

        if($records){
            foreach ($records as $record){

                // quiz data
                $cm = new \stdClass();
                $cm->id = $record->cmid;
                $cm->instance = $record->readaloudid;
                $comptest = new \mod_readaloud\comprehensiontest($cm);
                // passage picture
                if($record->passagepicture) {
                    $zeroitem = new \stdClass();
                    $zeroitem->id = 0;
                    $record->passagepictureurl = $comptest->fetch_media_url( \mod_readaloud\constants::PASSAGEPICTURE_FILEAREA, $zeroitem);
                }else{
                    $record->passagepictureurl = '';
                }
            }
        }
        return $records;
    }

    public static function fetch_flowerimage_opts($context) {

        $options = ['maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true, 'context' => $context, 'subdirs' => true, 'accepted_types' => ['image']];
        return $options;
    }

    public static function fetch_placeholderflower_opts($context) {

        $options = ['maxfiles' => 1,
            'noclean' => true, 'context' => $context, 'subdirs' => true, 'accepted_types' => ['image']];
        return $options;
    }

}//end of class
