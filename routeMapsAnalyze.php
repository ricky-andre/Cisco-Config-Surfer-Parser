<?php
 # here are passed the configuration and the route-map that has to be checked.
 include "phpGlobal.php";
 # echo $_GET['conf'].$_GET['routemap'];
 
 $handle = fopen($base_dir.$_GET["conf"], "r");
 #echo "Examining file ".$base_dir.$conf_list[$z]."<P>";
 $inside_route_map=0;
 $rmap="";
 $prefix_list=array();
 $extcomm_list=array();
 $access_lists=array();

 while ($line=fgets($handle))
   {
     # results for regexp search
     $line_num++;
     
     # check if the configuration about a VRF is started ... this regexp is based
     # on the fact that the vrf definition starts (for example " ip vrf forwarding opnet" under an interface)
     # is not matched because of the starting space character.
     
     if (preg_match("/^route-map ".$_GET["routemap"]." (permit|deny) (\d+)/",$line,$match))
       {
         # echo "Found route-map $match[1] statement $match[3]<P>";
	 $rmap.="route-map <B>".$_GET["routemap"]."</B> $match[1] <B>$match[2]</B><P>";
	 $inside_route_map=1;
	 continue;
       }
     else if (preg_match("/!/",$line) || preg_match("/^route-map ".$_GET["routemap"]." (permit|deny) (\d+)/",$line))
       {
	 $inside_route_map=0;
       }
     else if ($inside_route_map)
       {
	 $rmap.="&nbsp;&nbsp;".$line."<P>";
	 # check if there is a match on an access-list, an extcommunitylist or anything else ...
       }
     
     # also when there is a match to a prefix-list, data should be stored
     if (preg_match("/^access-list (\d+) (.*)/",$line,$match))
       {
	 $access_lists[$match[1]][count($access_lists[$match[1]])]="access-list <B>".$match[1]."</B> $match[2]";
	 continue;
       }
     if (preg_match("/^ip extcommunity-list (\d+) (.*)/",$line,$match))
       {
	 $extcomm_list[$match[1]][count($extcomm_list[$match[1]])]="ip extcommunity-list <B>".$match[1]."</B> $match[2]";
	 continue;
       }
     if (preg_match("/^ip\s+community-list\s+(\d+)\s+(.*)/",$line,$match))
       {
	 $comm_list[$match[1]][count($comm_list[$match[1]])]="ip community-list <B>".$match[1]."</B> $match[2]";
	 continue;
       }
   }

 echo "<h2 align=center>Route-map \"".$_GET["routemap"]."\" on \"".preg_replace("/\.txt$/","",$_GET["conf"])."\"</h2>";
 echo "<TABLE border=1 cellspacing=5 cellpadding=20><TR><TD>";
 echo $rmap."</TD><TD>";
 
 # now parse the route-map, for every match about an extcommunity or an access-list, print out the details
 # about the extended community or about the access-list (the statement numbers could be written in bold)
 $rmap_lines=preg_split("/<P>/",$rmap);
 for ($i=0;$i<count($rmap_lines);$i++)
   {
     if (preg_match("/match extcommunity (\d+)/",$rmap_lines[$i],$match))
       {
	 #echo "match extcomm";
	 for ($j=0;$j<count($extcomm_list[$match[1]]);$j++)
	   $ref.=$extcomm_list[$match[1]][$j]."<P>";
	 unset($extcomm_list[$match[1]]);
       }
     else if (preg_match("/match ip address (\d+)/",$rmap_lines[$i],$match))
       {
	 #echo "match acl ";
	 for ($j=0;$j<count($access_lists[$match[1]]);$j++)
	   $ref.=$access_lists[$match[1]][$j]."<P>";
	 unset($access_lists[$match[1]]);
       }
     else if (preg_match("/match community (\d+)/",$rmap_lines[$i],$match))
       {
	 #echo "match community ";
	 for ($j=0;$j<count($comm_list[$match[1]]);$j++)
	   $ref.=$comm_list[$match[1]][$j]."<P>";
	 unset($comm_list[$match[1]]);
       }
   }
echo "$ref</TD></TR>
</TABLE>";
?>
