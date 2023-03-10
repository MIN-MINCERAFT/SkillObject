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

declare(strict_types=1);

namespace skymin\SkillObject\task;

use pocketmine\scheduler\Task;
use skymin\SkillObject\object\SkillObject;

final class SkillObjectTask extends Task
{

	/** @var SkillObject[] */
	private static array $objects = [];

	public static function addObject(SkillObject $object): void
	{
		self::$objects[] = $object;
	}

	public function onRun(): void
	{
		foreach(self::$objects as $key => $object) {
			if($object->isClosed()) {
				unset(self::$objects[$key]);
				continue;
			}
			$object->skillTick();
		}
	}
}
