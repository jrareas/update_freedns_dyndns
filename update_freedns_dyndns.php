#!/usr/bin/php

<?php
	$curr_dir=getcwd();
	$excludes = array();
	$credentials = array();
	#reading options
	$options = getopt("f:u:p:he:",array("file:","user:","passwd:","help","exclude:"));
	$array_keys = array_keys($options);

	if(in_array('h',$array_keys) || in_array('help',$array_keys)){
		help();
	}
	echo_log("Reading parameters...");
	foreach ($options as $key=>$value){
		switch ($key){
			case "f":
			case "file":
				$credentials = get_credentials($value);
				break;
			case "e":
			case "exclude":
				$excludes = get_excludes($value);
				break;
			case "u":
			case "user":
				$credentials['username'] = $value;
				break;
			case "passwd":
			case "p":
				$credentials['password'] = $value;
		}
	} 
	echo_log("done\nGetting Public IP...");
	$curr_ip = file_get_contents('http://ipecho.net/plain');	
	$curr_ip = trim($curr_ip);
	echo_log("Found IP $curr_ip\n");
	if(!isset($credentials['username']) || !isset($credentials['password'])){
		echo_log("Aborted!!! There is not enough parameters to proceed. Please, see help");
		exit;
	}
	$sha_str = sha1(trim($credentials['username']) . "|" . trim($credentials['password']));
	$url = "http://freedns.afraid.org/api/?action=getdyndns&sha=$sha_str";
	$content = file_get_contents($url);
	if(strpos($content,"ERROR")!==False){
		echo_log("Aborted!!! The server response is not as expected. Please, review your username and password\nServer Response : $content");
		exit;
	}
	$lines = explode("\n",$content);
	foreach($lines as $line){
		$date = date("F j, Y, g:i a");
		$fields = explode("|",$line);
		if(in_array($fields[0],$excludes)){
			$log = "$date - Subdomain ". $fields[0] . " marked as exluded.\n";
			echo_log($log);
			continue;
		}
		if($fields[1] != $curr_ip){
			$log = "$date - Updating subdomain " . $fields[0] . "...";
			$ret = file_get_contents($fields[2]);
			$log .= "done\n";
		}else{
			$log = "$date - Domain " . $fields[0] . " is up to date\n";
		}
		echo_log($log);
	}
	function echo_log($log){
		echo $log;
	}
	function tput($opt){
		system("tput $opt");
	}
	function get_credentials($file){
		$cred = explode(",",file_get_contents($file));
		return array('username'=>$cred[0],'password'=>$cred[1]);
	}
	function get_excludes($file){
		$cont = file_get_contents($file);
		return explode("\n",$cont);
	}
	function help(){
		echo "\n\n		FreeDns Updater\n\n";
		echo "Use this program to update Freedns dynamic DNS. The program will return all subdomains related with an specif user and \n";
                echo "update it if the IP associated to subdomain is different of the Public IP of the machine it is runnig. The credentias \n";
		echo "are provided by freedns. This program uses PHP as interpreter. Make sure PHP CLI is installed before run it.\n";
		echo "usages :update_freedns_dyndns.php [options]\n";
		echo "    Options:\n";
		tput('bold');	
		echo "	-f, --file : ";
		tput('sgr0') ; 
		echo "tell the script which credential file will be used. The format is one single line with the following content:\n";
		tput('bold'); 
		echo "       		username,password\n";
		
		echo " 	-u, --user : ";
		tput('sgr0') ;
		echo "the user name to connect with freedns. This parameter will not be read if -f or --file is passed.\n";
		tput('bold');
		echo "	-p,--passwd : ";
		tput('sgr0');
		echo "the password to connect with freedns. This parameter will not be read if -f or --file is passed.\n";
		tput('bold');
		echo "	-e, --exclude : ";
		tput("sgr0");
		echo "a file with a list of domains/subdomains should not be updated.\n"; 
		echo "    Examples:\n";
		echo "	update_freedns_dyndns.php -u username -p password -e exclude.txt\n";
 		echo "	update_freedns_dyndns.php --file credentials --exclude exclude.txt\n"; 
		echo "\n\n\n\n";
		exit;
	}


?>
