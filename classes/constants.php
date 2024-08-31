<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/16
 * Time: 19:31
 */

namespace block_readaloudstudent;

defined('MOODLE_INTERNAL') || die();

class constants
{
//component name, db tables, things that define app
const M_COMP='block_readaloudstudent';
const M_NAME='readaloudstudent';
const M_URL='/blocks/readaloudstudent';
const M_CLASS='block_readaloudstudent';
const M_RSTABLE='readaloud';
const M_RSURL='/mod/readaloud/view.php?n=';
const M_ATTEMPTTABLE='readaloud_attempt';
const M_AITABLE='readaloud_ai_result';
const M_DUMMY_FLOWER_URL='/blocks/readaloudstudent/flowers/p_dummy.svg';
const M_THISCOURSE=0;
const M_ENROLLEDCOURSES=1;
const M_ACTIVECOURSES=2;
const M_SHOWALLREADINGS=0;
const M_SHOWMYREADINGS=1;
const M_FORCESEQUENCE=1;
const M_DONTFORCESEQUENCE=0;
const M_ACTIONWPMRESULT=1;
const M_METER_RADIUS = 60;
const FLOWERPICTURES_FILEAREA="flowerpictures";
const PLACEHOLDERFLOWER_FILEAREA="placeholderflower";

}