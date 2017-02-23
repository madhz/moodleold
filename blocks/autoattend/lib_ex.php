<?php 

function autoattend_repairDB()
{
	global $DB;

	$students = $DB->get_records('autoattend_students');

	foreach($students as $student) {
		$users = $DB->get_records('autoattend_students', array('attsid'=>$student->attsid, 'studentid'=>$student->studentid));
		//
		if (count($users)>1) {
			$countp = 0;
			foreach($users as $user) {
				if ($user->status!='X' and $user->status!='Y') $countp++;
			}
			//
			if ($countp>0) {	// X,Y 以外の出席がある．
				$counts = 0;
				foreach($users as $user) {
					if ($user->status!='X' and $user->status!='Y') $counts++;
					if ($user->status=='X' or $user->status=='Y' or $counts>1) {
						$DB->delete_records('autoattend_students', array('id'=>$user->id));
					}
				}
			}
			else {			  // 全て X か Y
				$counts = 0;
				foreach($users as $user) {
					$counts++;
					if ($counts>1) {
						$DB->delete_records('autoattend_students', array('id'=>$user->id));
					}
				}
			}
		}
	}
}
