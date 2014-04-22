<?php

/**
  Name: Willem 'W1lla' van den Munckhof
  Date: 22-4-2014
  Version: 2 (GA2K14)
  Project Name: ESWC Stadium Statistics

  What to do:

/**
 * ---------------------------------------------------------------------
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * You are allowed to change things or use this in other projects, as
 * long as you leave the information at the top (name, date, version,
 * website, package, author, copyright) and publish the code under
 * the GNU General Public License version 3.
 * ---------------------------------------------------------------------
 */

namespace ManiaLivePlugins\TrackMania\Stadium;


use ManiaLive\Data\Storage;
use ManiaLive\Utilities\Console;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\Utilities\Validation;
use ManiaLib\Gui\Elements\Icons128x128_1;
use Maniaplanet\DedicatedServer\Structures;

class Stadium extends \ManiaLive\PluginHandler\Plugin {


    function onInit() {
        $this->setVersion('0.0.1a');
    
        //$this->logger = new Log($this->storage->serverLogin);
		//$this->mapdirectory = $this->connection->getMapsDirectory();
  }
  
  function onLoad() {
        $this->enableDatabase();
        $this->enableDedicatedEvents();
		$this->enablePluginEvents();
  }
  
      function onReady() {

        $this->connection->setModeScriptSettings(array('S_UseScriptCallbacks' => true));

        //$this->connection->setModeScriptSettings(array('S_UsePlayerClublinks' => true)); //Debug Way...
        $this->connection->setCallVoteRatios(array(array('Command' => 'SetModeScriptSettingsAndCommands', 'Ratio' => 0.4 )));
    
    Console::println('[' . date('H:i:s') . '] [TrackMania] Stadium Core v' . $this->getVersion());
    foreach ($this->storage->players as $player) {
        $this->connection->chatSendServerMessage('$fff» $fa0Welcome, this server uses $fff [TrackMania] Stadium Stats$fa0!', $player->login);
    }
    

        //Restart map to initialize script
        $this->connection->executeMulticall(); // Flush calls
        $this->connection->restartMap();

        $this->enableDedicatedEvents(ServerEvent::ON_MODE_SCRIPT_CALLBACK);
          
    }
	
	public function onModeScriptCallback($event, $json) {
	//var_dump($json);
	switch ($event) {
            case 'LibXmlRpc_BeginMatch':
                $this->onXmlRpcStadiumBeginMatch($json);
                break;
            case 'LibXmlRpc_LoadingMap':
                $this->onXmlRpcStadiumLoadingMap($json);
                break;
            case 'LibXmlRpc_BeginMap':
                $this->onXmlRpcEliteBeginMap($json);
                break;
            case 'LibXmlRpc_OnStartLine':
                $this->onXmlRpcStadiumOnStartLine($json);
                break;
            case 'LibXmlRpc_OnGiveUp':
                $this->onXmlRpcStadiumOnGiveUp($json);
                break;
            case 'LibXmlRpc_OnWayPoint':
                $this->onXmlRpcStadiumWayPoint($json);
                break;
			case 'LibXmlRpc_OnRespawn':
			    $this->onXmlRpcStadiumRespawn($json);
                break;
	}
	}
	
		function onXmlRpcStadiumBeginMatch($content) {
		$MatchNumber = $content[0];
	}
	
		function onXmlRpcStadiumLoadingMap($content) {
		$MapNumber = $content[0];
	}
	
		function onXmlRpcEliteBeginMap($content) {
		$MapNumber = $content[0];
	}
	
		function onXmlRpcStadiumOnStartLine($content) {
		$StartingPlayer = $content[0];
	}
	
		function onXmlRpcStadiumOnGiveUp($content) {
	$GivingUpPlayer = $content[0];
	}
	
		function onXmlRpcStadiumRespawn($content) {
	$RespawnPlayer = $content[0];
	}
	
		function onXmlRpcStadiumWayPoint($content) {
	$PlayerCPLogin = $content[0];
	$WaypointId = $content[1];
	$RaceTime = $content[2];
	$CP = $content[3];
	$WayPointEndofRace = $content[4];
	$CurrentLapTime = $content[5];
	$WayPointInLap = $content[6];
	$LastWayPointInLap = $content[7];
	echo $PlayerCPLogin;
	echo $RaceTime;
	echo $CP;
	echo $WayPointEndofRace;
	}
	
}
?>