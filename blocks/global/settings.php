<?php
$settings->add(new admin_setting_heading(
            'headerconfig',
            get_string('headerconfig', 'block_helloworld'),
            get_string('descconfig', 'block_helloworld')
        ));
 
$settings->add(new admin_setting_configcheckbox(
            'helloworld/Setting_background',
            get_string('labelbackground', 'block_helloworld'),
            get_string('descbackground', 'block_helloworld'),
            '0'
        ));