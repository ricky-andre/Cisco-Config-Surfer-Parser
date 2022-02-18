<?php
 #Dec 20 01:11:12 10.193.164.252 44684: Dec 19 17:02:17: %SNMP-3-AUTHFAIL: Authentication failure for SNMP req from host 10.192.10.58
 #Dec 20 01:11:12 127.0.0.1 100: <30>   dmgt[2396]: 3007(I):Started application(Interactor) ...
 #
 # Some common parts are identified ... there is the date, the host ip address and the messageID. There is a recent internet draft
 # of year 2007 that tries to standardize the syslog protocol.

 # When analyzing the syslog messages, it must be clear what has to be kept in consideration.
 # Jan 01 06:43:23 10.192.24.252 11578: Jan 1 06:31:12: %RCMD-4-RSHPORTATTEMPT: Attempted to connect to RSHELL from 10.128.200.70 (line 4951)
 # Jan 01 06:43:23 10.192.8.252 4298: Jan 1 06:32:04: %RCMD-4-RSHPORTATTEMPT: Attempted to connect to RSHELL from 10.128.200.70 (line 4961)
 # Dec 31 01:13:32 10.193.28.251 83563: Dec 31 01:00:01: %SNMP-3-AUTHFAIL: Authentication failure for SNMP req from host 10.192.10.58 (line 2)
 # Dec 31 01:13:32 10.193.164.252 48980: Dec 30 17:00:07: %SNMP-3-AUTHFAIL: Authentication failure for SNMP req from host 10.192.10.58 (line 8)
 # Dec 31 01:13:32 10.193.208.163 85886: Dec 31 01:00:12: %SNMP-3-AUTHFAIL: Authentication failure for SNMP req from host 10.192.10.58 (line 12)
 # Dec 31 01:13:32 10.193.200.79 100870: Dec 31 01:00:17: %SNMP-3-AUTHFAIL: Authentication failure for SNMP req from host 10.192.10.58 (line 18)
 #
 # in the above examples, the sending host is different, but the message is the same and should be matched and printed out,
 # because there is an host that performs snmp queries or rsh connection attempts even if it is not allowed to do so. What could be used
 # here is that the syslog message part is exactly the same (except for the time).
 # 
 # Dec 21 02:59:00 10.176.0.1 3714: .Dec 21 02:55:30.597 MET: %CLNS-5-ADJCHANGE: ISIS: Adjacency to NAGSR002 (POS1/0) Down, interface deleted(non-iih) (line 1966)
 # Dec 21 02:59:00 10.176.0.1 3715: .Dec 21 02:55:30.597 MET: %CLNS-5-ADJCHANGE: ISIS: Adjacency to NAGSR002 (POS1/0) Down, interface deleted(non-iih) (line 1967)
 # Dec 21 03:46:21 10.176.0.1 3721: .Dec 21 03:42:12.737 MET: %CLNS-5-ADJCHANGE: ISIS: Adjacency to NAGSR002 (POS1/0) Up, new adjacency (line 2662)
 # Dec 21 03:46:21 10.176.0.194 962: .Dec 21 03:42:12.364 MET: %CLNS-5-ADJCHANGE: ISIS: Adjacency to MIGSR001 (POS9/0) Up, new adjacency (line 2663)
 # Dec 21 08:21:13 10.176.0.1 3723: .Dec 21 08:16:09.529 MET: %CLNS-5-ADJCHANGE: ISIS: Adjacency to NAGSR002 (POS1/0) Down, interface deleted(non-iih) (line 7484)
 # Dec 21 08:21:13 10.176.0.1 3724: .Dec 21 08:16:09.529 MET: %CLNS-5-ADJCHANGE: ISIS: Adjacency to NAGSR002 (POS1/0) Down, interface deleted(non-iih) (line 7485)
 # Dec 21 08:21:13 10.176.0.194 972: .Dec 21 08:18:06.824 MET: %CLNS-5-ADJCHANGE: ISIS: Adjacency to MIGSR001 (POS9/0) Up, new adjacency (line 7591)
 # Dec 21 08:21:13 10.176.0.1 3730: .Dec 21 08:18:07.226 MET: %CLNS-5-ADJCHANGE: ISIS: Adjacency to NAGSR002 (POS1/0) Up, new adjacency (line 7592)
 # 
 # In the ABOVE examples, again the same message appears twice in the same day ... this means that the neighbor flapped at least two times (up/down)
 # in the same configured temporal window. Moreover, for some strange reasons, two syslog messages appear to be sent at the same time. A check should
 # be done so that just one message is considered.
 # 
 # Dec 20 23:53:46 10.192.40.248 24654: Dec 20 23:46:33: %OSPF-5-ADJCHG: Process 1, Nbr 10.192.40.163 on Vlan492 from FULL to DOWN, Neighbor Down: Too many ret.
 # Dec 20 23:53:46 10.192.40.248 24655: Dec 20 23:46:33: %OSPF-5-ADJCHG: Process 1, Nbr 10.192.40.139 on Vlan482 from FULL to DOWN, Neighbor Down: Too many ret.
 #
 # in the ABOVE example, the message IS NOT the same, because it regards two different Vlans ... so it should not be printed unless the same
 # vlan gives the same message for at least twice in the same configured temporal window.
 #
 # Results should be printed out ordered basing on the following criteria ...
 #
 # - the type of message (CLNS-5-ADJCHANGE,SNMP-3-AUTHFAIL ...)
 # - the message sender
 # - message sender and type of message (and this is the most important method to understand possible problems, should be printed as the first one)
    

 # There could be two approaches now ... all read elements are stored in memory. This would lead to exausting memory
 # if too many lines are read ... but it is the most simple approach.
 # 
 # this script could also be used to populate a mysql database with the following values:
 # row_index,time(secs from 1970),host,syslog_type,syslog_msg
 # 
 # log files should be read twice, the first time to populate the database, the second time, for every line a query should be
 # done to the database. The triple host-sys_type-sys_msg should be considered only once, the other lines can be skipped.
 # SELECT * FROM SYSLOG_DB WHERE timea-timeb<1day && syslog_type.=syslog_type && syslog_msg=syslog_msg ORDER BY time
 # 
 # all the complexity would be moved to the database ... it should be understood if everything gets faster ...
 # from some tests, it doesn't seem to be so ...
 
 # Start-time and end-time must be passed by the form or by the command line. An iterator element should be used in such a way
 # that this function is the only case dependent thing of this script. It will consider the name of log files, returning the 
 # correct files that will need to be parsed by the script, basing on the time window and so on.
 
 # running this script during the night, an output html file could be produced so that the output can be seen offline
 # in a very fast way.
 include "phpGlobal.php";
 date_default_timezone_set ('Europe/Paris');

 # window time in seconds ...
 $HOST=0;
 $TYPE=1;
 $MSG=2;
 $TIME=3;
 $MATCHED=4;

 # this threshold represents the number of times the syslog message must appear to be red colored.
 $cnt_thrsh=100;
 $return="\r\n";

 # here the conf_list should be populated with the arguments of the command line or the values passed in the form.
 # it will call a local function that will return the correct file list basing on the file names of the log files.
 if (count($_SERVER['argv']))
   {
     # the output file name should be set in the command line so that echo commands should not be replaced by
     # a check about the fact that an output file has been defined.
     if (count($_SERVER['argv'])<4)
       die("Command line must be \"c:\\php\\php.exe syslogAnalyzer.php startTime windowTime outputFileName\"");
     
     $startTime=strtotime($_SERVER['argv'][1]);
     #$endTime=strtotime($argv[3]);
     $window=$_SERVER['argv'][2]*86400;
     # to easy things out ... this will be the fileName used in references ... but the commandLine will be called
     # using it twice, something like the following:
     # \"c:\\php\\php.exe syslogAnalyzer.php startTime windowTime outputFileName > outputFileName\" 
     $outputFileName=$_SERVER['argv'][3];
     $writer=fopen($base_dir."syslogAnalyzedFiles/sysAnalyzer.log","w+");
   }
 else
   {
     $startTime=strtotime($HTTP_POST_VARS["sysStart"]);
     $window=$HTTP_POST_VARS["sysWindow"]*86400;
     if (!$HTTP_POST_VARS["sysWindow"])
       die("Insert the time window!");
     $outputFileName="syslogAnalyzer.php";
     $writer=fopen($base_dir."sysAnalyzer.log","w");
   }
 
 $handle = @opendir($base_dir) or die("Unable to open this directory");
 
 if (!preg_match("/\d+/",$window))
   die("Invalid window ".($window/86400)." it must be an integer number!");
 if (!$startTime)
   die("Invalid start time ".$HTTP_POST_VARS["sysStart"]);
 #if (!$endTime)
 #  die("Invalid end time ".$HTTP_POST_VARS["endStart"]);
 #if ($endTime<$startTime)
 #  die("Start time must be lower than the end time !<P>");

 # read the hostNames.txt database to retrieve the hostName for all the IpAddresses.
 $handle = fopen("hostNamesPerl.txt", "r");
 while ($line=fgets($handle))
   {
     if (preg_match("/(\d+\.\d+\.\d+\.\d+)\t(\w+)/",$line,$cols))
       $hostNames[$cols[1]]=$cols[2];
     # if the name of the host is not known (it is dead, no more available or something else)
     # we replace its name with its old ip address.
     if (preg_match("/Unknown/",$cols[2]))
       $hostNames[$cols[1]]=$cols[1];
   }
 fclose($handle);

 # since syslog files are written "one day later", the time is increased by one day:
 $startTime+=86400;
 #$endTime+=86400;

 # we start from the list of all log files ...
 $filter[0]="logs";
 $temp = getFiles($filter);

 # now the list of interesting files can be retrieved ...
 # echo "start time ".date("dmY",$startTime)." end time ".date("dmY",$endTime)." time date ".date("dmY",$time)."<P>";
 for ($time=$startTime-$window;$time<=$startTime;$time+=86400)
   {
     #echo "Parsing file names with regexp syslog.*".date("dmY",$time).".*<P>";
     for ($i=0;$i<count($temp);$i++)
       {
	 #echo "File examined $temp[$i] ...<P>";
	 if (preg_match("/syslog.*".date("dmY",$time).".*/",$temp[$i]))
	   { 
	     $conf_list[count($conf_list)]=$temp[$i]; 
	     #echo "Inserted file $temp[$i]<P>";
	   }
       }
   }

 # examine the configurations one by one ...
 echo "<h1 align=center>Configuration parsing results</h1>";
 $syslog_times=array();
 $syslog_data=array();
 $ordered_times=array();

 for ($i=0;$i<count($conf_list);$i++)
   {
     echo "Reading file: <B>\"".$conf_list[$i]."\"</B> ... ";

    $handle = fopen($base_dir.$conf_list[$i], "r");
    $line_num = 0;
    
    $start_time=strtotime("now");
    $lines=0;
    while ($line=fgets($handle))
      {
	$lines++;
	#if ($lines%1000==0)
	#  echo "Lines read $lines ".strtotime("now")."<P>";

	# results are stored in memory unless they stay inside the temporal window. Every time a new message is received,
	# old messages are removed using the array call functions.
	# strtotime function is used to convert the time into a timestamp and making simpler the above operation.
	if (preg_match("/\d\d:\d\d:\d\d\s+(\d+\.\d+\.\d+\.\d+|.+)\s+\d+:.+?(\w+)\s+(\d+)\s+(\d\d:\d\d:\d\d)(\.\d+|\s*).*?:\s+%(.+?):(.*)$/",$line,$match))
	  {
	    # $match[2] $match[3] $match[4] is when the syslog message has been sent by the sender
	    # $match[1] is the ip address or hostname of the sender
	    # $match[5] contains if any the ms to which the syslog message has been generated ...
	    # $match[6] is the syslog message type
	    # $match[7] is the syslog message body
	    
	    # here a match could be done on syslog_type to avoid considering certain errors ...	we do not mind for examples
	    # about acl matches  ...
	    if (preg_match("/SEC-6-IPACCESSLOG/",$match[6]) ||   # all access-lists denied packets matched here
		preg_match("/CS7SCCP-5-SCCPGNRL/",$match[6]) ||  # this is a SGW, generic error message not interesting
		preg_match("/SYS-5-CONFIG_I/",$match[6]))        # these are syslog "configured" messages ... they are not interesting
	      continue;

	    # echo "$match[2] $match[3] $match[4] strtotime ".strtotime("$match[2] $match[3] $match[4]")." sys type: ".$match[6]." sys msg: ".$match[7]."<P>";
            # a check must be done about the year ... if the date is in the past, it will be considered as belonging to the
            # current year, otherwise it will be considered as belonging to the previous year.
	    $unix_time=strtotime("$match[2] $match[3] $match[4]");
	    if ($unix_time>strtotime("now"))
	      $unix_time-=31536000;
	    
	    # insert the new element into syslog array, the first value is time, the other values are the generator's address
	    # and the syslog type message. 0 is the message body, while 1 is the flag that is set when a match is found within
	    # the time window in seconds.
	    $match[7]=trim($match[7]);
	    # store everything in the array.
	    $index=count($syslog_times);
	    $syslog_times[$index]=$unix_time;
	    $syslog_data[$index][$HOST]=$match[1];
	    $syslog_data[$index][$TYPE]=$match[6];
	    $syslog_data[$index][$MSG]=$match[7];
	    $syslog_data[$index][$TIME]=$unix_time;
	  }
	else
	  {
	    if (preg_match("/MsgType udt/",$line) ||                   # other SGW logs
		preg_match("/[O|D]PC: /",$line) ||                     # other SGW logs
		preg_match("/SEC-6-IPACCESSLOG/",$line) ||             # access-lists logs
		preg_match("/translation configured/",$line) ||        # matches SGW logs ...
		preg_match("/\d+\.\d+\.\d+\.\d+\s+\d+:\s+$/",$line) || # matches a blank log
		preg_match("/MICA-3-NOMAILELEMENTS/",$line) ||          # other strange syslog messages about a mail server maybe ...
		preg_match("/CDP-4/",$line) ||                          # CDP log messages are not interesting ...
		preg_match("/dmgt/",$line))                            # internal syslog daemon server logs
	      {}
	    else
	      fwrite($writer,$line.$return);
	  }
      }
    fclose($handle);
    fclose($writer);
    
    echo " finished! Lasted time <B>".(strtotime("now")-$start_time)."s</B> <P>";
   }
 
 # now go back within the array until you stay within the time window.
 # before sorting we have for example:
 # syslog_times[0]=100
 # syslog_times[1]=101;
 arsort($syslog_times);
 # now with the foreach we access the array in the following way
 # syslog_times[1]=101;
 # syslog_times[0]=100

 # the algorithm below is O(Nsquare), with 42000 lines log in one day it is almost infinite 
 # with a time window of one day.
 # The above array should be parsed and for every (host,msg_type,msg) triple, the indexes
 # of the array where they appear should be stored into an hash. This would consume even more
 # memory ... but it could be acceptable (much less memory than the syslog file itself).
 # With a file of 42000 lines, and a time_window of one hour (3600 sec), it needed 149 seconds
 # to parse the whole file. Doubling the time_window (7200secs), the time becomes 369s.
 # Other operations that linearly parse the file, just take something like 15 seconds or even less.
 # Using a mysql database could slow down things, but probably not that much: a query like the one
 # we need takes something like 0,5s with a database of 6Mbytes of one months of syslog data files ... 
 # and it should be done for every line so that for one day log with 42000 lines, it would take 
 # something like 20000s, more than 5 hours.
 # The hash $ordHtmHash is built up containing the requested references.
 
 # after the keys has been resorted, it is necessary to know the new ordered indexes ...
 foreach ($syslog_times as $index=>$time)
{
  $ordered_times[count($ordered_times)]=$index;
  $h_host=$syslog_data[$index][$HOST];
  $h_type=$syslog_data[$index][$TYPE];
  $h_msg=$syslog_data[$index][$MSG];
  #if (preg_match("/BGP-5-ADJCHANGE/",$h_type))
  #  echo "inserting index $index for host $h_host, message $h_msg, time".date("F d Y H:i:s",$syslog_data[$index][$TIME])."<P>";
  $ordHtmHash[$h_host][$h_type][$h_msg][count($ordHtmHash[$h_host][$h_type][$h_msg])]=$index;
  if (!$hostNames[$h_host])
    {
      $hostNames[$h_host]="";
      $rewriteHostNamesFile=1;
    }
}
 
 # write down the hostNames file ... updated with the new hosts
 if ($rewriteHostNamesFile)
   {
     asort($hostNames);
     $handle = fopen("hostNamesPhp.txt", "w");
     foreach ($hostNames as $h_host=>$name)
       fwrite($handle,"$h_host\t$name\r\n");
     fclose($handle);
   }

 # now the three dimensional array defined above can be parsed. Indexes to the big matrix will be parsed in an ordered time manner,
 # from the youngest to the oldest.
 $start_time=strtotime("now");
 echo "Examining read data to find matches ... ";

foreach ($ordHtmHash as $h_host=>$val)
{
  foreach ($ordHtmHash[$h_host] as $h_type=>$val2)
    {
      foreach ($ordHtmHash[$h_host][$h_type] as $h_msg=>$val3)
	{
	  for ($i=1;$i<count($ordHtmHash[$h_host][$h_type][$h_msg]);$i++)
	    {
	      $index1=$ordHtmHash[$h_host][$h_type][$h_msg][$i-1];
	      $index2=$ordHtmHash[$h_host][$h_type][$h_msg][$i];
	      $time1=$syslog_data[$index1][$TIME];
	      $time2=$syslog_data[$index2][$TIME];
	      if ($time1!=$time2 && $time1-$window<$time2)
		{
                  # the match has been found ... the two logs are the same and are nearer than the window time.
		  # if time1==time2, they are considered the same log (sent twice because of an error ...)
		  if (!$syslog_data[$index1][$MATCHED])
		    $host_list[$syslog_data[$index1][$HOST]][$syslog_data[$index1][$TYPE]][$syslog_data[$index1][$MSG]]++;
		  
		  if (!$syslog_data[$index2][$MATCHED])
		    $host_list[$syslog_data[$index2][$HOST]][$syslog_data[$index2][$TYPE]][$syslog_data[$index2][$MSG]]++;
		  
		  if (!$sys_type_list[$syslog_data[$index2][$TYPE]])
		    $sys_type_list[$syslog_data[$index2][$TYPE]]=$syslog_data[$index2][$TYPE];
		  
		  $syslog_data[$index1][$MATCHED]=1;
		  $syslog_data[$index2][$MATCHED]=1;
		}
	    }
	}
    }
}
echo " finished! Lasted time <B>".(strtotime("now")-$start_time)."s</B> <P>";

## here some application specific things could be marked to be printed out ...
## for example interfaces flapping in vipnet should be always printed out, even if it happens
## just one time during the day ...


 # here values are examined from the oldest to the newest
 ksort($host_list);
 ksort($sys_type_list);

 # Results should be printed out ordered basing on the following criteria ...
 #
 # - message sender and type of message (and this is the most important method to understand possible problems, should be printed as the first one)
 # - type of message and message sender

 echo "<TABLE cellpadding=20><TR><TD align=top>";
 echo "<TABLE border=9 cellpadding=5><TR bgcolor=FFFF66>
<TD align=center><h2><B>host</B></h2></TD>
<TD align=center><h2><B>syslog message types</B></h2></TD>
</h2></TR>";
 foreach ($host_list as $host=>$val)
{
  if ($hostNames[$host])
    $hostPrint=$hostNames[$host];
  else
    $hostPrint=$host;

  ksort($host_list[$host]);
  echo "<TR><TD><a href=\"$outputFileName#$host\">$hostPrint</a></TD><TD>
";
  foreach ($host_list[$host] as $type=>$messages)
    {
      $counter=0;
      foreach ($host_list[$host][$type] as $message=>$cnt)
	$counter+=$cnt;

      echo "<a href=\"$outputFileName#".$host."_".$type."\">".$type."</a> ";
      if ($counter>$cnt_thrsh)
	echo "<font color=red><B>($counter)</B></font><P>
";
      else
	echo "($counter)<P>
";
    }
  echo "</TD></TR>";
}
 echo "</TABLE></TD><TD align=top>";
 
 
 echo "<TABLE border=9 cellpadding=5><TR bgcolor=FFFF66>
<TD align=center><h2><B>syslog message type</B></h2></TD>
<TD align=center><h2><B>interested hosts</B></h2></TD>
</h2></TR>";
 foreach ($sys_type_list as $sys_type=>$val) 
{ 
  echo "<TR><TD><B>$sys_type</B></TD><TD>
";
  # now we should make a cycle on all hosts ... if the syslog message appears, print the host.
  foreach ($host_list as $host=>$val)
    {
      if ($hostNames[$host])
	$hostPrint=$hostNames[$host];
      else
	$hostPrint=$host;
      
      if ($host_list[$host][$sys_type])
	echo "<a href=\"$outputFileName#".$host."_".$sys_type."\">$hostPrint</a> ";
      
      $counter=0;
      foreach ($host_list[$host][$sys_type] as $message=>$cnt)
	$counter+=$cnt;
      
      if ($counter>$cnt_thrsh)
	echo "<font color=red><B>($counter)</B></font><P>
";
      else if ($counter)
	echo "($counter)<P>
";
    }
  echo "</TD></TR>";
}
 echo "</TABLE></TD></TR>";
 echo "</TABLE>"; # close the mother table ...
 echo "<P>&nbsp;<P>";

 $start_time=strtotime("now");
 # the vector ordered_indexes could be used also here to slow down the process.
 foreach ($host_list as $host=>$val)
{
  echo "<H2><a name=\"$host\">".$hostNames[$host]." ($host)</a></H2><P>";
  foreach ($host_list[$host] as $sys_type=>$val2)
    {
      # echo "examining message type ".$sys_type."<P>";
      foreach ($host_list[$host][$sys_type] as $message=>$counter)
	{
	  # here cycle using the $ordHtmHash array
	  echo "Message type <a name=\"".$host."_".$sys_type."\"><font color=red><B>$sys_type</B></font></a>, 
";
	  echo "message body: <B>$message</B><P>Recurred <B>$counter</B> times (";
	  for ($i=0;$i<count($ordHtmHash[$host][$sys_type][$message]);$i++)
	    echo date("F d Y H:i:s",$syslog_data[$ordHtmHash[$host][$sys_type][$message][$i]][$TIME]).",";
	  echo ")<P>";
	}
    }
}
 echo "Time to format data: <B>".(strtotime("now")-$start_time)."</B> seconds<P>";

?>
