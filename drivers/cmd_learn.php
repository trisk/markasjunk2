<?php

/**
 * Command line learn driver
 * @version 1.0
 * @author Philip Weir
 */

function learn_spam($uids) {
	do_salearn($uids, true);
}

function learn_ham($uids) {
	do_salearn($uids, false);
}

function do_salearn($uids, $spam) {
    $rcmail = rcmail::get_instance();
    $temp_dir = realpath($rcmail->config->get('temp_dir'));

    if ($spam)
    	$command = $rcmail->config->get('markasjunk2_spam_cmd');
    else
    	$command = $rcmail->config->get('markasjunk2_ham_cmd');

    if (!$command)
    	return;

    $command = str_replace('%u', $_SESSION['username'], $command);

    if (strpos($_SESSION['username'], '@') !== false) {
        $parts = explode("@", $_SESSION['username'], 2);

        $command = str_replace(array('%l', '%d'),
						array($parts[0], $parts[1]),
						$command);
    }

	foreach (explode(",", $uids) as $uid) {
		if (strpos($command, '%f') === false) {
		        $spec = array(array('pipe', 'r'), array('pipe', 'w'));
			$proc = proc_open($command, $spec, $pipes);
			if (!is_resource($proc))
				return;
			fwrite($pipes[0], $rcmail->imap->get_raw_body($uid));
			fclose($pipes[0]);
			$output = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($proc);
		} else {
			$tmpfname = tempnam($temp_dir, 'rcmSALearn');
			file_put_contents($tmpfname, $rcmail->imap->get_raw_body($uid));

			$command = str_replace('%f', $tmpfname, $command);
			exec($command, $output);
		}

		if ($rcmail->config->get('markasjunk2_debug')) {
			write_log('markasjunk2', $command);
			write_log('markasjunk2', $output);
		}

		if (isset($tmpfname)) {
			unlink($tmpfname);
		}
		$output = '';
	}
}

?>
