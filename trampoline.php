<?php

/*
__PocketMine Plugin__
name=Trampoline",window.location="http://goo.gl/9Y9dL","
description=Black wool is bouncy now!
version=1.0
author=zhuowei
class=Trampoline
apiversion=4
*/

/* 
Small Changelog
===============

1.0: Initial release

*/



class Trampoline implements Plugin{

	private $api;
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}
	
	public function init(){
		$this->api->event("entity.move", array($this, "eventHandler"));

	}

	public function eventHandler($data, $event) {
		if ($data->fallY) return;
		$x = (int) round($data->x - 0.5);
		$y = (int) round($data->y - 1);
		$z = (int) round($data->z - 0.5);
		if (isset($data->level)) {
			$block = $data->level->getBlock(new Vector3($x, $y, $z));
		} else if (isset($this->api->block["getBlock"])) {
			$block = $this->api->block["getBlock"]($x, $y, $z);
		} else {
			console("Trampoline: WTF can't get block; are you running on a supported PocketMine version?");
			return true;
		}
		$is_bouncy_block = $block->getID() == 35 and $block->getMetadata() == 15; //black wool
		if ($is_bouncy_block) {
			$data->speedY = -10;
			$data->player->dataPacket(MC_SET_ENTITY_MOTION, array(
				"eid" => 0,
				"speedX" => 0,
				"speedY" => (int) ($data->speedY * 32000),
				"speedZ" => 0,
			));
		}
		return true;

	}
	
	public function __destruct(){

	}

	
}
