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

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\{StringTag, CompoundTag};
use pocketmine\entity\{Skin, Entity, Location, EntitySizeInfo};
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\data\SavedDataLoadingException;
use Ramsey\Uuid\{Uuid, UuidInterface};

use pocketmine\network\mcpe\protocol\{
	AddPlayerPacket,
	MovePlayerPacket,
	PlayerListPacket,
	PlayerSkinPacket,
	AdventureSettingsPacket
};
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\entity\{EntityIds, EntityMetadataProperties};
use pocketmine\network\mcpe\convert\{TypeConverter, SkinAdapterSingleton};

use function in_array;

abstract class SkillEntity extends Entity{

	protected ?Player $owner = null;
	protected Skin $skin;
	protected SkinData $skinData;
	protected UuidInterface $uuid;
	
	public static function getNetworkTypeId() : string{ return EntityIds::PLAYER; }

	public static function parseSkinNBT(CompoundTag $nbt) : Skin{
		$skinTag = $nbt->getCompoundTag('Skin');
		if($skinTag === null){
			throw new SavedDataLoadingException('Missing skin data');
		}
		return new Skin(
			$skinTag->getString('Name'),
			($skinDataTag = $skinTag->getTag('Data')) instanceof StringTag ? $skinDataTag->getValue() : $skinTag->getByteArray('Data'),
			$skinTag->getByteArray('CapeData', ''),
			$skinTag->getString('GeometryName', ''),
			$skinTag->getByteArray('GeometryData', '')
		);
	}

	public function __construct(Location $pos, Skin $skin, ?CompoundTag $nbt = null){
		$this->skin = $skin;
		$this->skinData = SkinAdapterSingleton::get()->toSkinData($skin);
		parent::__construct($pos, $nbt);
	}

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.0, 1.0, 0.5);
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->uuid = Uuid::uuid3(Uuid::NIL, ((string) $this->getId()) . $this->skin->getSkinData() . $this->getNameTag());
	}

	public function saveNBT() : CompoundTag{
		$skin = $this->skin;
		$nbt = parent::saveNBT();
		if($this->skin !== null){
			$nbt->setTag('Skin', CompoundTag::create()
				->setString('Name', $skin->getSkinId())
				->setByteArray('Data', $skin->getSkinData())
				->setByteArray('CapeData', $skin->getCapeData())
				->setString('GeometryName', $skin->getGeometryName())
				->setByteArray('GeometryData', $skin->getGeometryData())
			);
		}
		return $nbt;
	}

	public final function setOwner(Player $player) : void{
		$this->owner = $player;
	}

	public final function getOwner() : ?Player{
		return $this->owner;
	}

	public final function attack(EntityDamageEvent $source) : void{
		$source->cancel();
	}

	public final function getUniqueId() : UuidInterface{
		return $this->uuid;
	}

	public final function getSkin() : Skin{
		return $this->skin;
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$owner = $this->owner;
		if($owner !== null && ($owner->isClosed() || !$owner->isAlive() || !$owner->isOnline())){
			$this->close();
			return false;
		}
		if(!$this instanceof SkillEffect){
			return parent::entityBaseTick($tickDiff);;
		}
		$this->skillEffect();
		if($this instanceof SkillBase){
			$pos = $this->location;
			$world = $pos->world;
			foreach($world->getEntities() as $target){
				if($pos->distance($target->getPosition()) > static::getDistance()) continue;
				if(in_array($target::class, SkillManager::$canTarget, true)){
					$this->skillAttack($target);
					continue;
				}
				if(in_array($world->getFolderName(), SkillManager::$pvpWorlds, true)){
					if($target instanceof Player && ($owner === null || $owner->getId() !== $target->getId())){
						if($target->getGamemode()->getEnglishName() === 'Survival'){
							$this->skillAttack($target);
						}
					}
				}
			}
		}
		return true;
	}

	protected final function sendSpawnPacket(Player $player) : void{
		$network = $player->getNetworkSession();

		$playerListAddPacket = new PlayerListPacket();
		$playerListAddPacket->type = PlayerListPacket::TYPE_ADD;
		$playerListAddPacket->entries = [PlayerListEntry::createAdditionEntry(
			$this->uuid,
			$this->id,
			$this->nameTag,
			$this->skinData
		)];
		$network->sendDataPacket($playerListAddPacket);

		$pos = $this->location;
		$addPlayerPacket = new AddPlayerPacket();
		$addPlayerPacket->uuid = $this->uuid;
		$addPlayerPacket->username = '';
		$addPlayerPacket->actorRuntimeId = $addPlayerPacket->actorUniqueId = $this->id;
		$addPlayerPacket->position = $pos->asVector3();
		$addPlayerPacket->pitch = $pos->pitch;
		$addPlayerPacket->yaw = $addPlayerPacket->headYaw = $pos->yaw;
		$addPlayerPacket->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(VanillaItems::AIR()));
		$this->getNetworkProperties()->setByte(EntityMetadataProperties::COLOR, 0);
		$addPlayerPacket->metadata = $this->getAllNetworkData();
		$addPlayerPacket->adventureSettingsPacket = AdventureSettingsPacket::create(0, 0, 0, 0, 0, $this->getId());
		$addPlayerPacket->gameMode = 1;
		$network->sendDataPacket($addPlayerPacket);

		$playerListRemovePacket = new PlayerListPacket();
		$playerListRemovePacket->type = PlayerListPacket::TYPE_REMOVE;
		$playerListRemovePacket->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
		$network->sendDataPacket($playerListRemovePacket);
	}

	public function broadcastMovement(bool $teleport = false) : void{
		$pos = $this->location;
		$pk = new MovePlayerPacket();
		$pk->actorRuntimeId = $this->id;
		$pk->position = $this->getOffsetPosition($pos);
		$pk->pitch = $pos->pitch;
		$pk->yaw = $pk->headYaw = $pos->yaw;
		$pk->mode = $teleport ? MovePlayerPacket::MODE_TELEPORT : MovePlayerPacket::MODE_NORMAL;
		$pos->world->broadcastPacketToViewers($pos, $pk);
	}

}
