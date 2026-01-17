<?php

declare(strict_types=1);

namespace Mysonied\WeatherChunks;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;



class Main extends PluginBase implements Listener{

    /** @var array<string, int> */
    private array $weatherData = [];

    public Weather $weatherManager;

    protected function onEnable(): void {
        $this->getLogger()->info("WeatherChunks enabled");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->weatherManager = (new Weather($this->weatherData));

        $this->weatherManager->setWeather($this->getServer()->getWorldManager()->getDefaultWorld(), 0, 0, Weather::RAIN);
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
            $sender->sendMessage("§cUsage: /weatherchunks <clear|rain|thunder>§r");
			return false;
		}

        $world = $sender->getWorld();

		if ($args[0] === "clear") {
			$this->weatherManager->switchWeather($world, Weather::CLEAR);
			return true;
		}

		if ($args[0] === "rain") {
			$this->weatherManager->switchWeather($world, Weather::RAIN);
			return true;
		}

		if ($args[0] === "thunder") {
			$this->weatherManager->switchWeather($world, Weather::THUNDER);
			return true;
		}

		return false;
	}

    public static function getWeatherManager(): Weather {
        return $this->weatherManager;
    }
}
