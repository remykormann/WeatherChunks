<?php

declare(strict_types=1);

namespace Mysonied\WeatherChunks;

use pocketmine\world\World;
use pocketmine\scheduler\Task;

class Shedule extends Task{
    private Weather $weatherManager;
    private World $world;

    public function __construct(Weather $weatherManager, World $world) {
        $this->weatherManager = $weatherManager;
        $this->world = $world;
    }

    public function onRun(): void {
        $this->weatherManager->updateWeather($this->world);
    }

}