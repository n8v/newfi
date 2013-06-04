<?php

/* For simulating DreamHost crappiness */
/*
if (rand(1,100) > 33 ) { // && isset($_REQUEST['s'])) { 
    sleep( 2 ); 
    header($_SERVER['SERVER_PROTOCOL'] . " 504 Ain't nobody got time for that.", true, 504); 
    exit; 
} 
*/

date_default_timezone_set('America/Anchorage');

header('Content-type: text/html');

//$datafile = dirname($_SERVER{'SCRIPT_FILENAME'}) . '/newfi';
$datafile = './newfi';

$max_post_seconds = 10;
$retry_microseconds = 1000;
$maxtries = $max_post_seconds * 1000 * 1000 / $retry_microseconds;
$tries = 0;

if (isset($_REQUEST['s'])) {
   $timeday = array();
   $timeday = gettimeofday();

   $say = strftime("%m-%d %H:%M") . ": " . $_REQUEST['s'] . "\r\n";

   $file = false;
   do {
       $file = @fopen($datafile,"a");
       //   fseek($file,0,SEEK_END);
       if (!$file) usleep($retry_microseconds); 

   }
   while (!$file && $tries <= $maxtries);

   if ($file) {
       if (flock($file, LOCK_EX)) {
	   fwrite($file,$say,strlen($say));
	   flock($file, LOCK_UN);
	   fclose($file);
	   exit;
       }
       else {
	       die( "COULD NOT FLOCK $datafile");
       }
   }
   else {
       echo "COULD NOT OPEN $datafile FOR APPENDING after $tries tries!";
       exit;
   }
}

function pretty($string)
{
    $newstring = array();
    $newchars = 0;
    for($i = 0 ; $i < strlen($string) ; $i++) {
	// ascii delete or backspace
	if ($string[$i] == "\x7f" || $string[$i] == "\x08") { 
	    $newchars--;
	    $newstring[$newchars] = '';
	} 
	else  {
	    $newstring[$newchars++] = $string[$i];
	}
    }
    //	$string = preg_replace('/.\x7f/','',$string);
    //  $string = preg_replace('/\n/','<br>',$string);
    return implode('',$newstring);
}

/*

 However, the timeout value has to be chosen carefully; indeed,
   problems can occur if this value is set too high (e.g., the client
   might receive a 408 Request Timeout answer from the server or a 504
   Gateway Timeout answer from a proxy).  The default timeout value in a
   browser is 300 seconds, but most network infrastructures include
   proxies and servers whose timeout is not that long.

    -- http://tools.ietf.org/html/draft-loreto-http-bidirectional-07#section-5.5

*/

$max_poll_seconds = 30;
$retry_microseconds = 100000;
$maxtries = $max_poll_seconds * (1000000 / $retry_microseconds);
$tries = 0;

$max_bytes_fistory = 8192;

if (isset($_REQUEST['g'])) {
    $get = intval($_REQUEST['g']);

    $skip_first_partial_line = false;
    if ($get == 0) {
	$stat = stat($datafile);
	$get = max($stat['size'] - $max_bytes_fistory, 0);
	if ( $get > 0 ) {
	    $skip_first_partial_line = true;
	}
    }


    do {
	usleep($retry_microseconds);

	clearstatcache();
	$stat = stat($datafile);
	$seek_position = $stat['size'];

    } while ($seek_position <= $get && $tries++ <= $maxtries) ;
    
    $file = fopen($datafile,"r");
    if ($file) {		
	if (flock($file, LOCK_SH)) {
	    fseek($file,$get,0);
	    //      stream_select($read,$write,$except,1);
	    $data = fread($file, $max_bytes_fistory);
	    $seek_position = ftell($file);
	    flock($file, LOCK_UN);
        }
	else {
	    die("COULD NOT aquire shared lock.");
	}

	fclose($file);

	$data = pretty($data);

	if ($skip_first_partial_line) {
	    $data= preg_replace('/^[^\n]+/s', '', $data);
	}

	echo $seek_position . ";" . $data;
	exit;
    }
    else {
	die("ERRAR. Could not open $datafile for reading.");
    }

}

