<?php

function check_ip_address ($address)
{
	if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)[ ]*\/[ ]*(\d+)/",$address,$matches))
	{
		for ($i=1;$i<5;$i++)
		{
			if ($matches[$i]<0 || $matches[$i]>255)
				return 0;
		}
		if ($matches[5]<0 || $matches[5]>32)
			return 0;

		return 1;
	}
	else if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)[ ]*$/",$address,$matches))
	{
		for ($i=1;$i<5;$i++)
		{
			if ($matches[$i]<0 || $matches[$i]>255)
				return 0;
		}
		if ($matches[5]<0 || $matches[5]>32)
			return 0;

		return 1;
	}
	else
	{
		return 0;
	}
}

# Some functions to retrieve info from the ip addres. The IP address is supposed to be written as above!
# This function could be used to check if two addresses belong to the same subnet. For example for:
# 10.1.2.30/24
# the following function will return the adddress 10.1.2.0.
# For the address 10.1.2.65/29, the following function returns 10.1.2.64. Since the two are different,
# they do not belong to the same subnet.
function get_ip_base_subnet ($address)
{
	if (!check_ip_address ($address))
		return 0;

	if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)[ ]*\/[ ]*(\d+)/",$address,$matches))
	{
		$bits_mask = $matches[5];
		
		# this should speed up the procedure ... often subnet masks are multiple of 8 bits ...
		# in this case it is easy to find out the base_subnet.
		if ($bits_mask%8 == 0)
		{
			switch ((int)($bits_mask/8))
			{
				case 0:
					return "0.0.0.0";
					break;
				case 1:
					return "$matches[1].0.0.0";
					break;

				case 2:
					return "$matches[1].$matches[2].0.0";
					break;
				case 3:
					return "$matches[1].$matches[2].$matches[3].0";
					break;
				case 4:
					return "$matches[1].$matches[2].$matches[3].$matches[4]";
					break;
			}
		}
		else
		{	
			$step = bcpow (2, 8 - $bits_mask%8);
			switch ((int)($bits_mask/8))
			{
				case 0:
					$result = $matches[1]-($matches[1]%$step);
					return "$result.0.0.0";
					break;

				case 1:
					$result = $matches[2]-($matches[2]%$step);
					return "$matches[1].$result.0.0";
					break;

				case 2:
					$result = $matches[3]-($matches[3]%$step);
					return "$matches[1].$matches[2].$result.0";
					break;
				case 3:
					$result = $matches[4]-($matches[4]%$step);
					return "$matches[1].$matches[2].$matches[3].$result";
					break;
				case 4:
					echo "This should never happen!";
					break;
				default:
					echo "This should never happen!";
					break;
			}
		}
	}
	else
		return 0;

	return 1;
}

# $address must be of the type x.y.z.t, from 255.255.255.240 return 28
# from 255.255.255.0 return 24 ... and so on ...
function get_num_bits_mask ($address)
{
  if (!check_ip_address ($address))
    return -1;
  
  $numBitsMask=0;
  
  if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)/",$address,$matches))
    {
      for ($i=1;$i<5;$i++)
	for ($j=0;$j<8;$j++)
	  { 
	    if (($matches[$i]>>$j) & 0x01)
	      { 
		$numBitsMask++;
		# echo "octet $i bit $j value ".($matches[$i]>>$j)." bcpow ".bcpow(2, $j)." ;;;;;;";
	      }
	  }
      
      return $numBitsMask;
    }
  else
    return -1;
}

# this function expects an ip address in the format "10.24.32.0/20" and 
# gives back the straight subnet mask, in the above example "255.255.240.0"
function get_ip_subnet_mask ($address)
{
  if (!check_ip_address ($address))
    return 0;
	
  if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)[ ]*\/[ ]*(\d+)/",$address,$matches))
    {
      $numBitsMask = 32;
      $power = 0;
      while ($numBitsMask >= 32 - $matches[5])
	{
	  $power += bcpow (2, $numBitsMask);
	  $numBitsMask--;
	}
      
      return (($power >> 24) & 0xff).".".(($power >> 16) & 0xff).".".(($power >> 8) & 0xff).".".($power & 0xff);
    }
  else
    return "255.255.255.255";
  
  return 1;
}

# this function expects an ip address in the format "10.24.32.0/24" and returns "10.24.32.0"
function get_ip_address ($address)
{
	if (!check_ip_address ($address))
		return 0;

	if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)[ ]*\/[ ]*(\d+)/",$address,$matches))
	{
		return "$matches[1].$matches[2].$matches[3].$matches[4]";
	}
	else if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)[ ]*$/",$address,$matches))
	{
		return "$matches[1].$matches[2].$matches[3].$matches[4]";
	}
	else
		return 0;
}

# this function accepts the mask in the format "255.255.240.0"
# and returns the inverse mask, in the above example "0.0.15.255"
function get_inverse_ip_mask ($address)
{
	if (!check_ip_address ($address))
		return 0;

	$mask = get_ip_subnet_mask ($address);
	
	if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)[ ]*$/",$mask,$matches))
	{
		return (255-$matches[1]).".".(255-$matches[2]).".".(255-$matches[3]).".".(255-$matches[4]);
	}
	else
		return 0;

	return 1;
}

# this function accepts the inverse mask in the format "0.0.15.255"
# and returns the straight mask, in the above example "255.255.240.0"
function get_straight_ip_mask ($address)
{
	if (!check_ip_address ($address))
		return 0;
	
	if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)[ ]*$/",$address,$matches))
	{
		return (255-$matches[1]).".".(255-$matches[2]).".".(255-$matches[3]).".".(255-$matches[4]);
	}
	else
		return 0;

	return 1;
}

# the ip address must be in the format "x.y.z.t" or "x.y.z.t/mask"
# 
class ipAddress
{
	var $address, $subnetMask, $numBitsMask;

	function ipAddress ()
	{
		$this->address="0.0.0.0";
		$this->subnetMask="255.255.255.255";
		$this->numBitsMask=32;
	}

	function setIp ($add)
	{
		if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)[ ]*\/[ ]*(\d+)$/",$add,$matches))
		{
			$this->address = "$matches[1].$matches[2].$matches[3].$matches[4]";
			$this->numBitsMask = $matches[5];
			$this->subnetMask = get_ip_subnet_mask($add);
			return 1;
		}
		else if (preg_match("/^\d+\.\d+\.\d+\.\d+$/",$add))
		{
			$this->address = $add;
			$this->subnetMask = "255.255.255.255";
			$this->numBitsMask = 32;
			return 1;
		}
		else
			echo "Wrong ip address!<P>";
		return 0;
	}

	function getIp ()
	{
		return $this->address;
	}
	
	function getMask ()
	{
	  return $this->subnetMask;
	} 

	function addIp ($add)
	{
	  if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/",$add,$matches))
	    {
	      preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/",$this->address,$matches2);
	      for ($i=4;$i>0;$i--)
		{
		  $matches2[$i]+=$matches[$i];
		  if ($matches2[$i]>255)
		    {
		      $matches2[$i]-=256;
		      if ($i>1)
			$matches2[$i-1]+=1;
		    }
		}
	      return $matches2[1].".".$matches2[2].".".$matches2[3].".".$matches2[4];
	    }		
	}
	
	function includes ($ip)
	  {
            # checks if the $ip is included into address ... if the ip has been passed without the mask,
	    # the mask is assumed to be "/32".
	    
	    if (!check_ip_address ($ip))
		return 0;
	    
	    if (!preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)\/(\d+)/",$ip,$matches))
	      {
		preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)/",$ip,$matches);
		$matches[5]=32;
	      }
	    
	    $mask = get_ip_subnet_mask ($ip);
	    #echo "mask for $ip is: $mask ";
	    
	    if (get_num_bits_mask($mask)<($this->numBitsMask))
	      return 0;
	    
            # this is address is 10.30.10.4/24, start_ip is 10.30.10.0
	    $base_subnet = get_ip_base_subnet($this->address."/".$this->numBitsMask);
	    $start_ip = new ipAddress();
	    $start_ip->setIp($base_subnet."/".$this->numBitsMask);
            # end_ip is now 10.30.10.255
	    $inverse_mask = get_inverse_ip_mask($this->address."/".$this->numBitsMask);
	    $end_ip = $start_ip->addIp($inverse_mask);
	    # now we must check if every octect is in the middle of the other.
	    
	    #echo "this ip ".$this->address." mask /".$this->numBitsMask." inv_mask $inverse_mask base_subnet $base_subnet start_ip ".$start_ip->getIp()." end_ip $end_ip<P>\n";
	    
	    preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)/",$ip,$matches);
	    preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/",$start_ip->getIp(),$matches2);
	    preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/",$end_ip,$matches3);
	    
	    #echo "matches ".print_r($matches);
	    #echo "matches2 ".print_r($matches2);
	    #echo "matches3 ".print_r($matches3);
	    
	    for ($i=1;$i<5;$i++)
	      if (!($matches2[$i]<=$matches[$i] && $matches[$i]<=$matches3[$i]))
		return 0;
	    
	    return 1;
	  }
}

php?>
