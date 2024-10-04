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
 * Newblock block caps.
 *
 * @package    block_readaloudstudent
 * @copyright  Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_readaloudstudent\constants;
use block_readaloudstudent\common;

class block_readaloudstudent extends block_base {

    function init() {
        $this->title = ''; // get_string('myreadaloudflowers', constants::M_COMP);
    }

    function get_content() {
        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = [];
        $this->content->icons = [];
        $this->content->footer = '';
        $this->content->text = '';

        // get the course this block is on
        $course = $this->page->course;

        // get the block instance settings (position , id  etc)
        $instancesettings = $this->instance;

        // get the admin config (that we define in settings.php)
        $adminconfig = get_config(constants::M_COMP);
        // get the instance config (that we define in edit_form)
        $localconfig = $this->config;
        // get best config. our helper class to merge local and admin configs
        $bestconfig = common::fetch_best_config($instancesettings->id);

        // get the courses we will show for this user
        switch($bestconfig->showcourses){
            case constants::M_THISCOURSE:
                $courses = [$course];
                break;

            case constants::M_ENROLLEDCOURSES:
                $courses = common::fetch_courses_myenrolled($USER->id);
                break;

            case constants::M_ACTIVECOURSES:
            DEFAULT:
                $courses = common::fetch_courses_with_myattempts($USER->id);
                break;

        }

        // for each course get the set of attempts
        $coursedata = [];
        if ($courses) {
            foreach ($courses as $course) {
                $thecourse = new \stdClass();
                $thecourse->courseid = $course->id;
                $thecourse->fullname = $course->fullname;
                $thecourse->attemptdata = common::override_with_human_grade_if_present(common::fetch_user_readings($USER->id, $course->id, $this->context));
                $thecourse->allreadings = common::fetch_course_readings($course->id);
                $thecourse->nextreading = common::fetch_next_reading($USER->id, $course->id, $thecourse->attemptdata);
                $thecourse->id = $course->id;
                $coursedata[$course->id] = $thecourse;
                foreach ($thecourse->attemptdata as $attempt) {
                    $attempt->h_wpm = "33";
                }
            }
        }
        $renderer = $this->page->get_renderer(constants::M_COMP);
        $block = new \block_readaloudstudent\output\block($coursedata, $bestconfig, $this->context);
        $this->content->text = $renderer->render($block);
        $renderer->call_amd($coursedata);
        return $this->content;
    }

    // This is a list of places where the block may or may not be added by the admin
    public function applicable_formats() {
        return [
             'all' => false,
            'site' => true,
            'site-index' => true,
            'course-view' => true,
            'course-view-social' => false,
            'mod' => true,
            'mod-quiz' => false,
            'my' => true,
        ];
    }

    // Can we have more than one instance of the block?
    public function instance_allow_multiple() {
          return true;
    }

    public function hide_header() {
        return true;
    }

    function has_config() {
        return true;
    }


    /**
     * Serialize and store config data
     */
    function instance_config_save($data, $nolongerused = false) {
        global $DB;

        $config = clone($data);
        // Move embedded files into a proper filearea
        $itemid = 1;
        // flower pics
        $imageoptions = common::fetch_flowerimage_opts($this->context);
        $config->flowerpictures = file_save_draft_area_files($data->flowerpictures, $this->context->id, constants::M_COMP, constants::FLOWERPICTURES_FILEAREA, $itemid, $imageoptions);
        // placeholder flower
        $pimageoptions = common::fetch_placeholderflower_opts($this->context);
        $config->placeholderflower = file_save_draft_area_files($data->placeholderflower, $this->context->id, constants::M_COMP, constants::PLACEHOLDERFLOWER_FILEAREA, $itemid, $pimageoptions);

        parent::instance_config_save($config, $nolongerused);
    }

    function instance_delete() {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, constants::M_COMP);
        return true;
    }

    /**
     * Copy any block-specific data when copying to a new block instance.
     * @param int $fromid the id number of the block instance to copy from
     * @return boolean
     */
    public function instance_copy($fromid) {
        $fromcontext = context_block::instance($fromid);
        $fs = get_file_storage();
        // Do not use draft files hacks outside of forms.
        $itemid = 1;
        $fileareas = [constants::FLOWERPICTURES_FILEAREA, constants::PLACEHOLDERFLOWER_FILEAREA];
        foreach($fileareas as $filearea){
            $files = $fs->get_area_files($fromcontext->id, constants::M_COMP, $filearea, $itemid, 'id ASC', false);
            foreach ($files as $file) {
                $filerecord = ['contextid' => $this->context->id];
                $fs->create_file_from_storedfile($filerecord, $file);
            }
        }
        return true;
    }

    function content_is_trusted() {
        global $SCRIPT;

        if (!$context = context::instance_by_id($this->instance->parentcontextid, IGNORE_MISSING)) {
            return false;
        }
        // find out if this block is on the profile page
        if ($context->contextlevel == CONTEXT_USER) {
            if ($SCRIPT === '/my/index.php') {
                // this is exception - page is completely private, nobody else may see content there
                // that is why we allow JS here
                return true;
            } else {
                // no JS on public personal pages, it would be a big security issue
                return false;
            }
        }

        return true;
    }

}
