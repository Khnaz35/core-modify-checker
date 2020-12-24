<?php

if (isset($_SERVER['OS']) && stripos($_SERVER['OS'], 'Win') !== false)
    define('DS', '\\');
else
    define('DS', '/');

if ($argc > 1) {
    $param = array();
    if ($argc > 2) //if had parameters
    {
        for ($i = 2; $i < $argc; $i++) {
            $arg = trim($argv[$i]);
            if (strpos('$arg', '-') !== false)
                continue;
            $arg = str_replace('-', '', $arg);

            $param[$arg] = TRUE;
        }
    }

    $not_found_list = array();
    $diff_files_list = array();
    $new_files_list = array();
    $avail_file_list = array();

    $hash = file_get_contents(trim($argv[1]));
    if ($hash === FALSE)
        die('Error: Cannot read ' . trim($argv[1]) . ' file.');

    $hash = unserialize($hash);

    check_core($hash);

    file_put_contents(trim($argv[1]) . '.result', 'Results of scan by ' . trim($argv[1]) . " file.\n\n");

    if (!empty($diff_files_list)) {
        file_put_contents(trim($argv[1]) . '.result', "\n" . '--== Files list that is different from the original ' . trim($argv[1]) . " file. ==--\n", FILE_APPEND);
        file_put_contents(trim($argv[1]) . '.result', implode("\n", $diff_files_list) . "\n", FILE_APPEND);
    }

    if (!empty($not_found_list)) {
        file_put_contents(trim($argv[1]) . '.result', "\n" . '--== Files list that is not found compared with the original ' . trim($argv[1]) . " file. ==--\n", FILE_APPEND);
        file_put_contents(trim($argv[1]) . '.result', implode("\n", $not_found_list) . "\n", FILE_APPEND);
    }

    if (!empty($new_files_list)) {
        if (isset($param['verbose']))
            print "Files list over " . $argv[1] . " file:\n" . implode("\n", $new_files_list);

        file_put_contents(trim($argv[1]) . '.result', "\n" . '--== Files list with extra files compared with the original ' . trim($argv[1]) . " file. ==--\n", FILE_APPEND);
        file_put_contents(trim($argv[1]) . '.result', implode("\n", $new_files_list) . "\n", FILE_APPEND);

        if (isset($param['copynew'])) {
            if (!empty($new_files_list)) {
                if (!file_exists('_core.new'))
                    mkdir('_core.new', 0777, true);

                foreach ($new_files_list as $newfile) {
                    if (!file_exists(dirname('_core.new' . DS . $newfile)))
                        mkdir(dirname('_core.new' . DS . $newfile), 0777, true);

                    copy($newfile, '_core.new' . DS . $newfile);
                }
            }
        }
    }

    die('All operation successfully complete. Look into ' . trim($argv[1]) . '.result file');
} else {
    echo "\nUsage: php core_modify_check.php <_core.name> [<parameters>]\n";
    echo "Example: php core_modify_check.php  _core.wp_3.9 -verbose -copydiff\n";
    echo "All output will be saved into _core.wp_3.9.result file.\n\n";
    echo "Files in directory and it subdir saved in _core.ignore file will be ignored(one line = one dir).\n";
    echo "Available parameters.\n";
    echo "-verbose -> Show diff path in output .\n";
    echo "-copydiff -> Copy diff files into _core.diff folder .\n";
    echo "-copynew -> Copy files over hash file list into _core.new folder .\n";
}

exit;


function delslash(&$item, $key)
{
    $item = trim($item);
    $item = str_replace('\\', '/', $item);

    if (substr($item, -1) == '/')
        $item = substr($item, 0, -1);

    if (substr($item, 0, 2) == './')
        $item = substr($item, 2);

    if (substr($item, 0, 1) == '/')
        $item = substr($item, 1);
}

function check_core($hash)
{
    global $not_found_list, $diff_files_list, $new_files_list, $param, $argv;

    //make proper ignore list
    $ignores = getIgnores();

    foreach ($hash as $path => $crc) {

        $ppartDS = str_replace('/', DS, $path);
//		$ppart = explode('/', $path); //make available files list
//		array_pop($ppart);
//		$ppart = implode('/', $ppart);
//		$ppartDS = str_replace('/', DS, $ppart);

        if (isIgnore($ppartDS, $ignores)) //ignore from file
        {
            if (isset($param['verbose']))
                print $path . " -> Ignored by _core.ignore\n";
            continue;
        }

        if (file_exists($ppartDS)) //add all founded files
        {
            $scandir = dirname($ppartDS);
            foreach (scandir($scandir) as $file) {
                $filePath = str_replace('\\', '/', $scandir . DS . $file);
                $filePath = substr($filePath, 0, 2) == './' ? substr($filePath, 2) : $filePath;
                if ($file != '.' && $file != '..' && is_dir($filePath) === FALSE) {
                    if (isIgnore($filePath, $ignores)) //ignore script files!!!
                        continue;

                    $new_files_list[$filePath] = 1;
                }
            }
        } else {
            if (isset($param['verbose']))
                print $path . " -> File not found\n";
            $not_found_list[] = $path;
            continue;
        }

        $content = file_get_contents($ppartDS); //save diff
        $content = normalize($content);

        if (md5($content) != $crc) {
            if (isset($param['verbose']))
                print $path . " -> File is different from the original\n";
            $diff_files_list[] = $path;

            if (isset($param['copydiff'])) {
                if (!file_exists('_core.diff'))
                    mkdir('_core.diff', 0777, true);

                if (!file_exists(dirname('_core.diff' . DS . $ppartDS)))
                    mkdir(dirname('_core.diff' . DS . $ppartDS), 0777, true);

                copy($ppartDS, '_core.diff' . DS . $ppartDS);
            }
        }
    }

    $new_files_list = array_keys($new_files_list);
    $hash = array_keys($hash);
    $new_files_list = array_diff($new_files_list, $hash);    //тут диф на новые файлы в папках ядра
}

function isIgnore($path, $ignores)
{
    foreach ($ignores as $ignore) {
        if (empty($path) && $ignore == '<root>')
            return true;

        if (preg_match("#$ignore#si", $path))
            return true;
    }

    return false;
}

function getIgnores()
{
    global $argv;
    $ignores = file_exists(dirname(__FILE__) . DS . '_core.ignore') ? file(dirname(__FILE__) . DS . '_core.ignore') : array();

    foreach ($ignores as $key => $ignore) {
        $ignore = trim($ignore);
        if (empty($ignore))
            unset($ignores[$key]);

        if (strpos($ignore, '#') !== false)
            unset($ignores[$key]);
    }

    $ignores = array_merge($ignores);
    $ignores[] = $argv[1];
    $ignores[] = $argv[1] . '.result';
    $ignores[] = '_core.ignore';
    $ignores[] = 'core_modify_check.php';
    $ignores[] = 'hashgen.php';
    array_walk($ignores, 'delslash');

    return $ignores;
}


// Normalize line endings.
function normalize($s)
{
    // Convert all line-endings to UNIX format.
    $s = str_replace(array("\r\n", "\r", "\n"), "\n", $s);

    // Don't allow out-of-control blank lines.
    $s = preg_replace("/\n{3,}/", "\n\n", $s);
    return $s;
}
