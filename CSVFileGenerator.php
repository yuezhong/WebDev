<?php
class CSVFileGenerator
{
	private $file;
	private $filename;

	/**
	 * Create a new CSV file for writing to.
	 */
	function __construct($name, $snumber)
	{
		$this->filename = $name;
		$this->file = fopen("feedback/$this->filename-$snumber.csv","w") or exit("Problem creating $this->filename.csv");
	}

	/**
	 * Close the CSV file
	 */
	function __destruct()
	{
		fclose($this->file);
	}

	/**
	 * Append text to the CSV file
	 *
	 * @param string $append_string text to append
	 */
	public function append($append_string)
	{
		fwrite($this->file, $append_string . "\n");
	}
}
?>
