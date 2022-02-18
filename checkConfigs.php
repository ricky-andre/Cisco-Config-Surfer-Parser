<?php
 # with all the posted variables, retrieve all the configurations that must be examined
 # and put them in a vector.
 # $vipnet_filter can have one of the following values: "","GSR","VPE","VRR"
 # $regexp contains the regular expression that should be matched.
 include "phpGlobal.php";
 $USED=1;
 $NOTUSED=2;
 $DEBUG=1;
 
 $handle = @opendir($base_dir) or die("Unable to open this directory");
 
 # in file phpGlobal.php this function is defined since it is used by other scripts ...
 #$conf_list = getFiles($HTTP_POST_VARS["vipnet_filter"]);
 $conf_list = getFiles("");
 
 # examine the configurations one by one ...
 echo "<h1 align=center>Configuration consistency check</h1>";

 # the first step is reading all access-lists, route-maps, prefix-lists,
 # named access-lists, data about the configuration and so on.
 # during the second step, it is checked if the defined elements are used or not.
 # for access-lists and as-path lists:

 for ($z=0;$z<count($conf_list);$z++)
  {
    $routeMap=array();
    $accessList=array();
    $extcommList=array();
    $prefixList=array();
    $commList=array();
    $aspathList=array();
    $policyMap=array();
    $conf=array();
    $warning="";

    $line_num = 0;

    $handle = fopen($base_dir.$conf_list[$z], "r");
    if ($DEBUG>5)
      echo "Examining configuration <B>".$conf_list[$z]."</B> ... <P>";
    
    while ($line=fgets($handle))
      {
	$conf[$line_num]=$line;
	$line_num++;
	if (preg_match("/^route-map (.*) (permit|deny) \d+/",$line,$match))
	  {
	    if ($DEBUG>5)
	      echo "Found route-map $match[1], ";
	    $routeMap[trim($match[1])]=$NOTUSED;
	    continue;
	  }
	else if (preg_match("/^access-list (\d+) .*/",$line,$match) || 
		 preg_match("/^ip access-list extended (.*)/",$line,$match) ||
		 preg_match("/^ip access-list standard (.*)/",$line,$match))
	  {
	    $accessList[trim($match[1])]=$NOTUSED;
	    if ($DEBUG>5)
	      echo "Found access-list $match[1], ";
	    continue;
	  }
	else if (preg_match("/^ip extcommunity-list (\d+) .*/",$line,$match))
	  {
	    if ($DEBUG>5)
	      echo "Found extcomm list $match[1], ";
	    $extcommList[$match[1]]=$NOTUSED;
	    continue;
	  }
	else if (preg_match("/^ip prefix-list (.*) seq/",$line,$match))
	  {
	    if ($DEBUG>5)
	      echo "Found prefixList $match[1], ";
	    $prefixList[trim($match[1])]=$NOTUSED;
	    continue;
	  }
	else if (preg_match("/^ip as-path access-list (\d+) (permit|deny)/",$line,$match))
	  {
	    if ($DEBUG>5)
	      echo "Found as-path list $match[1], ";
	    $aspathList[$match[1]]=$NOTUSED;
	    continue;
	  }
	else if (preg_match("/^policy-map (.*)\s+/",$line,$match))
	  {
	    if ($DEBUG>5)
	      echo "Found policy-map $match[1], ";
	    $policyMap[trim($match[1])]=$NOTUSED;
	    continue;
	  }

	# also interface vlans and static routes should be read here ...
      }
    
    echo "<P>";
    # here the second scan should be done to check if the above identified elements are used or not.
    for ($i=0;$i<count($conf);$i++)
      {
	$match_found=0;
	
	# here some complex checks could be done ... for example the "preempt" keyword should be 
	# always configured on hsrp. On a couple of switches, for MSS and MGW control lans the 
	# root bridge should be fixed (for example for Vlan91 the first switch, for vlan 92 the
	# second switch).


	# this method could fail in case two different prefix-lists or route-maps have the same name ...
	foreach ($routeMap as $key=>$val)
	  {
	    if (preg_match("/ route-map $key\s/",$conf[$i]) ||
		preg_match("/ (im|ex)port map $key\s/",$conf[$i]))
	      {
		$routeMap[$key]=$USED;
		if ($DEBUG>5)
		  echo "Route-map $key used, ";
		$match_found=1;
		break;
	      }
	  }
	if ($match_found)
	  continue;
	
	# search for unused prefix lists
	foreach ($prefixList as $key=>$val)
	  {
	    if (!preg_match("/^ip prefix-list $key\s/",$conf[$i]) && 
		(preg_match("/prefix-list $key\s/",$conf[$i]) ||
		 preg_match("/list prefix $key\s/",$conf[$i])))
	      { 
		$prefixList[$key]=$USED; 
		if ($DEBUG>5)
		  echo "Prefix List $key used, ";
		$match_found=1;
		break; 
	      }
	  }
	if ($match_found)
	  continue;
	
	foreach ($policyMap as $key=>$val)
	  {
	    if (!$key)
	      continue;
	    
	    if (!preg_match("/^policy-map $key/",$conf[$i]) && 
		preg_match("/service-policy/",$conf[$i]))
	      { 
		# since policy-maps contain charachter "/" and character ".", they must be
		# replaced because they are used in regexp expressions ...
		$key2=preg_replace(array("/\//","/\./"),array("\/","\."),$key);
		if (preg_match("/service-policy(.*)$key2/",$conf[$i]))
		  {
		    $policyMap[$key]=$USED;
		    if ($DEBUG>5)
		      echo "Policy-map $key used, ";
		    $match_found=1;
		    break; 
		  }
	      }
	  }
	if ($match_found)
	  continue;
	
        # check if there is a reference to an acl. If so, check if it was found before ...
	# otherwise print an error because the acl is not defined !!!
	# The order of the lines is very important ...
	if (
	    preg_match("/ match ip address (.+)\s/",$conf[$i],$acl_match) ||                   # matched in route-maps
	    preg_match("/ ip access-group (.+) /",$conf[$i],$acl_match) ||                     # applied input or output to a vlan
	    preg_match("/ match access-group name (.+)\s/",$conf[$i],$acl_match) ||            # matches a named acl
	    preg_match("/ access-class (.+) /",$conf[$i],$acl_match) ||                        # 
	    preg_match("/^snmp-server community\s\w+\sR[O|W]\s(.+)/",$conf[$i],$acl_match) ||  # acl applied to limit snmp valid addresses
	    preg_match("/^snmp-server community.*view.*R[O|W]\s+(.+)/",$conf[$i],$acl_match) ||  # acl applied to limit snmp valid addresses and view
	    preg_match("/distribute-list (.+) (in|out)/",$conf[$i],$acl_match) ||              # applied inside ospf process "distribute-list 10 in"
	    preg_match("/access-group serve-only (.+)\s/",$conf[$i],$acl_match) ||             # applied to ntp clients ...
	    preg_match("/ip directed-broadcast (.+)\s/",$conf[$i],$acl_match) ||               # applied to PMP Hughes service
	    preg_match("/ access-group peer (.+)\s/",$conf[$i],$acl_match) ||                  # applied to ntp servers ...
	    preg_match("/access-group (\w+)?\s/",$conf[$i],$acl_match))                        # applied inside qos "... rate-limit access-group 10 320000 ..." 
	  {
	    if ($DEBUG>5)
	      echo "Found acl reference ".$acl_match[1]."<P>";
	    $acl_match[1]=trim($acl_match[1]);
	    
	    if ($accessList[$acl_match[1]]==$NOTUSED)
	      {
		$accessList[$acl_match[1]]=$USED;
		if ($DEBUG>5)
		  echo "access-list ".$acl_match[1]." reference found, used";
	      }
	    else if (!$accessList[$acl_match[1]]==$USED)
	      {
		$warning.="line \"<B>".$conf[$i]."</B>\": access-list ".$acl_match[1]." referenced but not defined!<P>";
	      }
	  }
	#foreach ($accessList as $key=>$val)
	#  {
	#    if (preg_match("/ match ip address $key\s/",$conf[$i]) ||        # matched in route-maps
	#	preg_match("/ ip access-group $key /",$conf[$i]) ||          # applied input or output to a vlan
	#	preg_match("/ match access-group name $key\s/",$conf[$i]) || # matches a named acl
	#	preg_match("/ access-class $key /",$conf[$i]) ||             # 
	#	preg_match("/^snmp-server community.*$key\s/",$conf[$i]) ||  # acl applied to limit snmp valid addresses
	#	preg_match("/-list $key (in|out)/",$conf[$i]) ||             # applied inside ospf process "distribute-list 10 in"
	#	preg_match("/access-group $key\s/",$conf[$i]) ||             # applied inside qos "... rate-limit access-group 10 320000 ..."
	#	preg_match("/access-group serve-only $key\s/",$conf[$i]) ||  # applied to ntp clients ...
	#	preg_match("/ip directed-broadcast $key\s/",$conf[$i]) ||    # applied to PMP Hughes service
	#	preg_match("/ access-group peer $key\s/",$conf[$i]))         # applied to ntp servers ...
	#      {
	#	$accessList[$key]=$USED;
	#	if ($DEBUG>5)
	#	  echo "access-list $key used, ";
	#	break;
	#      }
	#  }
	foreach ($aspathList as $key=>$val)
	  {
	    if (preg_match("/match as-path $key\s/",$conf[$i]))
	      { 
		$aspathList[$key]=$USED; 
		if ($DEBUG>5)
		  echo "as-path $key used, "; 
		break; 
	      }
	  }
      } # end of the second configuration analysis.

    foreach ($routeMap as $key=>$val)
      if ($val==$NOTUSED)
	$warning.="Route-map <B>$key</B> seems not to be used ...<P>";
    foreach ($prefixList as $key=>$val)
      if ($val==$NOTUSED)
	$warning.="Prefix-list <B>$key</B> seems not to be used ...<P>";
    foreach ($policyMap as $key=>$val)
      if ($val==$NOTUSED)
	$warning.="Policy-map <B>$key</B> seems not to be used ...<P>";
    foreach ($accessList as $key=>$val)
      if ($val==$NOTUSED)
	$warning.="Access-list <B>$key</B> seems not to be used ...<P>";
    foreach ($aspathList as $key=>$val)
      if ($val==$NOTUSED)
	$warning.="As-path <B>$key</B> seems not to be used ...<P>";
    
    if ($warning)
      {
	echo "Examining configuration <B>".$conf_list[$z]."</B> ... <P>";
	echo $warning;
	echo "<h3>&nbsp;</h3>";
      }
  } # end of configuration analysis 
 fclose($handle);
?>
