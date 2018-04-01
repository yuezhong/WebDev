<?php

/* CA3 Requirements
 * Image optimisation
 * Alt tags
 * Background images
 * Image links
 * Navbar
 * Image formatting
 * Image naming
 * Link to infotech
 * Each is worth 0.4
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

	public function start($file, $studentUsername, $StudentFileObj)
	{
	 //Load the HTML page
	 $html = file_get_contents($file);

	 //Create a new DOM document
	 $dom = new DOMDocument;

	 //Parse the HTML. The @ is used to suppress any parsing errors
	 @$dom->loadHTML($html);
	 
	 $this->getExtLink($dom, $studentUsername);
	 $this->getAltTags($dom);
	 $this->getImageLinks($dom, $StudentFileObj);
	 $this->checkImageProp($dom, $StudentFileObj);
	 $this->checkMime($dom, $StudentFileObj);
	 $this->checkBgImages($studentUsername, $StudentFileObj)
	 	 
	}
	
	
	// Find external link to infotech server.
	public function getExtLink($dom, $studentUsername)
	{
	 $mark = array( "link_no_ext" =>0,
					"link_ext" => 0);
	 $links = $dom->getElementsByTagName('a');

	 // External Link - to infotech server
	 foreach($links as $link)
	 {
		
	  if($link->hasAttribute('target'))
	  {
		if(($link->getAttribute('target') === '_blank') &&
		($link->getAttribute('href') === "http://infotech.scu.edu.au/~" . $studentUsername))
		{
			$mark["link_ext"]++;
		}
	  }
	  elseif($link->getAttribute('href') === "http://infotech.scu.edu.au/~" . $studentUsername)
	  {
		  $mark["link_no_ext"]++;
	  }
	 }
	  
	 if($mark["link_ext"] >= 1)
	 {
		$this->ca3Marks += 0.4;
		$this->ca3Comments .= "External link to infotech server found: Good";
	 }
	 elseif($mark["link_no_ext"] >= 1)
	 {
		$this->ca3Marks += 0.2;
		$this->ca3Comments .= 'Missing target="_blank" option for link' . 
		 ": http://infotech.scu.edu.au/~$studentUsername";
	 }
	 elseif(($mark["link_no_ext"] === 0) && ($mark["link_ext"] === 0))
	 {
		$this->ca3Comments .= 'Missing External Link with target="_blank" option' . 
		 ": http://infotech.scu.edu.au/~$studentUsername";
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
		 $this->ca3Marks += 0.4;
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
			$imageFile = $this->dirpath . $image->getAttribute('src');
			$pathToImage = $StudentFileObj["images"][$imageFile]->getFilepath();
			if($pathToImage === $imageFile)
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
			$this->ca3Marks += 0.2;
		}
		elseif(($link['broken'] === 0 ) && ($link['working'] > 0))
		{
			$this->ca3Marks += 0.4;
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
			$imageFile = $this->dirpath . $image->getAttribute('src');
			$pathToImage = $StudentFileObj["images"][$imageFile]->getFilepath();
			
			// Check Width
			if ($StudentFileObj["images"][$pathToImage]->getImageWidth() !== $image->getAttribute('width')) {
				$imageCheck['nopwidth']++;
			}
			else
			{
				$imageCheck['width']++;
			}
			
			// Check height
			if ($StudentFileObj["images"][$pathToImage]->getImageHeight() !== $image->getAttribute('height')) {
				$imageCheck['nopheight']++;
			}
			else
			{
				$imageCheck['height']++;
			}
			
			// Max image file size is 100kb
			if ($StudentFileObj["images"][$pathToImage]->getFileSize() > 102400) 
			{
				$imageCheck["nopsize"]++;
			}
			else
			{
				$imageCheck["opsize"]++;
			}
		}
		
		// Assuming that no more than 50% of images are > 100kb if thumbnails are used.
		// Max half of the images are thumbnails and other half are full images.
		$OpPercentage = ($imageCheck["nopsize"] / ($imageCheck["opsize"] + $imageCheck["nopsize"])) * 100;
		
		if((($imageCheck["nopwidth"] > 1) || ($imageCheck["nopheight"] > 1)) &&
			(($imageCheck["opwidth"] === 0 ) && ($imageCheck["opheight"] === 0)) &&
			($OpPercentage > 50))
		{
			$this->ca3Comments .= ";Height and width values do not match height 
			and width properties of images. Too many image file size is greater than 100kb.";
		}
		elseif((($imageCheck["nopwidth"] > 1) || ($imageCheck["nopheight"] > 1)) &&
			(($imageCheck["opwidth"] > 1) && ($imageCheck["opheight"] > 1)) &&
			(($OpPercentage > 50)))
		{
			$this->ca3Marks += 0.2;
			$this->ca3Comments .= ";Some height or width values do not match height and 
			width properties of images. Too many image file size is greater than 100kb.";
		}
		elseif((($imageCheck["nopwidth"] === 0) && ($imageCheck["nopheight"] === 0)) &&
			(($imageCheck["opwidth"] > 1) && ($imageCheck["opheight"] > 1)) &&
			($OpPercentage <= 50))
		{
			$this->ca3Marks += 0.2;
			$this->ca3Comments .= ";Image height and width values matches image properties, however
			too many image file size is greater than 100kb.";
		}
		elseif((($imageCheck["nopwidth"] === 0) && ($imageCheck["nopheight"] === 0)) &&
			(($imageCheck["opwidth"] > 1) && ($imageCheck["opheight"] > 1)) &&
			($OpPercentage <= 50))
		{
			$this->ca3Marks += 0.4;
			$this->ca3Comments .= ";Image height and width values matches image properties,
			the majority of image file sizes are less than 100kb: Good";
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
		
		if($badFormat > 1)
		{
			$this->ca3Comments .= ";Image File extension does not match its image format.";
		}
		else
		{
			$this->ca3Marks += 0.4;
			$this->ca3Comments .= ";No problems found with image format: Good.";
		}
	} // End checkMime
	
	// Check Background images in html
	public function checkBgImages($studentUsername, $StudentFileObj)
	{
		$urls = array();
		$url_regex = '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i';

		foreach($StudentFileObj["css"] as $cssfile)
		{
			if($cssfile->getusername() === $studentUsername)
			{
			 $text = file_get_contents($cssfile->getFilepath());
			 preg_match_all($url_regex, $text, $urls);
			}
		}
		
		foreach($StudentFileObj["html"] as $htmlfile)
		{
			if($htmlfile->getusername() === $studentUsername)
			{
			 $text = file_get_contents($htmlfile->getFilepath());
			 preg_match_all($url_regex, $text, $urls);
			}
		}
	print_r($urls);

	} // End checkBgImages

	
	// Find Navbar and make sure links works
	public function findNavbar($dom, $StudentFileObj)
	{
		
	}
	
}
 
?>