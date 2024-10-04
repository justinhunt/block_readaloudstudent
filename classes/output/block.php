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
 * Contains class block_readaloudstudent\output\block
 *
 * @package   block_readaloudstudent
 * @copyright 2019 David Watson http://evolutioncode.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_readaloudstudent\output;

use block_readaloudstudent\common;
use block_readaloudstudent\constants;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to help display the readaloud student block
 *
 * @package   block_readaloudstudent
 * @copyright 2019 David Watson http://evolutioncode.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block implements \renderable, \templatable {

    /**
     * The course data including all activities and attempts.
     * @var array
     */
    private $coursesdata;

    /**
     * The config for the block e.g. display prefs.
     * @var object
     */
    private $bestconfig;

    /**
     * After how many flower cards in a course should we render them as collapsed.
     * The idea is to have one row not collapsed and then collapse cards after that.
     * This is asjusted by JS after page load but when page is rendered we make a guess that row will be 5 long.
     * This gives JS less work to do.
     * @var int
     */
    private $hidecardsafterindex = 5;

    /**
     * Constructor
     * @param array $coursesdata the attempt and other data for each course being displayed.
     * @param object $bestconfig the user's block config
     */
    public function __construct($coursesdata, $bestconfig, $context) {
        $this->coursesdata = $coursesdata;
        $this->bestconfig = $bestconfig;
        $this->context = $context;
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param \renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return array data for template
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function export_for_template(\renderer_base $output) {
        global $CFG, $OUTPUT;
        foreach($this->bestconfig as $k => $v) {
            $data['config'][$k] = $v;
        }
        $flower = new \block_readaloudstudent\flower($this->context);
        $data['wwwroot'] = $CFG->wwwroot;
        $data['courses'] = [];
        $data['showdummycards'] = $this->bestconfig->showreadings == constants::M_SHOWALLREADINGS; // otherwise just show the completed ones.
        $hasnoattempts = true; // Overwritten if activities complete.
        $dummycardurl = $flower->get_placeholder_flower_url();
        foreach ($this->coursesdata as $singlecoursedata) {

            // We need to find the most recently completed attempt as we process the attempts.
            $mostrecentattempt = new \stdClass();
            $mostrecentattempt->timemodified = 0;

            if ($data['showdummycards']) {
                // We are showing all course modules, whether they have attempts or not.
                $attemptdatabyinstanceid = $this->attempt_data_by_instance_id($singlecoursedata->attemptdata);
                $cardsthiscourse = [];
                $index = 1;
                foreach ($singlecoursedata->allreadings as $singlecoursemodule) {
                    if (isset($attemptdatabyinstanceid[$singlecoursemodule['instance']])) {
                        // We have an attempt for this course module so display it as a card.
                        $attempt = $singlecoursedata->attemptdata[$attemptdatabyinstanceid[$singlecoursemodule['instance']]->attemptid];
                        $attemptdatathiscard = $this->attempt_data_transform_to_card($attempt);
                        $attemptdatathiscard['cardindex'] = $index;
                        if ($index > $this->hidecardsafterindex) {
                            $attemptdatathiscard['extraclasses'] = 'hidden';
                        }
                        $cardsthiscourse[] = $attemptdatathiscard;
                        if ($attempt->timemodified > $mostrecentattempt->timemodified) {
                            $mostrecentattempt = $attempt;
                        }
                        $hasnoattempts = false;
                    } else {
                        // No attempt so we will have a dummy card to represent the course module without an attempt.
                        $cardsthiscourse[] = [
                            'name' => $singlecoursemodule['name'],
                            'readaloudid' => $singlecoursemodule['instance'],
                            'flowerpicurl' => $dummycardurl,
                            'isdummy' => '1',
                            'cardindex' => $index,
                            'extraclasses' => $index > $this->hidecardsafterindex ? 'hidden' : '',
                        ];
                    }
                    if ($index === $this->hidecardsafterindex && count($singlecoursedata->allreadings) > $this->hidecardsafterindex) {
                        // We add a "show more" card here.
                        $cardsthiscourse[] = ['ismoreless' => 1, 'ismore' => 1];
                    }
                    $index++;
                }
                $coursedata = [
                    'coursename' => $singlecoursedata->fullname,
                    'courseid' => $singlecoursedata->courseid,
                    'cards' => $cardsthiscourse,
                ];
            } else {
                // We are not showing all course modules, only attempts.
                $attempts = $this->attempts_data_transform_to_cards($singlecoursedata->attemptdata);
                foreach ($attempts as $attempt) {
                    if ($attempt->timemodified > $mostrecentattempt->timemodified) {
                        $mostrecentattempt = $attempt;
                    }
                    $hasnoattempts = false; // No need now - they have activities.
                }
                $coursedata = [
                    'coursename' => $singlecoursedata->fullname,
                    'nextreading' => $singlecoursedata->nextreading,
                    'cards' => $attempts,
                ];
            }
            $coursedata['nextreading'] = $singlecoursedata->nextreading;
            $coursedata['complete-num'] = 0; // We will animate this to the real value in JS.
            $coursedata['complete-outof'] = count($singlecoursedata->allreadings);
            if ($coursedata['complete-outof'] === 12) {
                // The dot path indicator at present is only suitable for courses with 12 activities.
                $coursedata['usedotspathindicator'] = 1;
                $data['dots-indicator-url'] = $OUTPUT->image_url('dots-indicator', 'block_readaloudstudent')->out();
            } else {
                $coursedata['progress-dots-indicator'] = $this->dots_indicator($coursedata['complete-num'], $coursedata['complete-outof']);
            }

            $coursedata['mostrecentattempt'] = $this->attempt_data_transform_to_card($mostrecentattempt);
            if ($hasnoattempts) {
                $data['showintrovideo'] = true;
                $data['introvideoid'] = $this->bestconfig->introvideoid;
                $data['total-words-read'] = "0";
                $data['nextbuttontext'] = get_string('myfirstreading', 'block_readaloudstudent');
            } else {
                $data['nextbuttontext'] = get_string('mynextreading', 'block_readaloudstudent');
            }
            $data['showlesscard'] = ['ismore' => 0, 'extraclasses' => 'hidden'];
            $data['courses'][] = $coursedata;
        }

        $data['issinglecourse'] = count($data['courses']) === 1;
        $data['meters'] = [
            [
                'id' => '1',
                'name' => get_string('averagewpm', 'block_readaloudstudent'),
                'legend' => get_string('wpm', 'block_readaloudstudent'),
            ],
            [
                'id' => '2',
                'name' => get_string('averageaccuracy', 'block_readaloudstudent'),
                'legend' => "%",
            ],
        ];
        $data['meter-needle-url'] = $OUTPUT->image_url('meter-needle', 'block_readaloudstudent')->out();
        $data['meter-strokewidth'] = 20;
        $data['meter-strokewidth-inner'] = 19;
        $data['meter-radius'] = constants::M_METER_RADIUS;
        $data['meter-circum'] = round(pi() * pow($data['meter-radius'], 2), 1);
        $data['meter-circum-half'] = round($data['meter-circum'] / 2, 1);
        return $data;
    }


    /**
     * The "dots" progress indicator needs an array of rows and columns to render in mustache.
     * @param $numcomplete
     * @param $numoutof
     * @return array
     */
    private function dots_indicator($numcomplete, $numoutof) {
        $indicator = [
            'extrawide' => 0,
            'cells' => [],
        ];
        // Allow very large numbers to show without messing up display.
        if ($numoutof > 20) {
            $indicator['extrawide'] = "172px";
        }
        if ($numoutof > 30) {
            $divisor = (round($numoutof / 20));
            $numoutof = round($numoutof / $divisor);
            $numcomplete = round($numcomplete / $divisor);
        }
        for ($cell = 1; $cell <= $numoutof; $cell++) {
            if ($cell <= $numoutof) {
                $indicator['cells'][] = [
                    'cell-id' => $cell,
                    'complete' => $cell <= (int)$numcomplete ? 1 : 0,
                ];
            }
        }
        return $indicator;
    }
    /**
     * The attempts data are returned as a nested object but we need a flat array for mustache.
     * @param $attemptdata
     * @return array
     */
    private function attempts_data_transform_to_cards($attemptdata) {
        $cards = [];
        $index = 1;
        foreach($attemptdata as $attempt) {
            $attempt = $this->attempt_data_transform_to_card($attempt);
            $attempt['cardindex'] = $index;
            if ($index > $this->hidecardsafterindex) {
                $attempt['extraclasses'] = 'hidden';
            }
            $cards[] = $attempt;
            if ($index === $this->hidecardsafterindex && count($attemptdata) > $this->hidecardsafterindex) {
                // We add a "show more" card here.
                $cards[] = ['ismoreless' => 1, 'ismore' => 1];
            }
            $index++;
        }
        return $cards;

    }

    /**
     * The attempts data are returned as a nested object but we need a flat array for mustache.
     * @param $attempt
     * @return array
     */
    private function attempt_data_transform_to_card($attempt) {
        $newattempt = [];
        foreach($attempt as $k => $v) {
            if($k === 'flower') {
                foreach($v as $flowerkey => $flowervalue) {
                    $newattempt['flower' . $flowerkey] = $flowervalue;
                }
            } else {
                $newattempt[$k] = $v;
            }
        }
        return $newattempt;
    }
    /**
     * The database query returns the attempt data by attempt id but we want it by instance id.
     * May be better to change the query if it's not used elsewhere.
     * @see common::fetch_user_readings()
     * @param $attemptdata
     * @return array
     */
    private function attempt_data_by_instance_id($attemptdata) {
        $newdata = [];
        foreach($attemptdata as $attempt) {
            $newdata[$attempt->readaloudid] = $attempt;
        }
        return $newdata;
    }
}
