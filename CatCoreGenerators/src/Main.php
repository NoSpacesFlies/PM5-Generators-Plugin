<?php

declare(strict_types=1);

namespace ILoveFlies\CatCoreGenerators;

use ILoveFlies\CatCoreGenerators\Command\GensCommand;
use ILoveFlies\CatCoreGenerators\Event\GeneratorEvents;
use ILoveFlies\CatCoreGenerators\Task\AutoGensTask;
use ILoveFlies\ILoveFlies\CatCoreGenerators\Task\OreGensTask;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{

    public function onEnable(): void
    {
        // I forgot dis 8 months ago
        mkdir($this->getDataFolder() . "/Configs");
        //and finally
        parent::onEnable();
        $this->getServer()->getPluginManager()->registerEvents(new GeneratorEvents(), $this);
        $this->getServer()->getCommandMap()->register("gens", new GensCommand());
        $this->getScheduler()->scheduleRepeatingTask(new AutoGensTask(), 60);
        $this->getScheduler()->scheduleRepeatingTask(new OreGensTask(), 60);

    }
}
