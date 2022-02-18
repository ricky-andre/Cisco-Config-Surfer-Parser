<?php
 # here are passed the configuration and the route-map that has to be checked.
 include "phpGlobal.php";
 # echo $_GET['conf'].$_GET['routemap'];
 
 $handle = fopen($base_dir.$_GET["conf"], "r");
 #echo "Examining file ".$base_dir.$conf_list[$z]."<P>";
 $inside_rpl=0;
 $rplmap="";
 $prefix_set=array();
 $extcomm_list=array();
 $comm_list=array();
 $access_lists=array();

 while ($line=fgets($handle))
   {
     # results for regexp search
     $line_num++;
     
     # check if the configuration about a VRF is started ... this regexp is based
     # on the fact that the vrf definition starts (for example " ip vrf forwarding opnet" under an interface)
     # is not matched because of the starting space character.
     
     if (preg_match("/^route-policy ".$_GET["routepolicy"]."/",$line,$match))
       {
         # echo "Found route-policy $match[1]<P>";
	 $rplmap.="route-policy <B>".$_GET["routepolicy"]."</B><P>";
	 $inside_rpl=1;
	 continue;
       }
     else if ($inside_rpl && preg_match("/^!/",$line))
       {
	 $rplmap.=$line."<P>";
	 $inside_rpl=0;
       }
     else if ($inside_rpl)
       {
	 $rplmap.="&nbsp;&nbsp;".$line."<P>";
	 # check if there is a match on an access-list, an extcommunitylist, a standard community-list or maybe another route-policy !!
	 
       }
     
     # also when there is a match to a prefix-list, data should be stored
     if (preg_match("/^ipv\d access-list (\w+)/",$line,$match))
       {
	 do 
	   {
	     $rpl_ref_data["acl"][$match[1]].=$line."<P>"; 
	     $line=fgets($handle);
	   }
	 while (!preg_match("/!/",$line));
       }
     if (preg_match("/^extcommunity-set rt (\w+)/",$line,$match))
       {
	 do 
	   {
	     $rpl_ref_data["extcomm-set"][$match[1]].=$line."<P>"; 
	     $line=fgets($handle);
	   }
	 while (!preg_match("/!/",$line));
       }
     if (preg_match("/^prefix-set (\w+)/",$line,$match))
       {
	 do 
	   {
	     $rpl_ref_data["prefix-set"][$match[1]].=$line."<P>";
	     $line=fgets($handle);
	   }
	 while (!preg_match("/!/",$line));
       }
     if (preg_match("/^community-set (\w+)/",$line,$match))
       {
	 do 
	   {
	     $rpl_ref_data["comm-set"][$match[1]].=$line."<P>"; 
	     $line=fgets($handle);
	   }
	 while (!preg_match("/!/",$line));
       }
     if (preg_match("/^route-policy (\w+)/",$line,$match))
       {
	 do 
	   {
	     $rpl_ref_data["rpl"][$match[1]].=$line."<P>"; 
	     $line=fgets($handle);
	   }
	 while (!preg_match("/!/",$line));
       }
   }

 echo "<h2 align=center>route-policy \"".$_GET["routepolicy"]."\" on \"".preg_replace("/\.txt$/","",$_GET["conf"])."\"</h2>";
 echo "<TABLE border=1 cellspacing=5 cellpadding=20><TR><TD>";
 echo str_replace(" ","&nbsp;",$rplmap)."</TD><TD>";
 
 # now parse the route-policy, for every match about an extcommunity or an access-list, print out the details
 # about the extended community or about the access-list (the statement numbers could be written in bold)
 $rplmap_lines=preg_split("/<P>/",$rplmap);
 for ($i=0;$i<count($rplmap_lines);$i++)
   {
     if (preg_match("/destination in (\w+)/",$rplmap_lines[$i],$match))
       {
	 echo $rpl_ref_data["prefix-set"][$match[1]]."&nbsp;<P>";
       }
     else if (preg_match("/extcommunity rt matches-\w+\s+(\w+)/",$rplmap_lines[$i],$match))
       {
	 echo $rpl_ref_data["extcomm-set"][$match[1]]."&nbsp;<P>";
       }
     else if (preg_match("/community matches-\w+\s+(\w+)/",$rplmap_lines[$i],$match))
       {
	 echo $rpl_ref_data["comm-set"][$match[1]]."&nbsp;<P>";
       }
     else if (preg_match("/apply\s+(\w+)/",$rplmap_lines[$i],$match))
       {
	 echo $rpl_ref_data["rpl"][$match[1]]."&nbsp;<P>";
       }
   }
echo "$ref</TD></TR>
</TABLE>";
?>
