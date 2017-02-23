
<script type="text/javascript" src="jquery/jquery-2.1.4.min.js"></script>
<?php

require_once('../../config.php');
global $DB;
$id=$_GET['id'];
$user= $DB->get_record('mdl_register', array('id'=>$id));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('standard');

print $OUTPUT->header(); ?>
<form id="login" method="post" action="dbman.php">
<h1> Register Form</h1>
<input type="hidden" name="uid" id="uid" value="<?php echo $id; ?>">
<div><span id="errorfnm" style="color:red" ></span><label for="login_username">First Name</label>
<input type="text" name="fnm" id="fnm" value="<?php echo $user->firstname; ?>" /></div>
<div><span id="errorlnm" style="color:red" ></span><label for="login_username">Last Name</label>
<input type="text" name="lnm" id="lnm" value="<?php echo $user->lastname; ?>"/></div>
<div><span id="erroremail" style="color:red" ></span><label for="login_username">Email</label>
<input type="text" name="email" id="email"  value="<?php echo $user->email; ?>"/></div>

<div class="c1 btn"><input type="button" id="reg" name="reg" value="Update" /></div>

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
	  url:"updateuserdb.php",
	  success:function(data){
	    alert(data);
  }
 });
}
})
});
</script>