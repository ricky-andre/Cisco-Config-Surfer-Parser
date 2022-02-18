<?php
 # this script is used to retrieve some "stupid" information like for example
 # interfaces belonging to a certain vrf, bgp configuration regarding a certain vrf,
 # ospf configuration regarding a certain vrf, and so on ...
 
 # passed parameters MUST be:
 # PE name
 # information type (i.e. bgp, ospf, interface)
 # 
 # if necessary, vrf name

 # here are passed the configuration and the route-map that has to be checked.
 include "phpGlobal.php";
 # echo $_GET['conf'].$_GET['routemap'];

 $handle = fopen($base_dir.$_GET["conf"], "r");
 #echo "Examining file ".$base_dir.($_GET["conf"])."<P>";
 
 $inside=0;
 # unless there will be the need to retrieve more than one information at a time, 
 if ($_GET["intf"])
   {
     $interface=preg_replace("/\//","\/",$_GET["intf"]);
     #echo $interface."<P>";
     while ($line=fgets($handle))
      {
	 #echo "$line<P>";
	 if (preg_match("/^interface\s+$interface/",$line))
	   {
	     echo "<B>".$line."</B><P>
"; $inside=1; continue; 
	   }
	 if (preg_match("/^!/",$line))
	   { 
	     if ($inside)
	       echo "$line<P>" ; 
	     $inside=0;
	   }
	 
	 if ($inside)
	   {
	     #$line=str_replace(" ","&nbsp;",$line);
	     if (preg_match("/service-policy\s(input|output)\s(.*)\s/",$line,$match))
	       echo "&nbsp;&nbsp;service-policy ".$match[1]." <A href=\"./servicePolicyAnalyze.php?conf=".$_GET["conf"]."&servicepolicy=".$match[2]."\" target=\"_self\"> ".$match[2]."</a><P>
";
	     else
	       echo "&nbsp;&nbsp;".$line."<P>
";
	   }
       }
   }
 else if ($_GET["bgp"] && $_GET["vrf"])
   {
     $vrf=$_GET["vrf"];
     $inside_bgp=0;
     while ($line=fgets($handle))
       {
	 if (preg_match("/router bgp 30722/",$line))
	   $inside_bgp=1;
	 if  (!$inside_bgp)
	   continue;
	 
	 #echo $line."<P>";
	 if (preg_match("/\s+vrf $vrf/",$line))
	   { 
	     echo "router bgp 30722<P>
...<P>
&nbsp;<B>".$line."</B><P>
";
	     $inside=1;
	     continue; 
	   }
	 else if ($inside && (preg_match("/^!/",$line) || preg_match("/\s+vrf (\w+)/",$line)))
	   {
	     $inside=0;
	     $inside_bgp=0;
	     break;
	   }
	 else
	   {
	     $line=str_replace(" ","&nbsp;",$line);
	     if ($inside && preg_match("/(.*route-policy)&nbsp;(.*)(&nbsp;?.*)/",$line,$match))
	       echo "&nbsp;&nbsp;".$match[1]." <A href=\"./routePolicyAnalyze.php?conf=".$_GET["conf"]."&routepolicy=".$match[2]."\" target=\"_self\"> ".$match[2]."</a>".$match[3]."<P>
";
	     else if ($inside && preg_match("/(.*route-policy)&nbsp;(.*)(\s?.*)/",$line,$match))
	       echo "&nbsp;&nbsp;".$match[1]." <A href=\"./routePolicyAnalyze.php?conf=".$_GET["conf"]."&routepolicy=".$match[2]."\" target=\"_self\"> ".$match[2]."</a>".$match[3]."<P>
";
	     else if ($inside)
	       echo "&nbsp;&nbsp;".$line."<P>
";
	   }
       }
   }
 else if ($_GET["ospf"] && $_GET["vrf"])
   {
     echo $_GET["ospf"]."<P>";
     $vrf=$_GET["vrf"];
     while ($line=fgets($handle))
       {
	 if (preg_match("/router ospf ".$_GET["ospf"]."/",$line))
	   { 
	     echo "<B>".$line."</B><P>
"; 
	     $inside=1; 
	     continue; 
	   }
	 else if ($inside && preg_match("/^!/",$line))
	   { $inside=0; break; }
	 else if ($inside)
	   {
	     $line=str_replace(" ","&nbsp;",$line);
	     if (preg_match("/(.*route-policy)&nbsp;(\w+)(.*)/",$line,$match))
	       echo "&nbsp;&nbsp;".$match[1]." <A href=\"./routePolicyAnalyze.php?conf=".$_GET["conf"]."&routepolicy=".$match[2]."\" target=\"_self\"> ".$match[2]."</a>".$match[3]."<P>
";
	     else
	       echo "&nbsp;".$line."<P>
";
	   }
       }
   }
 else if ($_GET["static"] && $_GET["vrf"])
   {
     $vrf=$_GET["vrf"];
     $inside=0;
     while ($line=fgets($handle))
       {
	 if (!$inside && preg_match("/^router static/",$line))
	   { 
	     echo "<B>".$line."</B><P> &nbsp; ... <P>
"; 
	     $inside=1;
	     continue; 
	   }
	 else if ($inside==1 && preg_match("/^(\s+)vrf $vrf/",$line,$match))
	   {
	     echo "<B>".str_replace(" ","&nbsp;",$line)."</B><P>
";
	     $inside=2;
	   }
	 else if ($inside==2 && preg_match("/^$match[1]!/",$line))
	   {
	     $line=str_replace(" ","&nbsp;",$line);
	     echo $line."<P>
";
	     $inside=0;
	   }
	 else if ($inside==2)
	   {
	     $line=str_replace(" ","&nbsp;",$line);
	     echo $line."<P>
";
	   }
       }
   }
 else if ($_GET["standby"] && $_GET["vrf"])
   {
     # while reading the configuration, read the interfaces and to which vrf they belong ... then when reading the hsrp configs
     # only print the configurations related to interfaces belonging to that vrf
     $vrf=$_GET["vrf"];
     $inside_int=0;
     $inside=0;
     while ($line=fgets($handle))
       {
	 if (!$inside_int && preg_match("/^interface\s+(.*)$/",$line,$match))
	   {
	     $intf=$match[1];
	     $inside_int=1;
	   }
	 else if ($inside_int && preg_match("/^\s+vrf\s+$vrf/",$line))
	   $interface["$intf"]=1;
	 else if ($inside_int && preg_match("/^!/",$line))
	   $inside_int=0;
	 
	 if (preg_match("/^router hsrp/",$line))
	   {
	     echo "<B>".$line."</B><P> &nbsp; ... <P>
";
	     $inside=1;
	   }
	 else if ($inside && preg_match("/^!/",$line))
	   $inside=0;
	 else if ($inside)
	   { 
	     if (preg_match("/\s+interface\s+(.*)$/",$line,$match))
	       { 
		 #echo "Found if $match[1] inside hsrp<P>";
		 if ($interface["$match[1]"])
		   { 
		     do { echo str_replace(" ","&nbsp;",$line)."<P>"; $line=fgets($handle) ;}
		     while (!preg_match("/\s+!/",$line));
		     echo str_replace(" ","&nbsp;",$line)."<P>";
		   }
	       }
	   }
       }
   }
 fclose($handle);
?>
