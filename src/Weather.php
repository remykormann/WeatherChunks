<?php

declare(strict_types=1);

namespace Mysonied\WeatherChunks;

use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\world\World;
use pocketmine\player\Player;


class Weather{
        public const CLEAR = 0;
        public const RAIN = 2;
        public const THUNDER = 5;

        public function __construct(
            private array $weatherData = []
        ) {}

        public function switchPlayerWeather(Player $player, World $world, int $weather) : void {
            $packets = $this->getWeatherPackets($world, $weather);
            foreach ($packets as $pk) {
                $player->getNetworkSession()->sendDataPacket($pk);
            }
        }

    	public function getWeatherPackets(World $world, int $weather) : array {
            $pks = [ new LevelEventPacket(), new LevelEventPacket() ];
            $conds = [0, 5000, 30000, 100000, 5000, 30000, 100000];

            # defaults
            $pks[0]->eventId = LevelEvent::STOP_RAIN;
            $pks[0]->eventData = 0;
            $pks[1]->eventId = LevelEvent::STOP_THUNDER;
            $pks[1]->eventData = 0;

            if ($weather > Weather::CLEAR) {
                $pks[0]->eventId = LevelEvent::START_RAIN;
                $pks[0]->eventData = $conds[$weather];
                $pks[1]->eventId = LevelEvent::STOP_THUNDER;
                $pks[1]->eventData = 0;
            }

            if ($weather >= Weather::THUNDER) {
                $pks[1]->eventId = LevelEvent::START_THUNDER;
                $pks[1]->eventData = $conds[$weather];
            }

            return $pks;
        }

        public function setWeather(World $world, int $chunkX, int $chunkZ, int $weather) : void {
            $key = $this->key($world, $chunkX, $chunkZ);

            if ($weather === Weather::CLEAR) {
                unset($this->weatherData[$key]);
                return;
            }

            $this->weatherData[$key] = $weather;
        }

        public function updateWeather(World $world) : void {
            foreach ($world->getPlayers() as $player) {
                $weather = $this->getPlayerWeather($player);
                $this->switchPlayerWeather($player, $world, $weather);
            }
        }

        public function getPlayerWeather(Player $player) : int {
            $world = $player->getWorld();
            $pos = $player->getPosition();
            $chunkX = $pos->getFloorX() >> 4;
            $chunkZ = $pos->getFloorZ() >> 4;
            $key = $this->key($world, $chunkX, $chunkZ);
            return $this->weatherData[$key] ?? Weather::CLEAR;
        }

        private function key(World $world, int $chunkX, int $chunkZ) : string {
            return $world->getFolderName() . ":" . $chunkX . ":" . $chunkZ;
        }

        public function get(World $world, int $chunkX, int $chunkZ) : int {
            return $this->weatherData[$this->key($world, $chunkX, $chunkZ)] ?? Weather::CLEAR;
        }

        public function all() : array {
            return $this->weatherData;
        }

        public function load(array $data) : void {
            $this->weatherData = $data;
        }
}
