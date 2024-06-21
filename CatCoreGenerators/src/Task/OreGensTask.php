<?php

namespace ILoveFlies\ILoveFlies\CatCoreGenerators\Task;

use ILoveFlies\CatCoreGenerators\CatCoreAPIs\FileManager;
use pocketmine\scheduler\Task;
use pocketmine\block\VanillaBlocks;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class OreGensTask extends Task
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

                if ($type === 'ore') {
                    $blockAbove = match ($ore) {
                        'Coal' => VanillaBlocks::COAL_ORE(),
                        'Iron' => VanillaBlocks::IRON_ORE(),
                        'Diamond' => VanillaBlocks::DIAMOND_ORE(),
                        'Emerald' => VanillaBlocks::EMERALD_ORE(),
                    };
                    $position->getWorld()->setBlock($position, $blockAbove);
                }
            }
        }
    }
}