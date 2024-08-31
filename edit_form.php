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

use block_readaloudstudent\constants;
use block_readaloudstudent\common;

class block_readaloudstudent_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        //get admin settings config
        $config =get_config(constants::M_COMP);

         // Section header title according to language file.
         if(isset($this->block->instance->id)){
            $mform->addElement('static', 'blockinstanceid', get_string('blockinstanceid', constants::M_COMP),$this->block->instance->id);
         }

        // A sample string variable with a default value.
        //we need to prefix all our settings with config_ for the block to do its magic of saving and fetching them
        //for us
        $mform->addElement('text', 'config_maxpercourse', get_string('maxpercourse', constants::M_COMP));
        $mform->setDefault('config_maxpercourse', $config->maxpercourse);
        $mform->setType('config_maxpercourse', PARAM_INT);

        $options = common::fetch_showcourses_options();
        $mform->addElement('select', 'config_showcourses', get_string('showcourses', constants::M_COMP),$options);
        $mform->setDefault('config_showcourses',$config->showcourses);

        $options = common::fetch_showreadings_options();
        $mform->addElement('select', 'config_showreadings', get_string('showreadings', constants::M_COMP),$options);
        $mform->setDefault('config_showreadings',$config->showreadings);

        $options = common::fetch_forcesequence_options();
        $mform->addElement('select', 'config_forcesequence', get_string('forcesequence', constants::M_COMP),$options);
        $mform->setDefault('config_forcesequence',$config->forcesequence);

        //Flower pictures
        $imageoptions = common::fetch_flowerimage_opts($this->block->context);
        $mform->addElement('filemanager', 'config_flowerpictures', get_string('flowerpictures',constants::M_COMP), null, $imageoptions);

         //Flower pictures
        $p_imageoptions = common::fetch_placeholderflower_opts($this->block->context);
        $mform->addElement('filemanager', 'config_placeholderflower', get_string('placeholderflower',constants::M_COMP), null, $p_imageoptions);



    }

    function set_data($defaults) {
        $itemid=1;

        //flower pictures
        if (!empty($this->block->config) && !empty($this->block->config->flowerpictures)) {
            $draftitemid = file_get_submitted_draft_itemid('config_flowerpictures');
        }else{
            $draftitemid = 0;
        }
        
        $imageoptions = common::fetch_flowerimage_opts($this->block->context);
        file_prepare_draft_area($draftitemid, $this->block->context->id, constants::M_COMP, constants::FLOWERPICTURES_FILEAREA,$itemid,
                $imageoptions);
        $this->block->config->flowerpictures = $draftitemid;

        //placeholder flower
        if (!empty($this->block->config) && !empty($this->block->config->placeholderflower)) {
            $p_draftitemid = file_get_submitted_draft_itemid('config_placeholderflower');
        }else{
            $p_draftitemid = 0;
        }
        
        $p_imageoptions = common::fetch_placeholderflower_opts($this->block->context);
        file_prepare_draft_area($p_draftitemid, $this->block->context->id, constants::M_COMP, constants::PLACEHOLDERFLOWER_FILEAREA,$itemid,
                $p_imageoptions);
        $this->block->config->placeholderflower = $p_draftitemid;


        parent::set_data($defaults);
 
    }
}
