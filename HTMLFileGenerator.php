<?php
class HTMLFileGenerator
{
	private $file;
	private $filename;

	/**
	 * Create a new html file for writing to.
	 */
	function __construct($name, $snumber)
	{
		$this->filename = $name;
		$this->file = fopen("feedback/$this->filename-$snumber.html","w") or exit("Problem creating $this->filename.html");
	}

	/**
	 * Close the HTML file
	 */
	function __destruct()
	{
		fclose($this->file);
	}

	/**
	 * Append text to the HTML file
	 *
	 * @param string $append_string text to append
	 */
	public function append($append_string)
	{
		fwrite($this->file, $append_string . "\n");
	}
}
?>
