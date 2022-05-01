<?php
/**
 *      _                    _       
 *  ___| | ___   _ _ __ ___ (_)_ __  
 * / __| |/ / | | | '_ ` _ \| | '_ \ 
 * \__ \   <| |_| | | | | | | | | | |
 * |___/_|\_\\__, |_| |_| |_|_|_| |_|
 *           |___/ 
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 * 
 * @author skymin
 * @link   https://github.com/sky-min
 * @license https://opensource.org/licenses/MIT MIT License
 * 
 *   /\___/\
 * 　(∩`・ω・)
 * ＿/_ミつ/￣￣￣/
 * 　　＼/＿＿＿/
 *
 */

declare(strict_types = 1);

namespace skymin\SkillObject\object;

use skymin\SkillObject\SkillManager;
use skymin\SkillObject\interface\{SkillBase, SkillEffect};
use skymin\SkillObject\task\SkillObjectTask;

use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\entity\Location;

use function sin;
use function cos;
use function deg2rad;

abstract class SkillObject{

	protected ?Player $owner = null;

	protected Location $location;

	protected bool $closed = false;

	protected int $closeTimer = 10;

	public function __construct(Location $pos){
		$this->location = $pos;
	}

	public final function setOwner(Player $player) : void{
		$this->owner = $player;
	}

	public final function getOwner() : ?Player{
		return $this->owner;
	}

	public final function teleport(Vector3 $pos) : void{
		if($pos instanceof Location){
			$this->location = $pos;
			return;
		}
		$loc = $this->location;
		$world = $pos instanceof Position ? $pos->world : $loc->world;
		$this->location = Location::fromObject($pos, $world, $loc->yaw, $loc->pitch);
	} 

	public final function getLocation() : Location{
		return $this->location;
	}

	public final function getPosition() : Position{
		return $this->location->asPosition();
	}

	public final function getViewers() : array{
		$pos = $this->location;
		return $pos->world->getViewersForPosition($pos);
	}

	public final function isClosed() : bool{
		return $this->closed;
	}

	public final function close() : void{
		$this->closed = true;
	}

	public final function spawn() : void{
		SkillObjectTask::addObject($this);
	}

	public final function setCloseTimer(int $tick){
		$this->closeTimer = $tick;
	}

	public final function getDirectionVector() : Vector3{
		$loc = $this->location;
		$y = -sin(deg2rad($loc->pitch));
		$xz = cos(deg2rad($loc->pitch));
		$x = -$xz * sin(deg2rad($loc->yaw));
		$z = $xz * cos(deg2rad($loc->yaw));
		return (new Vector3($x, $y, $z))->normalize();
	}

	public function skillTick() : void{
		$this->closeTimer--;
		if($this->closeTimer < 1){
			$this->closed = true;
		}
		$owner = $this->owner;
		if($owner !== null && ($owner->isClosed() || !$owner->isAlive() || !$owner->isOnline())){
			$this->closed = true;
			return;
		}
		if($this instanceof SkillEffect){
			$this->skillEffect();
		}
		if($this instanceof SkillBase){
			$pos = $this->location;
			$world = $pos->world;
			foreach($world->getEntities() as $target){
				if($pos->distance($target->getPosition()) > static::getDistance()) continue;
				if($target instanceof ADMob){
					$this->skillAttack($target);
					continue;
				}
				if($world->getFolderName() === 'world'){
					if($target instanceof Player && ($owner === null || $owner->getId() !== $target->getId())){
						if($target->getGamemode()->getEnglishName() === 'Survival'){
							$this->skillAttack($target);
						}
					}
				}
			}
		}
	}

}
