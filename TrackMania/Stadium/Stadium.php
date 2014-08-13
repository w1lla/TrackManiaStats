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
		use Maniaplanet\DedicatedServer\Structures;
		use ManiaLive\Utilities\Logger;
		use ManiaLive\Utilities\Time;
		
		class Stadium extends \ManiaLive\PluginHandler\Plugin {
		
		static public $maps = array('Campaigns\TMStadium\01_White\A01.Map.Gbx','Campaigns\TMStadium\01_White\A02.Map.Gbx','Campaigns\TMStadium\01_White\A03.Map.Gbx','Campaigns\TMStadium\01_White\A04.Map.Gbx','Campaigns\TMStadium\01_White\A05.Map.Gbx');
		static public $prename ='ESWC2014';
		
		protected $count;
	
		protected $status = '';
	
		protected $previousTrackList;
	
		protected $previousSettings;
		
		public $PlayerScore;
	
		const MATCH = 'match';
		const FINALE = 'final';
		const TIMEATTACKS = 'ta';
		const NONE = '';
	
		protected $inMatch = false;
		
		protected $wasWarmUp = false;
		
		protected $matchContinuesOnNextMap = false;
		
		
		  function onInit() {
		        $this->setVersion('0.0.1a');
		    
		  }
		  
		  function onLoad() {
		        $this->enableDatabase();
		        $this->enableDedicatedEvents();
				$this->enablePluginEvents();
				
			$command = $this->registerChatCommand('prepare', 'prepare', 1, false, AdminGroup::get());
			$command->help = 'Prepare a new match';
			$command->isPublic = true;
	
			$command = $this->registerChatCommand('start', 'start', 0, false, AdminGroup::get());
			$command->help = 'Start the match';
			$command->isPublic = true;
	
			$command = $this->registerChatCommand('stop', 'stop', 0, false, AdminGroup::get());
			$command->help = 'Stop the match';
			$command->isPublic = true;
	
			$command = $this->registerChatCommand('bonus', 'bonus', 2, true, AdminGroup::get());
			$command->log = true;
			$command->help = 'Give a bonus to a player (login points to add)';
			$command->isPublic = true;
	
			$command = $this->registerChatCommand('malus', 'malus', 2, true, AdminGroup::get());
			$command->log = true;
			$command->help = 'Give a malus to a player (login score to have)';
			$command->isPublic = true;
			
			Console::print_rln(self::$maps);
		}
		  
		  function onReady() {
		
		
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
		    
		    
		  function prepare($type)
		{
			// sauvegarde de la configuration actuelle du serveur
			$this->previousTrackList = $this->connection->getMapList(-1, 0);
			$this->previousSettings = $this->connection->getModeScriptText();
			
			// mise en minuscule (les commandes ne sont pas sensible à la case d'origine mais sécurité)
			$type = strtolower($type);
			
			// définition du type et du nom du serveur
			if ($type == 'ta')
			{
				$name = 'Time Attack';
			}
			else if ($type == 'final')
			{
				$name = 'Final';
			}
			else if ($type == '1' OR $type == '2' OR $type == '3' OR $type == '4' OR $type == '5' OR $type == '6' OR $type == '7' OR $type == '8' OR $type == '9' OR $type == '10' OR $type == '11' OR $type == '12' OR $type == '13' OR $type == '14' OR $type == '15')
			{
				$name = 'Match #'.$type;
				$type = 'match';
			}
			else if ($type == 'match')
			{
				$name = 'Match';
			}
			else if (count(self::$maps) == 0)
			{
				$type = 'error';
				$name = 'Wait & See';
			}
			
			// lance la configuration du serveur
			switch($type)
			{
				case 'ta' :
					$this->prepareTA();
					break;
				case 'match' :
					$this->prepareMatch();
					break;
				case 'final' :
					$this->prepareFinal();
					break;
				case 'error' :
					$this->error();
					break;
			}
		}
		
		  function error()
		{
			$this->connection->chatSendServerMessage('Thanks to enter the maps in the configuration file.');
		}
		
		  function stop()
		{
			$connection = $this->connection;
			$maps = $this->connection->getMapList(-1, 0);

		foreach ($maps as $map) {
	        $mapsAtServer[] = $map->fileName;
		}

		array_shift($mapsAtServer);
			
			// mise en place de la nouvelle tracklist
			$this->connection->insertMapList($mapsAtServer);
			
		$dataDir = $this->connection->gameDataDirectory();
		$dataDir = str_replace('\\', '/', $dataDir);
		$ScriptFolder = $dataDir . "Scripts/Modes/TrackMania/";
			try
			{
			$Script = new \Maniaplanet\DedicatedServer\Structures\GameInfos();
			$this->connection->setGameMode($Script::GAMEMODE_SCRIPT);
			$ScriptFile = file_get_contents($ScriptFolder.'TimeAttack.Script.txt');
			$this->connection->setModeScriptText($ScriptFile);
			$this->connection->setModeScriptSettings(array('S_UseScriptCallbacks' => true));
			$this->connection->setModeScriptSettings(array('S_TimeLimit' => 600));
			$this->connection->setForceShowAllOpponents(1,true);
			$this->connection->setDisableRespawn(false,true);
			}
			catch (\Exception $e) {
			Console::println('[' . date('H:i:s') . '] [TrackMania] Error: '.$e.'');
			}
			
			
			// entre dans le chat IG un message d'allerte
			$this->connection->chatSendServerMessage('$oMatch stop !! !!');
			
			// passe au circuit suivant pour rendre actif les changements
			$connection->nextMap(false, true);
			
			// changement des statuts du serveur
			$this->status = self::NONE;
			$this->inMatch = false;
		}
		
		  function start()
		{
			// passe au circuit suivant pour rendre active la configuration d'un "/prepare"
			$this->connection->nextMap(false, true);
			
			// Souhaite bonne chance au joueurs
			$this->connection->chatSendServerMessage('$oGL\'n\'HF !! !! !!');
		}
		
		  function prepareFinal()
		{
		
				$dataDir = $this->connection->gameDataDirectory();
		$dataDir = str_replace('\\', '/', $dataDir);
		$ScriptFolder = $dataDir . "Scripts/Modes/TrackMania/";
			try
			{
			$Script = new \Maniaplanet\DedicatedServer\Structures\GameInfos();
			$this->connection->setGameMode($Script::GAMEMODE_SCRIPT);
			$ScriptFile = file_get_contents($ScriptFolder.'Cup.Script.txt');
			$this->connection->setModeScriptText($ScriptFile);
			$this->connection->setModeScriptSettings(array('S_UseScriptCallbacks' => true));
			$this->connection->setModeScriptSettings(array('S_RoundsPerMap' => 5));
			$this->connection->setModeScriptSettings(array('S_WarmUpDuration' => 1));
			$this->connection->setModeScriptSettings(array('S_NbOfWinners' => 3));
			$this->connection->setModeScriptSettings(array('S_PointsLimit' => 120));
			$this->connection->setForceShowAllOpponents(1,true);
			$this->connection->setDisableRespawn(false,true);
			}
			catch (\Exception $e) {
			Console::println('[' . date('H:i:s') . '] [TrackMania] Error: '.$e.'');
			}	
			// création de la nouvelle tracklist
			
			$maps = $this->connection->getMapList(-1, 0);

		foreach ($maps as $map) {
	        $mapsAtServer[] = $map->fileName;
		}

		array_shift($mapsAtServer);
			
			// mise en place de la nouvelle tracklist
			$this->connection->insertMapList($mapsAtServer);
			shuffle(self::$maps);
			shuffle(self::$maps);
			shuffle(self::$maps);
			print_r(self::$maps);
			
			// mise en place de la nouvelle tracklist
			$this->connection->insertMapList(self::$maps);
			$this->connection->setMaxPlayers(4, true);
			
			// envoie dans le chat IG l'ordre des circuits
			$Maps = $this->connection->getMapList(-1, 0);
			
			$message = 'Final mode'."\n";
			$message .= 'The maps are:'."\n";
			$MapList = array();
			foreach ($Maps as $Map)
			{
				$MapList[] = $Map->name.' $z$fffby '.$Map->author;
			}
			$message .= implode("\n", $MapList);
			$this->connection->chatSendServerMessage($message);
			
			// changement du statut du serveur
			$this->status = self::FINALE;
		}
		
		function prepareMatch()
		{
		
		$dataDir = $this->connection->gameDataDirectory();
		$dataDir = str_replace('\\', '/', $dataDir);
		$ScriptFolder = $dataDir . "Scripts/Modes/TrackMania/";
			try
			{
			$Script = new \Maniaplanet\DedicatedServer\Structures\GameInfos();
			$this->connection->setGameMode($Script::GAMEMODE_SCRIPT);
			$ScriptFile = file_get_contents($ScriptFolder.'Cup.Script.txt');
			$this->connection->setModeScriptText($ScriptFile);
			$this->connection->setModeScriptSettings(array('S_UseScriptCallbacks' => true));
			$this->connection->setModeScriptSettings(array('S_RoundsPerMap' => 5));
			$this->connection->setModeScriptSettings(array('S_WarmUpDuration' => 1));
			$this->connection->setModeScriptSettings(array('S_NbOfWinners' => 2));
			$this->connection->setModeScriptSettings(array('S_PointsLimit' => 100));
			$this->connection->setForceShowAllOpponents(1,true);
			$this->connection->setDisableRespawn(false,true);
			}
			catch (\Exception $e) {
			Console::println('[' . date('H:i:s') . '] [TrackMania] Error: '.$e.'');
			}
			
			$maps = $this->connection->getMapList(-1, 0);

		foreach ($maps as $map) {
	        $mapsAtServer[] = $map->fileName;
		}

		array_shift($mapsAtServer);
			
			// mise en place de la nouvelle tracklist
			$this->connection->insertMapList($mapsAtServer);
			shuffle(self::$maps);
			shuffle(self::$maps);
			shuffle(self::$maps);
			print_r(self::$maps);
			
			// mise en place de la nouvelle tracklist
			$this->connection->insertMapList(self::$maps);
			$this->connection->setMaxPlayers(4, true);
	
			$Maps = $this->connection->getMapList(-1, 0);
			
			$message = 'Match mode'."\n";
			$message .= 'The maps are:'."\n";
			$MapList = array();
			foreach ($Maps as $Map)
			{
				$MapList[] = $Map->name.' $z$fffby '.$Map->author;
			}
	
			$message .= implode("\n", $MapList);
	
			$this->connection->chatSendServerMessage($message);
	
			$this->status = self::MATCH;
		}
		
		function prepareTA()
		{
		$dataDir = $this->connection->gameDataDirectory();
		$dataDir = str_replace('\\', '/', $dataDir);
		$ScriptFolder = $dataDir . "Scripts/Modes/TrackMania/";
			try
			{
			$Script = new \Maniaplanet\DedicatedServer\Structures\GameInfos();
			$this->connection->setGameMode($Script::GAMEMODE_SCRIPT);
			$ScriptFile = file_get_contents($ScriptFolder.'TimeAttack.Script.txt');
			$this->connection->setModeScriptText($ScriptFile);
			$this->connection->setModeScriptSettings(array('S_UseScriptCallbacks' => true));
			$this->connection->setModeScriptSettings(array('S_TimeLimit' => 600));
			$this->connection->setForceShowAllOpponents(1,true);
			$this->connection->setDisableRespawn(false,true);
			}
			catch (\Exception $e) {
			Console::println('[' . date('H:i:s') . '] [TrackMania] Error: '.$e.'');
			}
			
			$maps = $this->connection->getMapList(-1, 0);

		foreach ($maps as $map) {
	        $mapsAtServer[] = $map->fileName;
		}

		array_shift($mapsAtServer);
			
			// mise en place de la nouvelle tracklist
			$this->connection->insertMapList($mapsAtServer);
			shuffle(self::$maps);
			shuffle(self::$maps);
			shuffle(self::$maps);
			print_r(self::$maps);
			
			// mise en place de la nouvelle tracklist
			$this->connection->insertMapList(self::$maps);
			$this->connection->setMaxPlayers(100, true);
	
			$Maps = $this->connection->getMapList(-1, 0);
	
			$message = 'Time Attack mode'."\n";
			$message .= 'The maps are:'."\n";
			$MapList = array();
			foreach ($Maps as $Map)
			{
				$MapList[] = $Map->name.' $z$fffby '.$Map->author;
			}
	
			$message .= implode("\n", $MapList);
	
			$this->connection->chatSendServerMessage($message);
	
			$this->status = self::TIMEATTACKS;
		}
	
		function bonus($login, $playerLogin, $points)
		{
			$admin = Storage::getInstance()->getPlayerObject($login);
	
			$player = $this->storage->getPlayerObject($playerLogin);
			$score = $this->PlayerScore['score'] + $points;
			$pointsAdd = "$player->login:$score";
			
						
			$this->connection->triggerModeScriptEventArray('LibXmlRpc_SetPlayersScores', array($pointsAdd)); 
			
			$player = $this->storage->getPlayerObject($playerLogin);
			$this->connection->triggerModeScriptEvent('LibXmlRpc_GetPlayersScores','');
			$message = 'The player '.$player->nickName.' has now '.$points.' points!';
			$this->connection->chatSendServerMessage($message);
		}
	
		function malus($login, $playerLogin, $points)
		{
			$admin = Storage::getInstance()->getPlayerObject($login);
	
			$player = $this->storage->getPlayerObject($playerLogin);
			$score = $this->PlayerScore['score'] - $points;
			$PointsRemove = "$player->login:$score";
			
			$this->connection->triggerModeScriptEventArray('LibXmlRpc_SetPlayersScores', array($PointsRemove)); 			
			
			$player = $this->storage->getPlayerObject($playerLogin);
			$this->connection->triggerModeScriptEvent('LibXmlRpc_GetPlayersScores','');
			$message = 'The player '.$player->nickName.' has now  '.$score.' points!';
			$this->connection->chatSendServerMessage($message);
		}

			
			function onModeScriptCallback($event, $json) {
			//var_dump($json);
			switch ($event) {
		            case 'LibXmlRpc_BeginMatch':
		                $this->onXmlRpcStadiumBeginMatch($json);
		                break;
		            case 'LibXmlRpc_LoadingMap':
		                $this->onXmlRpcStadiumLoadingMap($json);
		                break;
		            case 'LibXmlRpc_BeginMap':
		                $this->onXmlRpcStadiumBeginMap($json);
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
		            case 'LibXmlRpc_EndRound':
						$this->onXmlRpcStadiumEndRound($json);
						break;
		            case 'LibXmlRpc_PlayersRanking':
						$this->PlayersRanking($json);
						break;
					case 'LibXmlRpc_PlayersScores':
						$this->PlayerScores($json);
						break;
		            case 'LibXmlRpc_EndMap':
						$this->OnXmlRpcStadiumEndMap($json);
						break;
		            case 'LibXmlRpc_EndMatch':
						$this->onXmlRpcStadiumEndMatch($json);
						break;
		            case 'LibXmlRpc_WarmUp':
						$this->onXmlRpcStadiumWarmUp($json);
						break;
			}
			}
			
					function PlayerScores($content){
					if (count($content) > 0) {
						foreach ($content as $item) {
							$rank = explode(':', $item);
							//var_dump($rank);
							$player = $this->storage->getPlayerObject($rank[0]);
							$update = array(
								'login'     => $player->login,
								'score'		=> (int)$rank[1],
									);
							$this->PlayerScore = $update;
							}
					}		
		}
			
			function onXmlRpcStadiumBeginMatch($content) {
				$MatchNumber = $content[0];
			}
			
			function onXmlRpcStadiumBeginMap($content){
			$this->connection->triggerModeScriptEventArray('LibXmlRpc_GetPlayersRanking', array('300','0'));
			$this->connection->triggerModeScriptEvent('LibXmlRpc_GetWarmUp', '');
			}
			
			function onXmlRpcStadiumEndRound($content){
			$RoundNumber = $content[0];
			$this->connection->triggerModeScriptEventArray('LibXmlRpc_GetPlayersRanking', array('300','0'));
			$this->connection->triggerModeScriptEvent('LibXmlRpc_GetWarmUp', '');
			}
			
			function OnXmlRpcStadiumEndMap($content){
			$this->connection->triggerModeScriptEventArray('LibXmlRpc_GetPlayersRanking', array('300','0'));
			$this->connection->triggerModeScriptEvent('LibXmlRpc_GetWarmUp', '');
			}
			
			function onXmlRpcStadiumEndMatch($content){
			$this->connection->triggerModeScriptEventArray('LibXmlRpc_GetPlayersRanking', array('300','0'));
			$this->connection->triggerModeScriptEvent('LibXmlRpc_GetWarmUp', '');
			$this->matchContinuesOnNextMap = $content[0];
			}
			
			function onXmlRpcStadiumWarmUp($content){
			$this->wasWarmUp = $content[0];
			}
			
			function PlayersRanking($content){
			if (count($content) > 0) {
						foreach ($content as $item) {
							$rank = explode(':', $item);
	
							// Explode string and convert to integer
							$cps = array_map('intval', explode(',', $rank[2]));
							if (count($cps) == 1 && $cps[0] === -1) {
								$cps = array();
							}
							
							$player = $this->storage->getPlayerObject($rank[0]);
							$update = array(
								'rank'		=> (int)$rank[1],
								'login'		=> $player->login,
								'nickname'	=> $player->nickName,
								'time'		=> (int)$rank[6],
								'score'		=> (int)$rank[9],
								'cps'		=> $cps,
		 						'team'		=> (int)$rank[3],
								'spectator'	=> $rank[4],
								'away'		=> $rank[5],
									);	
									
						if($this->status == self::FINALE || $this->status == self::MATCH || $this->status == self::TIMEATTACKS)
			$this->inMatch = true;
			
			
			$logFilename = $this->connection->getServerName();
	
			$log = Logger::getLog($logFilename);
			
			if($this->inMatch && ($this->status == self::FINALE || $this->status == self::MATCH))
			{
				$log->write($this->storage->currentMap->name.';'.$this->storage->currentMap->author.';');
			}
			
			if($this->inMatch && ($this->status == self::FINALE || $this->status == self::MATCH))
			{
				$log->write('');
				$log->write($update['rank'].';'.$update['login'].';'.$update['nickname'].';'.Time::fromTM($update['time']).';'.$update['score'].';');
				$log->write('');
			}
			if ($this->inMatch) 
			{
			try
			{
			$this->connection->saveBestGhostsReplay(null, '', true);
			}catch (\Exception $e) {
			Console::println('[' . date('H:i:s') . '] [TrackMania] Error: '.$e.'');
			}
			}
			
			if($this->inMatch && $this->status == self::TIMEATTACKS)
			{
					$log->write('Time Attack Results :');
					$log->write('');
					$log->write($update['rank'].';'.$update['login'].';'.$update['nickname'].';'.Time::fromTM($update['time']).';'.$update['score'].';');
					$log->write('');
					$log->write('');
			}
			elseif($this->inMatch && !$this->wasWarmUp && ($this->status == self::FINALE || $this->status == self::MATCH))
			{
					$log->write('Map Results :');
					$log->write($update['rank'].';'.$update['login'].';'.$update['nickname'].';'.Time::fromTM($update['time']).';'.$update['score'].';');
				$log->write('-----------------------------------');
				$log->write('');
				$log->write('');
				if(!$this->matchContinuesOnNextMap)
				$this->stop();
			}
			}
			}
			}		
	
				function onXmlRpcStadiumLoadingMap($content) {
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
			if ($content[4] == "True"){
			$params = array($content[0], $content[1], (int)$content[2], ((int)$content[3]+1), (int)$content[5], (int)$content[6]);
			$this->onPlayerFinishHandling($content[0], $params);
			}
			elseif ($content[4] == "False"){
			$params = array($content[0], $content[1], (int)$content[2], ((int)$content[3]+1), (int)$content[5], (int)$content[6]);
			$this->onPlayerCheckPointHandling($content[0], $params);
			}
			if($content[7] == "True"){
			$params = array($content[0], $content[1], (int)$content[2], ((int)$content[3]+1), (int)$content[5], (int)$content[6]);
			$this->onPlayerFinishLapHandling($content[0], $params);
			}
			}
			
			function OnPlayerFinishHandling($login, $params){
			//var_dump($params);
			}
			
			function onPlayerCheckPointHandling($login, $params){
			//var_dump($params);
			}
			
			function OnPlayerFinishLapHandling($login, $params){
			//var_dump($params);
			}
			
		}
		?>