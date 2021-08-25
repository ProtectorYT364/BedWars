<?php

/**
 * Bedwars - Bedwars.php
 * @author Fludixx
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars\entity;

use pocketmine\entity\Villager as PMVillager;

class Villager extends PMVillager
{

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $this->setNameTagAlwaysVisible(true);
        $this->setNameTagVisible(true);
        return true;
    }

    public function getName(): string
    {
        return 'Shop';
    }
}