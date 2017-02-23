<script type="text/javascript" src="jquery/jquery-2.1.4.min.js"></script>
<?php

require_once('../../config.php');
global $DB;
echo $sql="SELECT * FROM mdl_course_sections WHERE course=6";
$result= $DB->get_record_sql($sql);
// $result=$DB->execute($sql);
print_r($result);
defined('MOODLE_INTERNAL') || die();

$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('standard');

$settingnode=$PAGE->settingsnav->add(get_string('pluginname','block_global'));
// $editurl=new moodle_url('/blocks/helloworld/view.php',array('courseid'));
$thingnode = $settingnode->add(get_string('pluginname','block_global'));

$thingnode->make_active();
print $OUTPUT->header(); ?>
<!--register form -->
<form id="login" method="post" action="dbman.php">
<h1> Register Form</h1>
<div><span id="errorfnm" style="color:red" ></span><label for="login_username">First Name</label>
<input type="text" name="fnm" id="fnm" value="" /></div>
<div><span id="errorlnm" style="color:red" ></span><label for="login_username">Last Name</label>
<input type="text" name="lnm" id="lnm" value="" /></div>
<div><span id="erroremail" style="color:red" ></span><label for="login_username">Email</label>
<input type="text" name="email" id="email" value="" /></div>

<div class="c1 btn"><input type="button" id="reg" name="reg" value="Register" /></div>

</form>

<?php print $OUTPUT->footer();
?>

<script type="text/javascript">
$(document).ready(function(){
  $('#reg').click(function(){

  var fnm=$('#fnm').val();
  var lnm=$('#lnm').val();
  var email=$('#email').val();
  if(fnm=='' || lnm=='' || email=='')
  {
	  if(fnm=='')
	  {
	  	$('#errorfnm').html('* please enter first name');
	  }
	  else
	  {
	  	$('#errorfnm').html('');
	  }
	  if(lnm=='')
	  {
	  	$('#errorlnm').html('* please enter Last name');
	  }
	  else
	  {
	  	$('#errorlnm').html('');
	  }
	  if(email=='')
	  {
	  	$('#erroremail').html('* please enter email');
	  }
	  else
	  {
	  	$('#erroremail').html('');
	  }
  }
  else
  {
  		$('#erroremail').html('');
  		$('#errorlnm').html('');
  		$('#errorfnm').html('');
	 var data=$('form').serialize();
	 $.ajax({
	  type:'POST',
	  data:data,
	  url:"dbman.php",
	  success:function(data){
	    alert(data);
  }
 });
}
})
});
</script>