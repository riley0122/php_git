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

function extractRepoName($repoUrl) {
    // Trim any leading or trailing whitespace
    $repoUrl = trim($repoUrl);

    // Check if the URL ends with ".git"
    if (str_ends_with($repoUrl, '.git')) {
        // Remove the ".git" extension from the URL[]
        $repoUrl = substr($repoUrl, 0, -4);
    }

    // Check if the URL starts with "http://" or "https://"
    if (!str_starts_with($repoUrl, 'https://') && !str_ends_with($repoUrl,"http://")) {
        throw new BadFunctionCallException("extractRepoName() expects parameter 1 to start with https:// or http://");
    }

    // Extract the repo name from the URL
    $repoName = substr($repoUrl, strrpos($repoUrl, '/') + 1);

    return $repoName;
}

function pullSubData($subDirectoryName, $extraPath = "") {
    if (!is_dir($subDirectoryName)) {
        throw new BadFunctionCallException("pullSubData() expects parameter 1 to be a directory");
    }

    // For every file in the sub directory
    foreach (scandir($subDirectoryName . "/" . $extraPath) as $file) {
        if ($file!= "." && $file!= "..") {
            if (is_dir($subDirectoryName . $extraPath . "/" . $file)) {
                if (!is_dir($subDirectoryName . "/\..\/" . $extraPath . "/" . $file)) {
                    mkdir($subDirectoryName . "/\..\/" .  $extraPath . "/" . $file);
                }
                pullSubData($subDirectoryName, $extraPath . $file);
            } else {
                // If the file isn't a sub directory, copy the file to the main directory
                $filePath = $subDirectoryName . "/" . $extraPath . "/" . $file;
                $fileContents = file_get_contents($filePath);

                $newFilePath = $subDirectoryName . "\/..\/" . $extraPath . "/" . $file;

                file_put_contents($newFilePath, $fileContents);
            }
        }
    }

    // Remove the sub directory if this is the root sub directory
    if($extraPath == ""){
        rmdir_recursive($subDirectoryName. "/". $extraPath);
    }

    return true;
}

function unpackGitZip($zip_data) {
    // Delete current wwwdata directory
    if (is_dir("wwwdata")) {
        rmdir_recursive("wwwdata");
    }

    // Delete the temorary files
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

    // Take items out of the github generated directory
    $repo_name = extractRepoName(GIT_URL);
    $branch = BRANCH;
    pullSubData("wwwdata/$repo_name-$branch");

    return true;
}

// === Everything below this line is for development purposes ===

// set response header to json
header('Content-Type: application/json');

$data = downloadGit("https://github.com/riley0122/php_git");
unpackGitZip($data);
?>