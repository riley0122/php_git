<?php
require_once 'config.php';

function downloadGit($inputUrl = "EMPTY"){
    $branch = BRANCH;
    $repoUrl = $inputUrl == "EMPTY"
            ? GIT_URL
            : $inputUrl;

    // Get all files on the server in a zip file
    $zip_url = "$repoUrl/archive/$branch.tar.gz";

    // Set user agent
    ini_set('user_agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36');

    // Get the contents of the repo
    $files = file_get_contents($zip_url);

    return $files;
}

function rmdir_recursive($dirname) {
    if (is_dir($dirname)) {
        // Get all files in the directory
        $objects = scandir($dirname);

        // Loop through the objects
        foreach ($objects as $object) {
            // Make sure the current object is not the parent or the root
            if ($object!= "." && $object!= "..") {
                if (filetype($dirname . "/" . $object) == "dir") {
                    // Recurse into the directory
                    rmdir_recursive($dirname . "/" . $object);
                } else {
                    unlink($dirname . "/" . $object);
                }
            }
        }
        rmdir($dirname);
    } else {
        throw new BadFunctionCallException("rmdir_recursive() expects parameter 1 to be a directory");
    }
}

function unpackGitZip($zip_data) {
    // Delete current wwwdata directory
    if (is_dir("wwwdata")) {
        rmdir_recursive("wwwdata");
    }

    if (file_exists("wwwdata.tar.gz")) {
        unlink("wwwdata.tar.gz");
    }

    if (file_exists("wwwdata.tar")) {
        unlink("wwwdata.tar");
    }

    // Save to file temporarily
    file_put_contents("wwwdata.tar.gz", $zip_data);

    // Decompress the file
    $p = new PharData("wwwdata.tar.gz");
    $p->decompress();

    // unarchive the file
    $phar = new PharData("wwwdata.tar");
    $phar->extractTo(getcwd() . '/wwwdata');

    // Delete the temporary files
    unlink("wwwdata.tar.gz");
    unlink("wwwdata.tar");

    return true;
}

// === Everything below this line is for development purposes ===

// set response header to json
header('Content-Type: application/json');

$data = downloadGit("https://github.com/riley0122/php_git");
unpackGitZip($data);
?>