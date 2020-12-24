# Core modify checker

Simple php scripts. One to make hash list of some distributive or package and other to check your copy for modification or creating/deleting core files.

It may be usefull when searching for backdoor or just understand who and how change original files.

# Usage

### hashgen.php
To create hash list run script with options bellow
```bash
php hashgen.php <path_to_core> <save_name> [<parameters>]
```
	Example: php hashgen.php ./wordpress-3.9 wp_3.9
	_core.wp_3.9 file will be generated.
	Available parameters:
	-verbose -> Show path, hash and memory usage in output.

### core_modify_check.php 
To check your files put core_modify_check.php and hash list into your files root directory and run:
```bash
php core_modify_check.php <_core.name> [<parameters>]
```
	Example: php core_modify_check.php  _core.wp_3.9 -verbose -copydiff -copynew
	All output will be saved into _core.wp_3.9.result file.\n
	Files in directory and it subdir saved in _core.ignore file will be ignored(one line = one dir).
	Available (optional) parameters:
		-verbose -> Show diff path in output .
		-copydiff -> Copy diff files into _core.diff folder .
		-copynew -> Copy files over hash file list into _core.new folder .