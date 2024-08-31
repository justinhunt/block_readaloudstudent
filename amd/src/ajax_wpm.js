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
 * @module ajax_wpm
 * @package block_readaloudstudent
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint space-before-function-paren: 0 */

define(["jquery"], function ($) {
    "use strict";

    var MAX_CHECKS = 30; // When we have checked this many times, we give up.
    var POLL_HOW_OFTEN = 20000; // Poll AJAX every 20 seconds until complete.

    /**
     * If the user has just completed a reading, the ai_wpm field will be empty so we keep polling server by AJAX to get it.
     * @param {number} timesToCheck how many times we should poll before giving up.
     * @param {array|undefined} wpmsToCheck
     * @param {number|undefined} index
     */
    var updateWpmAjaxRequests = function (timesToCheck, wpmsToCheck, index) {
        if (index !== undefined && wpmsToCheck !== undefined && index > wpmsToCheck.length - 1) {
            // We have reached the end of the list calling this function recursively, so do nothing.
            return;
        }
        require(["core/config", "core/log"], function(config, log) {
            /**
             * Find all the flowers with missing AI WPM figures.
             */
            var findMissingDataItems = function () {

                var emptyWpms = $('.flowerstats[data-aiwpm=""]');
                // Variable emptyWpms will contain duplicates.
                // This is because we have the same attempt repeated as most recent attempt and in main body - remove.
                var wpmsToCheck = [];
                emptyWpms.each(function (index, flower) {
                    flower = $(flower);
                    wpmsToCheck[flower.attr("data-attemptid")] = flower;
                });

                var arraySequentialIndex = [];
                wpmsToCheck.forEach(function(flower) {
                    arraySequentialIndex.push(flower);
                });

                return arraySequentialIndex;
            };

            /**
             * Once we receive response to AJAX request, decide what to do next and do it.
             */
            var afterAjaxRequest = function () {
                if (index === wpmsToCheck.length - 1) {
                    // We have reached the end of the list - this was our last item from recursive calls.
                    // Check to see if we have all data we need now, or need to poll again later.
                    var newWpmsToCheck = findMissingDataItems();
                    if (newWpmsToCheck.length > 0) {
                        log.debug(
                            'Finished checking all for this round.  Will check again in milliseconds: ' + POLL_HOW_OFTEN.toString()
                        );
                        setTimeout(function () {
                            updateWpmAjaxRequests(timesToCheck - 1, newWpmsToCheck, 0);
                        }, POLL_HOW_OFTEN);
                    } else {
                        log.debug("Data complete.  Stopping polling");
                    }
                } else {
                    // We have not yet reached the end of the list.
                    // Wait 0.5s (to space AJAX requests) then call ourselves again recursively until we reach end of list.
                    setTimeout(function () {
                        updateWpmAjaxRequests(timesToCheck, wpmsToCheck, index + 1);
                    }, 500);
                }
            };

            // If this is the first time we are calling this fn, get data about what to check.
            if (!wpmsToCheck) {
                wpmsToCheck = findMissingDataItems();
                index = 0;
            }

            log.debug('AJAX checks left before we give up: ' + timesToCheck.toString());

            // If we have data about what to check, and have not checked too many times already.
            if (wpmsToCheck.length > 0 && timesToCheck > 0) {
                var flower = $(wpmsToCheck[index]);
                log.debug("Polling for WPM data for attempt id: " + flower.attr("data-attemptid"));
                $.post(config.wwwroot + "/blocks/readaloudstudent/ajaxhelper.php",
                    {
                        cmid: flower.attr("data-cmid"),
                        action: 1,
                        data: flower.attr("data-attemptid")
                    })
                    .done(function (data) {
                        log.debug("AJAX response received");
                        if (data.success === true) {
                            if (data.data !== -1 && $.isNumeric(data.data)) {
                                // Here we set HTML for the *class* wpm-[id] so that we cover multiple instances for one attempt.
                                var wpm = $(".wpm-" + flower.attr("data-attemptid"));
                                wpm.fadeOut(300, function() {
                                    wpm.html(data.data).fadeIn(300);
                                });
                                flower.attr('data-aiwpm', data.data);
                                $("#recent-attempt-flowerstats-" + flower.attr("data-attemptid"))
                                    .find(".flowerstats").attr("data-aiwpm", data.data);
                            } else {
                                log.debug('AJAX response shows data not ready');
                            }
                        } else {
                            log.error('Error obtaining WPM data by AJAX');
                            log.debug(data);
                        }
                        afterAjaxRequest();
                    })
                    .fail(function (jqXHR, status, error) {
                        log.debug('AJAX fail for WPM request', jqXHR, status, error);
                        afterAjaxRequest();
                    });
            }
        });
    };

    return {
        init: function () {
            $(document).ready(function () {
                // Wait 2 seconds for UI animation to complete then do AJAX requests.
                setTimeout(function() {
                    updateWpmAjaxRequests(MAX_CHECKS);
                }, 2000);
            });
        }
    };
});