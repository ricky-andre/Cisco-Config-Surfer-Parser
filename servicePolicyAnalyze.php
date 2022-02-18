<?php
 # here are passed the configuration and the route-map that has to be checked.
 include "phpGlobal.php";
 #echo $_GET['conf']." ".$_GET['servicepolicy'];

 $handle = fopen($base_dir.$_GET["conf"], "r");
 $inside_spmap=0;
 $spmap="";
 $class_maps=array();

 while ($line=fgets($handle))
   {
     # results for regexp search
     $line_num++;
     
     # check if the configuration about a VRF is started ... this regexp is based
     # on the fact that the vrf definition starts (for example " ip vrf forwarding opnet" under an interface)
     # is not matched because of the starting space character.
     
     if (preg_match("/^policy-map ".$_GET["servicepolicy"]."\s/",$line,$match))
       {
         # echo "Found route-policy $match[1]<P>";
	 $rplmap.="policy-map <B>".$_GET["servicepolicy"]."</B><P>";
	 $inside_spmap=1;
	 continue;
       }
     else if ($inside_spmap && preg_match("/^!/",$line))
       {
	 $rplmap.="$line<P>";
	 $inside_spmap=0;
       }
     else if ($inside_spmap)
       {
	 $rplmap.="&nbsp;&nbsp;".$line."<P>";
	 # check if there is a match on an access-list, an extcommunitylist, a standard community-list or maybe another route-policy !!
       }
     else if (preg_match("/^class-map (match-all|match-any)\s(.*)\s/",$line,$match))
       {
	 do 
	   {
	     $rpl_ref_data["class-map"][$match[2]].=$line."<P>";
	     $line=fgets($handle);
	   }
	 while (!preg_match("/!/",$line));
       }
     else if (preg_match("/^(ipv\d) access-list\s+(.*)\s/",$line,$match))
       {
	 #echo "inserting acl ".$match[1]."_".$match[2]."<P>";
	 do 
	   {
	     $acl_name=trim($match[1])."_".trim($match[2]);
	     $rpl_ref_data[$acl_name].=$line."<P>";
	     $line=fgets($handle);
	   }
	 while (!preg_match("/!/",$line));
	 #echo $rpl_ref_data[$match[1]."_".$match[2]]."<P>";
       }
   }

 echo "<h2 align=center>policy-map \"".$_GET["servicepolicy"]."\" on \"".preg_replace("/\.txt$/","",$_GET["conf"])."\"</h2>";
 echo "<TABLE border=1 cellspacing=5 cellpadding=20><TR><TD>";
 echo str_replace(" ","&nbsp;",$rplmap)."</TD><TD>";
 
 # now parse the policy-map, for every match about an extcommunity or an access-list, print out the details
 # about the extended community or about the access-list (the statement numbers could be written in bold)
 $rplmap_lines=preg_split("/<P>/",$rplmap);
 for ($i=0;$i<count($rplmap_lines);$i++)
   {
     if (preg_match("/class\s(.*)\s/",$rplmap_lines[$i],$match))
	 {
	   echo str_replace(" ","&nbsp;",$rpl_ref_data["class-map"][$match[1]])."&nbsp;<P>";
	   if (preg_match("/match access-group (ipv\d)\s+(.*)\s/",$rpl_ref_data["class-map"][$match[1]],$match2))
	     {
	       $acl_name=trim($match2[1])."_".trim($match2[2]);
	       if (!$printed_acls[$acl_name])
		 {
		   $acl_out.= $rpl_ref_data[$acl_name]."<P>&nbsp;<P>";
		   $printed_acls[$acl_name]=1;
		   #$acl_out.="class-map $match[1] acl $match2[2]";
		 }
	     }
	 }
   }
 if (!$acl_out)
   echo $acl_out="&nbsp";
 echo "$ref</TD><TD>$acl_out</TD></TR>
 </TABLE>";
?>
