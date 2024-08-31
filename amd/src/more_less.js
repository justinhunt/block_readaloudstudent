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
 * Javascript Module to handle "Show more" and "Show fewer" flower tiles controls.
 *
 * @module more_less
 * @package block_readaloudstudent
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint space-before-function-paren: 0 */

define(["jquery"], function ($) {
    "use strict";

    /**
     * For a given course dashboard, watch its more less buttons and react if clicked.
     * @param {object} courseDashboard
     */
    var watchMoreLessButtons = function(courseDashboard) {
        var originallyHidden = courseDashboard.find(".flowertile-outer.hidden").not(".more-less-card");

        courseDashboard.find(".more-less-card").on("click", function(e) {
            var clickedItem = $(e.currentTarget);
            clickedItem.fadeOut(300, function() {
                if (clickedItem.attr("data-action") === "show-more") {
                    var hiddenTiles = courseDashboard.find(".flowertile-outer.hidden");
                    hiddenTiles.slideDown(300, function() {
                        courseDashboard.find(".less-card").removeClass("hidden").fadeIn(200);
                    });
                } else {
                    originallyHidden.slideUp(300, function() {
                        courseDashboard.find(".more-card").removeClass("hidden").fadeIn(200);
                    });
                }
            });
        });
    };

    return {
        init: function () {
            $(document).ready(function () {
                var courseDashes = $(".course-dash");
                courseDashes.each(function(index, dash) {
                    watchMoreLessButtons($(dash));
                });
            });
        }
    };
});