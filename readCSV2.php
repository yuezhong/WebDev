<?php
require_once "StudentObj.php";
require_once "StudentFileObj.php";
require_once "StudentMarksObj.php";
require_once "ParseCA1.php";
require_once "OutputResults.php";

$file = fopen("students.csv", "r") or exit("Unable to locate student csv file!");

while(($line = fgetcsv($file, 200, ",")) !== FALSE)
{
	$student = new StudentObj($line[1], $line[2]);
	echo $line[0] . "\n";
	$student_arr[$line[0]] = $student;
}

//print_r($student_arr);
fclose($file);


// Recurse through directories

try
{

	$directory = "./test_dir2";
    $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS));	

	// Find only the following files: (html|css|bmp|gif|jpg|jpeg|png)	
	$filepaths = new RegexIterator($dir, '/^.+\.(html|css|bmp|gif|jpg|jpeg|png)$/i');

	$i = 0;
	foreach($filepaths as $filepath)
	{
	 $path = explode('/', $dir->getSubPath());
	 //$studentFile = new StudentFileObj($path[0], );
	 $filename = basename($dir->getSubPathName());
	 $filetype = pathinfo($filename, PATHINFO_EXTENSION);
	 //echo $path[0] . "\n";
	 //echo "Filename: $filename\n"; 
	 //echo "Type: $filetype\n";
	 //echo $dir->getSubPathName() . " : " . $dir->key(). " : " .  $dir->getSize() ."\n";

	 $StudentFile[] = new StudentFileObj($path[0], $filename, $filetype);
	 $StudentFile[$i]->setFileSize($dir->getSize());
	 $StudentFile[$i]->setFilePath($dir->key());
	 $i++;
	}

	startChecks($StudentFile, $student_arr);

}
catch(Exception $error)
{
	echo $error->getMessage();
}


function startChecks($StudentFile, $student_arr)
{
	$running_total["lliu22"] = 0;

	$pca = new ParseCA1();
	$smarks = new StudentMarksObj("1", $student_arr["lliu22"]->getStudentId());
	//$studentMarks[$StudentFile[0]->getStudentId()] new StudentMarksObj("1", $StudentFile[0]->getStudentId());
	$pca->start($StudentFile[0]->getFilePath(), $StudentFile[0]->getusername());
	$smarks->setMarks($pca->getca1Marks());
	$smarks->setComments($pca->getca1Comments());

	$running_total["lliu22"] += $pca->getca1Marks();

	$htmlOut = new OutputResults($student_arr["lliu22"], "1");
	$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(), $smarks->getComments(), $smarks->getCA());
	
	$htmlOut->closeHTML($running_total["lliu22"]);
	//echo $pca->getca1Marks() . "\n";
	//echo $pca->getca1Comments();


}
?>
