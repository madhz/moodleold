<?php

function xmldb_block_autoattend_install() 
{
    global $DB;

	$rec = new stdClass();

	$rec->id = 0;
	$rec->courseid = 0;

    $rec->status = 'P';
	$rec->grade  =  2;
	$DB->insert_record('autoattend_settings', $rec);

    $rec->status = 'L';
	$rec->grade  =  1;
	$DB->insert_record('autoattend_settings', $rec);

    $rec->status = 'E';
	$rec->grade  =  1;
	$DB->insert_record('autoattend_settings', $rec);

    $rec->status = 'X';
	$rec->grade  =  0;
	$DB->insert_record('autoattend_settings', $rec);

    $rec->status = 'Y';
	$rec->grade  =  0;
	$DB->insert_record('autoattend_settings', $rec);

	//
	$DB->set_field('block', 'visible', 1, array('name'=>'autoattend'));
}

