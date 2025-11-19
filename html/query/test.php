<?php

$socket = fsockopen("67.81.225.200","7777", $errno, $errstr, 10);
if (!$socket){
     echo "$errstr ($errno)\n";
              }
  else{
fputs($socket, "action: login\r\n");
fputs($socket, "username: taxipaterson\r\n");
fputs($socket, "secret: TaxiPaterson2024!\r\n\r\n");
fputs($socket, "action: Waitevent\r\n");
$wrets=fgets($socket,128);

while(($buffer = fgets($socket,4096)) !== false)
  { $p1=strpos($buffer,"Newchannel");
    if($p1 > 0)
    echo "Calling ID =";
    echo substr($buffer,$p1+4,4);
   }  
 }
}
?>
