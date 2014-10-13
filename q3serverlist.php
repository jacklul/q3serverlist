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

# This is to secure access to the script when running on webspace, if not set everyone can execute this script
$secret = "";

# If you wish to use external config file specify it here, it will overwrite settings set in this file
$external_config = "";

############################################### FUNCTIONS ###############################################

function GetServers($master_server, $port, $protocol, $keywords = "empty full", $timeout = 1) {
	if($master_server != "" && $port != 0 && $protocol != 0 && $socket = fsockopen('udp://'.$master_server, $port))
	{
		stream_set_blocking($socket, 0);
		stream_set_timeout($socket, $timeout);
		
		fwrite($socket, str_repeat(chr(255),4).'getservers '.$protocol.' '.$keywords.''."\n");
		
		$time=time()+$timeout;
		$returned = "";
		while($time > time()) {
			$returned .= fgets($socket);
		}
		
		$servers = Array();
		for($i = 0; $i < strlen($returned)-10; $i++) {
			if($returned[$i] == "\\" && $returned[$i+7] == "\\") {
				$ip = ord($returned[$i+1]).".".ord($returned[$i+2]).".".ord($returned[$i+3]).".".ord($returned[$i+4]);
				$port = (ord($returned[$i+5])<<8) + ord($returned[$i+6]);

				array_push($servers, array($ip, $port));
			}
		}
		return $servers;
	}
	else
		return false;
}

function GetServerInfo($ip, $port, $timeout = 1)
{
	if($port != 0 && $ip != 0 && $socket = fsockopen('udp://'.$ip, $port))
	{
		socket_set_timeout ($socket, $timeout);
		fwrite ($socket, "\xFF\xFF\xFF\xFF\x02getstatus\x0a\x00");
		$data = fread ($socket, 10000);
		fclose ($socket);
		
		if($data)
		{
			$ret = NULL;
			$vars = explode("\x0a",$data);
			if(isset($vars[1]))
				$ret = explode("\\", substr($vars[1], 1, strlen($vars[1])));
			for ($i = 0; $i <= count($ret); $i = $i + 2) {
				$list[@$ret[$i]] = @$ret[$i + 1];
			}
			array_pop($list);
			
			$list['address'] = $ip;
			$list['port'] = $port;

			$players = array();
								
			for($i = 2; $i < sizeof($vars); $i++)
			{
				$infos = explode(' ', $vars[$i], 3);
				
				if(isset($infos[2]))
				{
					$name = explode('"', $infos[2]); 
					
					if(isset($name[1]))
						$name = $name[1];
					else
						$name = "";
				}
				else
					$name = "";
					
				if(isset($infos[0]))
					$score = $infos[0];
				else
					$score = 0;
				
				if(isset($infos[1]))
					$ping = $infos[1];
				else
					$ping = 999;
				
				array_push($players, array('score' => $score, 'ping' => $ping, 'name' => $name));
			}
			
			array_pop($players);
			
			if(isset($players[0]['ping']))
				$list['numplayers'] = sizeof($players);
			else
				$list['numplayers'] = 0;

			$infos = array();
			$infos = $list;
			$infos['players'] = $players;

			return $infos;
		}
		else
			return false;
	}
	else
		return false;
}

function CheckServer($data) {
	global $filter_game;
	global $filter_gamename;
	
	if(!isset($data['sv_hostname']))
		return false;
	
    if ($filter_game != "" && !isset($data['game']) || (isset($data['game']) && strtolower($data['game']) != strtolower($filter_game)))
		return false;
		
    if ($filter_gamename != "" && !isset($data['gamename']) || (isset($data['gamename']) && !preg_match("/".strtolower($filter_gamename)."/", strtolower($data['gamename']))))
		return false;
			
	return true;
}

function ScanServer($data) {
	global $custom_vars;
	
	if(!isset($data['sv_hostname']))
		return false;
		
	$address = $data['address'].":".$data['port'];
	$hostname = isset($data['sv_hostname']) ? $data['sv_hostname'] : NULL;
	$currentmap = isset($data['mapname']) ? $data['mapname'] : NULL;
	$playersonline = isset($data['numplayers']) ? $data['numplayers'] : NULL;
	$password = isset($data['g_needpass']) ? $data['g_needpass'] : NULL;
	
	if(isset($data['sv_privateClients']) && isset($data['sv_maxclients']) && $data['sv_privateClients'] != 0 && $data['sv_maxclients'] > $data['sv_privateClients'])
		$maxplayers = $data['sv_maxclients']-$data['sv_privateClients'];
	elseif(isset($data['sv_maxclients']))
		$maxplayers = $data['sv_maxclients'];
	else
		$maxplayers = 0;

	$customvars = "";
	if(sizeof($custom_vars) > 0) {
		for($i = 0; $i < sizeof($custom_vars); $i++)
			if(isset($data[$custom_vars[$i]]))
				$customvars .= $data[$custom_vars[$i]]."\t";
			else
				$customvars .= "-\t";
	}
	
	return "$address\t$hostname\t$currentmap\t$playersonline\t$maxplayers\t$password\t$customvars\t\n";
}

function printout($message)
{
	if(!defined("STDIN"))
		print(str_replace("\n", "<br>\n", $message));
	else
		fwrite(STDOUT, $message);
}

############################################ FUN BEGINS HERE ############################################

if($external_config != "" && file_exists($external_config))
	require_once($external_config);
elseif($external_config != "")
	die("Couldn't load config file: '$external_config'!\n");

if(defined("STDIN") && sizeof($argv) > 2)
	die("Please specify only one parameter!\n");
	
if(!defined("STDIN") && isset($_GET['action']) && $_GET['secret'] == $secret && isset($_GET['action']))
	$argv[1] = $_GET['action'];

if(isset($argv[1]) && $argv[1] == "getservers") {
	printout("Fetching server list from $masterserver_address:$masterserver_port...");
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
		printout(" done\n");
		
		$serversarray = Array();
		$servers = explode(";", $servers);

		for($i=0; $i<sizeof($servers); $i++) {
			$server = explode(":", $servers[$i]);
			
			if(isset($server[0]))
				$ip = $server[0];
			if(isset($server[1]))
				$port = $server[1];
			
			if($ip == "" || $ip == "0.0.0.0" || $port == "" || $port == "0")
				continue;
			
			array_push($serversarray, array($ip, $port));
		}		
		
		if(file_exists($dbfile) && filesize($dbfile) > 0)
			$servers = file_get_contents($dbfile);
		else
			$servers = NULL;

		$serverscount = 0;
		
		printout("Scanning ".($i-1)." servers");
		for($i = 0; $i < sizeof($serversarray); $i++)
		{
			$data = GetServerInfo($serversarray[$i][0], $serversarray[$i][1]);
			$thisserver = $serversarray[$i][0].":".$serversarray[$i][1];
			if(CheckServer($data)) {
				if(!preg_match("/$thisserver/", $servers)) {
					$servers .= $thisserver.";";
					$serverscount++;
				}
			}
			printout(".");
		}
		printout(" done\n");
		
		if($servers != "")
			file_put_contents($dbfile, $servers);
		
		if($serverscount>0)
			printout("Found $serverscount new server(s).\n");
		else
			printout("No new servers found.\n");
	}
	else 
		printout(" no reply received!\n");
}
elseif(isset($argv[1]) && $argv[1] == "refreshlist") {
	if(file_exists($dbfile) && filesize($dbfile) > 0) {
		$servers = explode(";", file_get_contents($dbfile));
		$serversarray = Array();

		for($i=0; $i < sizeof($servers); $i++) {
			$server = explode(":", $servers[$i]);
			$ip = isset($server[0]) ? $server[0] : NULL;
			$port = isset($server[1]) ? $server[1] : NULL;
			
			if($ip == "" || $ip == "0.0.0.0" || $ip == "0" || $port == "" || $port == "0")
				continue;

			array_push($serversarray, array($ip, $port));
		}
		
		$servers = NULL;
		$serverscount = 0;
		$playersonline = 0;
		
		printout("Scanning ".($i-1)." servers");
		for($i = 0; $i < sizeof($serversarray); $i++)
		{
			$data = GetServerInfo($serversarray[$i][0], $serversarray[$i][1]);
			$server = ScanServer($data);
			
			if($server) {
				$servers .= $server;
				$serverscount++;
			}
			printout(".");
		}
		printout(" done\n");
		
		file_put_contents($listfile, $servers);
		
		printout("Refreshed $serverscount server(s).\n");
	}
	else
		printout("ServersDB is empty!\n");
}
elseif(isset($argv[1]) && $argv[1] == "cleanup") {
	if(file_exists($dbfile) && filesize($dbfile) > 0) {
		$serverslist = file_get_contents($dbfile);
		$servers = explode(";", $serverslist);
		$serversarray = Array();

		for($i=0; $i<sizeof($servers); $i++) {
			$server = explode(":", $servers[$i]);
			
			if(isset($server[0]))
				$ip = $server[0];
			if(isset($server[1]))
				$port = $server[1];
					
			if($ip == "0.0.0.0" || $ip == "" || $port == "0" || $port == "")
				continue;

			array_push($serversarray, array($ip, $port));
		}

		$servers = NULL;
		$numremoved = 0;
		
		printout("Scanning ".($i-1)." servers");
		for($i = 0; $i < sizeof($serversarray); $i++)
		{
			$data = GetServerInfo($serversarray[$i][0], $serversarray[$i][1]);
			$thisserver = $serversarray[$i][0].":".$serversarray[$i][1];
			if(!CheckServer($data))	{
				
				$serverslist = str_replace("$thisserver;", "", $serverslist);
				$numremoved++;
			}
			printout(".");
		}
		printout(" done\n");
		
		
		if($serverslist != "" && $numremoved > 0) {	
			file_put_contents($dbfile, $serverslist);
			printout("Removed $numremoved server(s).\n");
		}
		else
			printout("Nothing to remove.\n");
	}
	else
		printout("ServersDB is empty!\n");
}
elseif(isset($argv[1]) && $argv[1] == "help" && defined("STDIN"))
	printout("Usage: php ".basename(__FILE__)." [ACTION]\n\nAvailable actions:\n getservers - Get servers from Master Server\n refreshlist - Refresh all servers stored in DB file\n cleanup - Remove offline servers from DB file\n help - This, derp.\n\n");
elseif(defined("STDIN"))
	printout("No action specified, try 'php ".basename(__FILE__)." help' for list of available actions!\n");

?>
