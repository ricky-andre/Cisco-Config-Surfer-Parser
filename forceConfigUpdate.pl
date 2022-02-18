#!c:/Perl/bin/perl.exe
# This is the minimalist form script to demonstrate the use of 
# the cgi-lib.pl library
use Net::Telnet;
require "perlGlobal.h";

# open the text file and load into vector @hosts all the
# hostnames listed there in the file.

sub error { 
print " connection failed, router died or removed!\n"; 
}

open STUFF, "RoutersList.txt" or die "Unable to open the file!";

while (<STUFF>) {
  @temp=split /\t/,$_;
  if ($temp[0] && !(-e "./Configs/".$temp[0].".txt") && !(/^#/))
    {
      print "Inserted hostname $temp[0] ...\n";
      $hosts[scalar(@hosts)]=$temp[0];
    }
}

$network = new Net::Telnet ( Timeout => 10,
			    Errmode => sub{&error},
			    Prompt => $prompt);

print "logging into HPUX server ...";

$network->open("$hpux_ip_address");
$network->waitfor('/login: /');
$network->print("$hpux_user");
$network->waitfor('/Password: /');
$network->print("$hpux_pwd");
$network->waitfor('/\$/');

print " logged!\n";

for ($i=0;$i<scalar(@hosts);$i++)
{
  print "Retrieving configuration for ".$hosts[$i]." ... ";
  $network->print("telnet ".$hosts[$i]);
  $network->waitfor('/sername: /i');
  $network->print($user);
  $network->waitfor('/assword: /i');
  $network->cmd($passwd);
  $network->cmd("terminal length 0");
  open(CONF,">Configs/".$hosts[$i].".txt");
  print CONF $network->cmd("show run");
  print CONF $network->last_prompt();
  close(CONF);
  $network->print('exit');
  $network->waitfor('/\$/');
  print "done!\n";
}

$network->print('exit');
