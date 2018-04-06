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

		// CSV file colums:
		// Fullname,StudentID,Username,ConceptMap,Storyboards,CodingStyle,Upload
		while(($line = fgetcsv($file, 200, ",")) !== FALSE)
		{
			$student = new StudentObj($line[0], $line[1], $line[2], 
						$line[3], $line[4], $line[5], $line[6]);
			$student_arr[$line[2]] = $student;
		}
		fclose($file);
		
		return $student_arr;
	} // End readStudentCSV
	
	// Check and rename directories
	public function prepDirectories($students)
	{
	 try{
		 $submission[] = array(
				"root" => 0,
				"images" => 0,
				"css" => 0,
				"docs" => 0
		 );
		 
		$dir = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
		$this->dirRoot, 
		FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS));
		
		// Loop through once to check formatting and 
		// see if css, images and doc folders are present
		foreach($dir as $name=>$value)
		{
			$rootpath = explode('/', $dir->getSubPath());
			$username = preg_split('/[\s_-]/', strtolower($rootpath[0]));
			if(strpos(strtolower($rootpath[0]), "isy10209_ass1"))
			{
			  $submission[$username[0]]["root"] = 1;
			}
			
			if(array_key_exists(1, $rootpath))
			{
				$filetype = strtolower(pathinfo($value, PATHINFO_EXTENSION));
				if((strtolower($rootpath[1]) === "css") &&
				($filetype === "css"))
				{
					$submission[$username[0]]["css"] = 1;
					//echo "$rootpath[1] : css folder present\n";
				}

				if(((strtolower($rootpath[1]) === "images") ||
				(strtolower($rootpath[1]) === "image") ||
				(strtolower($rootpath[1]) === "img") ||
				(strtolower($rootpath[1]) === "imgs")) &&
				(($filetype === "png") || ($filetype === "jpg") || ($filetype === "gif")))
				{
					$submission[$username[0]]["images"] = 1;
					//echo "$rootpath[1] : images folder present\n";
				}

				if(((strtolower($rootpath[1]) === "doc") ||
				(strtolower($rootpath[1]) === "docs") ||
				(strtolower($rootpath[1]) === "document") ||
				(strtolower($rootpath[1]) === "documents")) &&
				(($filetype === "doc") || ($filetype === "docx") || ($filetype === "pdf")
				|| ($filetype === "rtf")))
				{
					$submission[$username[0]]["docs"] = 1;
					//echo "$rootpath[1] : docs folder present\n";
				}
			}
		}
		
		// Move all files to another folder while changing root folder
		// to lowercase.
		foreach($dir as $name=>$value)
		{
			$rootpath = explode('/', $dir->getSubPath());
			$rootpath[0] = strtolower(trim($rootpath[0]));
			$npath = substr_replace($dir->key(), $rootpath[0], (strlen($this->dirRoot) + 1), strlen($rootpath[0]));
			// echo "$npath\n";
			$newName = realpath(dirname(__FILE__)) . "/re_" . $npath;
			$oldName = realpath(dirname(__FILE__)) . "/" . $value;
			$newDir = realpath(dirname(__FILE__)) . "/re_" . $this->dirRoot . "/" . implode("/", $rootpath);
		
            if(!file_exists($newDir))
			{
				echo "Making Dir: $newDir\n";
				mkdir($newDir);
			}
			copy($oldName,$newName);
			echo "Copied $oldName to $newName\n";
		}

		// Updating dirRoot to new location
		$this->dirRoot = "re_" . $this->dirRoot;
		// Assigning Marks
		foreach($submission as $student=>$marks)
		{
			if(array_key_exists($student, $students))
			{
				if(count($marks) === 4)
				{
					$students[$student]->setSubMark(1);
					$students[$student]->setSubComment("Overall Submission all ok.");
				}
				elseif(($marks["root"] === 0) && (count($marks) === 3))
				{
					$students[$student]->setSubMark(0.5);
					$students[$student]->setSubComment("Submission name incorrect, all folders present.");
				}
				elseif(($marks["root"] === 1) && (count($marks) <= 3))
				{
					$students[$student]->setSubMark(0.5);
					$students[$student]->setSubComment("Submission name correct, missing some folders.");
				}
			}			
		}
		return $students;
	 }
	 catch(Exception $error)
	 {
		 echo $error->getMessage();
	 }
	} // End Rename Directories
	
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
	// Uses external validator: vnu.jar
	// From https://validator.github.io/validator/
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
			$index = $this->dirRoot . "/" . $username . "_isy10209_ass1/index.html"; 
		}
		else
		{
			$index = $this->dirRoot . "/" . $username . "_isy10209_ass1/" . $cafile;
		}
		echo "$index \n";		
		if(array_key_exists($index, $StudentFiles["html"]) || 
		   array_key_exists($index, $StudentFiles["css"]))
		{		
			$pca = new ${"parseObj"}($this->dirRoot . "/" . $username . "_isy10209_ass1/");	
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
			$student->addRtotal($student->getSubMark());
			$htmlOut = new OutputResults($students[$username], "1");
			$smarks = new StudentMarksObj("1", $students[$username]->getStudentId());

			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA1", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(), $smarks->getComments(), "1");
			echo "Added CA1 marks and comments.\n";

			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA2", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(), $smarks->getComments(), "2");
			echo "Added CA2 marks and comments.\n";
			/*
			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA3", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(), $smarks->getComments(), "3");
			echo "Added CA3 marks and comments.\n";
			
			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA3a", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(), $smarks->getComments(), "3a");
			echo "Added CA3a marks and comments.\n";
		*/	
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
	// Prep directories and check for overall submission
	$students = $chAss->prepDirectories($students);
	

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