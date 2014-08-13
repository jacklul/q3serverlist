#!/usr/bin/php
<?php
/*
 * Quake 3 Server List Script <http://github.com/jacklul/q3serverlist>
 * 
 * Copyright 2014 Jack'lul <www.jacklul.com>
 * 
 * This script is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License (LGPL) as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this script. If not, see <http://www.gnu.org/licenses/>.
 * 
 */

############################################# CONFIGURATION #############################################

# Location of GameQ.php
$GameQ_path = "";

# File used to store servers IPs
$dbfile = "serversdb";

# File used to store server list
$listfile = "serverlist";

# Master Server connection data
$masterserver_address = "monster.idsoftware.com";
$masterserver_port = 27950;
$masterserver_protocol = 68;

# Server filtering, set to desired values or leave empty to disable filtering
$filter_game = "";
$filter_gamename = "";

# Custom variables you wish to be also added for each server in server list file, useful when you want show more info on the server list
$custom_vars = Array("g_gametype");

# If you wish to use external config file specify it here, it will overwrite settings set in this file
$external_config = "";

############################################### FUNCTIONS ###############################################

function GetServers($master_server, $port, $protocol, $keywords = "empty full", $timeout = 1) {
	$socket = fsockopen('udp://'.$master_server, $port);
	stream_set_blocking($socket, 0);
	stream_set_timeout($socket, $timeout);
	
	fwrite($socket, str_repeat(chr(255),4).'getservers '.$protocol.' '.$keywords.''."\n");
	
	$time=time()+$timeout;
	$returned = "";
	while($time > time()) {
		$returned .= fgets($socket);
	}
	
	$servers = Array();
	for($i = 0; $i < strlen($returned)-11; $i++) {
		if($returned[$i] == "\\" && $returned[$i+7] == "\\") {
			$ip = ord($returned[$i+1]).".".ord($returned[$i+2]).".".ord($returned[$i+3]).".".ord($returned[$i+4]);
			$port = (ord($returned[$i+5])<<8) + ord($returned[$i+6]);

			array_push($servers, array($ip, $port));
		}
	}
	return $servers;
}

function CheckServer($data) {
	global $filter_game;
	global $filter_gamename;
	
	if(!isset($data['sv_hostname']))
		return;
	
    if ($filter_game != "" && isset($data['game']) && strtolower($data['game']) != strtolower($filter_game))
		return false;
		
    if ($filter_gamename != "" && isset($data['gamename']) && !preg_match("/".strtolower($filter_gamename)."/", strtolower($data['gamename'])))
		return false;
			
	return $data['gq_address'].":".$data['gq_port'];
}

function ScanServer($data) {
	global $custom_vars;
	
	if(!isset($data['sv_hostname']))
		return;
		
	$address = $data['gq_address'].":".$data['gq_port'];
	$hostname = $data['sv_hostname'];
	$currentmap = $data['mapname'];
	$playersonline = $data['gq_numplayers'];
	$password = $data['g_needpass'];
	
	if(isset($data['sv_privateClients']) && $data['sv_maxclients'] != 0 && $data['sv_privateClients'] != 0 && $data['sv_maxclients'] > $data['sv_privateClients'])
		$maxplayers = $data['sv_maxclients']-$data['sv_privateClients'];
	else 
		$maxplayers = $data['sv_maxclients'];
		
	$customvars = "";
	if(sizeof($custom_vars) > 0) {
		for($i = 0; $i < sizeof($custom_vars); $i++)
			if(isset($data[$custom_vars[$i]]))
				$customvars .= $data[$custom_vars[$i]]."\t";
	}
	
	return "$address\t$hostname\t$currentmap\t$playersonline\t$maxplayers\t$password\t$customvars\t\n";
}

function array_push_associative(&$arr) {
	$args = func_get_args();
	$ret = 0;
	foreach ($args as $arg) {
		if (is_array($arg)) 
			foreach ($arg as $key => $value) {
			   $arr[$key] = $value;
			   $ret++;
			}
		else
			$arr[$arg] = "";
	}
	return $ret;
}

############################################ FUN BEGINS HERE ############################################

if(!defined("STDIN"))
	die("This script cannot be run from the browser!\n");

if(sizeof($argv) > 2)
	die("Please specify only one parameter!\n");

$fullpath = dirname(__FILE__);

if($external_config != "" && file_exists($external_config))
	require_once($external_config);
elseif($external_config != "")
	die("Couldn't load config file: '$external_config'!\n");

if(file_exists($GameQ_path."/GameQ.php"))
	require_once($GameQ_path."/GameQ.php");
else
	die("Couldn't load GameQ.php!\n");

if(isset($argv[1]) && $argv[1] == "getservers") {
	fwrite(STDOUT, "Fetching server list from $masterserver_address:$masterserver_port...");
	$fetch = GetServers($masterserver_address, $masterserver_port, $masterserver_protocol);
	
	if($fetch)
	{
		$servers = NULL;

		if($fetch) {
			for($i=0; $i<sizeof($fetch); $i++) {
				$ip = $fetch[$i][0];
				$port = $fetch[$i][1];
				$servers .= $ip.":".$port.";";
			}
		}
		fwrite(STDOUT, " done\n");
		
		$serversarray = Array();
		$servers = explode(";", $servers);

		fwrite(STDOUT, "Reading server list...");
		
		for($i=0; $i<sizeof($servers); $i++) {
			$server = explode(":", $servers[$i]);
			
			if(isset($server[0]))
				$ip = $server[0];
			if(isset($server[1]))
				$port = $server[1];
			
			if($ip == "" || $ip == "0.0.0.0" || $port == "" || $port == "0")
				continue;
			
			array_push_associative($serversarray, array("$i" => array("quake3", $ip, $port)));
		}
		fwrite(STDOUT, " done, ".($i-1)." server(s)\n");
		
		$servers = NULL;
		if(file_exists($dbfile) && filesize($dbfile) > 0)
			$servers = file_get_contents($dbfile);
		
		$serverscount = 0;
		
		fwrite(STDOUT, "Getting info from servers...");
		$gq = new GameQ();
		$gq->addServers($serversarray);
		$gq->setOption('timeout', 1500);
		$gq->setFilter('normalise');	
		$results = $gq->requestData();
		fwrite(STDOUT, " done\n");

		fwrite(STDOUT, "Checking servers...");
		foreach($results as $id => $data) {
			if(CheckServer($data)) {
				$thisserver = $data['gq_address'].":".$data['gq_port'];
				if(!preg_match("/$thisserver/", $servers)) {
					$servers .= $thisserver.";";
					$serverscount++;
				}
			}
		}
		fwrite(STDOUT, " done\n");
		
		if($servers != "")
			file_put_contents($dbfile, $servers);
		
		if($serverscount>0)
			fwrite(STDOUT, "Found $serverscount new server(s).\n");
		else
			fwrite(STDOUT, "No new servers found.\n");
	}
	else 
		fwrite(STDOUT, " no reply received!\n");
}
elseif(isset($argv[1]) && $argv[1] == "refreshlist") {
	if(file_exists($dbfile) && filesize($dbfile) > 0) {
		fwrite(STDOUT, "Reading ServersDB...");
		$servers = file_get_contents($dbfile);
		$serversarray = Array();
		$servers = explode(";", $servers);

		for($i=0; $i < sizeof($servers); $i++) {
			$server = explode(":", $servers[$i]);
			
			if(isset($server[0]))
				$ip = $server[0];
			if(isset($server[1]))
				$port = $server[1];
			
			if($ip == "" || $ip == "0.0.0.0" || $port == "" || $port == "0")
				continue;

			array_push_associative($serversarray, array("$i" => array("quake3", $ip, $port)));
		}
		fwrite(STDOUT, " done, ".($i-1)." servers\n");
		
		$servers = NULL;
		$serverscount = 0;
		$playersonline = 0;
		
		fwrite(STDOUT, "Getting info from servers...");
		$gq = new GameQ();
		$gq->addServers($serversarray);
		$gq->setOption('timeout', 1500);
		$gq->setFilter('normalise');	
		$results = $gq->requestData();
		fwrite(STDOUT, " done\n");
		
		fwrite(STDOUT, "Scanning servers...");
		foreach($results as $id => $data) {
			$server = ScanServer($data);
		
			if($server != "") {
				$servers .= $server;
				$serverscount++;
			}
		}
		fwrite(STDOUT, " done\n");
		
		file_put_contents($listfile, $servers);
		
		fwrite(STDOUT, "Refreshed $serverscount server(s).\n");
	}
	else
		fwrite(STDOUT, "ServersDB is empty!\n");
}
elseif(isset($argv[1]) && $argv[1] == "cleanup") {
	if(file_exists($dbfile) && filesize($dbfile) > 0) {
		fwrite(STDOUT, "Reading ServersDB...");
		$serverslist = file_get_contents($dbfile);
		$serversarray = Array();
		$servers = explode(";", $serverslist);

		for($i=0; $i<sizeof($servers); $i++) {
			$server = explode(":", $servers[$i]);
			
			if(isset($server[0]))
				$ip = $server[0];
			if(isset($server[1]))
				$port = $server[1];
					
			if($ip == "0.0.0.0" || $ip == "" || $port == "" || $port == "0")
				continue;

			array_push_associative($serversarray, array("$i" => array("quake3", $ip, $port)));
		}
		fwrite(STDOUT, " done, ".($i-1)." servers\n");

		$servers = NULL;
		$numremoved = 0;

		fwrite(STDOUT, "Getting info from servers...");
		$gq = new GameQ();
		$gq->addServers($serversarray);
		$gq->setOption('timeout', 1500);
		$gq->setFilter('normalise');	
		$results = $gq->requestData();
		fwrite(STDOUT, " done\n");
		
		fwrite(STDOUT, "Checking servers...");
		foreach ($results as $id => $data) {
			if(!CheckServer($data))	{
				$thisserver = $data['gq_address'].":".$data['gq_port'];
				if(!preg_match("/$thisserver/", $servers)) {
					$serverslist = str_replace("$thisserver;", "", $serverslist);
					$numremoved++;
				}
			}
		}
		fwrite(STDOUT, " done\n");		
		
		if($serverslist != "" && $numremoved > 0) {	
			file_put_contents($dbfile, $serverslist);
			fwrite(STDOUT, "Removed $numremoved offline server(s).\n");
		}
		else
			fwrite(STDOUT, "Nothing to remove.\n");
	}
	else
		fwrite(STDOUT, "ServersDB is empty!\n");
}	
elseif(isset($argv[1]) && $argv[1] == "help")
	fwrite(STDOUT, "Usage: php ".basename(__FILE__)." [ACTION]\n\nAvailable actions:\n getservers - Get servers from Master Server\n refreshlist - Refresh all servers stored in DB file\n cleanup - Remove offline servers from DB file\n help - This, derp.\n\n");
else
	fwrite(STDOUT, "No action specified, try 'php ".basename(__FILE__)." help' for list of available actions!\n");

?>
