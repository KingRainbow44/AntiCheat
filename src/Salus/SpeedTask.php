<?php
namespace Salus;


use pocketmine\entity\Effect;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task as PluginTask;

class SpeedTask extends PluginTask {

	const WALKING_SPEED = 4.3;
	
	/** @var array */
	public $previousPosition;
	/** @var array */
	public $previousMotion;
	/** @var array */
	public $lastCheckTick;
    /** @var Main */
    private $p;
    
    public function __construct($p){
        $this->p = $p;
    }

	public function onRun($currentTick) {
		$main = $this->p;
		foreach($main->getServer()->getOnlinePlayers() as $player){
			$prev = $this->previousPosition[$player->getName()];
			$current = $player->getPosition();

			if(!($prev instanceof Position)) {
				return;
			}

			if($prev->getLevel() != $current->getLevel()) {
				return;
			}

			$maxDistance = $main->getMaxDistance($player, $currentTick - $this->lastCheckTick[$player->getName()]);

			// Ignore Y values (in case of jump boosts etc)
			$actualDistance = sqrt(abs(($prev->getX() - $current->getX()) * ($prev->getZ() - $current->getZ())));

			$diff = $maxDistance - $actualDistance;
			if($diff > 0) {
			
			}

			// Store current variables for the next tick
			$this->previousMotion[$player->getName()] = $player->getMotion();
			$this->previousPosition[$player->getName()] = $player->getPosition();
			$this->lastCheckTick[$player->getName()] = $currentTick;
		}
	}
}
