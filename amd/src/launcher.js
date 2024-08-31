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
 * Javascript Module to launch other JS modules.
 *
 * @module launcher
 * @package block_readaloudstudent
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint space-before-function-paren: 0 */

define(["block_readaloudstudent/dash_animation", "block_readaloudstudent/ajax_wpm", "block_readaloudstudent/more_less"],
    function (dashAnimation, ajaxWpm, moreLess) {
    "use strict";
    return {
        init: function (metersData, meterRadius, courseToOpen) {
            dashAnimation.init(metersData, meterRadius, courseToOpen);
            ajaxWpm.init();
            moreLess.init();
        }
    };
});