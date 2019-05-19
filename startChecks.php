<?php
require_once "checkAssignments.php";

/*
 *	Start everything from here
 */
if ($argc != '3') 
{
	echo "Usage: \n startChecks.php /path/to/dir 1,2,3\n" . "Where 1 -3 is the assignment number.\n";
	die();
} 
else 
{
	$directoryRoot = rtrim($argv[1], "/");
	$assignment = $argv[2];
	$StudentFiles["html"] = array();
	$StudentFiles["css"] = array();
	$StudentFiles["images"] = array();
	
	$startCheck = new checkAssignments($directoryRoot, $assignment);
	
	echo "Reading in csv file...\n";
	// Call our function to read in the student list
	$students = $startCheck->readStudentCSV();
	
	foreach($students as $student)
	{
	echo "Looking for html files for student: " . $student->getusername() . "\n";
	// Call our function to recurse through the directories
	$htmlfiles = $startCheck->recurseDir("html", $student->getUsername());
	$StudentFiles["html"] = $StudentFiles["html"] + $htmlfiles;

	echo "Looking for css files for student:  " . $student->getusername() . "\n";
	$cssfiles = $startCheck->recurseDir("css", $student->getUsername());
	$StudentFiles["css"] = $cssfiles + $StudentFiles["css"];
	
	echo "Looking for image files for student: " . $student->getusername() . "\n";
	$imagefiles = $startCheck->recurseDir("images", $student->getUsername());
	$StudentFiles["images"] = $imagefiles + $StudentFiles["images"];
	

	}
//	print_r($StudentFiles);
	$startCheck->validateFiles($StudentFiles);

	switch($assignment)
	{
		case 1:
			$startCheck->startAssignment1($students, $StudentFiles);
			break;
		case 2:
			$startCheck->startAssignment2($students, $StudentFiles);
			break;
		case 3:
			//$chAss->startAssignment3();
			break;
	}
	
	// Output all results to CSV
	$startCheck->printTotals($students);
	
	unset($StudentFiles);
	unset($startCheck);
}

?>
