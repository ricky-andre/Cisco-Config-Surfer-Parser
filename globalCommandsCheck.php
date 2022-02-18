<?php
 # with all the posted variables, retrieve all the configurations that must be examined
 # and put them in a vector.
 # $vipnet_filter can have one of the following values: "","GSR","VPE","VRR"
 # $regexp contains the regular expression that should be matched.
 include "phpGlobal.php";
 date_default_timezone_set ('Europe/Paris');
 
 $handle = @opendir($base_dir) or die("Unable to open this directory");
 #echo "Network filter ".print_r($_POST["vipnet_filter2"])."<P>";
 
 # in file phpGlobal.php this function is defined since it is used by other scripts ...
 $conf_list=getFiles($_POST["vipnet_filter2"]);
 #print_r($conf_list);

 #$prev_line=0;
 # parse all the files for the first time and get all the different global configuration commands

 # a list of lines that needs to be ignored should be inserted here. Also should be inserted a list
 # of replacements that need to be performed. For example:
 # - static routes should be ignored
 # - span session configuration should be ignored
 # - encrypted passwords should be replaced with empty spaces ...
 # - spanning-tree priority statements should be ignored ...
 $ignore_line[0]="^monitor session";
 $ignore_line[1]="^spanning-tree";
 $ignore_line[2]="^no spanning-tree";
 $ignore_line[3]="^ip route";
 $ignore_line[4]="^!\s+\w";
 $ignore_line[5]="^Current configuration";
 $ignore_line[6]="^access-list";
 $ignore_line[7]="^interface";
 $ignore_line[8]="^mls qos aggregate-policer";
 $ignore_line[9]="^ntp clock-period";
 $ignore_line[10]="^route-map";
 $ignore_line[11]="^vlan ";
 $ignore_line[12]="^version";
 $ignore_line[13]="^ip extcommunity-list";
 $ignore_line[14]="^ip community-list";
 $ignore_line[15]="^ip prefix-list";
 $ignore_line[16]="^ip flow-export source";
 $ignore_line[17]="^controller";
 $ignore_line[18]="^ip host";
 $ignore_line[19]="^ip as-path";
 $ignore_line[20]="^hw-module slot \d+ qos interface queues \d+";
 $ignore_line[21]="^hw-module slot \d+ qos account layer2 encapsulation length 14";
 $ignore_line[22]="^dialer-list \d+";
 $ignore_line[22]="^ipv4 virtual address";

 # these statements are used to replace things ... for example vtp domain must be configured everywhere
 # but the value will be different from host to host
 $repl_line[0]=array("hostname\s+\w+","hostname");
 $repl_line[1]=array("enable secret 5.*","enable secret 5");
 $repl_line[2]=array("enable password 7.*","enable password 7");
 $repl_line[3]=array("vtp domain\s+\w+","vtp domain");
 $repl_line[4]=array("snmp-server location.*","snmp-server location");
 $repl_line[5]=array("username backup password 7.*","username backup password 7");
 $repl_line[6]=array("username cisco password 7.*","username cisco password 7");
 $repl_line[7]=array("tacacs-server key 7.*","tacacs-server key 7");
 $repl_line[8]=array("snmp-server engineID local.*","snmp-server engineID local");
 $repl_line[9]=array("^ntp authentication-key \d+ md5 \w+ 7","ntp authentication-key --num-- md5 --pwd--");
 $repl_line[10]=array("^username \w+ password 7 \w+","username --user-- password 7 --pwd--");
 
 # parse all the files for the first time and get all the different global configuration commands
 for ($z=0;$z<count($conf_list);$z++)
 {
   # echo "Examining file: $conf_list[$z]<P>";
   $handle = fopen($base_dir.$conf_list[$z], "r");
   $INSIDE_BANNER=0;

   while ($line=fgets($handle))
    {
	$line=preg_replace("/\s+$/","",$line);
	#echo $line."<P>";

	if (preg_match("/^banner login/",$line) || preg_match("/^banner motd/",$line))
	{
	  # the continue statement here must not be configured, otherwise a line would be lost
	  $INSIDE_BANNER=1;
	}
	else if ($INSIDE_BANNER && preg_match("/^!/",$line))
	{
	  $INSIDE_BANNER=0;
	  unset($prev_line);
	  continue;
	}
	else if ($INSIDE_BANNER)
	  continue;

	#if (preg_match("/ntp source/",$line))
	#   echo "line \"ntp source\"<P>";
	
	# before inserting the current line, we must be sure that the next line has not an indentation
	# so the current line must be checkd for indentation, if it is NOT indented and the previous line
	# is not empty, insert the previous line.
	
	# results for regexp search
	if (preg_match("/^\w/",$line) || preg_match("/^!/",$line))
	{
	  $insert_line=1;
	  for ($i=0;$i<count($ignore_line);$i++)
	    if (preg_match("/".$ignore_line[$i]."/",$prev_line))
	      {
		$insert_line=0;
		break;
	      }
	
	  # echo $prev_line."<P>";
	  # here the prev_line could be replaced in some parts, like for the encrypted passwords
	  for ($i=0;$i<count($repl_line) && $insert_line;$i++)
	     $prev_line=preg_replace("/".$repl_line[$i][0]."/",$repl_line[$i][1],$prev_line);

	  if ($prev_line && $insert_line && !preg_match("/^!/",$prev_line))
	  {
	     $glob_cmd["$prev_line"][count($glob_cmd["$prev_line"])]=$conf_list[$z];
	     $conf_pres_cmds[$conf_list[$z]][$prev_line]=1;
	     #echo "Inserted global command \"$prev_line\"<P>";
	  }
	  $prev_line=$line;
      	}
	else
	  unset($prev_line);
    }
    fclose($handle);
    unset($prev_line);
 } # and of reading files ...

 echo "<h2>The results have been written also to a local text file</h2><P>&nbsp;<P>";
 $handle=fopen("./glbCommCheckResults.txt","w");
 fwrite($handle,"Priority	Command	Action	Network Elements	Comments\r\n");

 # now that you've got all the data, print out the interesting results ... commands that appear on all
 # configurations, could be printed out at the end of the page, or could be not printed at all ...
 foreach ($glob_cmd as $key => $value)
 {
   $missing=0;
   $miss_elem_str="";

   if (count($value)!=count($conf_list))
     {
	# something has been probably forgotten on configurations ...
	echo "Command <B>\"$key\"</B> is present on the following network elements:<P>";
	$pres_elem_str=preg_replace("/\.txt/","",join(", ",$value));
	echo $pres_elem_str."<P>";
	
	echo " ... and missing on the following network elements<P>";
	for ($i=0;$i<count($conf_list);$i++)
	  {
		if (!$conf_pres_cmds[$conf_list[$i]][$key])
		   {
			$miss_elem_str.=preg_replace("/\.txt/","",$conf_list[$i]).", ";
			$missing++;
		   }
	  }
	echo $miss_elem_str;
	echo "<P>&nbsp;<P>";

	if ($missing>count($value))
	  fwrite($handle,"	$key	delete	".$pres_elem_str."	\r\n");
	else if ($missing<count($value))
	  fwrite($handle,"	$key	add	$miss_elem_str	\r\n");
	else
	  fwrite($handle,"	$key	delete/remove	Present on".$pres_elem_str." missing on $miss_elem_str	\r\n");
     }
 }
 fclose ($handle);

 # A file could be written down with comma-separated values to be imported into excel for further
 # processing. On a first sight, it is decided if a command should be added or removed basing on the
 # number of appereances.
 

 echo "<h2>The following commands appear on every network element</h2><P>";
 foreach ($glob_cmd as $key => $value)
 {
   if (count($value)==count($conf_list))
   {
	echo "$key<P>";
   }
 }
?>
