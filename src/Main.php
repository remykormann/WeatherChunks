<?php

declare(strict_types=1);

namespace Mysonied\WeatherChunks;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\data\bedrock\BiomeIds;



class Main extends PluginBase implements Listener{

    /** @var array<string, int> */
    private array $weatherData = [];

    public Weather $weatherManager;

    protected function onEnable(): void {
        $this->getLogger()->info("WeatherChunks enabled");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->weatherManager = (new Weather($this->weatherData));
		$world = $this->getServer()->getWorldManager()->getDefaultWorld();

		$this->weatherManager->generateRainMap($world, (int)$world->getTime());

		$this->getScheduler()->scheduleRepeatingTask(
			new Shedule($this->weatherManager, $world),
			20 * 10
		);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
		$permissions = $command->getPermissions();
		$hasPermission = false;
		foreach ($permissions as $permission) {
			if ($sender->hasPermission($permission) or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
				$hasPermission = true;
				break;
			}
		}

		if ($hasPermission == false) {
			$sender->sendMessage("§cYou do not have permission to use this command.§r");
			return true;
		}

		if (count($args) < 1) {
            $sender->sendMessage("§cUsage: /weatherchunks <clear|rain|thunder|map>§r");
			return false;
		}

        $world = $sender->getWorld();

		$chunkX = $sender->getPosition()->getFloorX() >> 4;
		$chunkZ = $sender->getPosition()->getFloorZ() >> 4;

		if ($args[0] === "clear") {
			$this->weatherManager->setWeather($world, $chunkX, $chunkZ, Weather::CLEAR);
			return true;
		}

		if ($args[0] === "rain") {
			$this->weatherManager->setWeather($world, $chunkX, $chunkZ, Weather::DOWNFALL);
			return true;
		}

		if ($args[0] === "thunder") {
			$this->weatherManager->setWeather($world, $chunkX, $chunkZ, Weather::THUNDER);
			return true;
		}

		if ($args[0] === "map") {
			$this->weatherManager->sendRainMapToPlayer($sender);
			return true;
		}

		$sender->sendMessage("§cUsage: /weatherchunks <clear|rain|thunder>§r");

		return false;
	}

    public function getWeatherManager(): Weather {
        return $this->weatherManager;
    }
}
