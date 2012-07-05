<?php

header('Content-type: text/html');

//$datafile = dirname($_SERVER{'SCRIPT_FILENAME'}) . '/newfi';
$datafile = './newfi';

$maxtries = 9;
$tries = 0;

if (isset($_REQUEST['s'])) {
   $timeday = array();
   $timeday = gettimeofday();

   $say = strftime("%Y%m%d %H%M%S") . "." . $timeday["usec"] . ": " . $_REQUEST['s'] . "\r\n";

   $file = false;
   do {
       $file = @fopen($datafile,"a");
       //   fseek($file,0,SEEK_END);
       if (!$file) usleep(1000); // microseconds

   }
   while (!$file && $tries <= $maxtries);

   if ($file) {
       if (flock($file, LOCK_EX)) {
	   fwrite($file,$say,strlen($say));
	   fclose($file);
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
	if ($string[$i] == "\x7f" || $string[$i] == "\x08") {
	    $newchars--;
	    $newstring[$newchars] = '';
	} else $newstring[$newchars++] = $string[$i];
    }
    //	$string = preg_replace('/.\x7f/','',$string);
    return implode('',$newstring);
}

$maxtries = 9;
$tries = 0;

if (isset($_REQUEST['g'])) {
    $get = intval($_REQUEST['g']);
    
    $file = fopen($datafile,"r");
    if ($file) {
	
	if (flock($file, LOCK_SH)) {

	    do {
		//      $read = array($file);
		//      $write = NULL;
		//      $except = NULL;
		fseek($file,$get,0);
		//      stream_select($read,$write,$except,1);
		$data = fread($file,1000000);
		$newsize = ftell($file);
		if ($newsize == $get) usleep(100000);
		$tries++;
	    } while($newsize == $get && $tries <= $maxtries);
	    fclose($file);

	    $data = pretty($data);
    
	    echo $newsize . ";" . $data;
	}
	else {
	    die("COULD NOT aquire shared lock.");
	}
    }
    else {
	die("ERRAR. Could not open $datafile for reading.");
    }

}

