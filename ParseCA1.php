<?php
/* CA1 Requirements:
* - DTD
* - min 3 types of headings
* - <ul> <ol> <dl>
* - link to infotech site (new tab / window)
* - email link
*/


class ParseCA1
{
	private $ca1Marks;
	private $ca1Comments;

	public function __construct()
	{
	 // Default constructor
	 $this->ca1Marks = 0;

	 $this->ca1Comments = "";
	}

	public function getMarks()
	{
	 return $this->ca1Marks;
	}

	public function getComments()
	{
	 return $this->ca1Comments;
	}


	public function start($file, $studentUsername)
	{
	 //Load the HTML page
	 $html = file_get_contents($file);

	 //Create a new DOM document
	 $dom = new DOMDocument;

	 //Parse the HTML. The @ is used to suppress any parsing errors
	 @$dom->loadHTML($html);

	 $this->checkDTD($dom);
	 $this->getHeadingEmailLinkMark($dom, $studentUsername);
	 $this->countLists($dom);
	}

	// See if the HTML5 doctype is used
	public function checkDTD($dom)
	{
		// <!DOCTYPE html>
		if($dom->doctype->name === 'html')
		{
			$this->ca1Marks += 0.25;
			$this->ca1Comments = "DTD present: Good";
		}
		else
		{
			$this->ca1Comments = "No HTML5 DTD found";
		}
	}

	// Calls functions to headings, email, external link
	// Adds them up to give final mark. Only get marks (0.25)
	// if all is present, otherwise, no marks.
	public function getHeadingEmailLinkMark($dom, $studentUsername)
	{
		$heading_mark = 0;
		$maillink_mark = 0;

		$heading_mark = $this->countHeadings($dom);
		$maillink_mark = $this->checkMailLink($dom, $studentUsername);

		if(($heading_mark == 1) && ($maillink_mark == 1))
		{
			$this->ca1Marks += 0.25;
			$this->ca1Comments .= ";Multiple headings, email link and external link present";
		}
		else
		{
			$this->ca1Comments .= ";Need to have multiple headings, email link and external link to any website";
		}
	} // End getHeadingEmailLinkMark

	// Count Headings, min 3 types
	public function countHeadings($dom)
	{
	$heading_type = array(
					'h1' => 0,
					'h2' => 0,
					'h3' => 0,
					'h4' => 0,
					'h5' => 0,
					'h6' => 0
	);
	$total = 0;

	// Loop through and find how many different heading types used.
	// Need a minimum of 3 different types.
	foreach($heading_type as $heading=>$value)
	{
	 $element_headings = $dom->getElementsByTagName($heading);
	 foreach($element_headings as $element)
	 {
	  if($element->nodeValue !== '')
	  {
	   $heading_type[$heading] = 1;
	  }
	 }
	}

	// Count totals.
	foreach($heading_type as $heading=>$value)
	{
		$total = $total + $value;
	}

	if($total >= 3)
	{
		return(1);
	}
	else
	{
		return(0);
	}

	} // End countHeadings


	// Check for email and external link. Needs to open in new tab or window.
	public function checkMailLink($dom, $studentUsername)
	{
	 $mail_mark = 0;
	 $ex_mark = 0;
	 $links = $dom->getElementsByTagName('a');

	 // Email Mailto Link
	 foreach($links as $link)
	 {
	  if($link->hasAttribute('href'))
	  {
		$href = trim($link->getAttribute('href'));

		if(strtolower(substr($href, 0, 7)) === 'mailto:')
		{
		   $mail_mark++;
		}
	  }
	 }

	 // External Link - to any site
	 foreach($links as $link)
	 {
	  if($link->hasAttribute('target'))
	  {
		if(($link->getAttribute('target') === '_blank'))
		{
		  $ex_mark++;
		}
	  }
	 }

	 // Only gets marks if both are present.
	if(($ex_mark > 0) && ($mail_mark > 0))
	{
		return(1);
	}
	else
	{
		return(0);
	}
	} // End checkMailLink

	// Count lists, +1 nested
	public function countLists($dom)
	{
		$list_types = array(
						'ul' => 0,
						'ol' => 0,
						'dl' => 0
						);
		$found = 0;

		foreach($list_types as $type=>$value)
		{
		  $list = $dom->getElementsByTagName($type);
		  if($list->length > 0)
		  {
			$list_types[$type]++;
		  }
		}

		if(($list_types['ul'] > 0) && ($list_types['ol']) > 0 && ($list_types['dl'] > 0))
		{
			$this->ca1Marks += 0.25;
			$this->ca1Comments .= ";All 3 list types used: Good";
		}
		else
		{
			$this->ca1Comments .= ";Need to show all 3 list types";
		}

		// run through different combos of nested lists...
		$found += $this->countNestedList($dom, 'ul', 'ol');
		$found += $this->countNestedList($dom, 'ul', 'dl');
		$found += $this->countNestedList($dom, 'ol', 'ul');
		$found += $this->countNestedList($dom, 'ol', 'dl');
		$found += $this->countNestedList($dom, 'dl', 'ul');
		$found += $this->countNestedList($dom, 'dl', 'ol');

		if($found > 0)
		{
			$this->ca1Marks += 0.25;
			$this->ca1Comments .= ";At least 1 nested list used: Good";
		}
		else
		{
			$this->ca1Comments .= ";Need to use 1 nested list";
		}

	}

	public function countNestedList($dom, $outlist, $inlist)
	{
		$found = 0;

		foreach($dom->getElementsByTagName($outlist) as $outl)
		{
			foreach($dom->getElementsByTagName($inlist) as $inl)
			{
				$found++;
			}

		}

		return $found;
	}

}

?>