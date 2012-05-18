<?php
/**
 * Helps concatenating CSS files from a directory and its subdirectories recursively
 * while maintaining relative paths and resolving @import statements
 * User: martin.naumann
 * Date: 14.05.12
 * Time: 16:45
 */

if($argc < 2) die("Usage: php css_concatenator.php OUTPUTFILE (filename) CONCAT_BEFORE_RECURSE (0 / 1) FAULT_TOLERANT (0 / 1)");
define(RECURSE_AFTER_CONCAT, ($argc >= 3 ? $argv[2] : 1));
define(TOLERATE_INVALID_PATHS, ($argc == 4 ? $argv[3] : 0));

file_put_contents($argv[1], parseDirectory(''));

/**
 * @param $currentPath The path to scan for CSS files
 * @return string Content of all the CSS in this directory + subdirectories concatenated together
 */
function parseDirectory($currentPath) {

	$content = "";
	if(RECURSE_AFTER_CONCAT)
		$entries = array_slice(scandir(getcwd() . '/' . $currentPath, RECURSE_AFTER_CONCAT), 0, -2); //Remove "." and ".."
	else
		$entries = array_slice(scandir(getcwd() . '/' . $currentPath, RECURSE_AFTER_CONCAT), 2); //Remove "." and ".."

	foreach($entries as $entry) {
		if(is_file(getcwd() . '/' . $currentPath . $entry)) {
			if(substr($entry, -3) === 'css')
				$content .= parseFile($currentPath, $entry);
		}
		else {
			$content .= parseDirectory($currentPath . $entry . '/');
		}
	}
	return $content;
}

function parseFile($currentPath, $file) {
	echo "Parsing $file in $currentPath...\n";
	$content = '';
	$lines = file(getcwd() . '/' . $currentPath . $file);
	foreach($lines as $line) {
		if(strpos($line, '@import') !== false) {
			$content .= resolveImport($line, $currentPath);
			continue;
		}
		if(strpos($line, 'url(') !== false) $line = resolveRelativeUrl($line, $currentPath);
		$content .= $line;
	}
	return $content;
}

/**
 * @param $line string The line to work with
 * @param $currentPath The current path we're investigating
 * @return string the parsed content of the imported file
 */
function resolveImport($line, $currentPath) {
	preg_match('#@import url\((.*)\)#isU', $line, $matches);

	$absPath = relativeToAbsolutePath($matches[1], $currentPath);
	if($absPath == "") return '';
	return parseFile(dirname($absPath) . '/', basename($absPath));
}

function resolveRelativeUrl($line, $currentPath) {
	preg_match('#url\((.*)\)#isU', $line, $matches);
	$absPath = relativeToAbsolutePath($matches[1], $currentPath);

	if($absPath != "")
		$line = str_replace($matches[1], relativeToAbsolutePath($matches[1], $currentPath), $line);
	return $line;

}

function relativeToAbsolutePath($relativePath, $currentPath) {
	if($relativePath[0] == '/' || substr($relativePath,0,4) == 'http') return $relativePath; //This is already absolute.
	$path = rtrim($currentPath, '/') . '/' . $relativePath;
	$resolvedPath = realpath($path);
	if($resolvedPath == "" && !TOLERATE_INVALID_PATHS) die("ERROR: Path could not be resolved properly. [$path]\n");
/*	$maxLevel = 10;
	while(strpos($resolvedPath, '..') !== false && $maxLevel-- > 0) $resolvedPath = preg_replace('!/([^/]*)/\.\.(/.*)$!isU', '\2', $resolvedPath);*/
//	if(strpos($resolvedPath, '..')) die("ERROR: Interrupted because a path was not resolvable!");
	return str_replace(getcwd(), '', $resolvedPath);
}
