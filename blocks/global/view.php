<?php
require_once('../../config.php');
defined('MOODLE_INTERNAL') || die();
$courseid=required_param('courseid',PARAM_INT);
$blockid=required_param('blockid',PARAM_INT);
$global=required_param('global',PARAM_TEXT);

$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('standard');

$settingnode=$PAGE->settingsnav->add(get_string('pluginname','block_helloworld'));
// $editurl=new moodle_url('/blocks/helloworld/view.php',array('courseid'));
$thingnode = $settingnode->add(get_string('pluginname','block_helloworld'));

$thingnode->make_active();

print $OUTPUT->header();
switch($global)
{
	case 'course':
	print 'COURSE: ';
	print_object($COURSE);
	break;
	case 'user':
	print 'USER: ';
	print_object($USER);
	break;

}
print $OUTPUT->footer();

