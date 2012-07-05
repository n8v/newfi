<?php

header('Content-type: text/html');

if (isset($_REQUEST['s'])) {
   $timeday = array();
   $timeday = gettimeofday();

   $say = strftime("%Y%m%d %H%M%S") . "." . $timeday["usec"] . ": " . $_REQUEST['s'] . "\r\n";

   $file = fopen("/var/www/htdocs/newfi","a");
//   fseek($file,0,SEEK_END);

   fwrite($file,$say,strlen($say));

   fclose($file);

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

if (isset($_REQUEST['g'])) {
   $get = intval($_REQUEST['g']);
   $file = fopen("/var/www/htdocs/newfi","r");
   do {
//      $read = array($file);
//      $write = NULL;
//      $except = NULL;
      fseek($file,$get,0);
//      stream_select($read,$write,$except,1);
      $data = fread($file,1000000);
      $newsize = ftell($file);
      if ($newsize == $get) usleep(100000);
   } while($newsize == $get);
   fclose($file);

   $data = pretty($data);
 	
   echo $newsize . ";" . $data;


}

?>