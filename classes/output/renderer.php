<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace block_readaloudstudent\output;

use block_readaloudstudent\constants;
use block_readaloudstudent\common;


class renderer extends \plugin_renderer_base {
    /**
     * Get the average words per minute for the user across all courses and all attempts.
     * @param object $coursesdata
     * @return array()
     * @throws \coding_exception
     */
    private function meters_data($coursesdata) {
        $data = [];
        foreach ($coursesdata as $course) {
            if (!isset($course->courseid)) {
                $course->courseid = $course->id;
            }
            $totalwpm = 0;
            $totalaccuracy = 0;
            $totalwordsread = 0;
            $count = 0;
            $distinctcmids = [];
            foreach ($course->attemptdata as $attempt) {
                $totalwpm += $attempt->ai_wpm;
                $totalaccuracy += $attempt->ai_accuracy;
                $totalwordsread += $attempt->ai_totalwordsread;
                $distinctcmids[$attempt->cmid] = $attempt->cmid;
                $count++;
            }
            $averagewpm = $count === 0 ? 0 : floor($totalwpm / $count);
            $data[$course->courseid] = [
                'courseid' => $course->courseid,
                'wpm' => $averagewpm,
                'score' => $count === 0 ? 0 : floor($totalaccuracy / $count),
                'totalwordsread' => $totalwordsread,
                'countcmsattempted' => count($distinctcmids),
            ];
        }
        return $data;
    }

    /**
     * Embed the Read Aloud Student block.
     *
     * @param int $courseid The ID of the course.
     * @param int $blockid The ID of the block.
     * @return string The HTML content of the block.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function embed_blockreadaloudstudent($courseid=0, $blockid=0) {
        global $COURSE, $USER;
        // Get course.
        if ($courseid) {
            $thecourse = get_course($courseid);
        } else {
            $thecourse = $COURSE;
        }
        //get context
        if ($blockid) {
            $usecontext = \context_block::instance($blockid);
        }else{
            $usecontext = \context_course::instance($thecourse->id);
        }

        // Fetch config. using our helper class which merges admin and local settings.
        $config = common::fetch_best_config($blockid);

        // Show all courses or just this course.

        // We can get all the courses with attempts by this user.
        // That is how the block works, but in the renderer we are only going to show the current course.
        // $courses = common::fetch_courses_myenrolled($USER->id);
        $courses = [$thecourse];
        // For each course get the set of attempts.
        $coursedata = [];
        if ($courses) {
            foreach ($courses as $course) {
                // It is debatable if we should use the course context here, or the block context for fetch_user_readings call
                // We use block context so that we can add images in one place and use them in diff courses the embedded block view.
                // The use case I am thinking is one course is halloween course, and we want to show the halloween flowers.
                // On the main black and in the course block
                // But we might want to revise this.. 
                // TO DO see how it goes.
                $coursecontext = \context_course::instance($course->id);
                $thecourse = new \stdClass();
                $thecourse->courseid = $course->id;
                $thecourse->fullname = $course->fullname;
                $thecourse->attemptdata = common::override_with_human_grade_if_present(common::fetch_user_readings($USER->id, $course->id, $usecontext));
                $thecourse->allreadings = common::fetch_course_readings($course->id);
                $thecourse->nextreading = common::fetch_next_reading($USER->id, $course->id, $thecourse->attemptdata);
                $thecourse->id = $course->id;
                $coursedata[$course->id] = $thecourse;
                foreach ($thecourse->attemptdata as $attempt) {
                    $attempt->h_wpm = "33";
                }
            }
        }
        // Display the content of this page from our nice renderer.
        $block = new \block_readaloudstudent\output\block($coursedata, $config, $usecontext);
        $blockhtml = $this->render($block);
        // we do a sneaky wrap into a block_readseedstudent div here ... CSS needs this to apply
        $blockhtml = \html_writer::div($blockhtml, 'block_readaloudstudent');
        // Call the AMD so js works.
        $this->call_amd($coursedata);
        return $blockhtml;

    }

    /**
     * We do this here so that other plugins can access it.  E.g. mod_labelreadaloud needs to render the block in its view file.
     * @param object $coursedata
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function call_amd($coursesdata) {

        $jsparams = [
            'metersData' => json_encode($this->meters_data($coursesdata)),
            'meterRadius' => constants::M_METER_RADIUS,
            // Is this what we want?  Always open the first course?
            'courseToOpen' => isset(reset($coursesdata)->courseid) ? reset($coursesdata)->courseid : 0,
        ];

        $this->page->requires->js_call_amd(constants::M_COMP . '/launcher', 'init', $jsparams);
        // TO DO: this should not really be here, move this call somewhere more appropriate.
        if (!$this->page->headerprinted && !$this->page->requires->is_head_done()) {
            $this->page->requires->css(new \moodle_url('/blocks/readaloudstudent/fonts/fonts.css'));
        }
    }

    /**
     * Construct contents of new readaloud dashboard block.
     *
     * @param \templatable $templatable
     * @return string html to be displayed in block
     * @throws \moodle_exception
     */
    public function render_block(\templatable $templatable) {
        $data = $templatable->export_for_template($this);
        return $this->render_from_template('block_readaloudstudent/dashboard', $data);

    }
}
