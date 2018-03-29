<?php

class StudentFileObj
{
	private $filename;
	private $path;
	private $username;
	private $type;
	private $size;
	private $height;
	private	$width;
	private $subfolder;

	public function __construct($username, $filename, $type, $subfolder)
	{
			
		$this->filename = $filename;
		$this->type = $type;
		if(strpos($username, "_"))
		{
			$this->username = substr($username, 0, (strpos($username, "_") - 1));
		}
		else
		{
			$this->username = $username;
		} 
		$this->subfolder = $subfolder;
	}

	public function setFilename($name)
	{
		$this->filename = $name;
	}

	public function getFilename()
	{
		return $this->filename;
	}

	public function setusername($username)
	{
		$this->username = $username;
	}

	public function getusername()
	{
		return $this->username;
	}

	public function setFileType($type)
	{
		$this->type = $type;
	}

	public function getFileType()
	{
		return $this->type;
	}

	public function setFilepath($filepath)
	{
		$this->path = $filepath;
	}

	public function getFilepath()
	{
		return $this->path;
	}

	public function setFileSize($size)
	{
		$this->size = $size;
	}

	public function getFileSize()
	{
		return $this->size;
	}
	
	public function setImageHeight($height)
	{
		$this->height = $height;
	}

	public function getImageHeight()
	{
		return $this->height;
	}
	
	public function setImageWidth($width)
	{
		$this->width = $width;
	}

	public function getImageWidth()
	{
		return $this->width;
	}
	
	public function setSubFolder($subfolder)
	{
		$this->subfolder = $subfolder;
	}

	public function getSubFolder()
	{
		return $this->subfolder;
	}


} // End StudentObj


?>