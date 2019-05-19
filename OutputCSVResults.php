<?php
require_once ('CSVFileGenerator.php');
// Format results and comments into CSV

class OutputCSVResults
{
	private $csvOutput;
	private $maxTotal;
	private $tabstr;
	
	public function __construct($student, $assignment)
	{
		$studentName = $student->getStudentname();
		$subMarks = $student->getSubMark();
		$subComments = $student->getSubComment();
		$this->tabstr = chr(9);
		switch($assignment)
		{
			case 1:
			case 2:
			$this->maxTotal = 15;
			break;
			case 3:
			$this->maxTotal = 30;
			break;
		}		 
		
		$this->csvOutput = new csvFileGenerator($studentName, $student->getStudentId());
		
		$this->csvOutput->append("$studentName's Web Development Assignment $assignment Feedback" . $this->tabstr);
		$this->csvOutput->append("Student: " . $studentName . $this->tabstr);
		$this->csvOutput->append($student->getStudentId() .$this->tabstr. "ISY10209 - Assignment $assignment" . $this->tabstr);
		$this->csvOutput->append("Criteria" . $this->tabstr  . "Max" . $this->tabstr . "Mark" . $this->tabstr . "Comments" . $this->tabstr);
		$this->csvOutput->append("Overall Submission" . 
										$this->tabstr . "1.00" . $this->tabstr . $subMarks . $this->tabstr . $subComments . $this->tabstr);
	}

	public function buildcsv($marks, $maxmarks, $comments, $CA)
	{
		$commentArr = explode(";", $comments);
		$commentRows = count($commentArr);
		$this->csvOutput->append("Assessment" . $CA . $this->tabstr . $maxmarks . $this->tabstr . $marks . $this->tabstr);

		$this->csvOutput->append($this->tabstr);
		$comment = str_replace(";", PHP_EOL . $this->tabstr . $this->tabstr . $this->tabstr, $comments);
		$this->csvOutput->append($comment);
		$this->csvOutput->append($this->tabstr);	
		
		if(($commentRows !== 12) && ($CA !== '3a'))
		{
			for($i = 0; $i < (12 - $commentRows); $i++)
			{
				$this->csvOutput->append($this->tabstr);
			}
		}
		elseif($CA === '3a')
		{
			for($i = 0; $i < 2; $i++)
			{
				$this->csvOutput->append($this->tabstr);
			}
		}
	}
	
	public function closecsv($total)
	{
		$this->csvOutput->append($this->tabstr . $this->maxTotal . $this->tabstr . $total . $this->tabstr);
	}
	
}


?>
