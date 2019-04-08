<?php
require_once "FilenameFilter.php";
require_once "DirnameFilter.php";

$directory = new RecursiveDirectoryIterator("/home/sysadmin/WebDev/assignment1");

$filter = new DirnameFilter($directory, '/\.(?:php|html)$/');

foreach(new RecursiveIteratorIterator($filter) as $file) 
{
    echo $file . PHP_EOL;
}
?>
