<?php

declare(strict_types=1);

namespace Mysonied\WeatherChunks;

use pocketmine\plugin\PluginBase;

class Main extends PluginBase{
    protected function onEnable(): void {
        $this->getLogger()->info("WeatherChunks enabled");
    }
}
