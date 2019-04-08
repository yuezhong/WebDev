<?php

/* CA3 Requirements
 * Image optimisation
 * Alt tags
 * Background images
 * Image links
 * Navbar
 * Image formatting
 * Link to webserver
 * Validation 
 * Each is worth 0.5
 */
 
class ParseCA3
{
	private $ca3Marks;
	private $ca3Comments;
	private $dirpath;
	
	public function __construct($dirpath)
	{
		$this->ca3Marks = 0;		
		$this->ca3Comments = "";
		$this->dirpath = $dirpath;
	}
	
	public function getMarks()
	{
	 return $this->ca3Marks;
	}

	public function getComments()
	{
	 return $this->ca3Comments;
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
	 $this->dirpath .= "/$username/public_html/";

	 echo "Dirpath, CA3 $this->dirpath\n";
	
	 $this->getExtLink($dom, $username);
	 $this->getAltTags($dom);
	 $this->getImageLinks($dom, $StudentFileObj);
	 $this->checkImageProp($dom, $StudentFileObj); 
	 $this->checkMime($dom, $StudentFileObj);
	 $this->validateFiles($username, $StudentFileObj);
	 $this->checkBgImages($student, $StudentFileObj);
	 $this->findNavbar($dom, $StudentFileObj);
		 
	} // End Start
	
	
	// Find external link to infotech server.
	public function getExtLink($dom, $studentUsername)
	{
	 $mark = array( "link_no_ext" =>0,
			"link_ext" => 0);
	 $links = $dom->getElementsByTagName('a');
	 $webserver = "http://172.19.96.239/~$studentUsername";

	 // External Link - to infotech server
	 foreach($links as $link)
	 {
		
	  if($link->hasAttribute('target'))
	  {
		if(($link->getAttribute('target') === '_blank') &&
		($link->getAttribute('href') === $webserver))
		{
			$mark["link_ext"]++;
		}
	  }
	  elseif($link->getAttribute('href') === $webserver)
	  {
		  $mark["link_no_ext"]++;
	  }
	 }
	  
	 if($mark["link_ext"] >= 1)
	 {
		$this->ca3Marks += 0.5;
		$this->ca3Comments .= "External link to Web server: 172.19.96.239 found: Good";
	 }
	 elseif($mark["link_no_ext"] >= 1)
	 {
		$this->ca3Marks += 0.25;
		$this->ca3Comments .= 'Missing target="_blank" option for link' . 
		 ": http://172.19.96.239/~$studentUsername";
	 }
	 elseif(($mark["link_no_ext"] === 0) && ($mark["link_ext"] === 0))
	 {
		$this->ca3Comments .= 'Missing External Link with target="_blank" option' . 
		 ": http://172.19.96.239/~$studentUsername";
	 }
	} // End extLink
	
	/* Find Alt Tags in images
	 * Assume that a meaningful description has > 3 chars.
	 */
	public function getAltTags($dom)
	{
	 $images = $dom->getElementsByTagName('img');
	 $altTag = 0;
	 
	 foreach($images as $image)
	 {

		 if(strlen($image->getAttribute('alt')) >= 3)
		 {
			$altTag++;
			// echo  "Alt: ". $image->getAttribute('alt'). "$altTag\n";
		 }
	 }
	 
	 if($altTag > 1)
	 {
		 $this->ca3Marks += 0.5;
		 $this->ca3Comments .= ";Alt tags used for images: Good";
	 }
	 else
	 {
		 $this->ca3Comments .= ";Need to use meaningful alt tags for your images.";
	 }
	} // End getAltTags
	
	// Check image link, should be using relative addressing.
	// Absolute addressing gets 0 marks.
	public function getImageLinks($dom, $StudentFileObj)
	{
		$images = $dom->getElementsByTagName('img');
		$link = array(
			"broken" => 0,
			"working" =>0);
		
		foreach ($images as $image)
		{
			$imageFile = $this->dirpath . str_replace('./', "", $image->getAttribute('src'));
			if(array_key_exists($imageFile, $StudentFileObj["images"]))
			{
				//echo $imageFile . ":Matches\n";
				$link['working']++;
			}
			else
			{
				//echo $imageFile . " : " . $pathToImage . "\n";
				$link['broken']++;
			}
		}		
		
		if(($link['broken'] > 0) && ($link['working'] === 0))
		{
			$this->ca3Comments .= ";No working images found. Check your paths, spelling and capital usage.";
		}
		elseif(($link['broken'] > 0 ) && ($link['working'] > 0))
		{
			$this->ca3Comments .= ";" . $link['broken'] . " broken images and " . $link['working'] . 
					" working images found. Check your paths, spelling and capital usage.";
			$this->ca3Marks += 0.25;
		}
		elseif(($link['broken'] === 0 ) && ($link['working'] > 0))
		{
			$this->ca3Marks += 0.5;
			$this->ca3Comments .= ";All images appear to be working.";
		}
	} // End getImageLinks
	
	// Check image properties, height, width and size
	// Image size should be less than 100kb for optimal web delivery.
	public function checkImageProp($dom, $StudentFileObj)
	{
		$imageCheck = array(
			"opheight" => 0,
			"opwidth" => 0,
			"opsize" => 0,
			"nopheight" => 0,
			"nopwidth" => 0,
			"nopsize" => 0
		);
		
		$images = $dom->getElementsByTagName('img');
		
		foreach($images as $image)
		{
			$imageFile = $this->dirpath . str_replace('./', "", $image->getAttribute('src'));
			echo "checking image: $imageFile\n";
			if(array_key_exists($imageFile, $StudentFileObj["images"]))
			{
				//echo $StudentFileObj["images"][$imageFile]->getImageWidth() . " : " . $StudentFileObj["images"][$imageFile]->getImageHeight() . "\n";
				//echo $image->getAttribute('width') . " : " . $image->getAttribute('height') ."\n";
				// Check Width
				if ($StudentFileObj["images"][$imageFile]->getImageWidth() == $image->getAttribute('width')) {
					$imageCheck['opwidth']++;
				}
				else
				{
					$imageCheck['nopwidth']++;
				}

				// Check height
				if ($StudentFileObj["images"][$imageFile]->getImageHeight() == $image->getAttribute('height')) {
					$imageCheck['opheight']++;
				}
				else
				{
					$imageCheck['nopheight']++;
				}
				// Max image file size is 100kb
				if ($StudentFileObj["images"][$imageFile]->getFileSize() > 102400) 
				{
					echo "$imageFile: Non optimal\n";
					$imageCheck["nopsize"]++;
				}
				else
				{
					echo "$imageFile: Optimal\n";
					$imageCheck["opsize"]++;
				}
			}
		}

		//print_r($imageCheck);

		if($imageCheck["nopwidth"] > $imageCheck["opwidth"])
		{
			//echo "Too many images with widths that doesn't match their image properties: " . $imageCheck["nopwidth"] . " : " . $imageCheck["opwidth"] . "\n";
			$this->ca3Comments .= ";Too many Image width attributes not matching their image properties.";
		}		
		else
		{
			$this->ca3Comments .= ";Image width attributes matches their image properties.";
		}

		if($imageCheck["nopheight"] > $imageCheck["opheight"])
		{
			//echo "Too many Image height attributes not matching their image properties\n";
			$this->ca3Comments .= ";Too many Image height attributes not matching their image properties.";
		}
		else
		{
			$this->ca3Comments .= ";Image height attributes matches their image properties.";
		}

		// Assuming that no more than 50% of images are > 100kb if thumbnails are used.
		// Max half of the images are thumbnails and other half are full images.

		$OpPercentage = ($imageCheck["opsize"] / ($imageCheck["nopsize"] + $imageCheck["opsize"])) * 100;

		if($OpPercentage >= 50)
		{
			$this->ca3Comments .= ";Majority of Images are < 100kb, good.";
		}
		else
		{
			$this->ca3Comments .= ";Too many images > 100kb, not fully optimized, bad.";
		}
		
		if(($imageCheck["nopheight"] === 0) && ($imageCheck["nopwidth"] === 0) &&
		   ($imageCheck["opwidth"] > 0) && ($imageCheck["opheight"] > 0) &&
		   ($OpPercentage >= 50))
		{
			//echo "Width and Height att are fine. File optimized, $OpPercentage \n";
			$this->ca3Marks += 0.5;
		}
		
	} // End checkImageProp
	
	// check that the mime type of images matches its extension.
	public function checkMime($dom, $StudentFileObj)
	{
		$badFormat =0;
		$images = $dom->getElementsByTagName('img');
		
		foreach($images as $image)
		{
			$imageFile = $this->dirpath . $image->getAttribute('src');
			if(array_key_exists($imageFile, $StudentFileObj["images"]))
			{
				$ext = $StudentFileObj["images"][$imageFile]->getFileType();
				$mime = $StudentFileObj["images"][$imageFile]->getMimeType();

				if($mime === "jpeg")
				{
					$mime = "jpg";
				}
				if($ext !== $mime)
				{
					$badFormat++;
				}
			}
		}
		
		if($badFormat > 1)
		{
			$this->ca3Comments .= ";Image File extension does not match its image format.";
		}
		else
		{
			$this->ca3Marks += 0.5;
			$this->ca3Comments .= ";No problems found with image format: Good.";
		}
	} // End checkMime
	
	// Check Background images in html
	public function checkBgImages($student, $StudentFileObj)
	{
		$username = $student->getusername();
		$working = 0;
		$broken = 0;
		$urls = array();
		$url_regex = '/(?<=url\().*?(?=\);)/';

		// Find urls in css
		foreach($StudentFileObj["css"] as $cssfile)
		{
			if($cssfile->getusername() === $username)
			{
			 $text = file_get_contents($cssfile->getFilepath());
			 preg_match_all($url_regex, $text, $matches);
			 $urls = preg_replace('/\s+\"/', '', $matches[0]);
			}
		}
		
		// Find urls in html
		foreach($StudentFileObj["html"] as $htmlfile)
		{
			if($htmlfile->getusername() === $username)
			{
			 $text = file_get_contents($htmlfile->getFilepath());
			 preg_match_all($url_regex, $text, $matches);
			}
		}
		// Merge them
		$urls = array_merge($urls, preg_replace('/\s+\"/', '', $matches[0]));
		// print_r($urls);
		
		// Check if links work.
		foreach($urls as $url)
		{
			$index = $this->dirpath . $url;
			if(array_key_exists($index, $StudentFileObj["images"]))
			{
				$working++;
			}
			else
			{
				$broken++;
			}
		}
		
		if(($working > 0) && ($broken === 0))
		{
			$this->ca3Marks += 0.5;
			$this->ca3Comments .= ";Background images used and all works: Good";
		}
		elseif(($working > 0) && ($broken > 0))
		{
			$this->ca3Marks += 0.25;
			$this->ca3Comments .= ";Background images used and some works, check your paths.";
		}
		else
		{
			$this->ca3Comments .= ";No working background images used.";
		}
	} // End checkBgImages

	
	// Find Navbar and make sure links works
	public function findNavbar($dom, $StudentFileObj)
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
		
		// Check for div with either Class or Id called nav, navbar, navigation, menu
		$divs = $dom->getElementsByTagName("div");
		foreach($divs as $div)
		{
			if((strtolower($div->getAttribute("class")) === "nav") ||
			   (strtolower($div->getAttribute("class")) === "navbar") ||
			   (strtolower($div->getAttribute("class")) === "navigation") ||
			   (strtolower($div->getAttribute("class")) === "menu") ||
			   (strtolower($div->getAttribute("id")) === "nav") ||
			   (strtolower($div->getAttribute("id")) === "navbar") ||
			   (strtolower($div->getAttribute("id")) === "navigation") ||
			   (strtolower($div->getAttribute("id")) === "menu"))
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
						//echo "Found $webpage link: " . $navlink->getAttribute('href') ."\n";
						$rlink = $this->dirpath . $navlink->getAttribute('href');
						
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
		
		if((count($webpages) > 0) && (count($broken) === 0) && $navbar_used > 0)
		{
			$this->ca3Marks += 0.5;
			$this->ca3Comments .= ";Navigation bar found and all links appears to work: Good.";
		}
		elseif((count($webpages) > 0) && (count($broken) > 0) && $navbar_used > 0)
		{
			$this->ca3Marks += 0.25;
			$this->ca3Comments .= ";Navigation bar found and some broken links, 
			check your path and spelling.";
		}
		elseif((count($webpages) > 0) && (count($broken) === 0) && $navbar_used === 0)
		{
			$this->ca3Marks += 0.25;
			$this->ca3Comments .= ";No Navigation bar found but all links appears to work. You need to setup a div for your navigation with a class or id called nav, or menu.";
		}
		elseif((count($webpages) > 0) && (count($broken) > 0) && $navbar_used === 0)
		{
			$this->ca3Marks += 0.25;
			$this->ca3Comments .= ";No Navigation bar found and some broken links, 
			check your path and spelling. You need to setup a div for your navigation with a class or id called nav, or menu.";
		}
		elseif((count($webpages) === 0) && (count($broken) > 0) && $navbar_used === 0)
		{
			$this->ca3Comments .= ";No Navigation bar found and no working links, 
			check your path and spelling.";
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
			$this->ca3Marks += 0.5;
			$this->ca3Comments .= ";HTML and CSS validates, no errors.";
		}
		elseif($non_validatedCss > 0 && $non_validatedHtml = 0)
		{
			$this->ca3Marks += 0.25;
			$this->ca3Comments .= ";HTML validates but CSS contain errors.";
		}		
		elseif($non_validatedCss = 0 && $non_validatedHtml > 0)
		{
			$this->ca3Marks += 0.25;
			$this->ca3Comments .= ";HTML does not validates but CSS validates.";
		}		
		else
		{
			$this->ca3Comments .= ";HTML and CSS files doesn't validate.";
		}

		// Clear errors after we're done. We don't need to store this info.
		libxml_clear_errors();
	} // End validateFiles
}
 
?>
