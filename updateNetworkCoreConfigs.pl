#!c:/Perl/bin/perl.exe
# This is the minimalist form script to demonstrate the use of 
# the cgi-lib.pl library
use Net::Telnet;
require "cgi-lib.pl";
include "perlGlobal.h";

# Read in all the variables set by the form
&ReadParse(*input);

# Print the header + html top
print &PrintHeader;
print &HtmlTop ("Performing requested job ...<P>&nbsp;<P>");

$vipnet = new Net::Telnet ( Timeout=>30,
			    Errmode=>'die',
			    Prompt => $prompt);

print "logging into HPUX server ...<P>";

$vipnet->open('10.192.10.7');
$vipnet->waitfor('/login: /');
$vipnet->print("$hpux_user");
$vipnet->waitfor('/Password: /');
$vipnet->print("$hpux_pwd");
$vipnet->waitfor('/\$/');

# here parse the posted variables and retrieve/update configurations
# of posted network elements.
if (scalar(@ARGV)==0)
{
  print "Retrieving all hosts in the vipnet ...<P>";

  $vipnet->print("telnet MiVpe031");
  $vipnet->waitfor('/Username: /i');
  $vipnet->print($user);
  $vipnet->waitfor('/Password: /i');
  $vipnet->cmd($passwd);
  $vipnet->cmd("terminal length 0");
  @hosts = $vipnet->cmd("show isis hostname");
  
  $vipnet->print('exit');
  $vipnet->waitfor('/\$/');
  
  print "Exited from telnet ...<P>";
  
  for ($i=0;$i<scalar(@hosts);$i++)
    {
      if ($hosts[$i] =~ /\d\d\d\d\.\d\d\d\d\.\d\d\d\d (.*\d\d\d)/)
	{
	  print "Found host \"$1\" ...<P>";
	  $net_elem[scalar(@net_elem)]=$1;
	}
    }
} else {
  @net_elem=@ARGV;
}

for ($i=0;$i<scalar(@net_elem);$i++)
{
  print "Retrieving configuration for ".$net_elem[$i]."... ";
  $vipnet->print("telnet ".$net_elem[$i]);
  $vipnet->waitfor('/Username: /i');
  $vipnet->print($user);
  $vipnet->waitfor('/Password: /i');
  $vipnet->cmd($passwd);
  $vipnet->cmd("terminal length 0");
  open(CONF,">Configs/".$net_elem[$i].".txt");
  print CONF $vipnet->cmd("show run");
  print CONF $vipnet->last_prompt();
  close(CONF);
  $vipnet->print('exit');
  $vipnet->waitfor('/\$/');
  print "done!<P>";
}

$vipnet->print('exit');
