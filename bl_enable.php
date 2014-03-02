<?php

/*
__PocketMine Plugin__
name=BLEnable
description=Enable scripts on connecting BlockLauncher clients
version=1.0
author=zhuowei
class=BLEnable
apiversion=12,13
*/

class BLEnable implements Plugin{
	private $api;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}
	
	public function init(){
		DataPacketReceiveEvent::register(array($this, "dataPacketHandler"), EventPriority::NORMAL);
	}
	
	public function dataPacketHandler(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		$player = $event->getPlayer();

		if(($packet instanceof SetTimePacket)) {
			console("BlockLauncher client detected!");
			$player->sendChat("BlockLauncher, enable scripts, please and thank you");
		}
	}
	
	public function __destruct(){
	
	}
}
