<?php
  
/**
 *	Gmail attachment extractor.
 *
 *	Downloads attachments from Gmail and saves it to a file.
 *	Uses PHP IMAP extension, so make sure it is enabled in your php.ini,
 *	extension=php_imap.dll
 *
 */

require_once '../config.php';

//echo getMail();

function getMail() {

	set_time_limit(3000); 
	 
	$filesCount = 0;
	 
	/* connect to gmail with your credentials */
	global $mail_host, $mail_user, $mail_pswd;
	 
	 
	/* try to connect */
//	$inbox = imap_open($mail_host,$mail_user,$mail_pswd) or die('Cannot connect to Gmail: ' . imap_last_error());
	$inbox = imap_open($mail_host,$mail_user,$mail_pswd);
	if (!$inbox) {die('Cannot connect to Gmail: ' . imap_last_error());}
	 
	 
	/* get all new emails. If set to 'ALL' instead 
	 * of 'NEW' retrieves all the emails, but can be 
	 * resource intensive, so the following variable, 
	 * $max_emails, puts the limit on the number of emails downloaded.
	 * 
	 */
	$emails = imap_search($inbox,'FROM "microline" SUBJECT "ML-Comp" UNSEEN');
	 
	/* useful only if the above search is set to 'ALL' */
	$max_emails = 20;
	 
	 
	/* if any emails found, iterate through each email */
	if($emails) {
	 
		$count = 1;
	 
		/* put the newest emails on top */
	//    rsort($emails);
	 
		/* for every email... */
		foreach($emails as $email_number) 
		{
	 
			/* get information specific to this email */
			$overview = imap_fetch_overview($inbox,$email_number,0);

	//foreach ($overview as $ovw) {
	//    echo "#{$ovw->msgno} ({$ovw->date}) - From: {$ovw->from} {$ovw->subject}\n";
	//}
	 
			/* get mail message */
			$message = imap_fetchbody($inbox,$email_number,2);
	 
			/* get mail structure */
			$structure = imap_fetchstructure($inbox, $email_number);
	 
			$attachments = array();
	 
			/* if any attachments found... */
			if(isset($structure->parts) && count($structure->parts)) 
			{
				for($i = 0; $i < count($structure->parts); $i++) 
				{
					$attachments[$i] = array(
						'is_attachment' => false,
						'filename' => '',
						'name' => '',
						'attachment' => ''
					);
	 
					if($structure->parts[$i]->ifdparameters) 
					{
						foreach($structure->parts[$i]->dparameters as $object) 
						{
							if(strtolower($object->attribute) == 'filename') 
							{
								$attachments[$i]['is_attachment'] = true;
								$attachments[$i]['filename'] = $object->value;
							}
						}
					}
	 
					if($structure->parts[$i]->ifparameters) 
					{
						foreach($structure->parts[$i]->parameters as $object) 
						{
							if(strtolower($object->attribute) == 'name') 
							{
								$attachments[$i]['is_attachment'] = true;
								$attachments[$i]['name'] = $object->value;
							}
						}
					}
	 
					if($attachments[$i]['is_attachment']) 
					{
						$attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);
	 
						/* 4 = QUOTED-PRINTABLE encoding */
						if($structure->parts[$i]->encoding == 3) 
						{ 
							$attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
						}
						/* 3 = BASE64 encoding */
						elseif($structure->parts[$i]->encoding == 4) 
						{ 
							$attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
						}
					}
				}
			}
	 
			/* iterate through each attachment and save it */
			foreach($attachments as $attachment)
			{
				if($attachment['is_attachment'] == 1)
				{
					$filename = $attachment['name'];
					if(empty($filename)) $filename = $attachment['filename'];
	 
					if(empty($filename)) $filename = time() . ".dat";
	 
					$filename = imap_mime_header_decode($filename);
					$filename = iconv('KOI8-R','CP1251', $filename[0]->text);

					/* prefix the email number to the filename in case two emails
					 * have the attachment with the same file name.
					 */
					$fp = fopen($email_number . "-" . $filename, "w+");
					fwrite($fp, $attachment['attachment']);
					fclose($fp);

					$zip = new ZipArchive;
					$res = $zip->open($email_number . "-" . $filename);
					if ($res == TRUE) {
						$zip->extractTo('./tmp/');
						$zip->close();
						$filesCount++;
					}

					unlink($email_number . "-" . $filename);
				}
	 
			}
	 
			if($count++ >= $max_emails) break;
		}
	 
	} 
	 
	/* close the connection */
	imap_close($inbox);
	 
//	echo "Done";

	return $filesCount;
} 
?>
