<?php	

class ParseCA3a
{
	private $ca3aMarks;
	private $ca3aComments;
	private $dirpath;
	
	public function __construct($dirpath)
	{
		$this->ca3aMarks = 0;		
		$this->ca3aComments = "";
		$this->dirpath = $dirpath;
	}
	
	public function getMarks()
	{
	 return $this->ca3aMarks;
	}

	public function getComments()
	{
	 return $this->ca3aComments;
	}
	
	// Start
	public function start($file, $student, $StudentFileObj)
	{
	 //Load the HTML page
	 $html = file_get_contents($file);

	 //Create a new DOM document
	 $dom = new DOMDocument;

	 //Parse the HTML. The @ is used to suppress any parsing errors
	 @$dom->loadHTML($html);
	 
	 $username = $student->getusername();
	 $this->checkInternalLinks($dom, $username, $StudentFileObj);
	 
	} // End start
	
	// Check internal links
	public function checkInternalLinks($dom, $username, $StudentFileObj)
	{
		$images = $dom->getElementsByTagName('img');
		$link = array(
			"broken" => 0,
			"working" =>0);
		
		// Search through internal image links
		foreach ($images as $image)
		{
			$imageFile = $this->dirpath . $image->getAttribute('src');
			if($StudentFileObj["images"][$imageFile] !== '')
			{
				$link['working']++;
			}
			else
			{
				$link['broken']++;
			}
		}
		
		// Search through internal relative links.
		$alinks = $dom->getElementsByTagName('a');
		$abslink = "http";
		$infotech = "http://infotech.scu.edu.au/~" . $username;
		
		foreach($alinks as $alink)
		{	
			if($alink->hasAttribute('href'))
			{
				$href = trim($alink->getAttribute('href'));
				
				if(($href !== $infotech) && 
				(substr($href, 0, 4) !== $abslink) &&
				(strtolower(substr($href, 0, 7)) !== 'mailto:')
				)
				{
					$rlink = $this->dirpath . $alink->getAttribute('href');
					
					//echo $rlink . "\n";
					if(array_key_exists($rlink, $StudentFileObj["html"]) ||
					   array_key_exists($rlink, $StudentFileObj["css"]) ||
					   array_key_exists($rlink, $StudentFileObj["images"]))
					{
						$link['working']++;   
					}
					else
					{
						$link['broken']++;
					}
				}
			}
		}
		
		
		if(($link['broken'] > 0) && ($link['working'] === 0))
		{
			$this->ca3aComments .= ";No working links found. Check your paths, spelling and capital usage.";
		}
		elseif(($link['broken'] > 0 ) && ($link['working'] > 0))
		{
			$this->ca3aComments .= ";" . $link['broken'] . " broken links and " . $link['working'] . 
					" working links found. Check your paths, spelling and capital usage.";
			$this->ca3aMarks += 0.5;
		}
		elseif(($link['broken'] === 0 ) && ($link['working'] > 0))
		{
			$this->ca3aMarks += 1;
			$this->ca3aComments .= ";All internal links appear to be working.";
		}
		

	} // End checkInternalLinks
	
}



/*
	if($student->getUpload() < 4)
		{
			$this->ca1Comments .= ";Check your uploads and links. 
			Make sure your file name cases match your links. Ensure images and css files are in their own folders.";
		}
		else
		{
			$this->ca1Comments .= ";Uploaded and working: Good";
		}
		
	*/	
?>