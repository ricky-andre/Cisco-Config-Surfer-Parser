<?php
$routerGroups = array ( "Network PE" => '[A-z][A-z]VPE\d\d\d',
			"Network P" => '[A-z][A-z]GSR\d\d\d',
			"Network VAR" => '[A-z][A-z]VAR\d\d\d',
			"Network RR" => '[A-z][A-z]VRR\d\d\d',
			"Opnet Access sup720" => 'Opnet Access sup720',
			"Opnet Access sup2" => 'Opnet Access sup2',
			"Dsl Access sup720" => '[A-z][A-z]OSW[1-2]00',
			"Old Opnet Core" => '[A-z][A-z]\dORB\d\d',
			"Ipcn" => '[A-z][A-z]\d(CRD|CRB|CNS)\d\d',
			"LanMobile" => 'MI(IBG|VRX|VAR101|VAR201)',
			"Vmr" => '[A-z][A-z]VMR\d\d\d',
			"Grx" => '[MI|RM]3OBG0[1-4]',
			"logs" => '.*\.log$');

 $base_dir="./Configs/";
 $routersListFile="RoutersList.txt";

 # the vector passed MUST contain key values belonging to the array routerGroups.
 # the key values are regular expressions that are matched with file names values.
 # the exception is about Opnet Access catalyst switches, sup2 or sup720 must be
 # selected, in this situation the file "routersList.txt" is opened and parsed
 # to select only the correct catalyst switches.
 function getFiles ($regexpFilter)
 {
   global $base_dir;
   global $routerGroups;
   global $routersListFile;
   
   # check for the presence of other filters different from the regexp. Insert
   # the files and remove the item from the regexp filter.
   for ($i=0;$i<count($regexpFilter);$i++)
   {
	if (preg_match("/Opnet Access/i",$regexpFilter[$i]))
	{
		$handle=fopen($routersListFile, "r");
		while ($line=fgets($handle))
		{
			$elem=preg_split("/\t/",$line);
			if (preg_match("/".$routerGroups[$regexpFilter[$i]]."/i",$elem[1]))
			{
                     		#echo "inserted file \"".$elem[0].".txt"."\"<P>";
				$filesList[count($filesList)]=$elem[0].".txt";
			}
		}
		fclose($handle);
	}
   }

   $handle = @opendir($base_dir) or die("Unable to open this directory");
   while (false!==($file = readdir($handle)))
     {
       if ($file!="." && $file!="..")
	 {
	   if (count($regexpFilter)==0)
	     {
                #echo "inserted file ".$filesList[count($filesList)]."<P>";
		return $filesList;
	     }
	   else 
	     {
	       for ($i=0;$i<count($regexpFilter);$i++)
		 if (preg_match("/".$routerGroups[$regexpFilter[$i]]."/i",$file))
		   {
                     #echo "inserted file ".$file."<P>";
		     $filesList[count($filesList)]=$file;
		     break;
		   }
	     }
	 }
     }
   closedir($handle);
   return $filesList;
 }

 function getHosts ($regexpFilter)
 {
   global $base_dir;
   global $routerGroups;
   $hostsList=array();
   
   #print_r($regexpFilter);

   $handle = @opendir($base_dir) or die("Unable to open this directory");
   
   while (false!==($file = readdir($handle)))
     {
       if ($file!="." && $file!="..")
	 {
	   $file=preg_replace("/\.txt$/","",$file);
	   
	   if (count($regexpFilter)==0)
	     {
	       $hostsList[count($hostsList)]=$file;
               #echo "inserted file ".$hostsList[count($hostsList)]."<P>";
	     }
	   else 
	     {
	       for ($i=0;$i<count($regexpFilter);$i++)
		 if (preg_match("/".$routerGroups[$regexpFilter[$i]]."/i",$file))
		   {
		     $hostsList[count($hostsList)]=$file;
		     # echo "inserted file ".$hostsList[count($hostsList)-1]." filter \"".$regexpFilter[$i]."\" regexp \"".$routerGroups[$regexpFilter[$i]]."\"<P>";
		     break;
		   }
	     }
	 }
     }
   closedir($handle);
   
   return $hostsList;
 }
?>
