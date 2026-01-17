<?php

declare(strict_types=1);

namespace Mysonied\WeatherChunks;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;



class Main extends PluginBase implements Listener{

    private Weather $wather;

    protected function onEnable(): void {
        $this->getLogger()->info("WeatherChunks enabled");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->weather = (new Weather);
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
			$this->weather->switchWeather($world, Weather::CLEAR);
			return true;
		}

		if ($args[0] === "rain") {
			$this->weather->switchWeather($world, Weather::RAIN);
			return true;
		}

		if ($args[0] === "thunder") {
			$this->weather->switchWeather($world, Weather::THUNDER);
			return true;
		}

		return false;
	}
}
