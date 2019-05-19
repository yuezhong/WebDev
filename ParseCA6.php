<?php

class ParseCA6
{
	private $ca6Marks;
	private $ca6Comments;
	private $dirpath;

	public function __construct($dirpath)
	{
	 // Default constructor
	 $this->ca6Marks = 0;
	 $this->ca6Comments = "";
	 $this->dirpath = $dirpath;
	}

	public function getMarks()
	{
	 return $this->ca6Marks;
	}

	public function getComments()
	{
	 return $this->ca6Comments;
	}
	
	// Find any tables and provide a mark if it is a minimum of 4x3
	public function checkTables($dom)
	{
		$tables = $dom->getElementsByTagName('table');
		$maxrows = 0;
		$maxcols = 0;
		
		foreach($tables as $table)
		{
			// Get Rows
			$rows = $table->getElementsByTagName('tr');
			if($rows->length > $maxrows)
			{
				$maxrows = $rows->length;
			}
			
			// Get Cols
			foreach($rows as $row)
			{
				$cols = $row->getElementsByTagName('td');
				echo "Cols: " . $cols->length . "\n";
				if($cols->length > $maxcols)
				{
					$maxcols = $cols->length;
				}
			}

		}
		
		echo "Max table rows: $maxrows \n";
		echo "Max table cols: $maxcols \n";
		
		if(($maxcols >= 3) && ($maxrows >= 4))
		{
			$this->ca6Comments = "Table found with minimum of 4 rows and 3 cols.";
			$this->ca6Marks += 0.25;
		}
		else
		{
			$this->ca6Comments = "A Table with a minimum of 4 rows and 3 cols is required.";
		}
	
	} // End checkTables
	
	// Find captions and headers
	public function findCapHead($dom)
	{
		$capTotal = 0;
		$headTotal = 0;
		
		$tables = $dom->getElementsByTagName('table');
		
		foreach($tables as $table)
		{
			$captions = $table->getElementsByTagName('caption');
			foreach($captions as $caption)
			{
				if($caption->nodeValue != '')
				{
					echo "Caption found: " . $caption->nodeValue . "\n";
					$capTotal++;
				}
			}
			$headers = $table->getElementsByTagName('thead');
			foreach($headers as $header)
			{
				if($header->nodeValue != '')
				{
				 // echo "Headers: " . $header->nodeValue . "\n";
				 $headTotal++;
				}
			}
		}
	
		if(($capTotal > 0) && ($headTotal > 0))
		{
			$this->ca6Marks += 0.25;
			$this->ca6Comments .= ";Captions and headers used - Good";
		}
		elseif(($capTotal === 0) && ($headTotal > 0))
		{
			$this->ca6Comments .= ";Captions not used, but headers used.";
		}
		elseif(($capTotal > 0) && ($headTotal === 0))
		{
			$this->ca6Comments .= ";Captions used but headers not used.";
		}
		else
		{
			$this->ca6Comments .= ";Captions and headers not used.";
		}
		
	} // End findCapHead
	
	// Find border
	public function findBorder($username, $StudentFiles)
	{
		$filepath = "";
		$total = 0;
		// Look for styles_CA6.css file
		foreach($StudentFiles["css"] as $sfcss)
		{
			if((strpos(strtoupper($sfcss->getFilename()), "CA6") !== FALSE) &&
			($sfcss->getusername() === $username))
			{
				$filepath = $sfcss->getFilepath();
			}
			
			echo "Finding Border in: $filepath \n";
		
			// Set $cssfile to first value after ? if evalution is true, otherwise 2nd value after colon
			$cssfile = ($filepath !== "" ? file_get_contents($filepath) : "");
			
			// Regex finds table, anything in between border and solid, over multiline (/s)
			preg_match_all("/(?<=table).*?(border:).*?(solid)/s", $cssfile, $matches);
			$border = preg_replace('/\s+/', '', $matches[0]);
			print_r($border);
			$total += count($border);
		}
		
		if($total > 0)
		{
			$this->ca6Comments .= ";CSS styled Soild Table border found.";
			$this->ca6Marks += 0.25;
		}
		else
		{
			$this->ca6Comments .= ";CSS styled Solid Table border is required. Table border html attribute is deprecated.";
		}
	} // End findBorder
	
	// Find alternative shading using nth-of-type
	public function findPseudo($username, $StudentFiles)
	{
		$nth_type = 0;
		foreach($StudentFiles["css"] as $sfcss)
		{
			if((strpos(strtoupper($sfcss->getFilename()), "CA6") != FALSE) &&
			($sfcss->getusername() === $username))
			{
				$filepath = $sfcss->getFilepath();
			}
			else
			{
				$filepath = "";
			}
			
			// Set $cssfile to first value after ? if evalution is true, otherwise 2nd value after colon
			$cssfile = ($filepath !== "" ? file_get_contents($filepath) : "");
			if(strpos($cssfile, "nth-of-type"))
			{
				$nth_type++;
			}
		}
		
		if($nth_type > 0)
		{
			$this->ca6Comments .= ";nth-of-type found.";
			$this->ca6Marks += 0.25;
		}
		else
		{
			$this->ca6Comments .= ";Please use nth-of-type for alternative shading in tables.";
		}
	}
	
		// Validate HTML
	public function validateFiles($username, $StudentFiles)
	{
		$non_validatedCss = 0;
		$non_validatedHtml = 0;

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
			$this->ca6Marks += 0.25;
			$this->ca6Comments .= ";HTML and CSS validates, no errors.";
		}
		elseif($non_validatedCss > 0 && $non_validatedHtml = 0)
		{
			$this->ca6Comments .= ";HTML validates but CSS contain errors.";
		}		
		elseif($non_validatedCss = 0 && $non_validatedHtml > 0)
		{
			$this->ca6Comments .= ";HTML does not validates but CSS validates.";
		}		
		else
		{
			$this->ca6Comments .= ";HTML and CSS files doesn't validate.";
		}

	} // End validateFiles
	
	// Start all checks
	public function start($file, $student, $StudentFileObj)
	{
	 $html = file_get_contents($file);
		
	 //Create a new DOM document
	 $dom = new DOMDocument;

	 //Parse the HTML. The @ is used to suppress any parsing errors
	 @$dom->loadHTML($html);
	 
	 // Build link to students public_html folder on web server.
	 $username = $student->getusername();
	 $this->dirpath = $this->dirpath . "/$username/public_html";
	 
	 $this->checkTables($dom);
	 $this->findCapHead($dom);
	 $this->findBorder($username, $StudentFileObj);
	 $this->findPseudo($username, $StudentFileObj);
	 $this->validateFiles($username, $StudentFileObj);
	}
}
?>