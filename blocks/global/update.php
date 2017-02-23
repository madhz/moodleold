<?php

require_once('../../config.php');
global $DB;
// print_r($DB);
defined('MOODLE_INTERNAL') || die();

$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('standard');

print $OUTPUT->header(); 
$result=$DB->get_records('mdl_register');?>

<table border="1">
	<tr>
		<th>First Name</th>
		<th>Last Name </th>
		<th>Email</th>
		<th>Action</th>
		</tr>
<?php foreach($result as $res){?>
	<tr>
		<td><?php echo $res->firstname; ?></td>
		<td><?php echo $res->lastname; ?></td>
		<td><?php echo $res->email; ?></td>
		<td><a href="/moodle/blocks/helloworld/updateuser.php?id=<?php echo $res->id; ?>">Update</a></td>
	</tr>
<?php }?>
</table>

<?php  print $OUTPUT->footer();
?>
