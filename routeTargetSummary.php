<?php
 # with all the posted variables, retrieve all the configurations that must be examined
 # and put them in a vector.
 # $vipnet_filter can have one of the following values: "","GSR","VPE","VRR"
 # $regexp contains the regular expression that should be matched.
 include "phpGlobal.php";
 $handle = @opendir($base_dir) or die("Unable to open this directory");
 

 #echo "Network filter ".$HTTP_POST_VARS["Network_filter"]."<P>";
 #echo "Regexp to match ".$HTTP_POST_VARS["regexp"]."<P>";
 while (false!==($file = readdir($handle)))
   {
     if ($file!="." && $file!=".." && $file!="MIVPE019.txt" && $file!="MIVPE039.txt")
       {
	 if (preg_match("/VPE/",$file))
	   {
	     $conf_list[count($conf_list)]=$file;
             #echo "inserted file ".$conf_list[count($conf_list)-1]."<P>";
	   }
       }
   }
 closedir($handle);

 for ($z=0;$z<count($conf_list);$z++)
   {
     $PeNames[$z]=preg_replace("/\.txt$/","",$conf_list[$z]);
   }

 echo "<H1 align=center>Route target Analisys Results</H1><H3><B>";
 echo "<a href=\"routeTargetSummary.php#route-target-details\">1. Route-Targets Details</a><P>";
 echo "2. PE route-target details<P></B></H3>";
 for ($z=0;$z<count($conf_list);$z++)
   {
     echo "<a href=\"routeTargetSummary.php#PE-RT-$PeNames[$z]\">$PeNames[$z]</a>, ";
   }

 echo "<P><H3><a href=\"routeTargetSummary.php#vrf-details\">3. Vrf details</a></H3><P>";
 
 
 $vrf_pe_data=array();
 $vrf_list=array();
 
 for ($z=0;$z<count($conf_list);$z++)
   {
     $handle = fopen($base_dir.$conf_list[$z], "r");
     #echo "Examining file number ".$base_dir.$conf_list[$z]."<P>";
     $line_num = 0;
     $inside_vrf = 0;
     $inside_ospf = 0;
     $inside_bgp = 0;
     $inside_route_map=0;
     $route_map_stat=0;
     $inside_rstatic = 0;
     $inside_hsrp = 0;
     $rmap_data=array();
     $vre_pe_data[$z]=array();
     
     $rt_acl="";

     while ($line=fgets($handle))
       {
	 # results for regexp search
	 $line_num++;
	 
	 # check if the configuration about a VRF is started ... this regexp is based
	 # on the fact that the vrf definition starts (for example " ip vrf forwarding opnet" under an interface)
	 # is not matched because of the starting space character.
	 # the regexp matches also vrf IP-PBX_ACCESS that contains special carachters
	 if (preg_match("/^vrf (.*)\s/",$line,$match))
	   {
	     #echo "Found vrf \"$match[1]\" ...<P>";
	     $inside_vrf=trim($match[1]);
	     if (!$vrf_list[$inside_vrf])
	       $vrf_list[$inside_vrf]=1;
	     continue;
	   }
	 else if (preg_match("/^router bgp 30722/",$line))
	   {
	     $inside_bgp=1; 
	     continue;
	   }
	 else if (preg_match("/^router ospf (\d+)/",$line,$match))
	   {
	     #echo "Found ospf process \"$match[1]\" for vrf \"$match[2]\" ...<P>";
	     $inside_ospf=$match[1];
	     continue;
	   }
	 else if (preg_match("/^route-policy\s(.*)/",$line,$match))
	   {
	     #echo "Found route-map $match[1] statement $match[3]<P>";
	     $inside_route_policy=trim($match[1]);
	     continue;
	   }
	 else if (preg_match("/^interface\s+(.*)$/",$line,$match))
	   {
	     $interface=trim($match[1]);
	     # echo "Found interface \"$interface\"<P>";
	     continue;
	   }
	 else if (preg_match("/^router static(.*)$/",$line,$match))
	   {
	     $inside_rstatic=1;
	     continue;
	   }
	 else if (preg_match("/^router hsrp(.*)$/",$line,$match))
	   {
	     $inside_hsrp=1;
	     continue;
	   }
	 else if (preg_match("/^!/",$line))
	   {
	     $inside_vrf=0;
	     $inside_route_policy=0;
	     $route_map_stat=0;
	     $interface=0;
	     $inside_bgp=0;
	     $inside_ospf=0;
	     $inside_hsrp=0;
	     $inside_rstatic=0;
	     continue;
	   }
	 
	 # fill in all available data about vrf 
	 if ($inside_vrf)
	   {
	     if (preg_match("/import route-target/",$line))
	       {
		 $vrf_rt_mode="import";
	       }
	     else if (preg_match("/export route-target/",$line))
	       {
		 $vrf_rt_mode="export";
	       }
	     else if (preg_match("/(\d+:\d+)/",$line,$match))
	       {
		 if ($vrf_rt_mode=="export")
		   { 
		     $vrf_pe_data[$z][$inside_vrf]["export-default"][count($vrf_pe_data[$z][$inside_vrf]["export-default"])]=$match[1];
		     # the first key is the route-target value, the second the name of the vrf, the third is the vector
		     # of all the PEs exporting that rt in that Vrf.
		     $routeTarget[$match[1]][$inside_vrf][count($routeTarget[$match[1]][$inside_vrf])]=$PeNames[$z];
		     #echo "Found rt $match[1] for vrf $inside_vrf on $peNames[$z], vector length ".count($routeTarget[$match[1]][$inside_vrf])."<P>";
		   }
		 else
		   $vrf_pe_data[$z][$inside_vrf]["import"][$match[1]]="1";
	       }
	     else if (preg_match("/import route-policy\s+(.*)\s/",$line,$match))
	       {
		 $vrf_pe_data[$z][$inside_vrf]["import-route-pol"]=trim($match[1]);
		 #echo "found import-route-pol $match[1]<P>";
	       }
	     else if (preg_match("/export route-policy\s+(.*)\s/",$line,$match))
	       {
		 $vrf_pe_data[$z][$inside_vrf]["export-route-pol"]=trim($match[1]);
		 #echo "found export-route-pol $match[1]<P>";
	       }
	   }
	 else if ($inside_route_policy)
	   {
	     # if it's a route-map statement, append the line for every statement as an array ...
	     $rmap_data[$inside_route_policy]["$route_map_stat"].=trim($line).";;;";
	   }
	 else if ($inside_ospf)
	   {
	     if (preg_match("/^\s+vrf (.*)\s/",$line,$match))
	       { 
		 $vrf_pe_data[$z][trim($match[1])]["ospf"]=$inside_ospf;
		 #echo "found vrf $match[1] inside ospf process $inside_ospf<P>";
	       }
	   }
	 else if ($inside_hsrp)
	   {
	     #  this match works until the router hsrp commands are put AFTER the interfaces' configurations
	     if (preg_match("/\s+interface\s+(.*)$/",$line,$match))
	       {
		 $interface=preg_replace("/^GigabitEthernet/","Gi",(trim($match[1])));
		 # we should now cycle over ALL interfaces of all vrf ...
		 foreach ($vrf_pe_data[$z] as $key=>&$value)
		   for ($i=0;$i<count($value["interfaces"]);$i++)
		     {
		       if ($interface==$value["interfaces"][$i])
			 {
			   $vrf_pe_data[$z][$key]["hsrp"]=1;
			   #echo "Found interface hsrp interface \"$interface\" belonging to vrf $key<P>";
			   break;
			 }
		     }
	       }
	   }
	 else if ($inside_rstatic)
	   {
	     if (preg_match("/^\s+vrf (.*)\s/",$line,$match))
	       { 
		 $vrf_pe_data[$z][trim($match[1])]["static"]=1;
		 #echo "found vrf $match[1] inside router static<P>";
	       }
	   }
	 else if ($inside_bgp)
	   {
	     #echo $line."<P>";
	     if (preg_match("/^\s+vrf (.*)\s/",$line,$match))
	       {
		 $vrf_inside_bgp=trim($match[1]);
                 #echo "found inside bgp vrf $match[1]<P>";
	       }
	     else if (preg_match("/rd 30722:(\d+)/",$line,$match))
	       {
		 $vrf_pe_data[$z][$vrf_inside_bgp]["rd"]=$match[1];
                 #echo "found inside bgp vrf $vrf_inside_bgp rd value $match[1]<P>";
	       }
	   }
	 else if ($interface)
	   {
	     # check if the interface is assigned to a certain vrf, add the interface inside the vrf information of the PE ...
	     # interfaces are stored in the format "Gi7/2.801;;;Gi7/2.802;;;" and so on
	     if (preg_match("/^\s+vrf (.*)\s/",$line,$match))
	       {
		 
		 # echo "interface $interface belonging to vrf $match[1] on $PeNames[$z], inserted value ".preg_replace("/GigabitEthernet/","Gi",$interface)."<P>";
		 $vrf_pe_data[$z][trim($match[1])]["interfaces"][count($vrf_pe_data[$z][trim($match[1])]["interfaces"])]=preg_replace("/^GigabitEthernet/","Gi",$interface);
		 continue;
	       }
	   }
       } #end of while line ...
     #print_r($vrf_pe_data[$z]);
     fclose($handle);
     
     # parse the file to understand how many rt are exported by those vrf who use an export-route-policy
     # cycle on every vrf and if an export-route-policy has been defined, check on every statement for
     # a "set extcommunity rt ...", if it is found put all the other lines in the "match criteria"
     # value associated to that vrf and that route-target.
     if (is_array($vrf_pe_data[$z]))
       foreach ($vrf_pe_data[$z] as $key=>&$value)
	 {
	   if ($value["export-route-pol"])
	   {
	     $rmap_name=$value["export-route-pol"];
             #echo "PE num $z ($PeNames[$z]), vrf $key, parsing route-policy $rmap_name<P>";
	     
	     # now parse the route-map statements
	     if (is_array($rmap_data[$rmap_name]))
	       foreach ($rmap_data[$rmap_name] as $key2=>&$value2)
		 {
                   #echo $key2." valore ".$value2."<P>";
		   $lines=preg_split("/;;;/",$value2);
		   #echo "rpl ".print_r($lines)."<P>";
		   for ($i=0;$i<(count($lines));$i++)
		     {
		       if (preg_match("/set\s+extcommunity\s+rt.*\((\d+:\d+)\)/",$lines[$i],$match))
			 {
			   # echo "found $match[1]<P>";
			   $rt=$match[1];
			   $routeTarget[$rt][$key][count($routeTarget[$rt][$key])]=$PeNames[$z];
			   $vrf_pe_data[$z][$key]["export"]["$rt"]="1";
			   #print_r($vrf_pe_data[$z][$key]["export"])."<P>";
			 }
		     }
		 }
	   }
       }
   } # end of the cycle on all the PEs

 # here you can print arrays, variables to perform debugging at low level
 #print_r($routeTarget);
 #print_r($vrf_pe_data);
 #print_r($vrf_list);

 # Check for errors here ... for example if a PE imports a route-target that is not exported
 # by any other PE, print a red line with a reference to the error line.
 # The same checks are done below (printing the details for every PE) so that an exact match is found and a reference 
 # number to the error can be inserted.
 # This is a little bit unefficient, but there are not many other ways to print this on the top of the page ...
 $error_count=0;
 for ($z=0;$z<count($conf_list);$z++)
   {
     if (is_array($vrf_pe_data[$z]))
       {
	 foreach ($vrf_pe_data[$z] as $key=>&$value)
	   if (is_array($vrf_pe_data[$z][$key]["import"]))
	     {
	       foreach ($vrf_pe_data[$z][$key]["import"] as $key2=>&$value2)
		 if (!is_array($routeTarget[$key2]))
		   {
		     # $error_count++;
		     # echo "<h3><font color=red><B>Error detected on <a href=\"routeTargetSummary.php#PE-RT-$PeNames[$z]\">$PeNames[$z]'s</a> configuration !!!<B></font></h3><P>";
		   }
	     }
       }
   }
 $error_count=0;
 
 echo "<H1>&nbsp;</H1><H1 align=center>PE's RT import/export details</H1>";

 # printing out the details for every PE ...
 # the table could also include details about interfaces inside every vrf ...
 for ($z=0;$z<count($conf_list);$z++)
   {
     echo "<a name=\"PE-RT-$PeNames[$z]\"></a>
";
     echo "<TABLE border=1 cellpadding=4 align=center><TR bgcolor=#FFFF00><TD colspan=4><h2 align=center><a href=\"".$base_dir.$conf_list[$z]."\">$PeNames[$z]</a></h2></TD></TR>
";
     # for each vrf, print out the details ...
     if (is_array($vrf_pe_data[$z]))
       {
	 # $key is the name of the vrf while $value contains all the vrf data of the PE
	 foreach ($vrf_pe_data[$z] as $key=>&$value)
	   {
	     # the first column has to be large as the maximum number of imported/exported route-targets ...
	     $rowspan_value=count($vrf_pe_data[$z][$key]["import"])+count($vrf_pe_data[$z][$key]["export"])+3;
	     echo "<TR bgcolor=#CCFFFF><TD rowspan=".$rowspan_value.">vrf <B><a href=\"routeTargetSummary.php#vrfTable-$key\">$key</a></B><P>rd 30722:".$value["rd"]."<P>exp-map 
";
	     if ($value["export-route-pol"])
	       {
		 echo "<A href=\"./routePolicyAnalyze.php?conf=".$conf_list[$z]."&routepolicy=".$value["export-route-pol"]."\" target=\"_self\">".$value["export-route-pol"]."</a>";
	       }
	     echo "<P>imp-map ";
	     if ($value["import-route-pol"])
	       {
		 echo "<A href=\"./routePolicyAnalyze.php?conf=".$conf_list[$z]."&routepolicy=".$value["import-route-pol"]."\" target=\"_self\">".$value["import-route-pol"]."</a>";
	       }
	     # html links to retrive the bgp configuration and, if present, the ospf configuration
	     echo "<P>
<A href=\"./configRetrieve.php?conf=".$conf_list[$z]."&vrf=".$key."&bgp=1\" target=\"_self\">bgp config</a>";
	     if ($value["ospf"])
	       echo ", <A href=\"./configRetrieve.php?conf=".$conf_list[$z]."&vrf=".$key."&ospf=".$value["ospf"]."\" target=\"_self\">ospf ".$value["ospf"]." config</a> ";
	     
	     if ($value["hsrp"])
	       echo ", <A href=\"./configRetrieve.php?conf=".$conf_list[$z]."&vrf=".$key."&standby=1\" target=\"_self\"> hsrp config</a> ";

	     if ($value["static"])
	       echo ", <A href=\"./configRetrieve.php?conf=".$conf_list[$z]."&vrf=".$key."&static=1\" target=\"_self\"> static routes config</a> ";
	     
	     # here starts the second/third column that contains the values about exported/imported route-targets, and the fourth column about interfaces
	     echo "</TD><TD><B>exported route targets</B></TD><TD><B>match criteria/actions</B></TD><TD width=300 align=center rowspan=".$rowspan_value.">";
	     # here cycle over the interfaces, and make a reference to the php script that analyzes all the interfaces/bgp/ospf ...
	     $intf_string=array();
	     for ($i=0;$i<count($value["interfaces"]);$i++)
	       $intf_string[$i]="<A href=\"./configRetrieve.php?conf=".$conf_list[$z]."&vrf=".$key."&intf=".(preg_replace("/^Gi/","GigabitEthernet",$value["interfaces"][$i]))."\" target=\"_self\">".$value["interfaces"][$i]."</a>";
	     echo join(", ",$intf_string);
	     echo "</TD></TR>
";
	     
	     if (is_array($vrf_pe_data[$z][$key]["export"]))
	       foreach ($vrf_pe_data[$z][$key]["export"] as $key2=>&$value2)
		 echo "<TR><TD>$key2</TD><TD>(examine export rpl)</TD></TR>";
	     
	     echo "<TR><TD>";
	     for ($j=0;$j<count($vrf_pe_data[$z][$key]["export-default"]);$j++)
	       {
		 echo $vrf_pe_data[$z][$key]["export-default"][$j];
		 if ($j<(count($vrf_pe_data[$z][$key]["export-default"])-1))
		   echo ",&nbsp;";
	       }

	     echo " </TD><TD>(<B>default</B> exported rt)</TD></TR>";
	     
	     echo "<TR bgcolor=#CCFFFF><TD><B>imported route targets</B></TD><TD><B>action</B></TD></TR>
";             
	     foreach ($vrf_pe_data[$z][$key]["import"] as $key2=>&$value2)
	       {
                   # we suppose that every route-target is exported just by one Vrf ... so we just take the first vrf exporting that rt value
		   
		   # in the above array the key is the route-target value "x:y"
		   # the routeTarget array is organized in the following way
		   # $routeTarget[rt_value][vrf][array list of PEs]
		 if (count($routeTarget[$key2]))
		   {
		     foreach ($routeTarget[$key2] as $key3=>&$value3)
		       { $expby="(exported by vrf $key3)"; break; }
		     if ($vrf_pe_data[$z][$key]["import-route-pol"])
		       echo "<TR><TD><a href=\"routeTargetSummary.php#RT-$key2\">$key2</a> $expby</TD><TD>(examine import rpl)</TD></TR>
";
		     else
		       echo "<TR><TD><a href=\"routeTargetSummary.php#RT-$key2\">$key2</a> $expby</TD><TD>&nbsp;</TD></TR>
";
		   }
		 else
		   echo "<TR><TD>$key2 (not exported by any NETWORK PE)</TD><TD>&nbsp;</TD></TR>
";
	       } # end of foreach
	     #} #endif
	   }
       }
     echo "</TABLE><H1>&nbsp;</H1>";
   }

 # print out the route target details ...
 echo "<a name=\"route-target-details\"></a><H1>&nbsp;</H1><H1 align=center>Route target details</H1>
";
 ksort($routeTarget);
 foreach ($routeTarget as $key=>&$value)
 {
   echo "<a name=\"RT-$key\"></a>
";
   echo "<h3>Route-target <B>$key</B> </h3><P>";
   foreach ($routeTarget[$key] as $key2=>&$value2)
     {
       echo " for vrf <B>$key2</B> exported by ";
       for ($i=0;$i<count($routeTarget[$key][$key2]);$i++)
	 echo "<a href=\"routeTargetSummary.php#PE-RT-".$routeTarget[$key][$key2][$i]."\">".$routeTarget[$key][$key2][$i]."</a>, 
";
     }
   echo "<P>&nbsp;";
 }

# the trim below is needed to exactly match the anchor-name ...
ksort($vrf_list);
foreach ($vrf_list as $key=>$val)
  $vrfList[count($vrfList)]=$key;

# print out a table with the details about all vrf ... on every PE print out if they are defined and what route-map they use.
 echo "<a name=\"vrf-details\"></a><H1>&nbsp;</H1><H1 align=center>Vrf details</H1>
";
 # print out the list of all existing vrfs, and for each of them print out the exported route-targets (as a reference to the above
 # route-target list ...)
 for ($i=0;$i<count($vrfList);$i++)
   {
     echo "Vrf <a href=\"routeTargetSummary.php#vrfTable-$vrfList[$i]\">$vrfList[$i]</a> exports route-target(s): ";
     $temp=array();
     # Here we must cycle on all PEs searching for the vrf if it's present. If it is, we must consider
     # all the route-targets exported by the vrf, adding them to the array already with the reference to the routeTarget list ...
     for ($k=0;$k<count($vrf_pe_data);$k++)
       {
	 #echo "Examining PE $PeNames[$k] ... ";
	 if (is_array($vrf_pe_data[$k][$vrfList[$i]]))
	   {
	     #echo " vrf $vrfList[$i] exists here ... ";
	     if (is_array($vrf_pe_data[$k][$vrfList[$i]]["export"]))
	       foreach ($vrf_pe_data[$k][$vrfList[$i]]["export"] as $key=>&$value)
		 {
		   #echo " found exported rt $key ...";
		   if (!$temp[$key])
		     $temp[$key]="<a href=\"routeTargetSummary.php#RT-$key\">$key</a>";
		 }
	     for ($j=0;$j<count($vrf_pe_data[$k][$vrfList[$i]]["export-default"]);$j++)
	       if (!$temp[$vrf_pe_data[$k][$vrfList[$i]]["export-default"][$j]])
		 $temp[$vrf_pe_data[$k][$vrfList[$i]]["export-default"][$j]]="<a href=\"routeTargetSummary.php#RT-".$vrf_pe_data[$k][$vrfList[$i]]["export-default"][$j]."\">".$vrf_pe_data[$k][$vrfList[$i]]["export-default"][$j]."</a>";
	   }
       }
     ksort($temp);
     echo join(", ",$temp)."<P>
";
   }
 
 echo "<P>&nbsp;<P>Note that in the following table, \"none\" means that the vrf is defined but the import/export route-map is not. An empty cell means that the vrf is not defined at all on that PE.<P>
";


# number of vrfs for every table that is being printed out.
$NUM_VRF_PER_TABLE=4;
for ($i=0;$i<(count($vrfList)-(count($vrfList)%$NUM_VRF_PER_TABLE))/$NUM_VRF_PER_TABLE+1;$i++)
  {
    echo "<TABLE border=1 cellpadding=5 align=center>
<TR bgcolor=#FFFF00 rowspan=2><TD>PE \ VRF</TD>";
    for ($k=$i*$NUM_VRF_PER_TABLE;$k<count($vrfList) && $k<($i+1)*$NUM_VRF_PER_TABLE;$k++)
      {
	echo "<TD colspan=2><a name=\"vrfTable-".trim($vrfList[$k])."\"></a><B>$vrfList[$k]</B></TD>
";
      }
    echo "<TR bgcolor=#FFFF00><TD></TD>";
    for ($k=$i*$NUM_VRF_PER_TABLE;$k<count($vrfList) && $k<($i+1)*$NUM_VRF_PER_TABLE;$k++)
      {
	echo "<TD>import-route-pol</TD><TD>export map</TD>";
      }
    echo "</TR>
";
    # now print out the values for every PE ...
    for ($j=0;$j<count($conf_list);$j++)
      {
	echo "<TR bgcolor=#CCFFFF><TD><a href=\"routeTargetSummary.php#PE-RT-$PeNames[$j]\">$PeNames[$j]</a></TD>";
	for ($k=$i*$NUM_VRF_PER_TABLE;$k<count($vrfList) && $k<($i+1)*$NUM_VRF_PER_TABLE;$k++)
	  {
	    if (is_array($vrf_pe_data[$j][$vrfList[$k]]))
	      {
		if ($vrf_pe_data[$j][$vrfList[$k]]["import-route-pol"])
		  echo "<TD><A href=\"./routePolicyAnalyze.php?conf=".$PeNames[$j].".txt&routepolicy=".$vrf_pe_data[$j][$vrfList[$k]]["import-route-pol"]."\" target=\"_self\">".$vrf_pe_data[$j][$vrfList[$k]]["import-route-pol"]."</a></TD>
";
		else
		  echo "<TD>none</TD>";
		if ($vrf_pe_data[$j][$vrfList[$k]]["export-route-pol"])
		  echo "<TD><A href=\"./routePolicyAnalyze.php?conf=".$PeNames[$j].".txt&routepolicy=".$vrf_pe_data[$j][$vrfList[$k]]["export-route-pol"]."\" target=\"_self\">".$vrf_pe_data[$j][$vrfList[$k]]["export-route-pol"]."</a></TD>
";
		else
		  echo "<TD>none</TD>";
	      }
	    else
	      {
		echo "<TD>&nbsp;</TD><TD>&nbsp;</TD>";
	      }
	  }
	echo "</TR>
";
      }
    echo "</TR></TABLE><H1>&nbsp;</H1>";
  }
?>
