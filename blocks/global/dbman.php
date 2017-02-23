<?php
require_once('../../config.php');
global $DB;

$fnm=$_POST['fnm'];
$lnm=$_POST['lnm'];
$email=$_POST['email'];


$sql="INSERT INTO mdl_mdl_register(`firstname`,`lastname`,`email`)VALUES('$fnm','$lnm','$email')";
if($DB->execute($sql))
{
	echo 'Register Successfullly..!';
}
else
{
	echo "failed to register...!!!";

}
?>