<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace block_readaloudstudent\output;

use \block_readaloudstudent\constants;


class renderer extends \plugin_renderer_base {
    /**
     * Get the average words per minute for the user across all courses and all attempts.
     * @param object $coursesdata
     * @return array()
     * @throws \coding_exception
     */
    private function meters_data($coursesdata) {
        $data = [];
        foreach($coursesdata as $course) {
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
            $data[$course->courseid] = array(
                'courseid' => $course->courseid,
                'wpm' =>  $averagewpm,
                'score' => $count === 0 ? 0 : floor($totalaccuracy / $count),
                'totalwordsread' => $totalwordsread,
                'countcmsattempted' => count($distinctcmids)
            );
        }
        return $data;
    }

    /**
     * We do this here so that other plugins can access it.  E.g. mod_labelreadaloud needs to render the block in its view file.
     * @param object $coursedata
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function call_amd($coursesdata) {
        global $PAGE;

        $jsparams = array(
            'metersData' => json_encode($this->meters_data($coursesdata)),
            'meterRadius' => constants::M_METER_RADIUS,
            'courseToOpen' => isset(reset($coursesdata)->courseid) ? reset($coursesdata)->courseid : 0 // Is this what we want?  Always open the first course?
        );

        $PAGE->requires->js_call_amd(constants::M_COMP . '/launcher', 'init', $jsparams);
        $this->page->requires->css(new \moodle_url('/blocks/readaloudstudent/fonts/fonts.css'));
    }

    /**
     * Construct contents of new readaloud dashboard block.
     *
     * @param \templatable $templatable
     * @return string html to be displayed in block
     * @throws \moodle_exception
     */
    public function render_block(\templatable $templatable)
    {
        $data = $templatable->export_for_template($this);
        return $this->render_from_template('block_readaloudstudent/dashboard', $data);

    }
}