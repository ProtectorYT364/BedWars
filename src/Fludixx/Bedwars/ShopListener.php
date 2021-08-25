<?php

/**
 * @author Fludixx
 * @version 3.0
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat as f;

class ShopListener
{
    public $plugin;

    public function __construct(Bedwars $plugin)
    {

        $this->plugin = $plugin;

    }

    public function count(Player $player, int $id = Item::BRICK): int{
        $all = 0;
        $inv = $player->getInventory();
        $content = $inv->getContents();
        foreach ($content as $item) {
            if ($item->getId() == $id) {
                $c = $item->count;
                $all = $all + $c;
            }
        }
        return $all;
    }
    public function rm(Player $player, int $id = Item::BRICK){
        $player->getInventory()->remove(Item::get($id, 0, 1));
    }
    public function add(Player $player, int $i, int $id = Item::BRICK){
        $name = $player->getName();
        $inv = $player->getInventory();
        $c = 0;
        while($c < $i){
            $inv->addItem(
                Item::get(
                    $id,
                    0,
                    1));
            $c++;
        }
    }

    public function setPrice(Player $player, int $price, int $id) : bool {
        $woola = $this->count($player, $id);
        $name = $player->getName();
        if($woola < $price) {
            $need = (int)$price - (int)$woola;
            return false;
        } else {
            $woolprice = $price;
            $wooltot = $woola-$woolprice;
            $this->rm($player, $id);
            $this->add($player, $wooltot, $id);
            return true;}
    }

    public function onTransaction(Player $player, Item $itemClickedOn, Item $itemClickedWith): bool
    {
        if($itemClickedOn->getCustomName() == f::YELLOW."Sweaters Category") {
            $this->plugin->Overview($player);
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."Block Category") {
            $this->plugin->Bau($player);
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."Armors Category") {
            $this->plugin->Battle($player);
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."Combat Category") {
            $this->plugin->Ruestung($player);
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."Gadgets") {
            $this->plugin->Extra($player);
        }


        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Stick".f::RED." 8 Bronze") {
            $price = $this->setPrice($player, 8, Item::BRICK);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::KNOCKBACK);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Pickaxe".f::RED." 4 Bronze") {
            $price = $this->setPrice($player, 4, Item::BRICK);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::EFFICIENCY);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $enchantment = Enchantment::getEnchantment(Enchantment::UNBREAKING);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 2));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."16x".f::WHITE." Sandstone".f::RED." 4 Bronze") {
            $price = $this->setPrice($player, 4, Item::BRICK);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                //$enchantment = Enchantment::getEnchantment(Enchantment::KNOCKBACK);
                //$item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."4x".f::WHITE." Sandstone".f::RED." 1 Bronze") {
            $price = $this->setPrice($player, 1, Item::BRICK);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                //$enchantment = Enchantment::getEnchantment(Enchantment::KNOCKBACK);
                //$item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."64x".f::WHITE." Sandstone".f::RED." 16 Bronze") {
            $price = $this->setPrice($player, 16, Item::BRICK);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                //$enchantment = Enchantment::getEnchantment(Enchantment::KNOCKBACK);
                //$item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Endstone".f::RED." 16 Bronze") {
            $price = $this->setPrice($player, 16, Item::BRICK);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                //$enchantment = Enchantment::getEnchantment(Enchantment::KNOCKBACK);
                //$item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Cobweb".f::RED." 8 Bronze") {
            $price = $this->setPrice($player, 8, Item::BRICK);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                //$enchantment = Enchantment::getEnchantment(Enchantment::KNOCKBACK);
                //$item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Sword - 1".f::GRAY." 1 Iron") {
            $price = $this->setPrice($player, 1, Item::IRON_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::SHARPNESS);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $enchantment = Enchantment::getEnchantment(Enchantment::UNBREAKING);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 2));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Armor - 1".f::GRAY." 1 Iron") {
            $price = $this->setPrice($player, 1, Item::IRON_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::PROTECTION);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Cap".f::RED." 1 Bronze") {
            $price = $this->setPrice($player, 1, Item::BRICK);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::PROTECTION);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Trousers".f::RED." 1 Bronze") {
            $price = $this->setPrice($player, 1, Item::BRICK);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::PROTECTION);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Shoes".f::RED." 1 Bronze") {
            $price = $this->setPrice($player, 1, Item::BRICK);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::PROTECTION);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Armor - 2".f::GRAY." 3 Iron") {
            $price = $this->setPrice($player, 3, Item::IRON_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::PROTECTION);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 2));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Armor - 3".f::GRAY." 7 Iron") {
            $price = $this->setPrice($player, 7, Item::IRON_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::PROTECTION);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 3));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Sword - 2".f::GRAY." 3 Iron") {
            $price = $this->setPrice($player, 3, Item::IRON_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::SHARPNESS);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 2));
                $enchantment = Enchantment::getEnchantment(Enchantment::UNBREAKING);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 3));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Sword - 3".f::GRAY." 8 Iron") {
            $price = $this->setPrice($player, 8, Item::IRON_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::SHARPNESS);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 3));
                $enchantment = Enchantment::getEnchantment(Enchantment::UNBREAKING);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 4));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Bow - 1".f::GOLD." 4 Gold") {
            $price = $this->setPrice($player, 4, Item::GOLD_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),250, $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::PUNCH);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $enchantment = Enchantment::getEnchantment(Enchantment::INFINITY);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 4));
                $player->getInventory()->addItem($item);
                $player->getInventory()->addItem(Item::get(Item::ARROW,0, 1));
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Enderpearl".f::GOLD." 12 Gold") {
            $price = $this->setPrice($player, 12, Item::GOLD_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),10, $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::PUNCH);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $enchantment = Enchantment::getEnchantment(Enchantment::INFINITY);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 4));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." TNT".f::GOLD." 2 Gold") {
            $price = $this->setPrice($player, 2, Item::GOLD_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Lighter".f::GRAY." 5 Iron") {
            $price = $this->setPrice($player, 5, Item::IRON_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        if($itemClickedOn->getCustomName() == f::YELLOW."1x".f::WHITE." Rescue Platform".f::GOLD." 6 Gold") {
            $price = $this->setPrice($player, 6, Item::GOLD_INGOT);
            if($price == true) {
                $item = Item::get($itemClickedOn->getId(),$itemClickedOn->getDamage(), $itemClickedOn->getCount());
                $item->setCustomName($item->getVanillaName());
                $enchantment = Enchantment::getEnchantment(Enchantment::PUNCH);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 1));
                $enchantment = Enchantment::getEnchantment(Enchantment::INFINITY);
                $item->addEnchantment(new EnchantmentInstance($enchantment, 4));
                $player->getInventory()->addItem($item);
                return true;
            } else {
                $player->sendMessage(f::RED."Not enough resources!");
                $player->sendPopup(f::RED."Not enough resources");
                return false;
            }
        }
        return false;
    }
}