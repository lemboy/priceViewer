<?php

require_once '../config.php';

$files = scandir($scheme_dir);

$link = mysql_connect('localhost', $db_user, $db_pswd);
if (!$link) {
    die('Could not connect: ' . mysql_error());
}

foreach ($files as $file) {
    if (substr($file, 0, 1) == '.')
        continue;

    $tempsql = '';
    $lines = file($scheme_dir . "/" . $file);

    foreach ($lines as $line) {
        if (substr($line, 0, 2) == '--' || $line == '')
            continue;

        $tempsql .= $line;
    }

    if (substr($file, 0, 2) != '00')
        mysql_select_db($db_name) 
            or die("Error selecting database" . mysql_error());


    $sql = $tempsql;
    if (mysql_query($sql, $link)) {
        echo "Object " . $file . " created successfully\n";
    } else {
        die("Error creating object: " . $file . " / " . mysql_error() . "\n");
    }

}

?>

