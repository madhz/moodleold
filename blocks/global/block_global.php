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
 * Block displaying information about current logged-in user.
 *
 * This block can be used as anti cheating measure, you
 * can easily check the logged-in user matches the person
 * operating the computer.
 *
 * @package    block_myprofile
 * @copyright  2010 Remote-Learner.net
 * @author     Olav Jordan <olav.jordan@remote-learner.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Displays the current user's profile information.
 *
 * @copyright  2010 Remote-Learner.net
 * @author     Olav Jordan <olav.jordan@remote-learner.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_global extends block_base {
    /**
     * block initializations
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_global');
    }

    /**
     * block contents
     *
     * @return object
     */
    public function get_content() {
       // global $CFG, $USER, $DB, $OUTPUT, $PAGE;
        global $COURSE;
        global $USER;

        // if ($this->content !== NULL) {
        //     return $this->content;
        // }

    $this->content         =  new stdClass;

    //second change if (! empty($this->config->text)) 
    // {
    //     $this->content->text = $this->config->text;
    // }
    // else
    // {
    //     $this->content->text='This is the default value';
    // }
    //first change $this->content->text   = 'The content of our Hello World block!';
        $text='Course ID: '.$COURSE->id.'<br>';
        $text.='Course Name: '.$COURSE->fullname.'<br>';
        $text.='User ID: '.$USER->id.'<br>';
        $text.='User Name: '.$USER->firstname.'<br>';
        $this->content->text=$text;

        $url_site=new moodle_url('/blocks/global/view.php',array('blockid'=>$this->instance->id,'courseid'=>$COURSE->id,'global'=>'course'));
  $user_site=new moodle_url('/blocks/global/view.php',array('blockid'=>$this->instance->id,'courseid'=>$COURSE->id,'global'=>'user'));
         $register=new moodle_url('/blocks/global/register.php',array('blockid'=>$this->instance->id,'courseid'=>$COURSE->id,'global'=>'register'));
         $display=new moodle_url('/blocks/global/display.php',array('blockid'=>$this->instance->id,'courseid'=>$COURSE->id,'global'=>'display'));
          $update=new moodle_url('/blocks/global/update.php',array('blockid'=>$this->instance->id,'courseid'=>$COURSE->id,'global'=>'update'));

$footer=html_writer::link($url_site, get_string('linkname', 'block_global').':Coruse');
$footer.='<br><br><img src="pix/webding.png"> '.html_writer::link($user_site,get_string('linkname','block_global').':USER');
$footer.='<br><br>'.html_writer::link($register,get_string('linkname','block_global').':Register');
$footer.='<br><br>'.html_writer::link($display,get_string('linkname','block_global').':Display');
$footer.='<br><br>'.html_writer::link($update,get_string('linkname','block_global').':Update');
    $this->content->footer = $footer;
 
    // return $this->content;
        return $this->content;
    }

    public function specialization() {
    if (isset($this->config)) {
        if (empty($this->config->title)) {
            $this->title = get_string('contentinputtitle', 'block_global');            
        } else {
            $this->title = $this->config->title;
        }
 
           
    }
}
 function has_config() {return true;}
function html_attributes() {
  $attrs = parent::html_attributes();
if(get_config("helloworld","Setting_background")==1)
{
    $attrs['class'].= ' setbg';
}
  return $attrs;
}
public function instance_allow_multiple() {
  return true;
}

}
