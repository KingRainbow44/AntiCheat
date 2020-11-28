<?php
    namespace Salus;

    use pocketmine\event\Listener;
    use pocketmine\plugin\PluginBase;
    use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
    use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
    use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
    use pocketmine\event\server\DataPacketReceiveEvent;
    use pocketmine\event\player\PlayerJoinEvent;
    use pocketmine\event\player\PlayerQuitEvent;
    use pocketmine\event\player\PlayerMoveEvent;
    use pocketmine\event\entity\EntityDamageByEntityEvent;
    use pocketmine\event\entity\EntityDamageEvent;
    use pocketmine\command\ConsoleCommandSender;
    use pocketmine\utils\TextFormat as TF;
    use pocketmine\item\Item;
    use pocketmine\entity\Entity;
    use pocketmine\entity\Effect;
    use pocketmine\Player;
    use pocketmine\math\Vector3;
    use pocketmine\block\Block;
    use Salus\SpeedTask;

    class Main extends PluginBase implements Listener {

        /** @var array */
        public $point = array();
	    
        public $speed;
	    
        /** @var array */
        public $surroundings = array();
	    
        /** @var array */
        public $fly = array();
	    
	const WALKING_SPEED = 4.3;

        public function onEnable() {
            if (!$this->isSpoon()) {
                $this->getServer()->getPluginManager()->registerEvents($this, $this);
                if(!(file_exists($this->getDataFolder()))) {
                    @mkdir($this->getDataFolder());
                    chdir($this->getDataFolder());
                    @mkdir("players/", 0777, true);
                }
                $this->saveResource("config.yml");
                $this->getLogger()->info("§3Salus has been enabled!");
                @mkdir($this->getDataFolder() . "players");
                $this->getServer()->getScheduler()->scheduleRepeatingTask(new SpeedTask($this), 20);
            }
        }
        
        public function isSpoon(){
            if ($this->getServer()->getName() !== "PocketMine-MP") { 
                $this->getLogger()->error("Well... You're using a spoon. So enjoy a featureless AntiCheat plugin by Driesboy until you switch to PMMP! :)");
                return true;
            }
            if($this->getDescription()->getAuthors() !== ["Driesboy"] || $this->getDescription()->getName() !== "AntiCheat"){
                $this->getLogger()->error("You are not using the original version of this plugin (AntiCheat) by Driesboy.");
                return true;
            }
            return false;
        }

        public function ScanMessage($message, $player){
            $pos = strpos(strtoupper($message), "%PLAYER%");
            $newmsg = $message;
            if ($pos !== false){
                $newmsg = substr_replace($message, $player, $pos, 8);
            }
            return $newmsg;
        }
  
        public function CheckForceOP($event){
            $player = $event->getPlayer();
            if ($player->isOp()){
                if (!$player->hasPermission("salus.legitop")){
                    $event->setCancelled();
                    $this->HackDetected($player, "Force-OP Hacks", "Salus", "3");
                 }
            }
        }
        
        public function GetSurroundingBlocks(Player $player){
            $level       = $player->getLevel();
    
            $posX        = $player->getX();
            $posY        = $player->getY();
            $posZ        = $player->getZ();    
    
            $pos1        = new Vector3($posX  , $posY, $posZ  );
            $pos2        = new Vector3($posX-1, $posY, $posZ  );
            $pos3        = new Vector3($posX-1, $posY, $posZ-1);
            $pos4        = new Vector3($posX  , $posY, $posZ-1);
            $pos5        = new Vector3($posX+1, $posY, $posZ  );
            $pos6        = new Vector3($posX+1, $posY, $posZ+1);
            $pos7        = new Vector3($posX  , $posY, $posZ+1);
            $pos8        = new Vector3($posX+1, $posY, $posZ-1);
            $pos9        = new Vector3($posX-1, $posY, $posZ+1);
    
            $bpos1       = $level->getBlock($pos1)->getId();
            $bpos2       = $level->getBlock($pos2)->getId();
            $bpos3       = $level->getBlock($pos3)->getId();
            $bpos4       = $level->getBlock($pos4)->getId();
            $bpos5       = $level->getBlock($pos5)->getId();
            $bpos6       = $level->getBlock($pos6)->getId();
            $bpos7       = $level->getBlock($pos7)->getId();
            $bpos8       = $level->getBlock($pos8)->getId();
            $bpos9       = $level->getBlock($pos9)->getId();
    
            $this->surroundings = array ($bpos1, $bpos2, $bpos3, $bpos4, $bpos5, $bpos6, $bpos7, $bpos8, $bpos9);    
        }
        
        public function CheckFly($event){
            $player = $event->getPlayer();
            $oldPos = $event->getFrom();
		    $newPos = $event->getTo();
            if(!$player->isCreative() and !$player->isSpectator() and !$player->getAllowFlight()){
		        if ($oldPos->getY() <= $newPos->getY()){
		            if($player->GetInAirTicks() > 20){
			           $maxY = $player->getLevel()->getHighestBlockAt(floor($newPos->getX()), floor($newPos->getZ()));
			           if($newPos->getY() - 2 > $maxY){
			               $this->point[$player->getName()]["fly"] += (float) 1;
			               if((float) $this->point[$player->getName()]["fly"] > (float) 3){ 
			                   $event->setCancelled();
					           $this->HackDetected($player, "Fly Hacks", "Salus", "1");
                           }
			           }
		            }
                }else{
                    $this->point[$player->getName()]["fly"] = (float) 0;
                }
    	    }
    	}
    	
    	public function CheckNoClip($event)
	{
		# Not used. Causes too many issues as of API 3.0.0 release.
		return;
        }

        public function HackDetected(Player $player, $reason, $sender, $points){
            $player_name = $player->getName();
            if(!(file_exists($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt"))) {
                touch($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt");
                file_put_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt", "1");
            }else{
                $file = file_get_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt");
                file_put_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt", $file + $points);
            }
            $this->CheckMax($player ,$reason, $sender);
        }
        
        public function CheckMax(Player $player, $reason, $sender){
            $file = file_get_contents($this->getDataFolder() . "players/" . strtolower($player->getName()) . ".txt");
            if($file >= $this->getConfig()->get("max-warns")) {
                $this->Ban($player, TF::RED . "You are banned for using " . $reason . " by " . $sender, $sender);
            }else{
                $player->kick(TF::YELLOW . "You are warned for " . $reason . " by " . $sender);
                return true;
            }
        }
        
        public function Ban(Player $player, $message, $sender){
            $message = $this->getConfig()->get("punishment");
            if ($this->getConfig()->get("punishment") === "Ban"){
                $this->getServer()->getNameBans()->addBan($player->getName(), $message, null, $sender);
            }elseif ($this->getConfig()->get("punishment") === "IPBan"){
                $this->getServer()->getIPBans()->addBan($player->getAddress(), $message, null, $sender);
            }elseif ($this->getConfig()->get("punishment") === "ClientBan"){
                // todo
            }elseif ($this->getConfig()->get("punishment") === "MegaBan"){
                $this->getServer()->getIPBans()->addBan($player->getAddress(), $message, null, $sender);
                $this->getServer()->getNameBans()->addBan($player->getName(), $message, null, $sender);
                // todo ClientBan
            }elseif ($this->getConfig()->get("punishment") === "Command"){
                foreach($this->getConfig()->get("punishment-command") as $command){
                    $send = $this->ScanMessage($command, $player->getName());
                    $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $send);
                }
            }
        }
        public function getMaxDistance(Player $player, $tickDifference) {
		// Speed potions?
		$effects = $player->getEffects();

		$amplifier = 0;

		// Check for speed potions.
		if(!empty($effects)) {
			foreach($effects as $effect) {
				if($effect->getId() == Effect::SPEED) {
					$a = $effect->getAmplifier();

					// In-case there is more than one speed effect on a player, get the max.
					if($a > $amplifier) {
						$amplifier = $a;
					}
				}
			}
		}

		$distance = self::WALKING_SPEED + ($amplifier != 0) ? (self::WALKING_SPEED / (0.2 * $amplifier)) : 0;

		return $distance * ($tickDifference / 20);
	}

        public function onDamage(EntityDamageEvent $event){
            if($event instanceof EntityDamageByEntityEvent and $event->getEntity() instanceof Player and $event->getDamager() instanceof Player and $event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK){
                if(round($event->getEntity()->distanceSquared($event->getDamager())) >= 12){
                    $this->point[$event->getDamager()]["reach"] += (float) 1;
                    $event->setCancelled();
					if((float) $this->point[$event->getDamager()]["reach"] > (float) 3){ 
					   $this->HackDetected($event->getDamager(), "Reach Hacks", "Salus", "1");
                    }
                }else{
		    $player = $event->getEntity();
		    assert($player instanceof Player);
                    $this->point[$player->getName()]["reach"] = (float) 0;
                }
            }
        }

        public function onPlayerJoin(PlayerJoinEvent $event){
            $this->reset($event->getPlayer());
            $this->CheckForceOP($event->getPlayer());
    	}
    	
    	public function reset(Player $player){
            $this->point[$player->getName()]["speed"] = (float) 0;
            $this->point[$player->getName()]["fly"] = (float) 0;
            $this->point[$player->getName()]["reach"] = (float) 0;
            $this->point[$player->getName()]["noclip"] = (float) 0;
            $this->speed[$player->getName()];
        }

        public function onPlayerQuit(PlayerQuitEvent $event){
            unset($this->point[$event->getPlayer()->getName()]);
            unset($this->speed[$event->getPlayer()->getName()]);
    	}

        public function onPlayerMove(PlayerMoveEvent $event){
            $player = $event->getPlayer();
		    $this->CheckForceOP($event);
		    $this->CheckNoClip($event);
		    $this->CheckFly($event);
    	}

        public function onRecieve(DataPacketReceiveEvent $event) {
            $player = $event->getPlayer();
            $packet = $event->getPacket();
            if($packet instanceof UpdateAttributesPacket){ 
                $this->HackDetected($player, "UpdateAttributesPacket Hacks", "Salus", "1");
            }
            if($packet instanceof SetPlayerGameTypePacket){ 
                $this->HackDetected($player, "Force-GameMode Hacks", "Salus", "1");
            }
            if($packet instanceof AdventureSettingsPacket){
                if(!$player->isCreative() and !$player->isSpectator() and !$player->isOp() and !$player->getAllowFlight()){
                    switch ($packet->flags){
                        case 614:
                        case 615:
                        case 103:
                        case 102:
                        case 38:
                        case 39:
                            $this->HackDetected($player, "Fly and NoClip Hacks", "Salus", "1");
                            break;
                        default:
                            break;
                    }
                    if((($packet->flags >> 9) & 0x01 === 1) or (($packet->flags >> 7) & 0x01 === 1) or (($packet->flags >> 6) & 0x01 === 1)){
                        $this->HackDetected($player, "Fly and NoClip Hacks", "Salus", "1");
                    }
                }
            }
        }
    }
