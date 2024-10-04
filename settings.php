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
 * Block readaloudstudent
 *
 * @package    block_readaloudstudent
 * @copyright  Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_readaloudstudent\constants;
use block_readaloudstudent\common;

defined('MOODLE_INTERNAL') || die();
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(constants::M_COMP . '_config_header',
        get_string('headerconfig', constants::M_COMP),
        get_string('descconfig', constants::M_COMP)));


    $settings->add(new admin_setting_configtext(constants::M_COMP . '/maxpercourse',
        get_string('maxpercourse', constants::M_COMP),
        get_string('maxpercourse_desc', constants::M_COMP),
        0, PARAM_INT));

    $options = common::fetch_showcourses_options();
    $settings->add(new admin_setting_configselect(constants::M_COMP . '/showcourses',
        get_string('showcourses', constants::M_COMP),
        get_string('showcourses_desc', constants::M_COMP),
        constants::M_THISCOURSE, $options));

    $options = common::fetch_showreadings_options();
    $settings->add(new admin_setting_configselect(constants::M_COMP . '/showreadings',
        get_string('showreadings', constants::M_COMP),
        get_string('showreadings_desc', constants::M_COMP),
        constants::M_SHOWALLREADINGS, $options));

    $options = common::fetch_forcesequence_options();
    $settings->add(new admin_setting_configselect(constants::M_COMP . '/forcesequence',
        get_string('forcesequence', constants::M_COMP),
        get_string('forcesequence_desc', constants::M_COMP),
        constants::M_FORCESEQUENCE, $options));

    $settings->add(new admin_setting_configtext(constants::M_COMP . '/introvideoid',
        get_string('introvideoid', constants::M_COMP),
        get_string('introvideoid_desc', constants::M_COMP),
        '317918722', PARAM_TEXT));

    // Custom Default Place holder flower.

        $name = constants::M_COMP . '/customplaceholderflower';
        $title = get_string('customplaceholderflower', constants::M_COMP);
        $description = get_string('customplaceholderflower', constants::M_COMP);
        $settings->add(new admin_setting_configstoredfile($name,
         $title, $description,
         constants::M_CUSTOMPLACEHOLDERFLOWER_FILEAREA , 0));

    // Default flowers.
    $slots = constants::M_DEFAULTFLOWERSLOTS;
    foreach ($slots as $slot) {
        $name = constants::M_COMP . '/flower' . $slot;
        $title = get_string('customdefaultflower', constants::M_COMP, $slot);
        $description = get_string('customdefaultflower', constants::M_COMP, $slot);
        $settings->add(new admin_setting_configstoredfile($name, $title, $description, constants::M_CUSTOMDEFAULTFLOWER_FILEAREA,$slot));
    }
}
