
The pictures should provide a good idea of what this set of scripts can do. We will also provide
a description through this text file.


In the first panel, you can select the group of routers over which you want to run the script.
This is a fexible and useful thing to improve the speed. Moreover, very often your searches
will be related to the same family of routers, for example:
- PE routers
- P routers
- RR routers
- access switches
- syslog text files
- ...

There is a php subroutine that retrieves the list of routers, given the selection choice. You
will need to customize this to your needs.


The "Regexp Search" feature, simply searches for a "regular expression" (you must know what this is)  
in all the selected group of files. Do not underestimate the power of this simple feature:
- you can search for static routes
- you can search for an ip address/subnet
- you can search for a specific alarm
- you can search for an extended community, a standard community
- you can search where an access-list with a certain name has been configured



The "Indented Search" feature is even MORE powerful and important: you can search inside "paragraphs"
of the router's configurations. For example, you can search inside interfaces descriptions, or 
inside bgp neighbors configuration, inside BGP/ISIS/OSPF configuration (which is DIFFERENT from searching
the occurrences of a regular expression !!!).
Suppose that you have some mandatory security requirements, for which a bgp password should ALWAYS
be configured (everyone should have such a policy ...), or the ttl-security should always be configured:
you will be able to have a list of the neighbors that do not respect this policy in a few seconds.
The same can be said if you have a specific subset of standard configuration for your core network
interfaces, or for your PE-CE interfaces: a few seconds and you will be able to find all the
forgotten/out of policy configurations.



The "Comparison utility" feature is useful to compare a certain route-map, route-policy, prefix-list,
prefix-set, community-set, acl and how it is configured on the selected group of routers.
You should have access-lists to control telnet/snmp access to your routers, and ideally they should
be the SAME on ALL your routers. You can see if they are the same, and what are the differences,
if any. The same could be done for route-policies, prefix-set filters or other configuration
parameters that by design should be EQUAL on a certain set of routers.



The "Global commands comparisons utility" is probably not so useful, also because something similar
could be done with the simple "Regexp search". In any case, you will probably have a certain set of
global configuration commands that should be there on the same family of routers. This feature
finds out this list of global commands, highlighting the differences if any arises.
For example, if "no http server" should be configured everywhere, you will be able to see if and
on which routers this command is configured.



The "Syslog analysis script" feature should be better run offline, on a day by day basis. This
script analyzez the syslog messages in a certain period of time, and represents them through
two tables (see the picture to understand how it works). The first table contains the list
of all your network elements, the syslog messages they have sent and the number of occurrences:
if you have any instability you should be able to see it.
The second table is the opposite: there are the syslog messages occurrences, and the nodes that
have sent the same syslog message: you will probably be interested in routing sysog messages
(routing flapping/instabilities are usually the worse ...)
This is NOT a real-time tool (it can't be), instabilities are not always a problem in the network,
but they should be detected and solved anyway.



The "route-target summary" analyzes all the PE's configurations, providing a full list and
summarized information about every PE. For each PE, there wil be a table with a row for every
configured vrf, with the used import/export route-targets, import/export maps if any, RD,
and all the configured interfaces. You will also find hyperlinks to jump immediately to the
interfaces configuration, and to the bgp/ospf/hsrp/static routes configurations (if present).
From the bgp/ospf/interface configuration, you can find OTHER hyperlinks related for example to
route-policies or qos service-policies, if any is configured.


















 
