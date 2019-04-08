<?php

class StudentObj
{
	private $name;
	private $studentId;
	private $username;
	private $runningTotal;
	// Visual checks, all taken from csv file.
	private $ConceptMap;
	private $Storyboards;
	private $CodingStyle;
	private $Upload;
	private $subMark;
	private $subComment;

	public function __construct($name, $id, $username, $ConceptMap = 0, $Storyboards = 0, $CodingStyle = 0, $Upload = 0)
	{
		$this->name = $name;
		$this->studentId = $id;
		$this->username = $username;
		$this->ConceptMap = $ConceptMap;
		$this->Storyboards = $Storyboards;
		$this->CodingStyle = $CodingStyle;
		$this->Upload = $Upload;
	}

	public function setStudentname($name)
	{
		$this->name = $name;
	}

	public function getStudentname()
	{
		return $this->name;
	}

	public function setStudentId($studentid)
	{
		$this->studentId = $studentid;
	}

	public function getStudentId()
	{
		return $this->studentId;
	}
	
	public function getUsername()
	{
		return $this->username;
	}
	
	public function getRtotal()
	{
		return $this->runningTotal;
	}
	
	public function addRtotal($mark)
	{
		$this->runningTotal += $mark;
	}
	
	// Visual checks, all taken from csv file.
	public function getConceptMap()
	{
		return $this->ConceptMap;
	}
	
	public function getStoryboards()
	{
		return $this->Storyboards;
	}

	public function getCodingStyle()
	{
		return $this->CodingStyle;
	}
	
	public function getUpload()
	{
		return $this->Upload;
	}

	public function setSubMark($mark)
	{
		$this->subMark = $mark;
	}
	
	public function getSubMark()
	{
		return $this->subMark;
	}
	
	public function setSubComment($comment)
	{
		$this->subComment = $comment;
	}
	
	public function getSubComment()
	{
		return $this->subComment;
	}
} // End StudentObj

?>
