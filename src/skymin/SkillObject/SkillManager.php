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

namespace skymin\SkillObject;

use skymin\SkillObject\object\SkillEntity;
use skymin\SkillObject\task\SkillObjectTask;

use pocketmine\scheduler\TaskScheduler;
use pocketmine\world\World;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\{
	Living,
	EntityFactory,
	EntityDataHelper
};

use function count;
use function in_array;

final class SkillManager{

	/** @var string[] */
	private static array $pvpWorlds = [];

	/** @var string[] */
	private static array $canTarget = [];

	private static bool $register = false;

	public static function registerScheduler(TaskScheduler $scheduler) : void{
		if(!self::$register){
			$scheduler->scheduleRepeatingTask(new SkillObjectTask(), 1);
			self::$register = true;
		}
	}

	public static function addPVPWorld(string $world) : void{
		if(!in_array($world, self::$pvpWorlds, true)){
			self::$pvpWorlds[] = $world;
		}
	}

	public static function removePVPWorld(string $world) : void{
		foreach(self::$pvpWorlds as $key => $name){
			if($name === $world){
				unset(self::$pvpWorlds[$key]);
			}
		}
	}

	public static function registerMob(Entity $entity) : void{
		$class = $entity::class;
		if(!in_array($class, self::$canTarget, true)){
			self::$canTarget[] = $class;
		}
	}

	public static function standardRegisterSkillEntity(SkillEntity $skill) : void{
		$class = $skil::class;
		$arr = explode('/', $class);
		EntityFactory::getInstance()->register($class, function(World $world, CompoundTag $nbt) use($class) : SkillEntity{
			return new $class(EntityDataHelper::parseLocation($nbt, $world), SkillEntity::parseSkinNBT($nbt), $nbt);
		}, [$arr[count($arr) - 1], $class]);
	}

}
