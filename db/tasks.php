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
 * readaloudstudent block caps.
 *
 * @package   block_readaloudstudent
 * @copyright Justin Hunt <https://poodll.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_readaloudstudent\constants;


defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => constants::M_COMP . '\task\do_something',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '17',
        'day' => '1',
        'dayofweek' => '*',
        'month' => '*'
    )
);
