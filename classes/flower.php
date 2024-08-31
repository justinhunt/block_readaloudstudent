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
 * Flower handler for readaloudstudent plugin
 *
 * @package    block_readaloudstudent
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 namespace block_readaloudstudent;

defined('MOODLE_INTERNAL') || die();

use \block_readaloudstudent\constants;


/**
 * Functions used generally across this mod
 *
 * @package    block_readaloudstudent
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flower{

    private $context;
    private $flowers=false;
    private $flowerfiles=false;
    private $placeholderflowerurl=false;

    function __construct($context) {
        $this->context=$context;
    }


    //fetch a flower item for the completed attempt
    public function fetch_newflower($readaloudid,$userid){
        global $CFG, $USER,$DB;
        $flowers = self::fetch_flowers();

        //If this user on this readaloud already has a flower from a previous attempt we return that
        $sql ='SELECT flowerid';
        $sql .=' FROM {' . constants::M_ATTEMPTTABLE . '} a';
        $sql .=' WHERE a.readaloudid=? AND a.userid =? AND NOT a.flowerid=0';
        $record = $DB->get_record_sql($sql,array($readaloudid,$userid));
        if($record){
            $flower= $flowers[$record->flowerid];
            return $flower;
        }

        //get the new flower id and return it
        $used_flowerids = $DB->get_fieldset_select(constants::M_ATTEMPTTABLE, 'flowerid', 'userid =:userid', array('userid'=>$USER->id));
        //if we have used flowers and we have not used all our flowers, we reduce the flowers array to the ones we have not allocated yet.
        $candidates = array_filter($flowers, function($flower) use ($used_flowerids) {
            return !in_array($flower['id'], $used_flowerids);
        });
        if (empty($candidates)) {
            $candidates = $flowers;
        }

        $flowerid = array_rand($candidates);
        $flower= $flowers[$flowerid];
        return $flower;
    }
    public function fetch_flowers(){
        //if we have flowers already return those
        if($this->flowers){
            return $this->flowers;
        }

        if(!$this->flowerfiles){
            $fs = get_file_storage();
            $itemid=1;
            $this->flowerfiles = $fs->get_area_files($this->context->id,constants::M_COMP , constants::FLOWERPICTURES_FILEAREA , $itemid, 'id ASC', false);
        }

        if(empty($this->flowerfiles)){
            $this->flowers=$this->fetch_default_flowers();
            return $this->flowers;
        }
       
        $index=1;
        $flowers=[];
        foreach ($this->flowerfiles as $file) {
            $filename = $file->get_filename();

            //Make the displayname from the path name
            $displayname = pathinfo($filename, PATHINFO_FILENAME);
            $displayname = str_replace('_', ' ', $displayname);
            $displayname = ucwords($displayname);
            //pull the file
            $flowers[$index]=array('id'=>$index,'filename'=>$filename,'displayname'=>$displayname);
            $index++;
        }

        $flowers = array_map(function($flower) {
            $flower['picurl'] = $this->get_flower_url($flower);
            return $flower;
        }, $flowers);

        $this->flowers = $flowers;
        return $flowers;
    }

    public function get_dummy_flower(){
        global $OUTPUT;
        $flower=[];
        $flower['id']=0;
        $flower['picurl']=$this->get_placeholder_flower_url();
        $flower['displayname']=get_string('pendingflower',constants::M_COMP);
        $flower['filename']='pending';
        return $flower;
    }

    public function get_placeholder_flower_url(){
        global $CFG,$OUTPUT;
        if( $this->placeholderflowerurl){
            return $this->placeholderflowerurl;
        }

        $fs = get_file_storage();
        $itemid=1;
        $files = $fs->get_area_files($this->context->id,constants::M_COMP , constants::PLACEHOLDERFLOWER_FILEAREA , $itemid, 'id ASC', false);
        if($files){
            $file = array_shift($files);
            $filename = $file->get_filename();
                //The path to the image file
                $mediapath = $CFG->wwwroot.'/pluginfile.php/'.$this->context->id 
                . '/' . constants::M_COMP . '/' . constants::PLACEHOLDERFLOWER_FILEAREA  . '/'.$itemid.'/'.$filename;
                
        }else{
            $mediapath=$OUTPUT->image_url('dummy_flower', 'block_readaloudstudent')->out();
        }
        $this->placeholderflowerurl = $mediapath;
         return $mediapath;

    }


    public function get_flower($flowerid) {
        $flowers = $this->fetch_flowers();
        if(array_key_exists($flowerid,$flowers)){
            $flower = $flowers[$flowerid];
        }else{
            $flower=$this->get_dummy_flower();
        }
        return $flower;
    }

    public function get_flower_url($flower) {
        global $CFG;

        $itemid=1;
        if(!$this->flowerfiles){
            $fs = get_file_storage();
            $this->flowerfiles = $fs->get_area_files($this->context->id,constants::M_COMP , constants::FLOWERPICTURES_FILEAREA , $itemid, 'id ASC', false);
        }
       
        $mediapath="";
        foreach ($this->flowerfiles as $file) {
            $filename = $file->get_filename();
            if($filename==$flower['filename']){
                //The path to the image file
                $mediapath = $CFG->wwwroot.'/pluginfile.php/'.$this->context->id 
                . '/' . constants::M_COMP . '/' . constants::FLOWERPICTURES_FILEAREA  . '/'.$itemid.'/'.$filename;
                return $mediapath;
            }
            
        }
        return $mediapath;
    }

    public function fetch_default_flowers(){
        
        $flowers = array(
            1=>array('id'=>1,'filename'=>'seedles', 'displayname'=>'Seedles'),
            2=>array('id'=>2,'filename'=>'pipi','displayname'=>'Pippi Longseed'),
            3=>array('id'=>3,'filename'=>'bleep','displayname'=>'Bleep'),
            4=>array('id'=>4,'filename'=>'speed_seed','displayname'=>'Speed Seed'),
            5=>array('id'=>5,'filename'=>'mermaid', 'displayname'=>'Mermaid'),
            6=>array('id'=>6,'filename'=>'alien', 'displayname'=>'3-Eyes'),
            7=>array('id'=>7,'filename'=>'miss_seedy', 'displayname'=>'Miss Seedy'),
            8=>array('id'=>8,'filename'=>'shark', 'displayname'=>'Shark'),
            9=>array('id'=>9,'filename'=>'batseed', 'displayname'=>'Bat Seed'),
            10=>array('id'=>10,'filename'=>'triple_play', 'displayname'=>'Triple Play'),
            11=>array('id'=>11,'filename'=>'slugger', 'displayname'=>'Slugger'),
            12=>array('id'=>12,'filename'=>'billytheseed', 'displayname'=>'Billy the Seed'),
            13=>array('id'=>13,'filename'=>'sir_seed', 'displayname'=>'Sir Seed'),
            14=>array('id'=>14,'filename'=>'ned_kelly', 'displayname'=>'Ned'),
            15=>array('id'=>15,'filename'=>'disco_girl', 'displayname'=>'Disco Girl'),
            16=>array('id'=>16,'filename'=>'disco_boy', 'displayname'=>'Disco Boy'),
            17=>array('id'=>17,'filename'=>'snow_seed', 'displayname'=>'Snowseed'),
            18=>array('id'=>18,'filename'=>'seah', 'displayname'=>'Princess Seah'),
            19=>array('id'=>19,'filename'=>'red_riding_seed', 'displayname'=>'Red Riding Seed'),
            20=>array('id'=>20,'filename'=>'mon_seed', 'displayname'=>'Mon Seed'),
            21=>array('id'=>21,'filename'=>'wonder_seed', 'displayname'=>'Wonder Seed'),
            22=>array('id'=>22,'filename'=>'guy_seedy', 'displayname'=>'Guy Seedy'),
            23=>array('id'=>23,'filename'=>'agent_seed', 'displayname'=>'Agent Seed'),
            24=>array('id'=>24,'filename'=>'ellie', 'displayname'=>'Ellie'),
            25=>array('id'=>25,'filename'=>'ninja','displayname'=>'Ninja'),
        );

        return array_map(function($flower) {
            global $OUTPUT;
            $flower['picurl'] = $OUTPUT->image_url($flower['filename'], 'block_readaloudstudent')->out();
            return $flower;
        }, $flowers);

    }
}
