<?php

/**
 * Bedwars - Bedwars.php
 * @author Fludixx
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars;

use Fludixx\Bedwars\command\BedWarsCommand;
use Fludixx\Bedwars\entity\Villager;
use Fludixx\Bedwars\event\BlockEventListener;
use Fludixx\Bedwars\event\ChatListener;
use Fludixx\Bedwars\event\EntityDamageListener;
use Fludixx\Bedwars\event\InteractListener;
use Fludixx\Bedwars\event\PlayerJoinListener;
use Fludixx\Bedwars\provider\JsonProvider;
use Fludixx\Bedwars\provider\ProviderInterface;
use Fludixx\Bedwars\ranking\JsonStats;
use Fludixx\Bedwars\ranking\StatsInterface;
use Fludixx\Bedwars\task\BWTask;
use Fludixx\Bedwars\task\SignTask;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as f;

/**
 * Class Bedwars
 * @package Fludixx\Bedwars
 * This is the Main class this class sets everything up, it loads and converts arenas into an Arena object (@see Arena.php), for example
 */
class Bedwars extends PluginBase implements Listener {

    const NAME    = "§c- BedWars -";
    const PREFIX  = "§7[§cBedWars§7] §f";
    const JOIN    = "§a[JOIN]";
    const FULL    = "§c[FULL]";
    const RUNNING = "§7[SPECTATE]";
    const BLOCKS = [ // Breakable blocks
        Block::SANDSTONE, Block::END_STONE, Block::GLASS,
        Block::CHEST, Block::COBWEB, Block::TNT
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
        Entity::registerEntity(Villager::class, true, ["ShopEntity", "bedwars:shop"]);
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
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
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

    public function bed(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $action = $event->getAction();
        if($action == $event::RIGHT_CLICK_BLOCK){
            if($block->getItemId() == Item::BED){
                if(isset(self::$arenas[$player->getLevel()->getFolderName()])){
                    $arena = self::$arenas[$player->getLevel()->getFolderName()];
                    if($arena->getState() == Arena::STATE_INUSE){
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    public function onDamage(EntityDamageEvent $event)
    {
        $player = $event->getEntity();
        if ($player->getNameTag() == f::GOLD.TextFormat::BOLD."Shop" && $player instanceof Villager) {
            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if ($damager instanceof Player) {
                    if(!isset(self::$arenas[$damager->getLevel()->getFolderName()])){
                        $event->setCancelled();
                        return;
                    }
                    $arena = self::$arenas[$damager->getLevel()->getFolderName()];
                    if($arena->getState() == Arena::STATE_INUSE){
                        $event->setCancelled();
                        $this->Overview($damager);
                        $event->setCancelled();
                    } else {
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    public function Overview(Player $player) {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->readonly();
        $menu->setName(f::GOLD.TextFormat::BOLD."Shop");
        $minv = $menu->getInventory();
        $platzhalter1 = Item::get(Item::STAINED_GLASS_PANE, 8)->setCustomName("");
        $platzhalter2 = Item::get(Item::STAINED_GLASS_PANE, 7)->setCustomName("");
        $selected = Item::get(Item::STAINED_GLASS_PANE, 14)->setCustomName("Current Category");
        $sandstone = Item::get(Item::SANDSTONE, 0, 16);
        $sandstone->setCustomName(f::YELLOW."16x".f::WHITE." Sandstone".f::RED." 4 Bronze");
        $stick = Item::get(Item::STICK, 0, 1);
        $stick->setCustomName(f::YELLOW."1x".f::WHITE." Stick".f::RED." 8 Bronze");
        $picke = Item::get(Item::WOODEN_PICKAXE, 0, 1);
        $picke->setCustomName(f::YELLOW."1x".f::WHITE." Pickaxe".f::RED." 4 Bronze");
        $schwert1 = Item::get(Item::GOLDEN_SWORD, 0, 1);
        $schwert1->setCustomName(f::YELLOW."1x".f::WHITE." Sword - 1".f::GRAY." 1 Iron");
        $helm = Item::get(Item::LEATHER_CAP)->setCustomName(f::YELLOW. "1x".f::WHITE." Cap".f::RED." 1 Bronze");
        $brust1 = Item::get(Item::CHAINMAIL_CHESTPLATE)->setCustomName(f::YELLOW. "1x".f::WHITE." Armor - 1".f::GRAY." 1 Iron");
        $hose = Item::get(Item::LEATHER_LEGGINGS)->setCustomName(f::YELLOW. "1x".f::WHITE." Trousers".f::RED." 1 Bronze");
        $boots = Item::get(Item::LEATHER_BOOTS)->setCustomName(f::YELLOW. "1x".f::WHITE." Shoes".f::RED." 1 Bronze");
        $bett = Item::get(Item::BED, 14, 1);$bett->setCustomName(f::YELLOW."Sweaters Category");
        $stein = Item::get(Item::BRICK_BLOCK, 0, 1);$stein->setCustomName(f::YELLOW."Block Category");
        $brust = Item::get(Item::CHAINMAIL_CHESTPLATE, 0, 1);$brust->setCustomName(f::YELLOW."Armors Category");
        $battle = Item::get(Item::IRON_SWORD, 0, 1);$battle->setCustomName(f::YELLOW."Combat Category");
        $extra = Item::get(Item::EXPERIENCE_BOTTLE, 0, 1);$extra->setCustomName(f::YELLOW."Gadgets");
        $minv->setItem(0, $stick);
        $minv->setItem(1, $picke);
        $minv->setItem(2, $sandstone);
        $minv->setItem(3, $schwert1);
        $minv->setItem(4, $platzhalter1);
        $minv->setItem(5, $helm);
        $minv->setItem(6, $brust1);
        $minv->setItem(7, $hose);
        $minv->setItem(8, $boots);
        $minv->setItem(9, $platzhalter2);
        $minv->setItem(10, $platzhalter2);
        $minv->setItem(11, $selected);
        $minv->setItem(12, $platzhalter2);
        $minv->setItem(13, $platzhalter2);
        $minv->setItem(14, $platzhalter2);
        $minv->setItem(15, $platzhalter2);
        $minv->setItem(16, $platzhalter2);
        $minv->setItem(17, $platzhalter2);
        $minv->setItem(18, $platzhalter1);
        $minv->setItem(19, $platzhalter1);
        $minv->setItem(20, $bett);
        $minv->setItem(21, $stein);
        $minv->setItem(22, $brust);
        $minv->setItem(23, $battle);
        $minv->setItem(24, $extra);
        $minv->setItem(25, $platzhalter1);
        $minv->setItem(26, $platzhalter1);
        $menu->send($player);
        $menu->setListener([new ShopListener($this), "onTransaction"]);
    }

    public function Bau(Player $player) {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->readonly();
        $menu->setName(f::DARK_GRAY."Shop");
        $minv = $menu->getInventory();
        $platzhalter1 = Item::get(Item::STAINED_GLASS_PANE, 8)->setCustomName("");
        $platzhalter2 = Item::get(Item::STAINED_GLASS_PANE, 7)->setCustomName("");
        $selected = Item::get(Item::STAINED_GLASS_PANE, 14)->setCustomName("Current Category");
        $sandstone = Item::get(Item::SANDSTONE, 0, 4);
        $sandstone->setCustomName(f::YELLOW."4x".f::WHITE." Sandstone".f::RED." 1 Bronze");
        $sandstone2 = Item::get(Item::SANDSTONE, 0, 16);
        $sandstone2->setCustomName(f::YELLOW."16x".f::WHITE." Sandstone".f::RED." 4 Bronze");
        $sandstone3 = Item::get(Item::SANDSTONE, 0, 64);
        $sandstone3->setCustomName(f::YELLOW."64x".f::WHITE." Sandstone".f::RED." 16 Bronze");
        $endstein = Item::get(Item::END_STONE, 0, 1);
        $endstein->setCustomName(f::YELLOW."1x".f::WHITE." Endstone".f::RED." 16 Bronze");
        $web = Item::get(Item::WEB, 0, 1);
        $web->setCustomName(f::YELLOW."1x".f::WHITE." Cobweb".f::RED." 8 Bronze");
        $bett = Item::get(Item::BED, 14, 1);$bett->setCustomName(f::YELLOW."Sweaters Category");
        $stein = Item::get(Item::BRICK_BLOCK, 0, 1);$stein->setCustomName(f::YELLOW."Block Category");
        $brust = Item::get(Item::CHAINMAIL_CHESTPLATE, 0, 1);$brust->setCustomName(f::YELLOW."Armors Category");
        $battle = Item::get(Item::IRON_SWORD, 0, 1);$battle->setCustomName(f::YELLOW."Combat Category");
        $extra = Item::get(Item::EXPERIENCE_BOTTLE, 0, 1);$extra->setCustomName(f::YELLOW."Gadgets");
        $minv->setItem(0, $sandstone);
        $minv->setItem(1, $sandstone2);
        $minv->setItem(2, $sandstone3);
        $minv->setItem(3, $platzhalter1);
        $minv->setItem(4, $endstein);
        $minv->setItem(5, $web);
        $minv->setItem(6, $platzhalter1);
        $minv->setItem(7, $platzhalter1);
        $minv->setItem(8, $platzhalter1);
        $minv->setItem(9, $platzhalter2);
        $minv->setItem(10, $platzhalter2);
        $minv->setItem(11, $platzhalter2);
        $minv->setItem(12, $selected);
        $minv->setItem(13, $platzhalter2);
        $minv->setItem(14, $platzhalter2);
        $minv->setItem(15, $platzhalter2);
        $minv->setItem(16, $platzhalter2);
        $minv->setItem(17, $platzhalter2);
        $minv->setItem(18, $platzhalter1);
        $minv->setItem(19, $platzhalter1);
        $minv->setItem(20, $bett);
        $minv->setItem(21, $stein);
        $minv->setItem(22, $brust);
        $minv->setItem(23, $battle);
        $minv->setItem(24, $extra);
        $minv->setItem(25, $platzhalter1);
        $minv->setItem(26, $platzhalter1);
        $menu->send($player);
        $menu->setListener([new ShopListener($this), "onTransaction"]);
    }

    public function Ruestung(Player $player) {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->readonly();
        $menu->setName(f::DARK_GRAY."Shop");
        $minv = $menu->getInventory();
        $platzhalter1 = Item::get(Item::STAINED_GLASS_PANE, 8)->setCustomName("");
        $platzhalter2 = Item::get(Item::STAINED_GLASS_PANE, 7)->setCustomName("");
        $selected = Item::get(Item::STAINED_GLASS_PANE, 14)->setCustomName("Current Category");
        $sandstone = Item::get(Item::SANDSTONE, 0, 16);
        $sandstone->setCustomName(f::YELLOW."16x".f::WHITE." Sandstone".f::RED." 4 Bronze");
        $stick = Item::get(Item::STICK, 0, 1);
        $stick->setCustomName(f::YELLOW."1x".f::WHITE." Stick".f::RED." 8 Bronze");
        $picke = Item::get(Item::WOODEN_PICKAXE, 0, 1);
        $picke->setCustomName(f::YELLOW."1x".f::WHITE." Pickaxe".f::RED." 4 Bronze");
        $schwert1 = Item::get(Item::GOLDEN_SWORD, 0, 1);
        $schwert1->setCustomName(f::YELLOW."1x".f::WHITE." Sword - 1".f::GRAY." 1 Iron");
        $schwert2 = Item::get(Item::GOLDEN_SWORD, 0, 1);
        $schwert2->setCustomName(f::YELLOW."1x".f::WHITE." Sword - 2".f::GRAY." 3 Iron");
        $schwert3 = Item::get(Item::GOLDEN_SWORD, 0, 1);
        $schwert3->setCustomName(f::YELLOW."1x".f::WHITE." Sword - 3".f::GRAY." 8 Iron");
        $bow1 = Item::get(Item::BOW, 0, 1);
        $bow1->setCustomName(f::YELLOW."1x".f::WHITE." Bow - 1".f::GOLD." 4 Gold");
        $bett = Item::get(Item::BED, 14, 1);$bett->setCustomName(f::YELLOW."Sweaters Category");
        $stein = Item::get(Item::BRICK_BLOCK, 0, 1);$stein->setCustomName(f::YELLOW."Block Category");
        $brust = Item::get(Item::CHAINMAIL_CHESTPLATE, 0, 1);$brust->setCustomName(f::YELLOW."Armors Category");
        $battle = Item::get(Item::IRON_SWORD, 0, 1);$battle->setCustomName(f::YELLOW."Combat Category");
        $extra = Item::get(Item::EXPERIENCE_BOTTLE, 0, 1);$extra->setCustomName(f::YELLOW."Gadgets");
        $minv->setItem(0, $schwert1);
        $minv->setItem(1, $schwert2);
        $minv->setItem(2, $schwert3);
        $minv->setItem(3, $platzhalter1);
        $minv->setItem(4, $bow1);
        $minv->setItem(5, $platzhalter1);
        $minv->setItem(6, $platzhalter1);
        $minv->setItem(7, $platzhalter1);
        $minv->setItem(8, $platzhalter1);
        $minv->setItem(9, $platzhalter2);
        $minv->setItem(10, $platzhalter2);
        $minv->setItem(11, $platzhalter2);
        $minv->setItem(12, $platzhalter2);
        $minv->setItem(13, $platzhalter2);
        $minv->setItem(14, $selected);
        $minv->setItem(15, $platzhalter2);
        $minv->setItem(16, $platzhalter2);
        $minv->setItem(17, $platzhalter2);
        $minv->setItem(18, $platzhalter1);
        $minv->setItem(19, $platzhalter1);
        $minv->setItem(20, $bett);
        $minv->setItem(21, $stein);
        $minv->setItem(22, $brust);
        $minv->setItem(23, $battle);
        $minv->setItem(24, $extra);
        $minv->setItem(25, $platzhalter1);
        $minv->setItem(26, $platzhalter1);
        $menu->send($player);
        $menu->setListener([new ShopListener($this), "onTransaction"]);
    }

    public function Battle(Player $player) {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->readonly();
        $menu->setName(f::DARK_GRAY."Shop");
        $minv = $menu->getInventory();
        $platzhalter1 = Item::get(Item::STAINED_GLASS_PANE, 8)->setCustomName("");
        $platzhalter2 = Item::get(Item::STAINED_GLASS_PANE, 7)->setCustomName("");
        $selected = Item::get(Item::STAINED_GLASS_PANE, 14)->setCustomName("Current Category");
        $sandstone = Item::get(Item::SANDSTONE, 0, 16);
        $sandstone->setCustomName(f::YELLOW."16x".f::WHITE." Sandstone".f::RED." 4 Bronze");
        $stick = Item::get(Item::STICK, 0, 1);
        $stick->setCustomName(f::YELLOW."1x".f::WHITE." Stick".f::RED." 8 Bronze");
        $picke = Item::get(Item::WOODEN_PICKAXE, 0, 1);
        $picke->setCustomName(f::YELLOW."1x".f::WHITE." Pickaxe".f::RED." 4 Bronze");
        $schwert1 = Item::get(Item::GOLDEN_SWORD, 0, 1);
        $schwert1->setCustomName(f::YELLOW."1x".f::WHITE." Sword - 1".f::GRAY." 1 Iron");
        $helm = Item::get(Item::LEATHER_CAP)->setCustomName(f::YELLOW. "1x".f::WHITE." Cap".f::RED." 1 Bronze");
        $brust1 = Item::get(Item::CHAINMAIL_CHESTPLATE)->setCustomName
        (f::YELLOW. "1x".f::WHITE." Armor - 1".f::GRAY." 1 Iron");
        $brust2 = Item::get(Item::CHAINMAIL_CHESTPLATE)->setCustomName
        (f::YELLOW. "1x".f::WHITE." Armor - 2" .f::GRAY." 3 Iron");
        $brust3 = Item::get(Item::IRON_CHESTPLATE)->setCustomName
        (f::YELLOW. "1x".f::WHITE." Armor - 3" .f::GRAY." 7 Iron");
        $brust4 = Item::get(Item::LEATHER_CHESTPLATE)->setCustomName
        (f::YELLOW. "1x".f::WHITE." Armor" .f::RED." 2 Bronze");
        $hose = Item::get(Item::LEATHER_LEGGINGS)->setCustomName
        (f::YELLOW. "1x".f::WHITE." Trousers".f::RED." 1 Bronze");
        $boots = Item::get(Item::LEATHER_BOOTS)->setCustomName
        (f::YELLOW. "1x".f::WHITE." Shoes".f::RED." 1 Bronze");
        $bett = Item::get(Item::BED, 14, 1);$bett->setCustomName(f::YELLOW."Sweaters Category");
        $stein = Item::get(Item::BRICK_BLOCK, 0, 1);$stein->setCustomName(f::YELLOW."Block Category");
        $brust = Item::get(Item::CHAINMAIL_CHESTPLATE, 0, 1);$brust->setCustomName(f::YELLOW."Armors Category");
        $battle = Item::get(Item::IRON_SWORD, 0, 1);$battle->setCustomName(f::YELLOW."Combat Category");
        $extra = Item::get(Item::EXPERIENCE_BOTTLE, 0, 1);$extra->setCustomName(f::YELLOW."Gadgets");
        $minv->setItem(0, $brust1);
        $minv->setItem(1, $brust2);
        $minv->setItem(2, $brust3);
        $minv->setItem(3, $brust4);
        $minv->setItem(4, $platzhalter1);
        $minv->setItem(5, $helm);
        $minv->setItem(6, $brust1);
        $minv->setItem(7, $hose);
        $minv->setItem(8, $boots);
        $minv->setItem(9, $platzhalter2);
        $minv->setItem(10, $platzhalter2);
        $minv->setItem(11, $platzhalter2);
        $minv->setItem(12, $platzhalter2);
        $minv->setItem(13, $selected);
        $minv->setItem(14, $platzhalter2);
        $minv->setItem(15, $platzhalter2);
        $minv->setItem(16, $platzhalter2);
        $minv->setItem(17, $platzhalter2);
        $minv->setItem(18, $platzhalter1);
        $minv->setItem(19, $platzhalter1);
        $minv->setItem(20, $bett);
        $minv->setItem(21, $stein);
        $minv->setItem(22, $brust);
        $minv->setItem(23, $battle);
        $minv->setItem(24, $extra);
        $minv->setItem(25, $platzhalter1);
        $minv->setItem(26, $platzhalter1);
        $menu->send($player);
        $menu->setListener([new ShopListener($this), "onTransaction"]);
    }

    public function Extra(Player $player) {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->readonly();
        $menu->setName(f::DARK_GRAY."Shop");
        $minv = $menu->getInventory();
        $platzhalter1 = Item::get(Item::STAINED_GLASS_PANE, 8)->setCustomName("");
        $platzhalter2 = Item::get(Item::STAINED_GLASS_PANE, 7)->setCustomName("");
        $selected = Item::get(Item::STAINED_GLASS_PANE, 14)->setCustomName("Current Category");
        $tnt = Item::get(Item::TNT, 0)->setCustomName(f::YELLOW. "1x".f::WHITE." TNT".f::GOLD." 2 Gold");
        $fire = Item::get(Item::FLINT_AND_STEEL, 0)->setCustomName(f::YELLOW. "1x".f::WHITE." Lighter".f::GRAY." 5 Iron");
        $ender = Item::get(Item::ENDER_PEARL, 0)->setCustomName(f::YELLOW. "1x".f::WHITE." Enderpearl".f::GOLD." 12 Gold");
        $safe = Item::get(Item::BLAZE_ROD, 0)->setCustomName(f::YELLOW. "1x".f::WHITE." Rescue Platform".f::GOLD." 6 Gold");
        $bett = Item::get(Item::BED, 14, 1);$bett->setCustomName(f::YELLOW."Sweaters Category");
        $stein = Item::get(Item::BRICK_BLOCK, 0, 1);$stein->setCustomName(f::YELLOW."Block Category");
        $brust = Item::get(Item::CHAINMAIL_CHESTPLATE, 0, 1);$brust->setCustomName(f::YELLOW."Armors Category");
        $battle = Item::get(Item::IRON_SWORD, 0, 1);$battle->setCustomName(f::YELLOW."Combat Category");
        $extra = Item::get(Item::EXPERIENCE_BOTTLE, 0, 1);$extra->setCustomName(f::YELLOW."Gadgets");
        $minv->setItem(0, $tnt);
        $minv->setItem(1, $ender);
        $minv->setItem(2, $safe);
        $minv->setItem(3, $fire);
        $minv->setItem(4, $platzhalter1);
        $minv->setItem(5, $platzhalter1);
        $minv->setItem(6, $platzhalter1);
        $minv->setItem(7, $platzhalter1);
        $minv->setItem(8, $platzhalter1);
        $minv->setItem(9, $platzhalter2);
        $minv->setItem(10, $platzhalter2);
        $minv->setItem(11, $platzhalter2);
        $minv->setItem(12, $platzhalter2);
        $minv->setItem(13, $platzhalter2);
        $minv->setItem(14, $platzhalter2);
        $minv->setItem(15, $selected);
        $minv->setItem(16, $platzhalter2);
        $minv->setItem(17, $platzhalter2);
        $minv->setItem(18, $platzhalter1);
        $minv->setItem(19, $platzhalter1);
        $minv->setItem(20, $bett);
        $minv->setItem(21, $stein);
        $minv->setItem(22, $brust);
        $minv->setItem(23, $battle);
        $minv->setItem(24, $extra);
        $minv->setItem(25, $platzhalter1);
        $minv->setItem(26, $platzhalter1);
        $menu->send($player);
        $menu->setListener([new ShopListener($this), "onTransaction"]);
    }

    public function addShop(Player $player)
    {
        $nbt = Entity::createBaseNBT($player->asVector3()->add(0, 0, 0), $player->getMotion(), $player->yaw, $player->pitch);
        $villager = new Villager($player->getLevel(), $nbt);
        $villager->setNameTagAlwaysVisible(true);
        $villager->setNameTagVisible(true);
        $villager->setNameTag(f::GOLD . f::BOLD . "Shop");
        $villager->setCanSaveWithChunk(true);
        $player->getLevel()->loadChunk($player->x >> 4, $player->z >> 4);
        $villager->spawnToAll();
    }
}