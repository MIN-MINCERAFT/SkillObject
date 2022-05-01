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

use pocketmine\entity\Skin;

use function chr;
use function imagesx;
use function imagesy;
use function imagecolorat;
use function imagecreatefrompng;
use function file_get_contents;

use Ramsey\Uuid\Uuid;

final class SkinTool{

	private const SKIN_PATH = __DIR__ . '/../../../resources/';

	private static array $skins = [];

	public static function create(string $type) : ?Skin{
		if(isset(self::$skins[$type])){
			return self::$skins[$type];
		}
		$img = imagecreatefrompng(self::SKIN_PATH . $type . '.png');
		if($img === false) return null;
		$h = imagesy($img);
		$w = imagesx($img);
		$skindata = '';
		for($y = 0; $y < $h; $y++){
			for($x = 0; $x < $w; $x++){
				$colorat = imagecolorat($img, $x, $y);
				$a = ((~((int) ($colorat >> 24))) << 1) & 0xff;
				$r = ($colorat >> 16) & 0xff;
				$g = ($colorat >> 8) & 0xff;
				$b = $colorat & 0xff;
				$skindata .= chr($r) . chr($g) . chr($b) . chr($a);
			}
		}
		$skinType = '';
		$model = '';
		if(file_exists(self::SKIN_PATH . $type . '.json')){
			$skinType = 'geometry.rmsp.' . $type;
			$model = file_get_contents(self::SKIN_PATH . $type . '.json');
		}
		$skin = new Skin(Uuid::uuid4()->toString(), $skindata, '', $skinType, $model);
		self::$skins[$type] = $skin;
		return $skin;
	}

}
