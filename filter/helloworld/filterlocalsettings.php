<?php
class helloworld_filter_local_settings_form extends filter_local_settings_form {
    protected function definition_inner($mform) {
        $mform->addElement('text', 'word', get_string('word', 'filter_helloworld'), array('size' => 20));
        $mform->setType('word', PARAM_NOTAGS);
    }
}
?>