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

use block_readaloudstudent\constants;
use block_readaloudstudent\common;

/**
 * ReadAloud Student Instances.
 *
 * @copyright 2024 Justin Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   block_readaloudstudent
 * @category  files
 * @param stdClass $course course object
 * @param stdClass $birecord_or_cm block instance record
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 * @todo MDL-36050 improve capability check on stick blocks, so we can check user capability before sending images.
 */
function block_readaloudstudent_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options=[]) {
    global $DB, $CFG, $USER;


    // If block is in course context, then check if user has capability to access course.
    if ($context->get_course_context(false)) {
        require_course_login($course);
    } else if ($CFG->forcelogin) {
        require_login();
    } else {
        // Get parent context and see if user have proper permission.
        $parentcontext = $context->get_parent_context();
        if ($parentcontext->contextlevel === CONTEXT_COURSECAT) {
            // Check if category is visible and user can view this category.
            if (!core_course_category::get($parentcontext->instanceid, IGNORE_MISSING)) {
                send_file_not_found();
            }
        } else if ($parentcontext->contextlevel === CONTEXT_USER && $parentcontext->instanceid != $USER->id) {
            // The block is in the context of a user, it is only visible to the user who it belongs to.
            send_file_not_found();
        }
        // At this point there is no way to check SYSTEM context, so ignoring it.
    }

    // Check if the filearea is customdefaultflower or customplaceholderflower
    if ($filearea === constants::M_CUSTOMDEFAULTFLOWER_FILEAREA || $filearea === constants::M_CUSTOMPLACEHOLDERFLOWER_FILEAREA) {
        return block_readaloudstudent_setting_file_serve($filearea, $args, $forcedownload,  $options);
    }
    //otherwise it should be flowerpictures or placeholderflower
    if ($filearea !== constants::FLOWERPICTURES_FILEAREA && $filearea !== constants::PLACEHOLDERFLOWER_FILEAREA ) {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    // $filepath = $args ? '/'.implode('/', $args).'/' : '/';
    $filepath = '/';
    $itemid = 1;

    if (!$file = $fs->get_file($context->id, constants::M_COMP, $filearea, $itemid, $filepath, $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    if ($parentcontext = context::instance_by_id($birecordorcm->parentcontextid, IGNORE_MISSING)) {
        if ($parentcontext->contextlevel == CONTEXT_USER) {
            // force download on all personal pages including /my/
            // because we do not have reliable way to find out from where this is used
            $forcedownload = true;
        }
    } else {
        // weird, there should be parent context, better force dowload then
        $forcedownload = true;
    }

    // NOTE: it woudl be nice to have file revisions here, for now rely on standard file lifetime,
    // do not lower it because the files are dispalyed very often.
    \core\session\manager::write_close();
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param  string $filearea The filearea.
 * @param  array  $args The path (the part after the filearea and before the filename).
 * @return array The itemid and the filepath inside the $args path, for the defined filearea.
 */
function block_readaloudstudent_get_path_from_pluginfile(string $filearea, array $args): array {
    // This block never has an itemid (the number represents the revision but it's not stored in database).
    array_shift($args);

    // Get the filepath.
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    return [
        'itemid' => 0,
        'filepath' => $filepath,
    ];
}

function block_readaloudstudent_setting_file_serve($filearea, $args, $forcedownload, $options) {
    global $CFG;
    require_once("$CFG->libdir/filelib.php");

    $syscontext = context_system::instance();
    $component = constants::M_COMP;

    $revision = array_shift($args);
    if ($revision < 0) {
        $lifetime = 0;
    } else {
        $lifetime = 60 * 60 * 24 * 60;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);

    $fullpath = "/{$syscontext->id}/{$component}/{$filearea}/0/{$relativepath}";
    $fullpath = rtrim($fullpath, '/');
    if ($file = $fs->get_file_by_hash(sha1($fullpath))) {
        send_stored_file($file, $lifetime, 0, $forcedownload, $options);
        return true;
    } else {
        send_file_not_found();
    }
}
