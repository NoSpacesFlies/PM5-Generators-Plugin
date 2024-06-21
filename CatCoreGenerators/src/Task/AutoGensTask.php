<?php

namespace ILoveFlies\CatCoreGenerators\Task;

use pocketmine\scheduler\Task;
use pocketmine\item\VanillaItems;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\block\tile\Chest;
use ILoveFlies\CatCoreGenerators\CatCoreAPIs\FileManager;
use pocketmine\world\World;

class AutoGensTask extends Task
{
    public function onRun(): void
    {
        $folderName = "/Configs";
        $generatorData = FileManager::getData($folderName, "GeneratorBlockData");

        foreach ($generatorData as $generatorProperties) {
            $type = $generatorProperties['type'];
            $ore = $generatorProperties['tier'];
            $positionArray = $generatorProperties['position'];
            $worldName = $generatorProperties['world'];
            $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
            if ($world instanceof World) {
                $positionVector = new Vector3($positionArray[0], $positionArray[1], $positionArray[2]);
                $position = Position::fromObject($positionVector, $world);

                if ($type === 'auto') {
                    $itemInChest = match ($ore) {
                        'Coal' => VanillaItems::COAL(),
                        'Iron' => VanillaItems::IRON_INGOT(),
                        'Diamond' => VanillaItems::DIAMOND(),
                        'Emerald' => VanillaItems::EMERALD(),
                    };
                    $chest = $position->getWorld()->getTile($position);
                    if ($chest instanceof Chest) {
                        $chest->getInventory()->addItem($itemInChest);
                    }
                }
            }
        }
    }
}
