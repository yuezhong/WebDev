<?php
require_once "StudentObj.php";
require_once "StudentFileObj.php";
require_once "StudentMarksObj.php";
require_once "ParseCA1.php";
require_once "ParseCA2.php";
require_once "ParseCA3.php";
require_once "ParseCA3a.php";
require_once "OutputResults.php";

class checkAssignment
{
	private	$studentcsv = "students.csv";
	private $dirRoot;
	private $assignment;

	public function __construct($dirRoot, $assignment)
	{
		$this->dirRoot = $dirRoot;
		$this->assignment = $assignment;
	}
	
	public function getDirRoot()
	{
		return $this->dirRoot;
	}
	
	// Read Student CSV file
	public function readStudentCSV()
	{
		$file = fopen($this->studentcsv, "r") or exit("Unable to locate student csv file!");

		while(($line = fgetcsv($file, 200, ",")) !== FALSE)
		{
			$student = new StudentObj($line[0], $line[1], $line[2], $line[3], $line[4], $line[5], $line[6]);
			$student_arr[$line[2]] = $student;
		}
		fclose($file);
		
		return $student_arr;
	} // End readStudentCSV
	
	// recurse through the directories
	public function recurseDir($filetypes)
	{
	 try{
		$dir = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
		$this->dirRoot, 
		FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS));
		
		switch ($filetypes)
		{
			// Find html files only
			case "html":
				$filepaths = new RegexIterator($dir, '/^.+\.(html)$/i');
			break;
			// Find css files only
			case "css":
				$filepaths = new RegexIterator($dir, '/^.+\.(css)$/i');
			break;
			// Find all images, png, jpg, gif
			case "images":
				$filepaths = new RegexIterator($dir, '/^.+\.(gif|jpg|png)$/i');
			break;
		}
		
		foreach($filepaths as $filepath)
		{
			$path = explode('/', $dir->getSubPath());
			$filename = $filepath->getfileName();
			$filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			if(count($path, COUNT_NORMAL) > 1)
			{
				$subfolder = $path[1];
			}
			else{
				$subfolder = "";
			}
			
			// Using file path as index, while removing the ./ at the start
			// $index = substr($dir->key(),2);
			$index = $dir->key();
			$FileObj[$index] = new StudentFileObj($path[0], $filename, $filetype, $subfolder);
			$FileObj[$index]->setFileSize($dir->getSize());
			$FileObj[$index]->setFilePath($dir->key());
			
			if($filetypes === "images")
			{
				$imageProps = getimagesize($index);
				$FileObj[$index]->setImageWidth($imageProps[0]);
				$FileObj[$index]->setImageHeight($imageProps[1]);
				$mime = explode("/", $imageProps["mime"]);
				$FileObj[$index]->setMimeType($mime[1]);
			}
		}
		
		return $FileObj;
	 }
	 catch(Exception $error)
	 {
		 echo $error->getMessage();
	 }
	} // End Recurse Directories
	
	// Validate files
	public function validateFiles($StudentFiles)
	{
		foreach($StudentFiles["css"] as $cssfiles)
		{
			// Calling external validator
			exec("java -Xss512k -jar ./vnu.jar --css --errors-only " . 
			$cssfiles->getFilepath() . " 2>&1", $output, $validatedCss);
			if($validatedCss > 0)
			{
				// Any errors found, set validation to N
				$cssfiles->setValidation("n");
			}
			else
			{
				// No errors, set validation to y
				$cssfiles->setValidation("y");
			}
		}
	} // End validateFiles
	
	// Start Assesessment checks
	public function checkAssessment($username, $StudentFiles, $smarks, $ca, $student)
	{
		// Find CA file e.g: studentName _CA1.html
		$cafile = $username . "_" . $ca .".html";	
		$parseObj = "Parse" . $ca;
		

		if(($ca === "CA3") || ($ca === "CA3a"))
		{
			$index = $this->dirRoot . "/" . $username . "_ISY10209_Ass1/index.html"; 
		}
		else
		{
			$index = $this->dirRoot . "/" . $username . "_ISY10209_Ass1/" . $cafile;
		}
			
		if(array_key_exists($index, $StudentFiles["html"]) || 
		   array_key_exists($index, $StudentFiles["css"]))
		{		
			$pca = new ${"parseObj"}($this->dirRoot . "/" . $username . "_ISY10209_Ass1/");	
			echo "Checking file: " . $index . "\n";
			$pca->start($index, $student, $StudentFiles);
			$smarks->setMarks($pca->getMarks());
			$smarks->setComments($pca->getComments());
			$student->addRtotal($pca->getMarks());
		}
		else{
			$smarks->setMarks(0);
			$smarks->setComments("No $ca File found.");
		}
		return $smarks;

	} // End CheckAssessment
		
	// Start Assignment 1 checks
	public function startAssignment1($students, $StudentFiles)
	{
		
		foreach($students as $username=>$student)
		{
			$htmlOut = new OutputResults($students[$username], "1");
			$smarks = new StudentMarksObj("1", $students[$username]->getStudentId());

			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA1", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(), $smarks->getComments(), "1");
			echo "Added CA1 marks and comments.\n";

			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA2", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(), $smarks->getComments(), "2");
			echo "Added CA2 marks and comments.\n";
			
			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA3", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(), $smarks->getComments(), "3");
			echo "Added CA3 marks and comments.\n";
			
			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA3a", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(), $smarks->getComments(), "3a");
			echo "Added CA3a marks and comments.\n";
			
			$htmlOut->closeHTML($student->getRtotal());
			echo "Feedback file generated for $username.\n";

		}
	}
	
	
} // End Class


/*
 *	Start everything from here
 */
if ($argc != '3') 
{
	echo "Usage: \n checkAssignment.php directory_name 1,2,3\n";
	die();
} 
else 
{
	$directoryRoot = rtrim($argv[1], "/");
	$assignment = $argv[2];
	
	$chAss = new checkAssignment($directoryRoot, $assignment);
	
	// Call our function to read in the student list
	$students = $chAss->readStudentCSV();
	
	// Call our function to recurse through the directories
	$StudentFiles["html"] = $chAss->recurseDir("html");
	$StudentFiles["css"] = $chAss->recurseDir("css");
	$StudentFiles["images"] = $chAss->recurseDir("images");

	$chAss->validateFiles($StudentFiles);

	switch($assignment)
	{
		case 1:
			$chAss->startAssignment1($students, $StudentFiles);
			break;
		case 2:
			//$chAss->startAssignment2();
			break;
		case 3:
			//$chAss->startAssignment3();
			break;
	}
}

?>