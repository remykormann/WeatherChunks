<?php

declare(strict_types=1);

namespace Mysonied\WeatherChunks;

use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\world\World;


class Weather{

        public const CLEAR = 0;
        public const RAIN = 2;
        public const THUNDER = 5;

        public function switchWeather(World $world, int $weather) : void {
            $packets = $this->getWeatherPackets($world, $weather);
            foreach ($world->getPlayers() as $player) {
                foreach ($packets as $pk) {
                    $player->getNetworkSession()->sendDataPacket($pk);
                }
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
}
