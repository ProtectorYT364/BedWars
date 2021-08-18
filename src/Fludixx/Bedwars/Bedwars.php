<?php

/**
 * Bedwars - Bedwars.php
 * @author Fludixx
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars;

use Fludixx\Bedwars\command\BedWarsCommand;
use Fludixx\Bedwars\event\BlockEventListener;
use Fludixx\Bedwars\event\ChatListener;
use Fludixx\Bedwars\event\EntityDamageListener;
use Fludixx\Bedwars\event\InteractListener;
use Fludixx\Bedwars\event\PlayerJoinListener;
use Fludixx\Bedwars\event\TakeItemListener;
use Fludixx\Bedwars\provider\JsonProvider;
use Fludixx\Bedwars\provider\ProviderInterface;
use Fludixx\Bedwars\ranking\JsonStats;
use Fludixx\Bedwars\ranking\StatsInterface;
use Fludixx\Bedwars\task\BWTask;
use Fludixx\Bedwars\task\SignTask;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

/**
 * Class Bedwars
 * @package Fludixx\Bedwars
 * This is the Main class this class sets everything up, it loads and converts arenas into an Arena object (@see Arena.php), for example
 */
class Bedwars extends PluginBase {

    const NAME    = "§c- BedWars -";
    const PREFIX  = "§7[§cBedWars§7] §f";
    const JOIN    = "§a[JOIN]";
    const FULL    = "§c[FULL]";
    const RUNNING = "§7[SPECTATE]";
    const BLOCKS = [ // Breakable blocks
        Block::SANDSTONE, Block::END_STONE, Block::GLASS,
        Block::CHEST, Block::COBWEB
    ];

    /** @var Bedwars */
    private static $instance;
    /** @var ProviderInterface */
    public static $provider;
    /** @var BWPlayer[] */
    public static $players = [];
    /** @var Arena[] */
    public static $arenas = [];
    public static $mysqlLogin = [];
    /** @var StatsInterface */
    public static $statsSystem;
    private $settings = [
        'stats' => 'json'
    ];

    public function onEnable()
    {
        self::$instance = $this;
        @mkdir($this->getDataFolder(), 0777, true);
        $this->saveDefaultConfig();
        if(!file_exists($this->getDataFolder()."/mysql.yml")) {
            $mysql = new Config($this->getDataFolder()."/mysql.yml", Config::YAML);
            $mysql->setAll([
                'host' => '127.0.0.1',
                'user' => 'admin',
                'pass' => 'admin',
                'db'   => 'bwStats'
            ]);
            $mysql->save();
        }
        $mysql = new Config($this->getDataFolder()."/mysql.yml", Config::YAML);
        self::$mysqlLogin = $mysql->getAll();
        switch ($this->settings['stats']) {
            case 'mysql':
                // TODO make mysql stats
                //self::$statsSystem = new MySqlStats();
                break;
            default:
                self::$statsSystem = new JsonStats();
        }
        if(!$this->getServer()->loadLevel("transfare"))
            $this->getServer()->generateLevel("transfare");
        self::$provider = new JsonProvider();
        $this->registerCommands();
        $this->registerEvents();
        $this->loadArenas();
        $this->getScheduler()->scheduleRepeatingTask(new BWTask(), 20);
        $this->getScheduler()->scheduleRepeatingTask(new SignTask(), 20);
        $this->getLogger()->info(self::PREFIX."Bedwars loaded");
        if (!InvMenuHandler::isRegistered())
            InvMenuHandler::register(Bedwars::getInstance());
    }

    private function registerEvents() {
        $pm = $this->getServer()->getPluginManager();
        $pm->registerEvents(new PlayerJoinListener(), $this);
        $pm->registerEvents(new EntityDamageListener(), $this);
        $pm->registerEvents(new BlockEventListener(), $this);
        $pm->registerEvents(new InteractListener(), $this);
        $pm->registerEvents(new ChatListener(), $this);
    }

    private function registerCommands() {
        $map = $this->getServer()->getCommandMap();
        $map->register("bw", new BedWarsCommand());
    }

    private function loadArenas() {
        foreach (self::$provider->getArenas() as $name => $data) {
            $this->getServer()->loadLevel($data['mapname']);
            $level = $this->getServer()->getLevelByName($data['mapname']);
            self::$arenas[$name] = new Arena($data['mapname'], (int)$data['ppt'], (int)$data['teams'], $level, $data['spawns']);
        }
    }

    public static function getInstance() : Bedwars {
        return self::$instance;
    }
}