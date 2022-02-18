<?php
 # with all the posted variables, retrieve all the configurations that must be examined
 # and put them in a vector.
 # $vipnet_filter can have one of the following values: "","GSR","VPE","VRR"
 # $regexp contains the regular expression that should be matched.
 include "phpGlobal.php";
 date_default_timezone_set ('Europe/Paris');
 
 $handle = @opendir($base_dir) or die("Unable to open this directory");

 #echo "Network filter ".print_r($_POST["network_filter"])."<P>";
 #echo $_POST["regexp"]."<P>";
 
 #This substitution fixes a problem about the fact that "\" character is substituted by
 # "\\" by the post action done by the browser. It is still a mistery why so many 
 # occurrences of the "\" character are needed to do it in the right way ...
 $_POST["regexp"]=preg_replace("/\\\\\\\\/","\\",$_POST["regexp"]);
 # echo $_POST["regexp"]."<P>";
 
 $_POST["comp_regexp"]=trim($_POST["comp_regexp"]);
 $_POST["comp_regexp"]=preg_replace("/\\\\\\\\/","\\",$_POST["comp_regexp"]);
 
 # in file phpGlobal.php this function is defined since it is used by other scripts ...
 $conf_list=getFiles($_POST["network_filter"]);

 # print_r($conf_list);

 # when the "log" files need to be examined, the period must be examined so that files are considered from the oldest
 # to the newest ... so this code examines the file names and selects them in order by time.
 for($i=0;$i<count($_POST["network_filter"]);$i++)
   if (preg_match("/logs/",$_POST["network_filter"][$i]))
     {
       $startTime=strtotime($_POST["sysRegStart"]);
       $endTime=strtotime($_POST["sysRegEnd"]);
       
       if (!$startTime)
	 die("Invalid start time ".$_POST["sysRegStart"]."to search in log files!");
       if (!$endTime)
	 die("Invalid end time ".$_POST["sysRegEnd"]."to search in log files!");
       if ($endTime<$startTime)
	 die("Start time must be lower than the end time !<P>");
       
     # since syslog files are written "one day later", the time is increased by one day:
       $startTime+=86400;
       $endTime+=86400;
       
       $temp=$conf_list;
       unset($conf_list);
     # now the list of interesting files can be retrieved ...
     # echo "start time ".date("dmY",$startTime)." end time ".date("dmY",$endTime)." time date ".date("dmY",$time)."<P>";
       for ($time=$startTime;$time<=$endTime;$time+=86400)
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
     }

 # examine the configurations one by one ...
 echo "<h1 align=center>Configuration parsing results</h1>";

 # discriminate basing the decision on the button that has been pressed ...
 # the first one is the regexp search function ...
 if ($_POST["regexp_butt"])
   {
     for ($i=0;$i<count($conf_list);$i++)
       {
	 $handle = fopen($base_dir.$conf_list[$i], "r");
	 $line_num = 0;
	 
	 while ($line=fgets($handle))
	   {
             # results for regexp search
	     $line_num++;
	     if ($_POST["regexp"] && preg_match("/".$_POST["regexp"]."/".$_POST["case"],$line))
	       {
		 if (!$matched[$i])
		   {
		     $matched[$i]=1;
		     echo "<P>Match found on <B>".$conf_list[$i]."</B> (last updated <B>".date("F d Y H:i:s", filectime($base_dir.$conf_list[$i]))."</B>):<P>";
		   }
		 #echo "line <U>$line_num</U>: $line<P>";
		 echo "$line (line <U>$line_num</U>)<P>";
	       }
	   }
       }
     fclose($handle);
     
     echo "<P>&nbsp;<P>Match not found on the following configurations:<P>";
     for ($i=0;$i<count($conf_list);$i++)
       {
	 if (!$matched[$i])
	   echo $conf_list[$i]."<P>";
       }
   }
 # "indented-search-notinside"
 # this function searches for one or more sentences which MUST BE or  MUST NOT be present inside
 # an indented paragraph
 else if ($_POST["indent_regexp"])
   {
     # $_POST["indented-search"]
     # we should separate sentences in case a double reference "&&" is present, or a double "||" is present
     # the first one has an AND meaning, the second an OR meaning.
     # parenthesis are not managed, i.e. something like (A && B) || C, moreover this does not make sense
     # for the scope of this search function
     $indent_search=array();
     $indent_search_np=array();
     
     if (preg_match("/\|\|/",$_POST["indented-search"]))
       { 
	 $indent_search=preg_split("/\|\|/",$_POST["indented-search"]); 
	 #echo "or mode, ";
	 #print_r($indent_search);
	 $logic="OR";
       }
     else if (preg_match("/\&\&/",$_POST["indented-search"]))
       { 
	 $indent_search=preg_split("/\&\&/",$_POST["indented-search"]);
	 #echo "AND mode, ";
	 #print_r($indent_search);
	 $logic="AND";
       }
     else
       {
	 if (trim($_POST["indented-search"]))
	   {
	     $indent_search[0]=$_POST["indented-search"];
	     $logic="AND";
	   }
	 else $logic="NONE";
       }
     
     if (preg_match("/\|\|/",$_POST["indented-search-notinside"]))
       { 
	 $indent_search_np=preg_split("/\|\|/",$_POST["indented-search-notinside"]); 
	 $logic_np="OR";
       }
     else if (preg_match("/\&\&/",$_POST["indented-search-notinside"]))
       { 
	 $indent_search_np=preg_split("/\&\&/",$_POST["indented-search-notinside"]);
	 $logic_np="AND";
       }
     else
       {
	 if (trim($_POST["indented-search-notinside"]))
	   {
	     $indent_search_np[0]=$_POST["indented-search-notinside"];
	     $logic_np="AND";
	   }
	 else $logic_np="NONE";
       }
     
     for ($z=0;$z<count($conf_list);$z++)
       {
	 $handle = fopen($base_dir.$conf_list[$z], "r");
	 $line_num = 0;
	 $ind_length=0;
	 $inside=0;
	 
	 echo "Reading <B>$conf_list[$z]</B> ...<P>";
	 while ($line=fgets($handle))
	   {
             # results for regexp search
	     $line_num++;
	     if (preg_match("/".$_POST["indented-inside"]."/",$line) && $inside==0)
	       {
		 #echo "match found on line $line<P>";
		 preg_match("/^(\s+)/",$line,$match);
		 $ind_length=strlen($match[0]);
		 $inside=1;
		 $temp.="$line<P>";
		 #echo "len before string $ind_length <P>";
		 continue;
	       }
	     else if ($inside)
	       {
		 # follow hereafter the checks about what lines are found and are indented
		 unset($match);
		 preg_match("/^(\s+)/",$line,$match);
		 $line_ind_len=strlen($match[0]);
		 
		 if ($line_ind_len>$ind_length)
		   {
		     for ($j=0;$j<count($indent_search);$j++)
		       if (preg_match("/".$indent_search[$j]."/",$line))
			 {
                           #echo "Found a match inside for <B>".$_POST["indented-search"]."</B> length ".strlen($match[0])."<P>";
			   $temp.="$line<P>";
			   if (!$match_store[$j])
			     { $match_found++; $match_store[$j]++; }
			   break;
			 }
		     
		     for ($j=0;$j<count($indent_search_np);$j++)
		       if (preg_match("/".$indent_search_np[$j]."/",$line))
			   {
			     if (!$match_store_np[$j])
			       $match_found_np++;
			     #echo "Found a match inside \"$line\" for \"<B>".$indent_search_np[$j]."</B>\" logic $logic_np occurrences $match_found_np su max ".count($indent_search_np)."<P>";
			     break;
			   }
		   }
		 else if ($line_ind_len<=$ind_length)
		   {
		     #echo "present length $line_ind_len is less or equal than  $ind_length in line \"$line\"<P>";
		     
		     # the logic to understand if the negative search is successful, is quite complex ...
		     if ($logic_np=="AND")
		      { 
			if (!$match_found_np)
			  $ENTER=1;
		      }
		     else if ($logic_np=="OR" && $match_found_np<count($indent_search_np))
		       $ENTER=1;
		     else if ($logic_np=="NONE")
		       $ENTER=1;
		     
		     if ($ENTER)
		       {
			 if ($match_found>=count($indent_search) && $logic=="AND")
			   echo preg_replace("/ /","&nbsp;",$temp)."<P>&nbsp;<P>";
			 else if ($match_found && $logic=="OR")
			   echo preg_replace("/ /","&nbsp;",$temp)."<P>&nbsp;<P>";
			 else if ($logic=="NONE")
			   echo preg_replace("/ /","&nbsp;",$temp)."<P>&nbsp;<P>";
		       }
		     
		     $match_found=0;
		     $match_found_np=0;
		     $ind_length=0;
		     $inside=0;
		     $ENTER=0;
		     unset($temp);
		     unset($match_store);
		     unset($match_store_np);
		     continue;
		   }
	       } # else if ($inside)
	   } # while ($line=fgets($handle))
	 fclose($handle);
       }
   }
 # this script is entered in case someone has pressed the button under the "Comparison" utility
 # here we're going to work on December 13th to upgrade things for XR
 else if ($_POST["comp_button"])
   {
     # in this vector there is the data that has changed
     $data=array();
     
     for ($z=0;$z<count($conf_list);$z++)
       {
	 $temp=array();
	 unset($temp);
	 
	 $handle = fopen($base_dir.$conf_list[$z], "r");
	 $line_num = 0;
	 $inside_extacl=0;
	 $inside_rmap=0;
	 # we use just ONE variable to understand if we are inside a prefix-set, route-policy, extcomm-set and so on
	 $inside_xr=0;

	 while ($line=fgets($handle))
	   {
             # results for regexp search
	     $line_num++;
	     
             # fill in the configuration info ... these can be used for access-list, route-map comparisons ...
             # an if statement here is placed to handle the different cases ...
	     if ($_POST["comparison"]=="access-list")
	       {
                 # check if the "name" field is regular and not empty. If it is, check if the line contains the
	         # acl that is being searched.
		 if (preg_match("/^\d+$/",$_POST["comp_regexp"]) && preg_match("/^access-list ".$_POST["comp_regexp"]." (permit|deny)/",$line))
		   {
		     # echo "Inserting line $line ... temp elements ".count($temp)."<P>";
		     $temp[count($temp)]=$line;
		   }
		 else if (preg_match("/^[A-z]+$/",$_POST["comp_regexp"]))
		   {
		     if (preg_match("/^ip access-list extended ".$_POST["comp_regexp"]."/",$line))
		       $inside_extacl=1;
		     else if (preg_match("/^ip access-list extended/",$line) || preg_match("/^!/",$line))
		       $inside_extacl=0;
		   }
		 if ($inside_extacl)
		   {
		     if (!preg_match("/remark/",$line))
		       $temp[count($temp)]=$line; 
		   }
	       }
	     else if ($_POST["comparison"]=="extcomm")
	       {
                 # check if the "name" field is regular and not empty. If it is, check if the line contains the
	         # acl that is being searched.
		 if (preg_match("/^\d+$/",$_POST["comp_regexp"]) && preg_match("/^ip extcommunity-list ".$_POST["comp_regexp"]."/",$line))
		   {
		     $temp[count($temp)]=$line;
		     # echo "Inserting line $line ... temp elements ".count($temp)." is_array ".is_array($temp)."<P>";
		   }
	       }
	     # statements added when switched to XR software
	     # once we enter into the statement, we should read lines until the statement is finished
	     # and then we should continue ... or we should use a common flag which states if we are
	     # inside a statement or not
	     else if ($_POST["comparison"]=="route-policy")
	       {
		 if (preg_match("/^route-policy ".$_POST["comp_regexp"]."/",$line))
		   {
		     $temp[count($temp)]=trim($line);
		     $inside_xr=1;
		   }
		 else if ($inside_xr)
		   $temp[count($temp)]=trim($line);
	       }
	     else if ($_POST["comparison"]=="xr-acl")
	       {
		 if (preg_match("/^ipv\d access-list ".$_POST["comp_regexp"]."/",$line))
		   {
		     $temp[count($temp)]=trim($line);
		     $inside_xr=1;
		   }
		 else if ($inside_xr)
		   $temp[count($temp)]=trim($line);
	       }
	     else if ($_POST["comparison"]=="prefix-set")
	       {
		 if (preg_match("/^prefix-set ".$_POST["comp_regexp"]."/",$line))
		   {
		     $temp[count($temp)]=trim($line);
		     $inside_xr=1;
		   }
		 else if ($inside_xr)
		   $temp[count($temp)]=trim($line);
	       }
	     else if ($_POST["comparison"]=="extcomm-set")
	       {
		 if (preg_match("/^extcommunity-set rt ".$_POST["comp_regexp"]."/",$line))
		   {
		     $temp[count($temp)]=trim($line);
		     $inside_xr=1;
		   }
		 else if ($inside_xr)
		   $temp[count($temp)]=trim($line);
	       }
	     else if ($_POST["comparison"]=="comm-set")
	       {
		 if (preg_match("/^community-set ".$_POST["comp_regexp"]."/",$line))
		   {
		     $temp[count($temp)]=trim($line);
		     $inside_xr=1;
		   }
		 else if ($inside_xr)
		   $temp[count($temp)]=trim($line);
	       }
	     # ordinary IOS statements ...
	     else if ($_POST["comparison"]=="snmp")
	       {
		 if (preg_match("/^snmp/",$line))
		   {
		     if (!preg_match("/location/",$line) && !preg_match("/engineID local/",$line) && !preg_match("/contact/",$line))
		       $temp[count($temp)]=trim($line);
		   }
	       }
	     else if ($_POST["comparison"]=="prefix-list")
	       {
		 if (preg_match("/^ip prefix-list ".$_POST["comp_regexp"]." /",$line))
		   {
		     $temp[count($temp)]=trim($line);
		   }
	       }
	     else if ($_POST["comparison"]=="aaa")
	       {
		 if (preg_match("/^aaa/",$line))
		   $temp[count($temp)]=trim($line);
	       }
	     else if ($_POST["comparison"]=="logging")
	       {
		 if (preg_match("/^logging/",$line))
		   $temp[count($temp)]=trim($line);
	       }
	     else if ($_POST["comparison"]=="ntp")
	       {
		 if (preg_match("/^ntp/",$line))
		     {
		       if (!preg_match("/clock-period/",$line) && !preg_match("/ntp authentication-key \d+ md5/",$line))
			 $temp[count($temp)]=trim($line);
		     }
	       }
	     else if ($_POST["comparison"]=="tacacs")
	       {
		 if (preg_match("/^tacacs/",$line) || preg_match("/^ip tacacs/",$line))
		     {
		       if (!preg_match("/key 7/",$line))
			 $temp[count($temp)]=trim($line);
		     }
	       }
	     else if ($_POST["comparison"]=="route-map")
	       {
		 # This is quite difficult to be handled ... a vector containing the statements could be
		 # built up if the route-map name is correct. Then the comparison with the other route-maps
		 # is done statement by statement (in the same order but WITHOUT considering the statement
		 # number). All the lines belonging to the same route-map could be inserted one after the other.		 
		 if (preg_match("/^route-map ".$_POST["comp_regexp"]." (permit|deny)/",$line))
		   { 
		     $inside_rmap=1;
		     $temp[0].=$line."<P>";
		   }
		 else if (preg_match("/^!/",$line))
		   { 
		     $inside_rmap=0;
		   }
		 else if ($inside_rmap)
		   {
		     $temp[0].="&nbsp;&nbsp;".$line."<P>";
		   }
	       }
	     if (preg_match("/^!/",$line))
	       $inside_xr=0;
	   } # end of while loop, configuration totally examined.
	 fclose($handle);
	 
	 if ($_POST["comparison"]=="route-map")
	   {
	     # in this case comparisons must be handled slightly differently than in all the other cases.
	     $found=0;
	     for ($i=0;$i<count($data);$i++)
	       {
		 if ($data[$i]==$temp[0])
		   {
		     $data_routers[$i].=$conf_list[$z].";";
		     $found=1;
		     break;
		   }
	       }
	     if (!$found)
	       {
		 # echo "Inserted route-map $temp[0]<P>";
		 $data[count($data)]=$temp[0];
		 $data_routers[count($data_routers)]=$conf_list[$z].";";
	       }
	   }
	 else
	   {
             # now compare the vector temp with all the vectors in data array. If a match is found, add the router's name
             # to the list pointing to that element ...
	     $found=0;
	     for ($i=0;$i<count($data);$i++)
	       {
		 # echo "comparing $temp[0] and ".$data[$i]." ...<P>";
		 #print_r($data[$i]); 
		 #print_r($temp);
		 #print_r(array_diff($temp,$data[$i]));
		 if (is_array($temp) && is_array($data[$i]) && 
		     (count(array_diff_assoc($temp,$data[$i]))==0 && count(array_diff_assoc($data[$i],$temp))==0) ||
		     (count($temp)==0 && count($data[$i])==0))
		   {
		     #echo "exact match found between $temp[0] and $data[$i][0], data_routers position $i ".$data_routers[$i];
		     $data_routers[$i].=$conf_list[$z].";";
		     $found=1;
		     break;
		   }
	       }
	     if (!$found)
	       {
		 #echo "match not found, inserting ".$conf_list[$z]." at position ".count($data)." value $temp[0]<P>";
		 $data[count($data)]=$temp;
		 $data_routers[count($data_routers)]=$conf_list[$z].";";
	       }
	   }
	 
	 #echo "Examined file <B>".$conf_list[$z]."</B><P>";
       } # end of for loop examining all configuration
     
     #echo "Found ".count($data)." different data values ".$data[0][0][0];
     
     # it's time to print out the results ...
     if ($_POST["comparison"]=="route-map")
       {
         # in this case results-printing must be handled slightly differently than in all the other cases.
	 for ($i=0;$i<count($data);$i++)
	   {
	     echo "<P>The following router(s): <B>".$data_routers[$i]."</B> have(has) the following configuration:<P>
".$data[$i];
	   }
       }
     else
       {
	 # the comparisons between the configurations can be done here ... for every configuration, at the end
	 # print out something like \"click here to see the differences with configurationN.
	 for ($i=0;$i<count($data);$i++)
	   { 
	     echo "<a name=config".($i+1)."></a>
<h2>Configuration ".($i+1)."</h2><P>";
	     if (count($data[$i])==0)
	       echo "<P>Didn't find a match for the following routers: <B>".$data_routers[$i]."</B><P>";
	     else
	       { 
		 echo "<P>The following router(s): <B>".$data_routers[$i]."</B> have(has) the following configuration:<P>
";
		 for ($j=0;$j<count($data[$i]);$j++)
		   echo $data[$i][$j]."<P>";
		 echo "<P>";
		 for ($k=$i+1;$k<count($data);$k++)
		   echo "click <a href=\"parseConfs.php#diff-conf".($i+1)."-conf".($k+1)."\">here</a> to see differences with <a href=\"parseConfs.php#config".($k+1)."\">configuration ".($k+1)."</a><P>
";
	       }
	   }
	 
	 echo "<P><H1 align=center>Configuration differences</H1>";
         # now print out the comparison results for every couple of configurations
	 for ($i=0;$i<count($data);$i++)
	   {
	     if (count($data[$i])==0)
	       { echo "Configuration $i skipped because there is no configuration!<P>&nbsp;<P>"; continue; }
	     for ($k=$i+1;$k<count($data);$k++)
	       {
		 if (!is_array($data[$i]))
		   $data[$i]=array();
		 if (!is_array($data[$k]))
		   $data[$k]=array();
		 $diff=array_diff($data[$i],$data[$k]);
		 $diff2=array_diff($data[$k],$data[$i]);
#print_r($diff)."<P>";
#print_r($diff2)."<P>";
		 echo "<a name=\"diff-conf".($i+1)."-conf".($k+1)."\"></a><P>
Differences between <a href=\"parseConfs.php#config".($i+1)."\">configuration ".($i+1)."</a> and <a href=\"parseConfs.php#config".($k+1)."\">configuration ".($k+1)."</a>:<P>";
		 if (count($diff))
		   {
		     echo "Lines <U>not present</U> on config ".($k+1)." and <U>present</U> on config ".($i+1).":<P>
";
		     ksort($diff);
		     foreach ($diff as $key=>&$value)
		       echo $value." (line ".($key+1).")<P>
";
		   }
		 if (count($diff2))
		   {
		     echo "Lines <U>not present</U> on config ".($i+1)." and <U>present</U> on config ".($k+1).":<P>
";
		     ksort($diff2);
		     foreach ($diff2 as $key=>&$value)
		       echo $value." (line ".($key+1).")<P>
";
		   }
		 echo "&nbsp;<P>";
	       } # end of for $k cycle
	   } # end of for $i cycle
       } # end of "else" statement
   } # end of "else if" statement
?>
