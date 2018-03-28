<?php

class StudentObj
{
	private $name;
	private $studentId;
	private $username;
	private $runningTotal;

	public function __construct($name, $id, $username)
	{
			$this->name = $name;
			$this->studentId = $id;
			$this->username = $username;
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

} // End StudentObj

?>