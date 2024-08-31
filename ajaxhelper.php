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
 * Ajax helper for Read Seed Student block
 *
 *
 * @package    block_readaloudstudent
 * @copyright  Justin Hunt (justin@poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

use \block_readaloudstudent\constants;
use \block_readaloudstudent\common;

$cmid = required_param('cmid',  PARAM_INT); // course_module ID, or
$action= required_param('action',PARAM_INT);
$data= optional_param('data', 0, PARAM_INT);

if ($cmid) {
    $cm         = get_coursemodule_from_id(constants::M_RSTABLE, $cmid, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
  //  $readaloud  = $DB->get_record('readaloud', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    echo return_to_page(false,"You must specify a course_module ID");
    return;
}

require_login($course, false, $cm);
$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);


switch($action){
    case constants::M_ACTIONWPMRESULT:
        //data should be attemptid
        $ret = fetch_reading_results($data);
        echo $ret;
        break;
    default:
        echo return_to_page(false,"You must specify a known action");
}
return;



function fetch_reading_results($attemptid)
{
    $result = common::fetch_single_user_attempt($attemptid);
    //if we got no record, its all wrong just quit
    if(!$result){
        $success = false;
        $message = '';
        $returndata=-1;
        return return_to_page($success,$message,$returndata);
    }
    //if we got a result, return success and the value of WPM (-1 flags null ie not processed yet)
    $success = true;
    $message = '';
    $returndata=$result->wpm==null?-1:$result->wpm;
    return return_to_page($success,$message,$returndata);
}

//handle return to Moodle
function return_to_page($success, $message=false,$data=false)
{
    $ret = new stdClass();
    $ret->success = $success;
    $ret->data=$data;
    $ret->message = $message;
    return json_encode($ret);
}