<?php

if ($argc > 2) {
    $param = array();
    if ($argc > 3) //if had parameters
    {
        for ($i = 3; $i < $argc; $i++) {
            $arg = trim($argv[$i]);
            if (strpos('$arg', '-') !== false)
                continue;
            $arg = str_replace('-', '', $arg);

            $param[$arg] = TRUE;
        }
    }

    $directory_tree = array();

    $all_files = scan_directory_recursively(trim($argv[1]), trim($argv[1]));

    if (!$all_files)
        die('Error: Cannot read directory.');

    if ($f = fopen('_core.' . trim($argv[2]), 'w')) {
        fputs($f, serialize($all_files));
        fclose($f);

        die('File _core.' . trim($argv[2]) . ' successfully created');
    } else
        die('Cannot create _core file.');

} else {
    echo "\nUsage: php hashgen.php <path_to_core> <save_name> [<parameters>]\n";
    echo "Example: php hashgen.php ./wordpress-3.9 wp_3.9\n";
    echo "_core.wp_3.9 will be generated.\n\n";
    echo "Available parameters.\n";
    echo "-verbose -> Show path, hash and memory usage in output .\n";
}

exit;


// ------------------------------------------------------------
function scan_directory_recursively($directory, $root)
{
    global $directory_tree, $param;

    if (substr($directory, -1) == '/') // if the path has a slash at the end we remove it here
        $directory = substr($directory, 0, -1);


    if (!file_exists($directory) || !is_dir($directory)) // if the path is not valid or is not a directory ...
        return FALSE; // ... we return false and exit the function
    elseif (is_readable($directory)) // ... else if the path is readable
    {
        $directory_list = opendir($directory); // we open the directory


        while (FALSE !== ($file = readdir($directory_list))) // and scan through the items inside
        {
            if ($file != '.' && $file != '..') // if the filepointer is not the current directory // or the parent directory
            {
                $path = $directory . '/' . $file; // we build the new path to scan

                if (is_readable($path)) // if the path is readable
                {
                    if (is_dir($path)) // if the new path is a directory
                        scan_directory_recursively($path, $root); // add the directory details to the file list
                    else // if the new path is a file
                    {
                        $content = file_get_contents($path);
                        $content = normalize($content);

                        $path = str_replace('\\', '/', $path);
                        $path = substr($path, strlen($root) + 1); // remove path before root folter content for run from any place
                        $crc = md5($content);
                        $directory_tree[$path] = $crc;
                        unset($content);

                        if (isset($param['verbose']))
                            print $path . ' => ' . $crc . ' // mem=' . memory_get_usage() . "\n";
                    }
                }
            }

        }

        closedir($directory_list); // close the directory

        return $directory_tree; // return file list


    }

    return FALSE; // if the path is not readable ... // ... we return false
}

// Normalize line endings.
function normalize($s) {
    // Convert all line-endings to UNIX format.
    $s = str_replace(array("\r\n", "\r", "\n"), "\n", $s);

    // Don't allow out-of-control blank lines.
    $s = preg_replace("/\n{3,}/", "\n\n", $s);
    return $s;
}
