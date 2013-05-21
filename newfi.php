<?php

/* For simulating DreamHost crappiness */
/* if (rand(1,100) > 33 && isset($_REQUEST['s'])) { */
/*     sleep( 2 ); */
/*     header($_SERVER['SERVER_PROTOCOL'] . ' 504 Internal Server Error', true, 504); */
/*     exit; */
/* } */

date_default_timezone_set('America/Anchorage');

header('Content-type: text/html');

//$datafile = dirname($_SERVER{'SCRIPT_FILENAME'}) . '/newfi';
$datafile = './newfi';

$maxtries = 9;
$tries = 0;

if (isset($_REQUEST['s'])) {
   $timeday = array();
   $timeday = gettimeofday();

   $say = strftime("%m-%d %H:%M") . ": " . $_REQUEST['s'] . "\r\n";

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
	   flock($file, LOCK_UN);
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

$maxtries = 900; // about 90 seconds
$tries = 0;

if (isset($_REQUEST['g'])) {
    $get = intval($_REQUEST['g']);

    $skip_first_partial_line = false;
    if ($get == 0) {
	$stat = stat($datafile);
	$get = max($stat['size'] - 8192, 0);
	if ( $get > 0 ) {
	    $skip_first_partial_line = true;
	}
    }
    
    $file = fopen($datafile,"r");
    if ($file) {
	
      do {
		//      $read = array($file);
		//      $write = NULL;
		//      $except = NULL;
	if (flock($file, LOCK_SH)) {


		fseek($file,$get,0);
		//      stream_select($read,$write,$except,1);
		$data = fread($file,1000000);
		$newsize = ftell($file);
		flock($file, LOCK_UN);
        }
	else {
	    die("COULD NOT aquire shared lock.");
	}
	if ($newsize == $get) usleep(100000);
	$tries++;
      } while($newsize == $get && $tries <= $maxtries);
	    fclose($file);

	    $data = pretty($data);

	    if ($skip_first_partial_line) {
		$data= preg_replace('/^[^\n]+/s', '', $data);
	    }
    
	    echo $newsize . ";" . $data;
    }
    else {
	die("ERRAR. Could not open $datafile for reading.");
    }

}

