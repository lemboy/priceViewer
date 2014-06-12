<?php

require_once 'getMail.php';
require_once 'importToDbNested.php';

define("FILE_MASK", "./tmp/*.XLS");

/** Clearing tmp dir */
array_map('unlink', glob(FILE_MASK));

logToFile("Start processing");

/** Get messages from mailserver */
$msgCnt = getMail();
logToFile($msgCnt . " files to process");
if ($msgCnt > 0) {
	foreach (glob(FILE_MASK) as $file) {
		/** Import attachments to db */
		$rowsCnt = importToDbNested($file);
		unlink($file);
		logToFile($file . " processed (" . $rowsCnt . " rows)");
	}
}

logToFile("Done");

function logToFile($msg)
{ 
	$fd = fopen("./import.log", "a");
	$str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;	
	fwrite($fd, $str . "\n");
	fclose($fd);
}

?>
