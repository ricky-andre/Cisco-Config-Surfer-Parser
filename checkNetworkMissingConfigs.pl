#!c:/Strawberry_Perl/perl/bin/perl.exe
# This is the minimalist form script to demonstrate the use of 
# the cgi-lib.pl library
use Net::Telnet;
require "cgi-lib.pl";

# Read in all the variables set by the form
&ReadParse(*input);

# Print the header + html top
require "perlGlobal.h";

print &PrintHeader;
print &HtmlTop ("Performing requested job ...<P>&nbsp;<P>");

$network = new Net::Telnet ( Timeout=>30,
			    Errmode=>'die',
			    Prompt => '/MIVPE033#/');

print "logging into HPUX server ...<P>";

$network->open('10.192.10.7');
$network->waitfor('/login: /');
$network->print("$hpux_user");
$network->waitfor('/Password: /');
$network->print("$hpux_pwd");
$network->waitfor('/\$/');

print "Retrieving all hosts in the network ...<P>";

$network->print("telnet MiVpe033");
$network->waitfor('/Username: /i');
$network->print($user);
$network->waitfor('/Password: /i');
$network->cmd($passwd);
$network->cmd("terminal length 0");
@hosts = $network->cmd("show isis hostname");

$network->print('exit');
$network->waitfor('/\$/');

print "Exited from telnet ...<P>";

for ($i=0;$i<scalar(@hosts);$i++)
{
  #print "$hosts[$i]<P>";
  if ($hosts[$i] =~ /\d\d\d\d\.\d\d\d\d\.\d\d\d\d\s(.*\d\d\d)/)
    {
      if (!(-e "Configs/".$1.".txt"))
	{ 
         if (!($hosts[$i] =~ /BNG/))
	   {
	      print "Configuration for \"$1\" missing ...<P>";
	      $net_elem[scalar(@net_elem)]=$1;
	   }
	}
    }
}

for ($i=0;$i<scalar(@net_elem);$i++)
{
  # escape BNG devices
  next if ($net_elem[$i] =~ /BNG/);
  next if ($net_elem[$i] =~ /MIVPE019/);
  next if ($net_elem[$i] =~ /MIVPE039/);
  next if ($net_elem[$i] =~ /MIVPE011/);
  next if ($net_elem[$i] =~ /MIVPE031/);
  next if ($net_elem[$i] =~ /BOGSR100/);
  next if ($net_elem[$i] =~ /VEVPE013/);
  next if ($net_elem[$i] =~ /VEVPE014/);
  
  print "Retrieving configuration for ".$net_elem[$i]."... ";
  $network->print("telnet ".$net_elem[$i]);
  $network->waitfor('/Username: /i');
  $network->print($user);
  $network->waitfor('/Password: /i');

  $network->prompt("/".$net_elem[$i]."#/");

  $network->cmd($passwd);
  $network->cmd("terminal length 0");
  open(CONF,">Configs/".$net_elem[$i].".txt");
  print CONF $network->cmd("show run");
  print CONF $network->last_prompt();
  close(CONF);
  $network->print('exit');
  $network->waitfor('/\$/');
  print "done!<P>";
}

$network->print('exit');
