<?php
require_once('../../config.php');
global $DB;
$id=$_POST['uid'];
$fnm=$_POST['fnm'];
$lnm=$_POST['lnm'];
$email=$_POST['email'];


$sql="UPDATE mdl_mdl_register SET `firstname`='$fnm',`lastname`='$lnm',`email`='$email'  WHERE id=$id";
if($DB->execute($sql))
{
	echo 'Updated Successfullly..!';
}
else
{
	echo "failed to Update...!!!";

}
?>