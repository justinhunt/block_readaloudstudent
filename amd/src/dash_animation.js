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
 * Javascript Module to handle dashboard animation.
 *
 * @module dash_animation
 * @package block_readaloudstudent
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint space-before-function-paren: 0 */

define(["jquery"], function ($) {
    "use strict";

    var CONSTANTS = {
        CIRCLE_RADIUS: null,
        CIRCLE_CIRCUMF: null,
        CIRCLE_CIRCUMF_HALF: null,
        METER_COLOUR: "#70d54a",
        WORDS_ANIMATION_DURATION: 1000, // Duration of animation on page load.
        METER_FRAME_DURATION: 3,
        METER_TOTAL_ANIMATION_DURATION: 700,
        WORDS_TOTAL_ANIMATION_DURATION: 1000,
        WORD_COUNT_FRAME_DURATION: 30
    };

    /**
     * Set a meter value (e.g. WPM meter or accuracy meter) without animation.
     * @param meterId
     * @param percent
     */
    var setMeterValue = function(meterId, percent) {
        var meter = $("#meter-" + meterId);
        var meterValue = CONSTANTS.CIRCLE_CIRCUMF_HALF - ((percent * CONSTANTS.CIRCLE_CIRCUMF_HALF) / 100);
        meter.find('.mask').attr('stroke-dasharray', meterValue + ',' + CONSTANTS.CIRCLE_CIRCUMF);
        $("#meter-needle-" + meterId).css("transform", 'rotate(' + (270 + ((percent * 180) / 100)) + 'deg)');
        // $('.user-text').css('transform', ui.value)
        meter.attr("data-percent", percent);
    };

    /**
     * Anmimate a meter value (e.g. WPM meter or accuracy meter) from zero to final value.
     * @param {string} meterId
     * @param {number} percent
     * @param {number} finalValue
     * @param {number} increment
     */
    var animateMeterValue = function (meterId, percent, finalValue, increment) {
        var existingValue = parseInt($("#meter-" + meterId).attr("data-percent"));
        if (existingValue !== undefined && existingValue < percent) {
            setMeterValue(meterId, existingValue + increment);
            setTimeout(function() {
                animateMeterValue(meterId, percent, finalValue, increment);
            }, CONSTANTS.METER_FRAME_DURATION);
        } else {
            setMeterValue(meterId, percent);
            $("#meter-" + meterId + "-legend-val").html(finalValue);
            $("#meter-" + meterId + "-legend").fadeIn(500);
        }
    };

    /**
     * Amimate the word count meter
     * @param {number}courseId
     * @param {number}finalCount
     * @param {number}step
     * @param {object} counter jquery object
     */
    var animateWordCount = function (courseId, finalCount, step, counter) {
        if (counter === undefined) {
            counter = $("#wordcount-" + courseId);
        }
        var currentValue = isNaN(parseInt(counter.html())) ? 0 : parseInt(counter.html());
        if (currentValue < finalCount) {
            counter.html(currentValue + step);
            setTimeout(function() {
                animateWordCount(courseId, finalCount, step, counter);
            }, CONSTANTS.WORD_COUNT_FRAME_DURATION);
        } else {
            counter.html(finalCount);
        }
    };

    /**
     * Animate the progress indicator (3 / 10 activities complete) using path format (S shaped indicator)
     * @param courseId
     * @param finalCount
     * @param {object} progressIndicator the jquery object to animate
     */
    var anmimateProgressIndicatorPath = function(courseId, finalCount, progressIndicator) {
        var step = 1;
        var currentValue = parseInt(progressIndicator.attr("data-complete"));
        var update = function() {
            progressIndicator.attr("data-complete", (currentValue + step).toString());
            progressIndicator.find("#line-" + (currentValue - 1).toString()).css("stroke", CONSTANTS.METER_COLOUR);
            progressIndicator.find("#circle-" + currentValue.toString()).css("fill", CONSTANTS.METER_COLOUR);
            progressIndicator.find('#complete-num-complete-' + courseId).html(currentValue.toString());
        };
        if (currentValue < finalCount) {
            update();
            setTimeout(function() {
                anmimateProgressIndicatorPath(courseId, finalCount, progressIndicator);
            }, (Math.ceil(CONSTANTS.WORDS_ANIMATION_DURATION / finalCount)));
        } else if (currentValue <= finalCount) {
            update();
        }
    };

    /**
     * Animate the progress indicator (3 / 10 activities complete) using path format (S shaped indicator)
     * @param courseId
     * @param finalCount
     * @param {object} progressIndicator the jquery object to animate
     * @param {int} currentValue the current value of the indicator
     */
    var anmimateProgressIndicatorGrid = function(courseId, finalCount, progressIndicator, currentValue) {
        if (currentValue === undefined) {
            currentValue = 1;
        }
        if (currentValue <= finalCount) {
            progressIndicator.find('#progress-cell-' + currentValue.toString()).addClass("complete");
            progressIndicator.find('#complete-num-complete-' + courseId).html(currentValue.toString());
        }
        if (currentValue < finalCount) {
            setTimeout(function() {
                anmimateProgressIndicatorGrid(courseId, finalCount, progressIndicator, currentValue + 1);
            }, 200);
        }
    };

    /**
     * Animate the progress indicator (3 / 10 activities complete) which could be grid or path format.
     * @param courseId
     * @param finalCount
     */
    var animateProgress = function(courseId, finalCount) {
        // First try the grid indicator (not s-shaped path indicator).
        var progressIndicator = $("#dots-indicator-grid-" + courseId);
        if (progressIndicator.length === 0) {
            // Grid doesn't exist so try path indicator.
            progressIndicator = $("#dots-indicator-path-" + courseId);
            progressIndicator.fadeIn(200);
            anmimateProgressIndicatorPath(courseId, finalCount, progressIndicator);
        } else {
            anmimateProgressIndicatorGrid(courseId, finalCount, progressIndicator);
        }
    };

    /**
     * Animate all dashboard meters in a course based ojn data object
     * @param {number} courseId
     * @param {object} metersData
     */
    var animateDashMeters = function(courseId, metersData) {
        // Words per minute.
        var meterOneValue = parseInt(metersData.wpm);
        var WPM_MAX = 200; // Meter maxes out at this value as people not expected to exceed it.
        var meterOnePercent = meterOneValue === 0 ? 0 : Math.floor(meterOneValue / WPM_MAX * 100);
        var increment =
            Math.ceil(CONSTANTS.METER_TOTAL_ANIMATION_DURATION / meterOnePercent / CONSTANTS.METER_FRAME_DURATION);
        animateMeterValue(courseId + "-1", meterOnePercent, meterOneValue, increment);

        // Average accuracy.
        var meterTwoValue = parseInt(metersData.score);
        increment = Math.ceil(CONSTANTS.METER_TOTAL_ANIMATION_DURATION / meterTwoValue / CONSTANTS.METER_FRAME_DURATION);
        animateMeterValue(courseId + "-2", meterTwoValue, meterTwoValue, increment);

        var wordCount = parseInt(metersData.totalwordsread);
        var step = Math.ceil(wordCount / (CONSTANTS.WORDS_TOTAL_ANIMATION_DURATION / CONSTANTS.WORD_COUNT_FRAME_DURATION));
        animateWordCount(courseId, wordCount, step);

        setTimeout(function() {
            animateProgress(courseId, metersData.countcmsattempted);
        }, CONSTANTS.METER_TOTAL_ANIMATION_DURATION);
    };

    /**
     * Set meters quickly without animation
     * @param {number} courseId
     * @param {object} metersData
     */
    var setDashMeters = function (courseId, metersData) {
        // Words per minute.
        var meterId = courseId + "-1";
        var meterOneValue = parseInt(metersData.wpm);
        var WPM_MAX = 200; // Meter maxes out at this value as people not expected to exceed it.
        var meterOnePercent = meterOneValue === 0 ? 0 : Math.floor(meterOneValue / WPM_MAX * 100);
        setMeterValue(meterId, meterOnePercent);
        $("#meter-" + meterId + "-legend-val").html(meterOneValue);
        $("#meter-" + meterId + "-legend").fadeIn(500);

        // Average accuracy.
        meterId = courseId + "-2";
        var meterTwoValue = parseInt(metersData.score);
        setMeterValue(meterId, meterTwoValue);
        $("#meter-" + meterId + "-legend-val").html(meterTwoValue);
        $("#meter-" + meterId + "-legend").fadeIn(500);

        //Word count
        $("#wordcount-" + courseId).html(metersData.totalwordsread);

        // Progress.
        animateProgress(courseId, metersData.countcmsattempted);
    };

    return {
        init: function (metersData, meterRadius, courseToOpen) {

            $(document).ready(function () {
                CONSTANTS.CIRCLE_RADIUS = meterRadius;
                CONSTANTS.CIRCLE_CIRCUMF = Math.floor(2 * Math.PI * CONSTANTS.CIRCLE_RADIUS);
                CONSTANTS.CIRCLE_CIRCUMF_HALF = Math.floor(CONSTANTS.CIRCLE_CIRCUMF / 2);
                courseToOpen = parseInt(courseToOpen);

                try {
                    metersData = JSON.parse(metersData);
                } catch (ex) {
                    metersData = [];
                }

                // Check if we are displaying multiple courses and, if we are, expand one.
                var coursesAccordion = $(".course-accordion-wrapper");
                if (coursesAccordion.length > 0 && courseToOpen && Object.keys(metersData).length > 0) {
                    var courseDivToOpen = $('#course-collapse-' + courseToOpen);
                    if (courseDivToOpen.hasClass("collapse")) {
                        setTimeout(function() {
                            // Allow some time here to include Bootstrap collapse as seems jQuery is called first.
                            courseDivToOpen.collapse('show');
                            var HEADER_BAR_HEIGHT = 110;
                            $("body, html").animate({scrollTop: courseDivToOpen.offset().top - HEADER_BAR_HEIGHT}, "slow");
                        }, 300);

                    }
                }

                // Now animate the dash meters (words per minute and accuracy).
                if (Object.keys(metersData).length > 0) {

                    // If we have only one course, there is no accodion so we just animate that course meters.
                    if (coursesAccordion.length === 0) {
                        var courseid = $(".course-dash").attr("data-courseid");
                        animateDashMeters(courseid, metersData[courseid]);
                    } else {
                        // If there is > 1 course, we animate/set meters in all of them.
                        // First the course we expanded.
                        animateDashMeters(courseToOpen, metersData[courseToOpen]);

                        // Then wait and do the hidden ones too.
                        setTimeout(function() {
                            coursesAccordion.each(function(index, course) {
                                course = $(course).attr("data-courseid");
                                if (course !== courseToOpen) {
                                    setDashMeters(course, metersData[course]);
                                }
                            });
                        }, 2000);
                    }
                }
            });
        }
    };
});