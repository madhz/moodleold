<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
//moodleform is defined in formslib.php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
class lastaccess_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore! 
        $options=array();
        $options[0]=get_string('choose');
        $options=$this->_customdata['courses'];
        $maxbytes=$this->_customdata['maxsize'];
        $mform->addElement('select','course',get_string('course'),$options,'align=center');
        $mform->setType('course', PARAM_ALPHANUMEXT);  
        //Default value
        $mform->addElement('text','name',get_string('course'), 'maxlength="100" size="10"');
        $mform->addHelpButton('name', 'idnumbergroup');
        $mform->setType('name', PARAM_ALPHANUMEXT);


        $mform->addElement('filepicker', 'userfile', get_string('file'), null,
                   array('maxbytes' => $maxbytes, 'accepted_types' => '*'));

        $mform->addElement('filemanager', 'attachments', get_string('attachment', 'moodle'), null,
                    array('subdirs' => 0, 'maxbytes' => $maxbytes, 'areamaxbytes' => 10485760, 'maxfiles' => 50,
                          'accepted_types' => array('document'), 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL));
        $mform->addElement('submit', 'save', get_string('display','report_lastaccess'),'align=center'); 
        // $this->add_action_buttons();
    }
    //Custom validation should be added here
 function validation($data, $files) {
    //     global $COURSE, $DB, $CFG;

    //     $errors = parent::validation($data, $files);
    //     if(($data['name'])=='0'){
    //       $errors['name']=get_string('idnumbertaken');
    //     }
    //     return $errors;
    }

}

?>