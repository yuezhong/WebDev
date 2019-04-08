<?php
require_once ('HTMLFileGenerator.php');
// Format results and comments into HTML

class OutputResults
{
	private $htmlOutput;
	private $maxTotal;
	
	public function __construct($student, $assignment)
	{
		$studentName = $student->getStudentname();
		$subMarks = $student->getSubMark();
		$subComments = $student->getSubComment();
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
		
		$this->htmlOutput = new HTMLFileGenerator($studentName, $student->getStudentId());
		
		$this->htmlOutput->append('<!Doctype html>');
		$this->htmlOutput->append('<HTML lang="en">');
		$this->htmlOutput->append('<HEAD>');
		$this->htmlOutput->append("<TITLE>$studentName's Web Development Assignment $assignment Feedback</TITLE>");
		$this->htmlOutput->append('<META CHARSET="utf-8">');
		$this->htmlOutput->append('<STYLE>
		body {
			font-family: Verdana, Arial, Helvetica, sans-serif;
			color: #4f6b72;
			background: #E6EAE9;}
		
		#container {
			margin-left:	auto;
			margin-right:	auto;
			width:	1280px;
		}

		table {
			border:	2px;
			padding: 0;
			margin: 0;
			width:	1024px;}
			
		th {
			font-family: Verdana, Arial, Helvetica, sans-serif;
			padding: 5px 5px 5px 10px;
			background-color: #CAE8EA;}
			
		tr:nth-of-type(even) {
			background: #F5FAFA;
			color: #797268;}
			
		.headings {
			font-weight:	bold;
			background-color: 	#CAE8EA;
		}
		');
		
		$this->htmlOutput->append('</STYLE>');
		$this->htmlOutput->append('</HEAD>');
		$this->htmlOutput->append('<BODY><DIV id="container">');
		$this->htmlOutput->append('<TABLE><THEAD>');
		$this->htmlOutput->append("<TR><TH>Student:</TH><TH>" . $studentName . "</TH>");
		$this->htmlOutput->append("<TH>". $student->getStudentId() ."</TH>". "<TH>ISY10209 - Assignment $assignment </TH></TR>");
		$this->htmlOutput->append("<TR><TH>Criteria</TH><TH>Max</TH><TH>Mark</TH><TH>Comments</TH></TR></THEAD><TBODY>");
		$this->htmlOutput->append("<TR><TD>Overall Submission</TD><TD>1.00</TD><TD>
		$subMarks</TD><TD>$subComments</TD></TR>");
	}

	public function buildHTML($marks, $maxmarks, $comments, $CA)
	{
		$commentArr = explode(";", $comments);
		$commentRows = count($commentArr);
		$this->htmlOutput->append('<TR CLASS="headings">');
		$this->htmlOutput->append("<TD>Assessment $CA</TD><TD>$maxmarks</TD><TD>$marks</TD><TD></TD></TR>");

		$this->htmlOutput->append('<TR><TD colspan="3"></TD><TD>');
		$comment = str_replace(";", '</TD></TR><TR><TD colspan="3"></TD><TD>', $comments);
		$this->htmlOutput->append($comment);
		$this->htmlOutput->append("</TD></TR>");	
		
		if(($commentRows !== 12) && ($CA !== '3a'))
		{
			for($i = 0; $i < (12 - $commentRows); $i++)
			{
				$this->htmlOutput->append('<TR><TD colspan="4">&nbsp</TD></TR>');
			}
		}
		elseif($CA === '3a')
		{
			for($i = 0; $i < 2; $i++)
			{
				$this->htmlOutput->append('<TR><TD colspan="4">&nbsp</TD></TR>');
			}
		}
	}
	
	public function closeHTML($total)
	{
		$this->htmlOutput->append('<TR CLASS="headings">');
		$this->htmlOutput->append("<TD>Total</TD><TD>$this->maxTotal</TD><TD>$total</TD><TD></TD></TR>");
		$this->htmlOutput->append("</TBODY></TABLE>");
		$this->htmlOutput->append("</DIV></BODY>");
		$this->htmlOutput->append("</HTML>");
	}
	
}


?>
