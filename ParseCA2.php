<?php
/* CA2 Requirements:
 * - <p> alignment
 * - css levels, inline, embed, external
 * - css class
 * - css ID
 * - css attribute, min 2 text colour, min 2 background colour
 * - check font family
 * - check for bold, italic, strong etc.
 * - one div, one span
 * - ol to roman numerals
 * - ul to squares
 */
 
class ParseCA2
{
	private $ca2Marks;
	private $ca2Comments;
	
	public function __construct()
	{
		$this->ca2Marks = 0;
		$this->ca2Comments = "";
	}
	
	public function getMarks()
	{
	 return $this->ca2Marks;
	}

	public function getComments()
	{
	 return $this->ca2Comments;
	}

	// Start
	public function start($file, $student, $StudentFiles)
	{
	 //Load the HTML page
	 $html = file_get_contents($file);

	 //Create a new DOM document
	 $dom = new DOMDocument;

	 //Parse the HTML. The @ is used to suppress any parsing errors
	 @$dom->loadHTML($html);

	 $username = $student->getusername();
	 
	 $this->getPalignment($dom);
	 $this->validateFiles($dom, $file, $username, $StudentFiles);
	 $this->getDiv($dom);
	 $this->getSpan($dom);
	 $this->getCssStyle($dom);
	 $this->checkBulletsUL($dom);
	 $this->checkBulletsOL($dom);
	 $this->checkFontStyles($dom);
	 $this->searchForAttribute($dom, "class");
	 $this->searchForAttribute($dom, "id");
	 $this->checkCssAttrib($file, $username, $StudentFiles);

	} // End start
	
	
	// Get Paragraph Alignment.
	// Only get full marks (0.3) if all are present
	public function getPalignment($dom)
	{
		$paragraphs = $dom->getElementsByTagName('p');
		$mark = 0;
		// Alignment types, ignoring left, as that's default
		$alignment_type = array(
						'right' => 0,
						'center' => 0,
						'justify' => 0
						);

		// Search for paragraph alignment types.
		// Left is default, so no need to search for it.
		foreach($alignment_type as $type=>$value)
		{
			foreach($paragraphs as $paragraph)
			{
			 if($paragraph->getAttribute('align') === $type)
			 {
					$alignment_type[$type]++;
			 }
			 elseif($paragraph->hasAttribute('style'))
			 {
				$style = $paragraph->getAttribute('style');
				if((strtolower(substr($style, strlen("text-align:"), strlen($type))) === $type) 
					|| (strtolower(substr($style, strlen("text-align: "), strlen($type))) === $type))
				{
					$alignment_type[$type]++;
				}
			 }
			}
		}

		// Only get a mark if all 3 types are found. Left is ignored as that's the default one.
		if(($alignment_type['right'] > 0) && ($alignment_type['center'] > 0) && ($alignment_type['justify'] > 0))
		{
			$this->ca2Marks += 0.3;
			$this->ca2Comments .= "All paragraph alignment found: Good";
		}
		else
		{
			$this->ca2Comments .= "Need to use all 4 types of alignment: Left Right Center and Justify";
		}
	} // End getPalignment
	
	// Find divs
	public function getDiv($dom)
    {
	 $div_mark = 0;
	 $divs = $dom->getElementsByTagName('div');

        foreach($divs as $div)
		{
			if($div->nodeValue !== '')
			{
				$div_mark++;
			}
		}
		
         if($div_mark > 0)
         {
			$this->ca2Marks += 0.15;
			$this->ca2Comments .= ";Div used: Good";
         }
		 else
		 {
			 $this->ca2Comments .= ";Need at least 1 Div tag.";
		 }

    } // End getDiv
	
	// Find span
	public function getSpan($dom)
    {
	 $span_mark = 0;
	 $spans = $dom->getElementsByTagName('span');

		foreach($spans as $span)
		{
			if($span->nodeValue !== '')
			{
				$span_mark++;
			}
		}
		
         if($span_mark > 0)
         {
			$this->ca2Marks += 0.15;
			$this->ca2Comments .= ";Span tag used: Good";
         }
		 else
		 {
			 $this->ca2Comments .= ";Need at least 1 Span tag.";
		 }

    } // End getSpan

	
	// Look for a particular attribute in tags, such as
	// searching for class in div tag.
	public function searchForAttribute($dom, $attribute)
	{
	 // Tags to search through. This can be expanded.
	 $element_types = array(
			'h1' => 0,
			'h2' => 0,
			'h3' => 0,
			'h4' => 0,
			'h5' => 0,
			'h6' => 0,
			'p' => 0,
			'ul' => 0,
			'ol' => 0,
			'dl' => 0,
			'dd' => 0,
			'dt' => 0,
			'li' => 0,
			'img' => 0,
			'body' => 0,
			'div' => 0,
			'a' => 0,
			'span' => 0
	);

		// iterate through each types of tags.
		foreach($element_types as $type=>$value)
		{
		  $attrib_elements = $dom->getElementsByTagName($type);
		  foreach($attrib_elements as $element)
		  {
			if($element->getAttribute($attribute) !== '')
			{
				$element_types[$type]++;
			}
		  }
		}
		
		// Totals all of them.
		$total = 0;
		foreach($element_types as $types=>$value)
		{
			$total = $total + $value;
		}
		
		// Need a min of 1
		if($total >= 1)
		{
			$this->ca2Marks += 0.3;
			$this->ca2Comments .= ";Minimum 1 of " . $attribute . " attributes found: Good.";
		}
		else
		{
			$this->ca2Comments .= ";No " . $attribute . " attributes found.";
		}
		
	} // End searchForAttribute

	
	// Find CSS styles
	function getCssStyle($dom)
	{
	 $mark_ex = 0;
	 $mark_em = 0;
	 $mark_in = 0;

	 // Find External Stylesheets
	 $external_style = $dom->getElementsByTagName('link');
	 foreach($external_style as $external)
	 {
		if(($external->getAttribute('rel')) === 'stylesheet')
		{
		 $mark_ex++;
		}
	 }

	 // Find Embedded Stylesheets
	 $embedded_style = $dom->getElementsByTagName('style');
	 $nodevalues = '';
	 for($i = 0; $i<$embedded_style->length; $i++)
	 {
		$entry = $embedded_style->item($i);
		// echo $entry->nodeValue . "\n";
		$nodevalues .= $entry->nodeValue ;
	 }

	 if($nodevalues !== '')
	 {
		$mark_em++;
	 }


	 /* Find Inline StyleSheets
	  * Only going to search through headings, paragraphs, lists, images
	  * and other core items.
	  */
	 $element_types = array(
					'h1' => 0,
					'h2' => 0,
					'h3' => 0,
					'h4' => 0,
					'h5' => 0,
					'h6' => 0,
					'p' => 0,
					'ul' => 0,
					'ol' => 0,
					'dl' => 0,
					'dd' => 0,
					'dt' => 0,
					'li' => 0,
					'img' => 0,
					'body' => 0,
					'span' => 0,
					'a' => 0,
					'blockquote' => 0
	);

	foreach($element_types as $type=>$value)
	{
	  $style_elements = $dom->getElementsByTagName($type);
	  foreach($style_elements as $element)
	  {
		if($element->getAttribute('style') !== '')
		{
			$element_types[$type]++;
		}
	  }
	}

	foreach($element_types as $types=>$value)
	{
		$mark_in += $value;
	}

	if(($mark_in > 0) && ($mark_em > 0) && ($mark_ex > 0))
	{
		$this->ca2Marks += 0.3;
		$this->ca2Comments .= ";All levels of CSS present: Good";
	}
	else
	{
		$this->ca2Comments .= ";Need to use Inline Embedded and External CSS";
	}
	} // End getCssStyle

	// Find Bullet change for <UL> lists
	public function checkBulletsUL($dom)
	{
	 $uBullets = 0;

	 $ulists = $dom->getElementsByTagName('ul');
	 foreach($ulists as $ulist)
	 {
	  if($ulist->hasAttribute('type'))
	  {
		if($ulist->getAttribute('type') === 'square')
		{
		 $uBullets++;
		}
	  }
	  elseif($ulist->hasAttribute('style'))
	  {
	   $style = $ulist->getAttribute('style');
	   if((strtolower(substr($style, 0)) === 'list-style-type: square') 
		   || (strtolower(substr($style, 0)) === 'list-style-type:square'))
	   {
		$uBullets++;
	   }
	  }
	 }
	 
	 if($uBullets > 0)
	 {
		 $this->ca2Marks += 0.3;
		 $this->ca2Comments.= "; Square UL bullets: Good";
	 }
	 else
	 {
		 $this->ca2Comments.= ";No change to UL bullet type";
	 }
	} // End checkBulletsUL
	
	
	// Find Bullet change for <OL> lists
	public function checkBulletsOL($dom)
	{
	 $oBullets = 0;

	 $olists = $dom->getElementsByTagName('ol');
	 foreach($olists as $olist)
	 {
	  if($olist->hasAttribute('type'))
	  {
		if($olist->getAttribute('type') === 'i')
		{
		 $oBullets++;
		}
	  }
	  elseif($olist->hasAttribute('style'))
	  {
	   $style = $olist->getAttribute('style');
	   if((strtolower(substr($style, 0)) === 'list-style-type: lower-roman') 
		   || (strtolower(substr($style, 0)) === 'list-style-type:lower-roman'))
	   {
		$oBullets++;
	   }
	  }
	 }
	 
	 if($oBullets > 0)
	 {
		 $this->ca2Marks += 0.3;
		 $this->ca2Comments .= ";OL bullet type changed to lower-roman: Good";
	 }
	 else
	 {
		 $this->ca2Comments .= ";Need to change OL bullets to lower-roman.";
	 }
	} // End checkBulletsOL
	
	// Find font-styles, weight, size, e.g. Bold, Italic,
	public function checkFontStyles($dom)
	{
		$fonts = array (
			'b' => 0,
			'i' => 0,
			'em' => 0,
			'small' => 0,
			'strong' => 0,
			'sub' => 0,
			'sup' => 0,
			'mark' => 0
			
		);
		
		// Only need to ensure that at least 1 of each type is used.
		foreach($fonts as $font=>$value)
		{
          $element_fonts = $dom->getElementsByTagName($font);

          foreach($element_fonts as $element_font)
          {
			if($element_font->nodeValue !== '')
			{
				$fonts[$font] = 1;
			}
          }
         }
		 
		 // Total them
		 $total = 0;
		 foreach($fonts as $font=>$value)
		 {
			$total += $value;
		 }
		 
		 if($total >= 2)
		 {
			$this->ca2Marks += 0.3;
			$this->ca2Comments .= ";At least 2 font-weight, styles, size used: Good";
		 }
		 else
		 {
			 $this->ca2Comments .= ";Need to use at least 2 of the following: bold, italics, mark, strong, sub, sup";
		 }		
	} // End checkFontStyles
	
	// Validate HTML
	public function validateFiles($dom, $file, $username, $StudentFiles)
	{
		libxml_use_internal_errors(true);
		$dom->load($file);
		
		// Look for styles_CA2.css file
		foreach($StudentFiles["css"] as $sfcss)
		{
			if(($sfcss->getFilename() === "styles_CA2.css") &&
			($sfcss->getusername() === $username))
			{
				$filepath = $sfcss->getFilepath();
			}
		}
		
		if($dom->validate() && ($StudentFiles["css"][$filepath]->getValidation() === "y"))
		{
			$this->ca2Marks += 0.3;
			$this->ca2Comments .= ";HTML and CSS validates, no errors.";
		}
		elseif(($dom->validate() === TRUE) && ($StudentFiles["css"][$filepath]->getValidation() === "n"))
		{
			$this->ca2Marks += 0.15;
			$this->ca2Comments .= ";HTML validates but CSS contain errors.";
		}		
		elseif(($dom->validate() === FALSE) && ($StudentFiles["css"][$filepath]->getValidation() === "y"))
		{
			$this->ca2Marks += 0.15;
			$this->ca2Comments .= ";HTML does not validates but CSS validates.";
		}		
		else
		{
			$this->ca2Comments .= ";HTML doesn't validate.";
		}

		// Clear errors after we're done. We don't need to store this info.
		libxml_clear_errors();
	} // End validateFiles
	
	// Find text and background colours
	public function checkCssAttrib($file, $username, $StudentFiles)
	{
		// Look for styles_CA2.css file
		foreach($StudentFiles["css"] as $sfcss)
		{
			if(($sfcss->getFilename() === "styles_CA2.css") &&
			($sfcss->getusername() === $username))
			{
				$filepath = $sfcss->getFilepath();
			}
		}

		// Get Text Colours
		$cssfile = file_get_contents($filepath);
		// Search for pattern, starting with color: and ending with ;
		// preg_replace removes whitespaces
		preg_match_all("/(?<=color:).*?(?=;)/", $cssfile, $matches);
		$textcolours = preg_replace('/\s+/', '', $matches[0]);

		$htmlfile = file_get_contents($file);
		preg_match_all("/(?<=color:).*?(?=;)/", $htmlfile, $matches);	
		$textcolours = array_merge($textcolours, preg_replace('/\s+/', '', $matches[0]));
		
		$results_colors = array_unique($textcolours);
		
		// Get Background Colours
		preg_match_all("/(?<=background-color:).*?(?=;)/", $cssfile, $matches);
		$bgcolours = preg_replace('/\s+/', '', $matches[0]);
		
		preg_match_all("/(?<=background-color:).*?(?=;)/", $htmlfile, $matches);
		$bgcolours = array_merge($bgcolours, preg_replace('/\s+/', '', $matches[0]));
		
		$results_bgcolours = array_unique($bgcolours);
		
		if((count($results_bgcolours) >= 2) && (count($results_colors) >= 2))
		{
			$this->ca2Marks += 0.3;
			$this->ca2Comments .= ";2 or more text and background colours used: Good.";
		}
		elseif((count($results_bgcolours) >= 2) && (count($results_colors) < 2) ||
		(count($results_bgcolours) < 2) && (count($results_colors) >= 2))
		{
			$this->ca2Marks += 0.15;
			$this->ca2Comments .= ";Need 2 or more text and background colours. 
			Found:" . count($results_bgcolours) ." Background colours, " . count($results_colors)
			. " colours.";
		}
		else
		{
			$this->ca2Comments .= ";Need at least 2 or more of text colours and background colours.";
		}
		
	} // End checkCssAttrib
	
}

?>
