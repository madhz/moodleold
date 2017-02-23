<?php

global $CFG;
global $DB;
error_reporting(E_ALL);
ini_set("display_errors", 1);
// require_once("../../config.php");
//include simplehtml_form.php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require(dirname(dirname(dirname(__FILE__))).'/report/lastaccess/index_form.php');
require_once($CFG->dirroot . '/repository/lib.php');
//Instantiate simplehtml_form 
// bye';
defined('MOODLE_INTERNAL') || die();
$systemcontext=get_system_context();
$url=new moodle_url('/report/lastaccess/index.php');
require_capability('report/lastaccess:view',$systemcontext);
$strtitle=get_string('title','report_lastaccess');


$PAGE->set_url($url);
$PAGE->set_context($systemcontext);
$PAGE->set_title($strtitle);


$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add($strtitle, new moodle_url('/report/lastaccess/index.php'));
$PAGE->set_pagelayout('standard');
$context = $PAGE->context;
$contextid=$context->id;
// $section = $PAGE->section;
// $sectionid = $section->id;
    // $mform=();	
    echo $OUTPUT->header();
    $maxsize=$CFG->maxbytes;
    // echo $OUTPUT->heading($strtitle );
  	$sql="SELECT id,fullname from mdl_course where visible = :visible and id!= :siteid ORDER BY fullname";
    $courses=$DB->get_records_sql_menu($sql,array('visible'=>1,'siteid'=>SITEID));

   	$mform=new lastaccess_form('',array('courses'=>$courses,'maxsize'=>$maxsize));
   	$mform->display();

if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
if (empty($entry->id)) {
  $entry = new object();
  $entry->id = null;
  $entry->definition = '';
  $entry->format = FORMAT_HTML;
}

    // ... store or update $entry
    // file_save_draft_area_files('attachments', $context->id, 'mod_glossary', 'userfile',
    //                $entry->id, array('subdirs' => 0, 'maxbytes' => $maxsize, 'maxfiles' => 50));


   $cid=$fromform->course;
       $realfilename = $mform->get_new_filename('userfile');
   $importfile = "{$CFG->tempdir}/myform/{$realfilename}";
   make_temp_directory('myform');
   if (!$result = $mform->save_file('userfile', $importfile, true)) {
   throw new moodle_exception('uploadproblem');
}




	$sql="SELECT firstname, lastname, fullname,FROM_UNIXTIME(lastaccess, '%Y-%m-%d') as 'last'
FROM `mdl_user` mu, `mdl_user_lastaccess` l, `mdl_course` c
WHERE c.id = l.courseid
AND l.userid = mu.id and courseid=$cid";
$users=$DB->get_records_sql($sql);

if($users=$DB->get_records_sql($sql))
{
	$table=new html_table();
	$table->head=array('course name','First name','Last name','Last access');
	foreach($users as $u){
		//date in php date("Y-M-d",$u->last)
		$table->data[]=array($u->fullname,$u->firstname,$u->lastname,$u->last);
	}
	echo html_writer::table($table);
}
else
{

}
  //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {

}

    echo $OUTPUT->footer();
    ?>