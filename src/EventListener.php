<?php

declare(strict_types=1);

namespace Mysonied\WeatherChunks;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;
use pocketmine\event\player\PlayerJoinEvent;

class EventListener implements Listener {

    public function __construct(private Main $plugin) {}

    //on move event
    public function onPlayerMove(PlayerMoveEvent $event) : void {
        $from = $event->getFrom();
        $to = $event->getTo();

        if (
            $from->getFloorX() === $to->getFloorX() &&
            $from->getFloorZ() === $to->getFloorZ()
        ) {
            return;
        }

        $chunkFromX = $from->getFloorX() >> 4;
        $chunkToX = $to->getFloorX() >> 4;
        $chunkFromZ = $from->getFloorZ() >> 4;
        $chunkToZ = $to->getFloorZ() >> 4;

        if($chunkFromX === $chunkToX && $chunkFromZ === $chunkToZ) return;

        $this->onPlayerChangeChunk($event->getPlayer(), $chunkFromX, $chunkFromZ, $chunkToX, $chunkToZ);
    }

    public function onPlayerChangeChunk(Player $player, int $chunkFromX, int $chunkFromZ, int $chunkToX, int $chunkToZ) : void {
        $world = $player->getWorld();
        $weatherManager = $this->plugin->weatherManager;

        if ($weatherManager === null) {
            return;
        }

        $weatherFrom = $weatherManager->get($world, $chunkFromX, $chunkFromZ);
        $weatherTo = $weatherManager->get($world, $chunkToX, $chunkToZ);

        if($weatherFrom === $weatherTo) return;

        $weatherManager->switchPlayerWeather($player, $world, $weatherTo);
    }

    public function onPlayerJoin(PlayerJoinEvent $event) : void {
        $player = $event->getPlayer();
        $world = $player->getWorld();
        $pos = $player->getPosition();
        $chunkX = $pos->getFloorX() >> 4;
        $chunkZ = $pos->getFloorZ() >> 4;

        $weatherManager = $this->plugin->weatherManager;
        if ($weatherManager === null) {
            return;
        }

        $weather = $weatherManager->get($world, $chunkX, $chunkZ);
        $weatherManager->switchPlayerWeather($player, $world, $weather);
    }

}
