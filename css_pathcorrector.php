<?php
/**
 * Helps converting CSS files from a directory and its subdirectories recursively
 * to be relocated by making relative paths absolute in respect to the DOC_ROOT
 * It will put the content of @import files into the importing file
 *
 * User: martin.naumann
 * Date: 14.05.12
 * Time: 16:45
 */

if($argc < 2) die("Usage: php css_pathcorrector.php TOLERATE_INVALID_PATHS");


define(DOCROOT, getcwd());
define(TOLERATE_INVALID_PATHS,$argv[1]);
parseDirectory('');

/**
 * @param $currentPath The path to scan for CSS files
 * @return string Content of all the CSS in this directory + subdirectories concatenated together
 */
function parseDirectory($currentPath) {

	$content = "";
	$entries = array_slice(scandir(getcwd() . '/' . $currentPath), 2); //Remove "." and ".."

	foreach($entries as $entry) {
		if(is_file(getcwd() . '/' . $currentPath . $entry)) {
			if(substr($entry, -3) === 'css')
				file_put_contents(getcwd() . '/' . $currentPath . $entry, parseFile($currentPath, $entry));
		}
		else {
			parseDirectory($currentPath . $entry . '/');
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
			$line = resolveImport($line, $currentPath);
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
	if($absPath == "") return $line;

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
	return str_replace(realpath(DOCROOT), '', $resolvedPath);
}
