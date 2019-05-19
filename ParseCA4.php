<?php

/* CA4 Requirements
 * Image optimisation
 * Image Dimensions
 * Images in separate folder
 * Logo
 * contact details and copyright notice
 * Navbar on all 3 pages
 * Validation 
 * Each is worth 0.25
 * Visual inspected: 2 cols, resizing, coding style, formatting and padding
 */
 
class ParseCA4
{
	private $ca4Marks;
	private $ca4Comments;
	private $dirpath;
	
	public function __construct($dirpath)
	{
		$this->ca4Marks = 0;		
		$this->ca4Comments = "";
		$this->dirpath = $dirpath;
	}
	
	public function getMarks()
	{
	 return $this->ca4Marks;
	}

	public function getComments()
	{
	 return $this->ca4Comments;
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
	 
	 // Build link to students public_html folder on web server.
	 $username = $student->getusername();
	 $this->dirpath = $this->dirpath . "/$username/public_html";
	 
	 echo "Dirpath, ca4 $this->dirpath\n";
	
	 $this->checkImageProp($dom, $StudentFileObj, $username);
	 $this->findCopySymbol($html);
	 $this->findNavbar($username, $StudentFileObj);
	 $this->checkImgDir($username, $StudentFileObj);
	 $this->validateFiles($username, $StudentFileObj);
		 
	} // End Start
	
	// Convert relative path to Absolute filepath to locate file
	public function findAbspath($image)
	{
			$imageFile = "";
			if(is_string($image))
			{
				$link = $image;
			}
			else
			{
				$link = $image->getAttribute('src');
			}
			
			if(substr($link, 0, 2) === "..")
			{
					$levels = substr_count($link, "../");
					$relpath = str_replace('../', "", $link, $levels);
					$imageFile = dirname($this->dirpath, $levels) . "/" . $relpath;
			}
			elseif(substr($link, 0, 2) === "./")
			{
					$imageFile = $this->dirpath . str_replace('./', "/", $link);
			}
			elseif(substr($link, 0, 1) === "/")
			{
					$imageFile = $this->dirpath . $link;
			}
			else
			{
					$imageFile = $this->dirpath . "/" . $link;
			}

			return $imageFile;
	}
	
	
	// Find copyright symbol
	public function findCopySymbol($html)
	{
		preg_match_all("/\&copy\;/", $html, $matches);
		// print_r($matches);

		$copyright = count($matches[0]);
		
		if($copyright > 0)
		{
			$this->ca4Comments .= ";Copyright symbol found";
			$this->ca4Marks += 0.25;
		}
		else
		{
			$this->ca4Comments .= ";HTML copyright symbol not found. You need to use the html code.";
		}
		
	} // End findCopySymbol
	
	
	// Check image properties, height, width and size
	// Image size should be less than 100kb for optimal web delivery.
	public function checkImageProp($dom, $StudentFileObj, $username)
	{
		$imageCheck = array(
			"opheight" => 0,
			"opwidth" => 0,
			"opsize" => 0,
			"nopheight" => 0,
			"nopwidth" => 0,
			"nopsize" => 0
		);
		
		// Get total of images in image folder and check their sizes.
		foreach($StudentFileObj["images"] as $imgfile)
		{
			if($imgfile->getusername() === $username)
			{
				// Max image file size is 100kb
				if ($imgfile->getFileSize() > 102400) 
				{
					echo $imgfile->getFilename() . ": Non optimal\n";
					$imageCheck["nopsize"]++;
				}
				else
				{
					echo $imgfile->getFilename() . ": Optimal\n";
					$imageCheck["opsize"]++;
				} 
			}
		}
		
		$images = $dom->getElementsByTagName('img');
		
		// Check height and width properties
		foreach($images as $image)
		{
			$imageFile = $this->findAbspath($image);
			
			echo "checking image properties: $imageFile\n";
			if(array_key_exists($imageFile, $StudentFileObj["images"]))
			{
                echo "Real properties: " . $StudentFileObj["images"][$imageFile]->getImageWidth() . " : "
                        . $StudentFileObj["images"][$imageFile]->getImageHeight() . "\n";
                echo "Tag Properties: " . $image->getAttribute('width') . " : " . $image->getAttribute('height') ."\n";
				// Check Width
				if ($StudentFileObj["images"][$imageFile]->getImageWidth() == $image->getAttribute('width')
					&& $image->hasAttribute('width')) {
					$imageCheck['opwidth']++;
				}
				else
				{
					$imageCheck['nopwidth']++;
				}

				// Check height
				if ($StudentFileObj["images"][$imageFile]->getImageHeight() == $image->getAttribute('height')
					&& $image->getAttribute('height')) {
					$imageCheck['opheight']++;
				}
				else
				{
					$imageCheck['nopheight']++;
				}
			}
		}

		print_r($imageCheck);

		if(($imageCheck["opwidth"] > 0) && ($imageCheck["nopwidth"] == 0))
		{
			$this->ca4Comments .= ";Image width attributes matches their image properties.";
		}		
		else
		{
			$this->ca4Comments .= ";Too many Image width attributes not matching their image properties.";
		}

		if(($imageCheck["opheight"] > 0) && ($imageCheck["nopheight"] == 0))
		{
			$this->ca4Comments .= " Image height attributes matches their image properties.";
		}
		else
		{
			$this->ca4Comments .= " Too many Image height attributes not matching their image properties.";
		}

		if((($imageCheck["opwidth"] > 0) && ($imageCheck["nopwidth"] == 0)) &&
			(($imageCheck["opheight"] > 0) && ($imageCheck["nopheight"] == 0)))
			{
				$this->ca4Marks += 0.25;
			}
						
		// Assuming that no more than 50% of images are > 100kb if thumbnails are used.
		// Max half of the images are thumbnails and other half are full images.

		echo "Non optimal total: " . $imageCheck['nopsize'] . ": Optimal Total: " . $imageCheck['opsize'] . "\n";
		$OpPercentage = ($imageCheck["opsize"] / ($imageCheck["nopsize"] + $imageCheck["opsize"])) * 100;
		
		if($OpPercentage >= 50)
		{
			$this->ca4Comments .= ";Majority of Images are < 100kb, good.";
			$this->ca4Marks += 0.25;
		}
		else
		{
			$this->ca4Comments .= ";Too many images > 100kb, not fully optimized, bad. One thumbnail image should be used for each big image you want to include.";
		}
		
	} // End checkImageProp
	
	
	// Find Navbar and make sure links works
	public function findNavbar($username, $StudentFileObj)
	{
		$navbar_used = 0;
		$webpages = array("index" => 0,
					   "resume" => 0,
					   "webskill" => 0,
					   "web_skill" => 0
		);
		$broken = array("index" => 0,
					   "resume" => 0,
					   "webskill" => 0,
					   "web_skill" => 0
		);
		
		// Loop through all html files belonging to to Student
		foreach($StudentFileObj["html"] as $file)
		{
			if((strpos(strtoupper($file->getFilename()), "CA4") != FALSE) &&
			($file->getusername() === $username))
			{			
				echo "\nChecking navbar :" . $file->getFilepath() . "\n";
				//Load the HTML page
				$html = file_get_contents($file->getFilepath());
				
				//Create a new DOM document
				$dom = new DOMDocument;

				//Parse the HTML. The @ is used to suppress any parsing errors
				@$dom->loadHTML($html);
			 
				// Check for div with either Class or Id has nav or menu in name
				$divs = $dom->getElementsByTagName("div");
				foreach($divs as $div)
				{
					$divClass = strtolower($div->getAttribute("class"));
					$divID = strtolower($div->getAttribute("id"));
					
					if(	strpos($divClass, "nav") || strpos($divID, "nav") ||
						strpos($divClass, "menu") || strpos($divID, "menu"))
					   {
						   $navbar_used++;
					   }  
				}
			
				// Find Nav Element
				$navtags = $dom->getElementsByTagName("nav");
				foreach($navtags as $navtag)
				{
					if($navtag->nodeValue !== '')
					{
						$navbar_used++;
					}
				}
			
				// Search all links and see if they point to: index, resume and webskills
				// Also check if the file exists.
				$navlinks = $dom->getElementsByTagName("a");
				foreach($webpages as $webpage=>$total)
				{
					foreach($navlinks as $navlink)
					{
						if($navlink->hasAttribute('href'))
						{
							$href = trim($navlink->getAttribute('href'));
							$spattern = "/$webpage/";
							preg_match_all($spattern, $href, $matches);
							//print_r($matches);
							if(array_key_exists(0, $matches[0]))
							{
								if(strpos($navlink->getAttribute('href'), "http"))
								{
									$link = substr($navlink->getAttribute('href'), strlen("http://172.19.96.239/~" . $username . "/"));
									$rlink = $this->findAbspath($link);
								}
								else
								{
									$rlink = $this->findAbspath($navlink->getAttribute('href'));
								}
								
								echo "Found $webpage link: " . $navlink->getAttribute('href') ."\n";
								echo "Absolute path: $rlink \n";
								
								if(array_key_exists($rlink, $StudentFileObj["html"]))
								{
									$webpages[$webpage]++;   
								}
								else
								{
									$broken[$webpage]++;
								}
							}
						}
					}
				}
		// print_r($webpages, $broken);
			}
		}
		
		if(($webpages["web_skill"] > 0 || $webpages["webskill"] > 0) &&
			$webpages["index"] > 0 && $webpages["resume"] > 0  && 
			(array_sum($broken) === 0) && $navbar_used > 0)
		{
			$this->ca4Marks += 0.25;
			$this->ca4Comments .= ";Navigation bar found on pages for index, resume and web_skills. 
					Links to these pages are working. Good.";
		}
		elseif(($webpages["web_skill"] > 0 || $webpages["webskill"] > 0) &&
			$webpages["index"] > 0 && $webpages["resume"] > 0  && 
			(array_sum($broken) > 0) && $navbar_used > 0)
		{
			$this->ca4Comments .= ";Navigation bar found on pages for index, resume and web_skills, 
			but some does not link to your index, resume and web_skills page.";
		}
		elseif(($webpages["web_skill"] > 0 || $webpages["webskill"] > 0) &&
			$webpages["index"] > 0 && $webpages["resume"] > 0  && 
			(array_sum($broken) === 0) && $navbar_used === 0)
		{
			$this->ca4Comments .= ";No Navigation bar found using div tag, or using nav tag 
			but all links to index, resume and web_skills pages appears to work.";
			$this->ca4Marks += 0.25;
		}
		elseif(($webpages["web_skill"] > 0 || $webpages["webskill"] > 0) &&
			$webpages["index"] > 0 && $webpages["resume"] > 0  && 
			(array_sum($broken) > 0) && $navbar_used === 0)
		{
			$this->ca4Comments .= ";No Navigation bar found using div tag, or using nav tag 
			and some does not link to your index, resume and web_skills page.";
		}
		elseif(($webpages["web_skill"] === 0 || $webpages["webskill"] === 0) &&
			$webpages["index"] === 0 && $webpages["resume"] === 0  && 
			(array_sum($broken) > 0) && $navbar_used === 0)
		{
			$this->ca4Comments .= ";No Navigation bar found using div tag, or using nav tag 
			and no index, resume or web_skills pages found.";
		}
	
	} // End findNavbar
	
	// Validate HTML
	public function validateFiles($username, $StudentFiles)
	{
		$non_validatedCss = 0;
		$non_validatedHtml = 0;
		
		// Turn of errors to the screen
		libxml_use_internal_errors(true);

		// Loop through all css files
		foreach($StudentFiles["css"] as $sfcss)
		{
			if(($sfcss->getusername() === $username))
			{
				$filepath = $sfcss->getFilepath();
				if($StudentFiles["css"][$filepath]->getValidation() === "n")
				{
					$non_validatedCss++;
				}
			}
		}
		
		// Loop through all html files
		foreach($StudentFiles["html"] as $sfhtml)
		{			
			if(($sfhtml->getusername() === $username))
			{
				if($sfhtml->getValidation() !== "y")
				{
					$non_validatedHtml++;
				}
			}
		}		

		if($non_validatedCss = 0 && $non_validatedHtml = 0)
		{
			$this->ca4Marks += 0.25;
			$this->ca4Comments .= ";HTML and CSS validates, no errors.";
		}
		elseif($non_validatedCss > 0 && $non_validatedHtml = 0)
		{
			$this->ca4Comments .= ";HTML validates but CSS contain errors.";
		}		
		elseif($non_validatedCss = 0 && $non_validatedHtml > 0)
		{
			$this->ca4Comments .= ";HTML does not validates but CSS validates.";
		}		
		else
		{
			$this->ca4Comments .= ";HTML and CSS files doesn't validate.";
		}

		// Clear errors after we're done. We don't need to store this info.
		libxml_clear_errors();
	} // End validateFiles
	
	// Images in separate folder
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
		  $this->ca4Comments .= ";All images in their corresponding image folders.";
		  $this->ca4Marks += 0.25;
		}
		else
		{
		  $this->ca4Comments .= ";All images should be in an image folder.";
		}

	} // End Check image folder
}
 
?>
