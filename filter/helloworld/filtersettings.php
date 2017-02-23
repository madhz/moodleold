<?php
$settings->add(new admin_setting_configtext('filter_helloworld/word',
        get_string('word', 'filter_helloworld'),
        get_string('word_desc', 'filter_helloworld'), 'world', PARAM_NOTAGS));
?>