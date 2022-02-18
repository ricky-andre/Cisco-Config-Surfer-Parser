<h1 align=center>Network Configurations Analyzer</h1>
<?php
 include "phpGlobal.php";
?>

<FORM action=parseConfs.php method=post>
<h2>Network Elements filter</h2>
Select this filter if you want to speed up the search procedure.<P>
Only configurations about selected network elements will be examined.<P>
<SELECT NAME="vipnet_filter[]" multiple size="10">
<?php
 foreach ($routerGroups as $key=>$value)
   echo "<OPTION VALUE=\"$key\">$key</OPTION>
";
?>
</SELECT>

<h2>Regexp Search</h2>
This functions searches on all selected configurations for a match with the inserted regular expression.<P>
It can be also used with syslog files to search for specific syslog information<P>
Some examples follow to understand the way it works:<P>
<TABLE cellpadding=5>
<TR><TD>- search for extended comm lists matching rt 4004</TD><TD>&nbsp;</TD><TD>extcomm.*1257:4004</TD></TR>
<TR><TD>- search where route-map MAP_TEST is defined</TD><TD>&nbsp;</TD><TD>^route-map MAP_TEST (permit|deny)</TD></TR>
<TR><TD>- search for defined vrf TEST_VRF</TD><TD>&nbsp;</TD><TD>^vrf TEST_VRF</TD></TR>
<TR><TD>- search for defined static route with AD</TD><TD>&nbsp;</TD><TD>(\d+\.\d+\.\d+\.\d+|Vlan\d+)\s+\d+(\s+$|\s+tag)</TD></TR>
<TR><TD>- search for syslog messages on two hosts</TD><TD>&nbsp;</TD><TD>(10\.193\.135\.5|10\.192\.48\.250).*Ethernet(0.6|9.25)</TD></TR>
<TR><TD>- search for sonet alarms</TD><TD>&nbsp;</TD><TD>SONET-4-ALARM</TD></TR>
<TR><TD>- search for B1-B2-B3-SF-SD alarms</TD><TD>&nbsp;</TD><TD>(exceeds|below) threshold</TD></TR>
</TABLE>
<P>
Regexp: <INPUT maxLength="60" size="50" name="regexp"> Case insensitive <INPUT name="case" value="i" type=checkbox>
<INPUT type=submit name="regexp_butt" value="send data"><P>
Only <B>for syslog files</B>: start time <INPUT maxLength="10" size="10" name="sysRegStart" value="<?php echo date("Y-m-d",strtotime("now")-86400) ?>">
end time <INPUT maxLength="10" size="10" name="sysRegEnd" value="<?php echo date("Y-m-d",strtotime("now")-86400) ?>"> format AAAA-mm-dd (example 2007-01-28)

<h2>Indented Search</h2>
This function searches a regular expression and when it is matched, it searches for a pattern INSIDE that paragraph ...<P>
An OR/AND logic can be used, do not use them both in a mixed way (unpredictable results would occur). <P>
Spaces are ignored at the beginning and the end of the input text. For example:<P>
<TABLE cellpadding=5>
   <TR><TD>- search for interfaces belonging to vrf TEST_VRF_1, search: </TD><TD><B>vrf forwarding TEST_VRF_1</B></TD><TD></TD><TD>inside <B>^interface</B></TD></TR>
   <TR><TD>- TEST_VRF_1 interfaces with their description, search: </TD><TD><B>vrf forwarding TEST_VRF_1&&description</B></TD><TD></TD><TD>inside <B>^interface</B></TD></TR>
   <TR><TD>- TEST_VRF_1 or TEST_VRF_2 interfaces, search: </TD><TD><B>vrf forwarding TEST_VRF_1||vrf forwarding TEST_VRF_2</B></TD><TD></TD><TD>inside <B>^interface</B></TD></TR>
   <TR><TD>- TEST_VRF_1 interfaces with no service-policy, search inside: </TD><TD><B>vrf forwarding TEST_VRF_1</B></TD><TD>not inside: <B>service-policy</B></TD><TD>inside <B>^interface</B></TD></TR>
   <TR><TD>- CORE interfaces with no mtu set: </TD><TD></TD><TD>not inside: <B>vrf forwarding&&tag-switching mtu</B></TD><TD>inside <B>^interface</B></TD></TR>
   <TR><TD>- CORE interfaces configs with no mtu set: </TD><TD>.*</TD><TD>not inside: <B>vrf forwarding&&tag-switching mtu</B></TD><TD>inside <B>^interface</B></TD></TR>
</TABLE>
<P>
<TABLE cellpadding=5>
<TR><TD>Search what IS inside: </TD><TD><INPUT maxLength="120" size="60" name="indented-search"></TD><TD></TD></TR>
<TR><TD>Search what IS NOT inside: </TD><TD><INPUT maxLength="120" size="60" name="indented-search-notinside"></TD><TD></TD></TR>
<TR><TD>Inside:&nbsp;</TD><TD><INPUT maxLength="120" size="60" name="indented-inside"></TD><TD><INPUT type=submit name="indent_regexp" value="search"></TD></TR>
</TABLE>

<h2>Route Targets</h2>
This utility represents a summary of the way route-targets are imported/exported by every PE in the Network.<P>
<a href="./routeTargetSummary.php">route targets summary</a><P>

<h2>Comparisons utility</h2>
This functions compares access-lists, route-maps, snmp configurations, ntp configurations and so on. <P>
For example, select "access-list" and write "50" in the "name" field to compare access-list 50 on selected configurations.<P>
For some choices like snmp, aaa, logging, tacacs it is not necessary to complete the "name" field, that will be ignored.<P>
The next window will also show the differences between all different configurations.<P>
<SELECT NAME="comparison" multiple size="5">
<OPTION VALUE="access-list">access-list</OPTION>
<OPTION VALUE="route-policy">[XR]route-policy</OPTION>
<OPTION VALUE="prefix-set">[XR]prefix-set</OPTION>
<OPTION VALUE="extcomm-set">[XR]extcomm-set</OPTION>
<OPTION VALUE="comm-set">[XR]comm-set</OPTION>
<OPTION VALUE="xr-acl">[XR]access-list</OPTION>
<OPTION VALUE="extcomm">ext community</OPTION>
<OPTION VALUE="prefix-list">prefix-list</OPTION>
<OPTION VALUE="route-map">route-map</OPTION>
<OPTION VALUE="aaa">aaa</OPTION>
<OPTION VALUE="snmp">snmp</OPTION>
<OPTION VALUE="ntp">ntp</OPTION>
<OPTION VALUE="logging">logging</OPTION>
<OPTION VALUE="tacacs">tacacs</OPTION>
</SELECT> Name/number: <INPUT maxLength=60 size=50 name=comp_regexp>
<INPUT type=submit name="comp_button" value="send data"><P>
</FORM>

<h2>Global commands comparisons utility</h2>
Clicking on the following button, you will perform a consistency check on all global configuration commands, searching for<P>
possible errors or missing commands:
<FORM action=globalCommandsCheck.php method=post>
<SELECT NAME="vipnet_filter2[]" multiple size="5">
<?php
 foreach ($routerGroups as $key=>$value)
   echo "<OPTION VALUE=\"$key\">$key</OPTION>
";
?>
</SELECT><P>
<INPUT type=submit name="glb_comm_chk" value="glb comm check">
</FORM>

<FORM action=syslogAnalyzer.php method=post>
<h2>Syslog analysis script</h2>
This script reads ".log" files starting from the selected "start time" backwards for the number of specified "window "days,<P>
 and searches for recurrent logs/alarms. Time must be specified in the AAAA-mm-dd format (example 2007-01-28).<P>
start time <INPUT maxLength="10" size="10" name="sysStart" value="<?php echo date("Y-m-d",strtotime("now")-86400) ?>">
window(days)<INPUT maxLength="2" size="2" name="sysWindow" value=1><P>
<INPUT type=submit name="syslog" value="analyze syslog"><P>
 Since this script is much memory-consuming (a trade-off to make it fast ...), set a high window at your own risk ... <P>
with 5 log files for a total of 32 Mbytes, apache reaches a peak memory usage of 240 Mbytes ...<P>
This script is also run off-line to produce a file every day with a <B>time window of one day</B>. The directory containing<P>
these files can be viewed clicking <a href=syslogAnalyzedFiles>here</a>. Recurrent logs could be normal if maintenance activities<P>
were done, otherwise they should be carefully analyzed and possibly removed,<P>
because they could lead to network problems and out of services.<P>
</FORM>

<h2>Check for Zombies</h2>
This utility checks for unused route-maps, access-lists, extcomm lists, prefix lists ... <P>
In the future, it will check for static routes pointing to a next-hop that is not directly connected<P>
(but this could be wanted ...), and it also searches for configured ports without a description, Vlans with<P>
 a default name, Vlans that have all its ports in a disconnected/disabled state ...<P>
This utility could help in cleaning up old configurations that have not been removed for any reason.<P>
<a href="./zombiesInConfigs.html">Check configs</a><P>

<h2>Manage Configs</h2>
<a href="Configs">Configuration Files</a><P>
<a href="./checkNetworkCoreMissingConfigs.pl">Check for new routers in the Network ...</a><P>
