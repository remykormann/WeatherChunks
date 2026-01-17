<?php

declare(strict_types=1);

namespace Mysonied\WeatherChunks;

use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\world\World;
use pocketmine\player\Player;
use pocketmine\plugin\PluginLogger as Logger;


class Weather{
    public const CLEAR = 0;
    public const DOWNFALL = 1;
    public const RAIN = 2;
    public const HEAVY_RAIN = 3;
    public const STORM = 4;
    public const THUNDER = 5;
    public const HEAVY_THUNDER = 6;

    public int $SIZE = 256;
    public array $rainMap = [];

    /*public function __construct(
        private array $weatherData = []
    ) {}*/

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
        //faire l'inverse de getRainIntensity pour placer la valeur dans la map
        $speedX = 0.001;
        $speedZ = 0.0007;
        $daytime = (int)$world->getTime();
        $size = $this->SIZE;
        $ox = (int)($daytime * $speedX) % $size;
        $oz = (int)($daytime * $speedZ) % $size;

        $x = ($chunkX + $ox) % $size;
        $z = ($chunkZ + $oz) % $size;

        $this->rainMap[$x < 0 ? $x + $size : $x][$z < 0 ? $z + $size : $z] = match($weather) {
            Weather::CLEAR => 0.0,
            Weather::DOWNFALL => 0.3,
            Weather::RAIN => 0.5,
            Weather::HEAVY_RAIN => 0.7,
            Weather::STORM => 0.85,
            Weather::THUNDER => 0.95,
            Weather::HEAVY_THUNDER => 1.0,
            default => 0.0,
        };

        $this->updateWeather($world);
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
        return $this->get($world, $chunkX, $chunkZ);
    }

    private function key(World $world, int $chunkX, int $chunkZ) : string {
        return $world->getFolderName() . ":" . $chunkX . ":" . $chunkZ;
    }

    public function get(World $world, int $chunkX, int $chunkZ) : int {
        $naturalWeather = $this->getRainIntensity(
            $chunkX,
            $chunkZ,
            (int)$world->getTime(),
            $this->rainMap,
            $this->SIZE
        );
        return $this->intensityToWeatherMode($naturalWeather);
    }

    private function hashInt(int $x): int {
        $x ^= ($x << 13);
        $x ^= ($x >> 17);
        $x ^= ($x << 5);
        return $x & 0x7fffffff;
    }

    function getRainIntensity(
        int $chunkX,
        int $chunkZ,
        int $daytime,
        array $map,
        int $size
    ): float {

        $speedX = 0.001;
        $speedZ = 0.0007;

        $ox = (int)($daytime * $speedX) % $size;
        $oz = (int)($daytime * $speedZ) % $size;

        $x = ($chunkX + $ox) % $size;
        $z = ($chunkZ + $oz) % $size;

        return $map[$x < 0 ? $x + $size : $x][$z < 0 ? $z + $size : $z];
    }

    public function generateRainMap(World $world, int $daytime): void {

        for ($x = 0; $x < $this->SIZE; $x++) {
            for ($z = 0; $z < $this->SIZE; $z++) {

                // biais fort vers 0 (zones sèches)
                $r = mt_rand() / mt_getrandmax();
                $this->rainMap[$x][$z] = pow($r, 3.0);
            }
        }

        // lissage spatial
        for ($i = 0; $i < 4; $i++) {
            $this->blur($this->rainMap, $this->SIZE);
        }

        // seuil doux : écrase les faibles intensités
        for ($x = 0; $x < $this->SIZE; $x++) {
            for ($z = 0; $z < $this->SIZE; $z++) {
                if ($this->rainMap[$x][$z] < 0.25) {
                    $this->rainMap[$x][$z] = 0.0;
                }
            }
        }
    }


    function blur(array &$map, int $size): void {
        $copy = $map;

        for ($x = 0; $x < $size; $x++) {
            for ($z = 0; $z < $size; $z++) {
                $sum = 0;
                $count = 0;

                for ($dx = -1; $dx <= 1; $dx++) {
                    for ($dz = -1; $dz <= 1; $dz++) {
                        $nx = ($x + $dx + $size) % $size;
                        $nz = ($z + $dz + $size) % $size;
                        $sum += $copy[$nx][$nz];
                        $count++;
                    }
                }

                $map[$x][$z] = $sum / $count;
            }
        }
    }

    function printLocalRainMap(
        array $map,
        int $size,
        int $centerX,
        int $centerZ,
        int $radius = 10
    ): void {

        $symbols = [' ', '.', '-', '=', '*', '#', '@'];

        for ($z = -$radius; $z <= $radius; $z++) {
            $line = '';
            for ($x = -$radius; $x <= $radius; $x++) {
                $ix = ($centerX + $x + $size) % $size;
                $iz = ($centerZ + $z + $size) % $size;

                $v = max(0.0, min(1.0, $map[$ix][$iz]));
                $state = (int) floor($v * 7);
                if ($state > 6) $state = 6;

                $line .= $symbols[$state];
            }
            echo $line . PHP_EOL;
        }
    }

    function intensityToWeatherMode(float $i): int {
        if ($i < 0.25) return self::CLEAR;
        if ($i < 0.45) return self::RAIN;
        if ($i < 0.65) return self::HEAVY_RAIN;
        if ($i < 0.80) return self::STORM;
        if ($i < 0.92) return self::THUNDER;
        return self::HEAVY_THUNDER;
    }

    public function sendRainMapToPlayer(Player $player): void {
        $player->sendMessage("Rain map around you:");
        $pos = $player->getPosition();
        $chunkX = $pos->getFloorX() >> 4;
        $chunkZ = $pos->getFloorZ() >> 4;
        for ($x = -1; $x <= 1; $x++) {
            $line = "";
            for ($z = -1; $z <= 1; $z++) {
                $line .= sprintf(
                    "%0.2f ",
                    $this->intensityToWeatherMode($this->getRainIntensity(
                        $chunkX + $x,
                        $chunkZ + $z,
                        (int)$player->getWorld()->getTime(),
                        $this->rainMap,
                        $this->SIZE
                    ))
                );
            }
            $player->sendMessage($line);
        }
    }


}
