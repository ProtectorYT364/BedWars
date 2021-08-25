<?php

declare(strict_types=1);

/**
 * Bedwars - ChatListener.php
 * @author Fludixx
 * @license MIT
 */

namespace Fludixx\Bedwars\event;

use Fludixx\Bedwars\Bedwars;
use Fludixx\Bedwars\utils\Utils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;

class ChatListener implements Listener {

    public function onChat(PlayerChatEvent $event) {
        $player = Bedwars::$players[$event->getPlayer()->getName()];
        if($player->getPos() !== 0) {
            $arena = Bedwars::$arenas[$event->getPlayer()->getLevel()->getFolderName()];
            $messageArray = explode(" ", $event->getMessage());
            if(in_array("@all", $messageArray) or in_array("@a", $messageArray)) {
                if(in_array("@a", $messageArray)) {
                    $index = array_search("@a", $messageArray);
                } elseif(in_array("@all", $messageArray)) {
                    $index = array_search("@all", $messageArray);
                }
                unset($messageArray[$index]);
                $message = implode(" ", $messageArray);
                $arena->broadcast("§f[" . Utils::ColorInt2Color(Utils::teamIntToColorInt($player->getPos())) . "§f]" ." §7{$player->getName()}"."§f: ".$message);
            } else {
                foreach ($arena->getPlayers() as $splayer) {
                    $bwplayer = Bedwars::$players[$splayer->getName()];
                    if ($bwplayer->getPos() === $player->getPos()) {
                        $splayer->sendMessage("§e{$splayer->getName()} " . Utils::ColorInt2Color(Utils::teamIntToColorInt($bwplayer->getPos())) . "§f: " . $event->getMessage());
                    }
                }
            }
            $event->setCancelled(TRUE);
        }
    }

}
