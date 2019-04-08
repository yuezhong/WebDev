<?php
require_once "StudentObj.php";
require_once "StudentFileObj.php";
require_once "StudentMarksObj.php";
require_once "ParseCA1.php";
require_once "ParseCA2.php";
require_once "ParseCA3.php";
require_once "ParseCA3a.php";
require_once "OutputResults.php";

class checkAssignments
{
	private	$studentcsv = "students.csv";
	private $dirRoot;
	private $assignment;
	private $rDirectory;

	// default Constructor
	public function __construct($dirRoot, $assignment)
	{
		$this->dirRoot = $dirRoot;
		$this->assignment = $assignment;
	}

	
	// default getter
	public function getDirRoot()
	{
		return $this->dirRoot;
	}
	
	// Read Student CSV file
	public function readStudentCSV()
	{
		$file = fopen($this->studentcsv, "r") or exit("Unable to locate student csv file!");

		// CSV file colums:
		// Fullname,StudentID,Username,ConceptMap,Storyboards,CodingStyle
		while(($line = fgetcsv($file, 200, ",")) !== FALSE)
		{
			$student = new StudentObj($line[0], $line[1], $line[2], 
						$line[3], $line[4], $line[5]);
			$student_arr[$line[2]] = $student;
		}
		fclose($file);
		
		unset($file);
		unset($line);
		return $student_arr;
	} // End readStudentCSV
	
	
	/* recurse through the directories
	 * Web server path: /var/www/www.sieftp.com/studentid/public_html/
	 *
	 */
	public function recurseDir($filetypes, $username)
	{
	 try{
                $rDirectory = new RecursiveDirectoryIterator($this->dirRoot . "/" . $username,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);

		$dir = new RecursiveIteratorIterator($rDirectory);
		$FileObj = array();
		
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
			$filename = $filepath->getfileName();
			$filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			echo $filepath . "\n";
			
		//	echo $dir->getSubPath() ."\n";
			$subfolder = $dir->getSubPath();
			$index = $dir->key();
			$FileObj[$index] = new StudentFileObj($username, $filename, $filetype, $subfolder);
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
		// clean out memory before leaving
		unset($dir);
		unset($filepaths);
		return $FileObj;
	 }
	 catch(Exception $error)
	 {
		 echo $error->getMessage();
	 }
	} // End Recurse Directories
	
	/* Validate files
	 * Uses external validator: vnu.jar
	 * From https://validator.github.io/validator/
	 */
	public function validateFiles($StudentFiles)
	{
		echo "Validating CSS files:\n";
		foreach($StudentFiles["css"] as $cssfiles)
		{
			echo $cssfiles->getFilepath() . " : ";
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
			echo $cssfiles->getValidation() . "\n";
		}
		
		echo "Validating HTML files:\n";
		foreach($StudentFiles["html"] as $htmlfiles)
		{
			echo $htmlfiles->getFilepath() . " : ";
			// Calling external validator
			exec("java -Xss512k -jar ./vnu.jar --html --errors-only " . 
			$htmlfiles->getFilepath() . " 2>&1", $output, $validatedHtml);
			if($validatedHtml > 0)
			{
				// Any errors found, set validation to N
				$htmlfiles->setValidation("n");
			}
			else
			{
				// No errors, set validation to y
				$htmlfiles->setValidation("y");
			}

			echo $htmlfiles->getValidation() . "\n";
		}
		echo "Validation completed.\n";
	} // End validateFiles
	
	
	public function checkAssessment($username, $StudentFiles, $smarks, $ca, $student)
	{
		$parseObj = "Parse" . $ca;

		echo "$username : $ca\n";
		
		if(($ca === "CA3") || ($ca === "CA3a"))
		{
			foreach($StudentFiles["html"] as $file)
			{
			 if((strtolower($file->getFilename()) === "index.html") &&
			    ($file->getusername() === $username))
			 {
			 	$index = $file->getFilepath();
			 }
			} 
		}
		else
		{
			$index = $this->findCAfile($ca, $StudentFiles, $username);	
		}
		echo "Checking $index \n";		


		if(array_key_exists($index, $StudentFiles["html"]) || 
		   array_key_exists($index, $StudentFiles["css"]))
		{		
			$pca = new ${"parseObj"}($this->dirRoot);	
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
	
	// locate desired ca file
	public function findCAfile($cafile, $StudentFiles, $username)
	{
		foreach($StudentFiles["html"] as $files)
		{
			if(strpos($files->getFilename(), $cafile) && ($files->getusername() === $username))
			{
			$cafile = $files->getFilepath();
			echo "Found $cafile \n";
			}
		}	
		return $cafile;
	} //End findCAfile
	
	// Output to CSV all student totals
	public function printTotals($students)
	{
		$csvfile = fopen("student_totals.csv", 'w');
		
		foreach($students as $student)
		{
			fputcsv($csvfile, array($student->getStudentname(), $student->getStudentId(),
			$student->getusername(), $student->getRtotal()), ",");
		}
		
		fclose($csvfile);
	} // End printTotals
		
	// Start Assignment 1 checks
	public function startAssignment1($students, $StudentFiles)
	{

		foreach($students as $username=>$student)
		{
		 echo $username . "\n";

			echo "Checking Student: " . $username ."\n";
			
			$student->addRtotal($student->getSubMark());
			$htmlOut = new OutputResults($students[$username], "1");
			$smarks = new StudentMarksObj("1", $students[$username]->getStudentId());
			
			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA1", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(1), $smarks->getComments(), "1");
			echo "Added CA1 marks and comments.\n";

			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA2", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(2), $smarks->getComments(), "2");
			echo "Added CA2 marks and comments.\n";
			
			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA3", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks(3), $smarks->getComments(), "3");
			echo "Added CA3 marks and comments.\n";
	
			$smarks = $this->checkAssessment($username, $StudentFiles, $smarks, "CA3a", $student);
			$htmlOut->buildHTML($smarks->getMarks(), $smarks->getMaxMarks("3a"), $smarks->getComments(), "3a");
			echo "Added CA3a marks and comments.\n";

			// give submission mark as freebie
			$student->addRtotal(1);
			$htmlOut->closeHTML($student->getRtotal());
			echo "Feedback file generated for $username.\n";

		}


	} // End startAssignment1
	
}
?>
