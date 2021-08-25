<?php

/**
 * Bedwars - EntityDamageListener.php
 * @author Fludixx
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars\event;

use Fludixx\Bedwars\Arena;
use Fludixx\Bedwars\Bedwars;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class EntityDamageListener implements Listener {

    /**
     * @var Config
     */
    public $config;

    public function __construct()
    {
        $this->config = new Config(Bedwars::getInstance()->getDataFolder() . "config.yml", Config::YAML);
    }

    public function onDamageByEntity(EntityDamageByEntityEvent $event) {
        $player = $event->getEntity();
        $damager = $event->getDamager();
        if($player instanceof Player and $damager instanceof Player) {
            if(Bedwars::$players[$player->getName()]->getPos() > 0 and Bedwars::$players[$player->getName()]->getPos() !== Bedwars::$players[$damager->getName()]->getPos()) {
                Bedwars::$players[$player->getName()]->setKnocker($damager->getName());
                if ($player->getHealth() <= $event->getFinalDamage()) {
                    $event->setCancelled(true);
                    $levelname = $player->getLevel()->getFolderName();
                    $pos = Bedwars::$arenas[$levelname]->getSpawns()[Bedwars::$players[$player->getName()]->getPos()];
                    Bedwars::$arenas[$levelname]->broadcast("§e" . Bedwars::$players[$player->getName()]->getName() . "§f was killed by §e" .
                        Bedwars::$players[$damager->getName()]->getName());
                    if($this->config->get("respawn-delay") == false){
                        $player->teleport($pos);
                    } else {
                        Bedwars::getInstance()->getScheduler()->scheduleRepeatingTask(new class($player, $pos) extends Task{

                            public $player;
                            public $pos;
                            public $timer = 4;

                            public function __construct(Player $player, $pos)
                            {
                                $this->player = $player;
                                $this->pos = $pos;
                            }

                            /**
                             * @param int $currentTick
                             */
                            public function onRun(int $currentTick)
                            {
                                if($this->timer == 4) {
                                    $this->player->teleport($this->pos);
                                    $this->player->setGamemode(3);
                                }
                                $this->timer--;
                                if($this->timer == 3){
                                    $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "3" . TextFormat::YELLOW . " sec.");
                                }
                                if($this->timer == 2){
                                    $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "2" . TextFormat::YELLOW . " sec.");
                                }
                                if($this->timer == 1){
                                    $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "1" . TextFormat::YELLOW . " sec.");
                                }
                                if($this->timer == 0){
                                    $this->player->teleport($this->pos);
                                    $this->player->addTitle(TextFormat::GREEN . "Respawned");
                                    $this->player->setGamemode(0);
                                    $this->getHandler()->cancel();
                                }
                            }
                        }, 20);
                    }
                    Bedwars::$statsSystem->set($player, 'deaths', (int)Bedwars::$statsSystem->get($player, 'deaths')+1);
                    Bedwars::$statsSystem->set($damager, 'kills', (int)Bedwars::$statsSystem->get($damager, 'kills')+1);
                    Bedwars::$players[$player->getName()]->die();
                }
                return;
            }
            $event->setCancelled(true);
            if($damager->getInventory()->getItemInHand()->getId() === Item::IRON_SWORD) {
                $mdamager = Bedwars::$players[$damager->getName()];
                $mdamager->setVaule('hit', $player->getName());
                $mplayer = Bedwars::$players[$player->getName()];
                $mdamager->sendMsg("You have challenged {$mplayer->getName()}!");
                $mplayer->sendMsg("{$mdamager->getName()} has challenged you!");
                if($mplayer->getVaule('hit') === $damager->getName()) {
                    $mplayer->sendMsg("Searching for an empty Arena...");
                    $mdamager->sendMsg("Searching for an empty Arena...");
                    foreach (Bedwars::$arenas as $name => $class) {
                        if($class->isDuelMap() and count($class->getPlayers()) === 0 and $class->getState() === Arena::STATE_OPEN) {
                            $mplayer->sendMsg("Arena $name found!");
                            $mdamager->sendMsg("Arena $name found!");
                            $mplayer->setTeam(1);
                            $mdamager->setTeam(2);
                            $mplayer->saveTeleport($class->getLevel()->getSafeSpawn());
                            $mdamager->saveTeleport($class->getLevel()->getSafeSpawn());
                            $class->setCountdown(5);
                            $player->getInventory()->clearAll();
                            $damager->getInventory()->clearAll();
                            return;
                        }
                    }
                    $mplayer->sendMsg("No free Arena found :(");
                    $mdamager->sendMsg("No free Arena found :(");
                    return;
                }
            }
        }
    }

    public function onDamage(EntityDamageEvent $event) {
        $player = $event->getEntity();
        if($player instanceof Player) {
            if(Bedwars::$players[$player->getName()]->getPos() <= 0 and $event->getCause() !== EntityDamageEvent::CAUSE_VOID) {
                $event->setCancelled(true);
                return;
            }
            if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                if($player instanceof Player){
                    if($player->isCreative()){
                        return;
                    }
                }
                if($player->getHealth() <= $event->getFinalDamage()) {
                    $event->setCancelled(true);
                    if(Bedwars::$players[$player->getName()]->getPos() > 0){
                        $levelname = $player->getLevel()->getFolderName();
                        $pos = Bedwars::$arenas[$levelname]->getSpawns()[Bedwars::$players[$player->getName()]->getPos()];
                        if(is_string(Bedwars::$players[$player->getName()]->getKnocker())) {
                            Bedwars::$arenas[$levelname]->broadcast("§e" . Bedwars::$players[$player->getName()]->getName() . "§f was killed by §e" .
                                Bedwars::$players[$player->getName()]->getKnocker());
                            if($this->config->get("respawn-delay") == false){
                                //only with this way to respawn without glitches
                                Bedwars::getInstance()->getScheduler()->scheduleRepeatingTask(new class($player, $pos) extends Task{

                                    public $player;
                                    public $pos;
                                    public $timer = 1;

                                    public function __construct(Player $player, $pos)
                                    {
                                        $this->player = $player;
                                        $this->pos = $pos;
                                    }

                                    /**
                                     * @param int $currentTick
                                     */
                                    public function onRun(int $currentTick)
                                    {
                                        $this->timer--;
                                        if($this->timer == 0){
                                            $this->player->teleport($this->pos);
                                            $this->player->setGamemode(0);
                                            $this->getHandler()->cancel();
                                        }
                                    }
                                }, 20);
                            } else {
                                Bedwars::getInstance()->getScheduler()->scheduleRepeatingTask(new class($player, $pos) extends Task{

                                    public $player;
                                    public $pos;
                                    public $timer = 4;

                                    public function __construct(Player $player, $pos)
                                    {
                                        $this->player = $player;
                                        $this->pos = $pos;
                                    }

                                    /**
                                     * @param int $currentTick
                                     */
                                    public function onRun(int $currentTick)
                                    {
                                        if($this->timer == 4) {
                                            $this->player->teleport($this->pos);
                                            $this->player->setGamemode(3);
                                        }
                                        $this->timer--;
                                        if($this->timer == 3){
                                            $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "3" . TextFormat::YELLOW . " sec.");
                                        }
                                        if($this->timer == 2){
                                            $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "2" . TextFormat::YELLOW . " sec.");
                                        }
                                        if($this->timer == 1){
                                            $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "1" . TextFormat::YELLOW . " sec.");
                                        }
                                        if($this->timer == 0){
                                            $this->player->teleport($this->pos);
                                            $this->player->addTitle(TextFormat::GREEN . "Respawned");
                                            $this->player->setGamemode(0);
                                            $this->getHandler()->cancel();
                                        }
                                    }
                                }, 20);
                            }
                        } else {
                            Bedwars::$arenas[$levelname]->broadcast("§e" . Bedwars::$players[$player->getName()]->getName() . "§f has died!");
                            if($this->config->get("respawn-delay") == false){
                                $player->teleport($pos);
                            } else {
                                Bedwars::getInstance()->getScheduler()->scheduleRepeatingTask(new class($player, $pos) extends Task{

                                    public $player;
                                    public $pos;
                                    public $timer = 4;

                                    public function __construct(Player $player, $pos)
                                    {
                                        $this->player = $player;
                                        $this->pos = $pos;
                                    }

                                    /**
                                     * @param int $currentTick
                                     */
                                    public function onRun(int $currentTick)
                                    {
                                        if($this->timer == 4) {
                                            $this->player->teleport($this->pos);
                                            $this->player->setGamemode(3);
                                        }
                                        $this->timer--;
                                        if($this->timer == 3){
                                            $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "3" . TextFormat::YELLOW . " sec.");
                                        }
                                        if($this->timer == 2){
                                            $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "2" . TextFormat::YELLOW . " sec.");
                                        }
                                        if($this->timer == 1){
                                            $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "1" . TextFormat::YELLOW . " sec.");
                                        }
                                        if($this->timer == 0){
                                            $this->player->teleport($this->pos);
                                            $this->player->addTitle(TextFormat::GREEN . "Respawned");
                                            $this->player->setGamemode(0);
                                            $this->getHandler()->cancel();
                                        }
                                    }
                                }, 20);
                            }
                        }
                        Bedwars::$statsSystem->set($player, 'deaths', (int)Bedwars::$statsSystem->get($player, 'deaths')+1);
                        if(is_string(Bedwars::$players[$player->getName()]->getKnocker())) {
                            $killer = Bedwars::getInstance()->getServer()->getPlayerExact(Bedwars::$players[$player->getName()]->getKnocker());
                            if ($killer instanceof Player)
                                Bedwars::$statsSystem->set($killer, 'kills', (int)Bedwars::$statsSystem->get($killer, 'kills') + 1);
                        }
                        Bedwars::$players[$player->getName()]->die();
                    } else {
                        $player->teleport($player->getLevel()->getSafeSpawn());
                    }
                    return;
                }
                $event->setCancelled(false);
            }
            else if($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                $event->setCancelled(true);
                if(Bedwars::$players[$player->getName()]->getPos() > 0) {
                    $levelname = $player->getLevel()->getFolderName();
                    $pos = Bedwars::$arenas[$levelname]->getSpawns()[Bedwars::$players[$player->getName()]->getPos()];
                    if(is_string(Bedwars::$players[$player->getName()]->getKnocker())) {
                        Bedwars::$arenas[$levelname]->broadcast("§e" . Bedwars::$players[$player->getName()]->getName() . "§f was killed by §e" .
                            Bedwars::$players[$player->getName()]->getKnocker());
                        if($this->config->get("respawn-delay") == false){
                            $player->teleport($pos);
                        } else {
                            Bedwars::getInstance()->getScheduler()->scheduleRepeatingTask(new class($player, $pos) extends Task{

                                public $player;
                                public $pos;
                                public $timer = 4;

                                public function __construct(Player $player, $pos)
                                {
                                    $this->player = $player;
                                    $this->pos = $pos;
                                }

                                /**
                                 * @param int $currentTick
                                 */
                                public function onRun(int $currentTick)
                                {
                                    if($this->timer == 4) {
                                        $this->player->teleport($this->pos);
                                        $this->player->setGamemode(3);
                                    }
                                    $this->timer--;
                                    if($this->timer == 3){
                                        $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "3" . TextFormat::YELLOW . " sec.");
                                    }
                                    if($this->timer == 2){
                                        $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "2" . TextFormat::YELLOW . " sec.");
                                    }
                                    if($this->timer == 1){
                                        $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "1" . TextFormat::YELLOW . " sec.");
                                    }
                                    if($this->timer == 0){
                                        $this->player->teleport($this->pos);
                                        $this->player->addTitle(TextFormat::GREEN . "Respawned");
                                        $this->player->setGamemode(0);
                                        $this->getHandler()->cancel();
                                    }
                                }
                            }, 20);
                        }
                    } else {
                        Bedwars::$arenas[$levelname]->broadcast("§e" . Bedwars::$players[$player->getName()]->getName() . "§f died!");
                        if($this->config->get("respawn-delay") == false){
                            $player->teleport($pos);
                        } else {
                            Bedwars::getInstance()->getScheduler()->scheduleRepeatingTask(new class($player, $pos) extends Task{

                                public $player;
                                public $pos;
                                public $timer = 4;

                                public function __construct(Player $player, $pos)
                                {
                                    $this->player = $player;
                                    $this->pos = $pos;
                                }

                                /**
                                 * @param int $currentTick
                                 */
                                public function onRun(int $currentTick)
                                {
                                    if($this->timer == 4) {
                                        $this->player->teleport($this->pos);
                                        $this->player->setGamemode(3);
                                    }
                                    $this->timer--;
                                    if($this->timer == 3){
                                        $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "3" . TextFormat::YELLOW . " sec.");
                                    }
                                    if($this->timer == 2){
                                        $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "2" . TextFormat::YELLOW . " sec.");
                                    }
                                    if($this->timer == 1){
                                        $this->player->addTitle(TextFormat::YELLOW . "Respawning in " . TextFormat::RED . "1" . TextFormat::YELLOW . " sec.");
                                    }
                                    if($this->timer == 0){
                                        $this->player->teleport($this->pos);
                                        $this->player->addTitle(TextFormat::GREEN . "Respawned");
                                        $this->player->setGamemode(0);
                                        $this->getHandler()->cancel();
                                    }
                                }
                            }, 20);
                        }
                    }
                    Bedwars::$players[$player->getName()]->die();
                } else {
                    $player->teleport($player->getLevel()->getSafeSpawn());
                }
            }
        }
    }

    public function onHunger(PlayerExhaustEvent $event) {
        $event->getPlayer()->setFood(20);
        $event->setCancelled(true);
    }

}
