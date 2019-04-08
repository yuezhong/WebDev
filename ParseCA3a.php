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
	
	/* Start the checks
	 * 
	 *
	 */
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
	 $this->checkUploads($student, $StudentFileObj);
	 $this->checkDirs($username, $StudentFileObj);
	 $this->checkImgDir($username, $StudentFileObj);	 
	
	} // End start
	
	// Check internal links
	public function checkInternalLinks($dom, $username, $StudentFileObj)
	{
		$images = $dom->getElementsByTagName('img');
		$webserverPath = $this->dirpath . "/$username/public_html/";
		$link = array(
			"broken" => 0,
			"working" =>0);
		
		// Search through internal image links
		foreach ($images as $image)
		{
			if(substr($image->getAttribute('src'), 0, 1) != ".")
			{
				$imageFile = $webserverPath . $image->getAttribute('src');
			}
			else
			{
				$imageFile = $webserverPath . substr($image->getAttribute('src'), 2);
			}
			echo "image file: $imageFile\n";

			if(array_key_exists($imageFile, $StudentFileObj["images"]))
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
		//$infotech = "http://infotech.scu.edu.au/~" . $username;
		// Internal web server address
		$webserver = "http://172.19.96.239/~" . $username;		

		foreach($alinks as $alink)
		{	
			if($alink->hasAttribute('href'))
			{
				$href = trim($alink->getAttribute('href'));
				
				if(($href !== $webserver) && 
				(substr($href, 0, 4) !== $abslink) &&
				(strtolower(substr($href, 0, 7)) !== 'mailto:')
				)
				{
					if(substr($alink->getAttribute('href'), 0 ) == ".")
					{
					 $rlink = $webserverPath . substr($alink->getAttribute('href'), 2);
					}
					else
					{
					 $rlink = $webserverPath . $alink->getAttribute('href');
					}

					// echo $rlink . "\n";
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
	
	// Check if uppercase used for file extensions
	public function checkUploads($student, $StudentFileObj)
	{
		$filetype = array("html", "css", "images");
		$upperfound = 0;

		foreach($filetype as $type)
		{
			foreach($StudentFileObj[$type] as $fileobj)
			{
			  //echo $fileobj->getfilename() . "\n";
			  $ext = pathinfo($fileobj->getfilename(), PATHINFO_EXTENSION);
			  if(ctype_upper($ext))
			  {
			    $upperfound++;
			  }
			}
		}
		
		if($upperfound > 0)
		{
			$this->ca3aComments .= ";Uppercase found in file extensions, this will affect your Webpage on Linux based servers.";
		}
		else
		{
			$this->ca3aComments .= ";Correct cases used for file extensions.";
			$this->ca3aMarks += 1;
		}
	}

	/* If uploaded correctly, should be in public_html folder 
	 * Check if this is the case
	 * @param $username	string, username of student
	 * @param $StudentFileObj	array of objects, array of all file objects
	 */
	public function checkDirs($username, $StudentFileObj)
	{
		$outPub = 0;
		$inPub = 0;
		$inSub = 0;

		// echo "Looking for public_html\n";
		foreach($StudentFileObj["html"] as $file)
		{
		  // echo $file->getSubFolder() . "\n";
		  if($file->getusername() === $username)
		  { 
		   //echo strpos($file->getSubFolder(), "html") . " : ". (substr($file->getSubFolder(), 11)) ."\n";
		   if((strpos($file->getSubFolder(), "public_html") === 0) && (substr($file->getSubFolder(), 11) == ""))
		   {
			echo "In public_html and no other subfolder under\n";
			$inPub++;
		   }
		   elseif((strpos($file->getSubFolder(), "public_html") === 0) && (substr($file->getSubFolder(), 11, 1) == "/"))
		   {
			echo "In subfolder\n";
			$inSub++;
		   }
		   elseif(substr($file->getSubFolder(), 0) != "public_html")
		   {
			echo "Not in public_html\n";
			$outPub++;
		   }
		  }			   
		}
	
		echo "$inPub : $inSub : $outPub \n";

		if(($outPub === 0) && ($inSub === 0) && ($inPub > 0))
		{
		  $this->ca3aComments .= ";All files uploaded to proper directory on server.";
		  $this->ca3aMarks += 1;
		}
		elseif($inSub > 0)
		{
		  $this->ca3aComments .= ";Some html files are under a sub folder. Please see assignment brief for proper folder structure.";
		  $this->ca3aMarks += 0.5;
		}
		elseif($outPub > 0)
		{
		  $this->ca3aComments .= ";Not all files uploaded to proper directory on server. See assignment brief for proper folder structure.";
		}
	} // End checkDirs

	public function checkImgDir($username, $StudentFileObj)
	{
		$inFolder = 0;
		$rTotal = 0;
		// echo "Looking for Image folders...\n";
		foreach($StudentFileObj["images"] as $images)
		{
		 $rTotal++;
		 if(($images->getusername() === $username) &&
				(($images->getSubFolder() === "public_html/img") ||
				($images->getSubFolder() === "public_html/imgs") ||
				($images->getSubFolder() === "public_html/image") ||
				($images->getSubFolder() === "public_html/images")))
		 {
				$inFolder++;
		 }
		}
		
		if(($inFolder / $rTotal) === 1)
		{
		  $this->ca3aComments .= ";All images in their corresponding image folders.";
		  $this->ca3aMarks += 1;
		}
		else
		{
		  $this->ca3aComments .= ";Most images in their corresponding image folders.";
		  $this->ca3aMarks += 0.5;
		}

	}
}

?>
