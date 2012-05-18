css-concatenator
================

Two small scripts to help you with optimizing CSS from legacy projects


Synopsis
--------

When optimizing legacy projects, a large number of CSS files resulting in a large number of HTTP requests
can be a good starting point.
Usually concatenating CSS files across multiple levels of subdirectories is not easy. Relative paths and import directives
will get in the way quite often.

To solve this problem, I wrote two scripts:


css-pathcorrector
-----------------

This script replaces relative url() calls with their absolute equivalent. Absolute in that case means: Absolute to the CWD (which should be the DOCROOT)
It also resolves @import directives in terms of including the content from the imported file in the current file.
After running the script, CSS files should be ready to be concatenated, either by solutions such as modconcat, some asset manager or even the css-concatenator.


css-concatenator
----------------

This script recursively walks through all sub-directories and concatenates CSS files, while replacing relative url() directives with absolute paths (starting from the current working directory).
This script may not be good at helping you with CSS where the order of concatenation matters (which is the case, if selectors overlap).
