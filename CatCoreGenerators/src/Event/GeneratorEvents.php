<?php

namespace ILoveFlies\CatCoreGenerators\Event;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\item\Item;
use pocketmine\nbt\TreeRoot;
use ILoveFlies\CatCoreGenerators\CatCoreAPIs\FileManager;
use pocketmine\world\Position;

class GeneratorEvents implements Listener
{
    public const MAX_STACK = 16;

    /**
     * @priority HIGHEST
     * @param BlockPlaceEvent $event
     * @return void
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $blocks = iterator_to_array($event->getTransaction()->getBlocks());
        if (count($blocks) === 1) {
            [$x, $y, $z] = $blocks[0];
            $block = new Position($x, $y, $z, $player->getWorld());


            $folderName = "/Configs/GeneratorItems";
            $ores = ['Coal', 'Iron', 'Diamond', 'Emerald'];
            $types = ['ore', 'auto'];

            foreach ($types as $type) {
                foreach ($ores as $ore) {
                    $fileName = ucfirst($ore) . "Generator";
                    $serializedItem = FileManager::getData($folderName, $fileName, $type);
                    if ($serializedItem !== null) {
                        $nbtSerializer = new BigEndianNbtSerializer();
                        $itemNbt = $nbtSerializer->read(zlib_decode(base64_decode($serializedItem)))->mustGetCompoundTag();
                        $inventoryTag = $itemNbt->getListTag("Inventory");
                        if ($inventoryTag !== null) {
                            foreach ($inventoryTag as $tag) {
                                $configItem = Item::nbtDeserialize($tag);

                                if ($item->equals($configItem)) {
                                    $blockKey = implode(':', [$block->getFloorX(), $block->getFloorY(), $block->getFloorZ(), $player->getWorld()->getFolderName()]);
                                    $blockData = FileManager::getData("/Configs", "GeneratorBlockData", $blockKey);
                                    if ($blockData !== null && $blockData['type'] === $type && $blockData['tier'] === ucfirst($ore)) {
                                        $blockData['Stack'] += 1;
                                        FileManager::setData("/Configs", "GeneratorBlockData", $blockKey, $blockData);
                                    } else {
                                        $blockData = [
                                            'position' => [$block->getFloorX(), $block->getFloorY() + 1, $block->getFloorZ()],
                                            'world' => $player->getWorld()->getFolderName(),
                                            'item' => base64_encode(zlib_encode($nbtSerializer->write(new TreeRoot($itemNbt)), ZLIB_ENCODING_GZIP)),
                                            'Stack' => 1,
                                            'type' => $type,
                                            'tier' => ucfirst($ore)
                                        ];
                                        FileManager::setData("/Configs", "GeneratorBlockData", $blockKey, $blockData);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();

        $folderName = "/Configs/GeneratorItems";
        $ores = ['Coal', 'Iron', 'Diamond', 'Emerald'];
        $types = ['ore', 'auto'];

        foreach ($types as $type) {
            foreach ($ores as $ore) {
                $fileName = ucfirst($ore) . "Generator";
                $serializedItem = FileManager::getData($folderName, $fileName, $type);
                if ($serializedItem !== null) {
                    $nbtSerializer = new BigEndianNbtSerializer();
                    $itemNbt = $nbtSerializer->read(zlib_decode(base64_decode($serializedItem)))->mustGetCompoundTag();
                    $inventoryTag = $itemNbt->getListTag("Inventory");
                    if ($inventoryTag !== null) {
                        foreach ($inventoryTag as $tag) {
                            $configItem = Item::nbtDeserialize($tag);

                            if ($item->equals($configItem)) {
                                $blockKey = implode(':', [$block->getPosition()->getFloorX(), $block->getPosition()->getFloorY(), $block->getPosition()->getFloorZ(), $player->getWorld()->getFolderName()]);
                                $blockData = FileManager::getData("/Configs", "GeneratorBlockData", $blockKey);
                                if ($blockData !== null && $blockData['type'] === $type && $blockData['tier'] === ucfirst($ore)) {
                                    if ($blockData['Stack'] < self::MAX_STACK) {
                                        $blockData['Stack'] += 1;
                                        FileManager::setData("/Configs", "GeneratorBlockData", $blockKey, $blockData);
                                        $item->pop();
                                        $player->getInventory()->setItemInHand($item);
                                        $event->cancel();
                                        $player->sendMessage("§bStacked: §6" . $blockData['Stack'] . "/" . self::MAX_STACK);
                                        return;
                                    } else {
                                        $player->sendTip("§7Maximum stack limit reached.");
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    /**
     * @ignoreCancelled true
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     * @return void
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $blockKey = implode(':', [$block->getPosition()->getFloorX(), $block->getPosition()->getFloorY(), $block->getPosition()->getFloorZ(), $player->getWorld()->getFolderName()]);
        $blockData = FileManager::getData("/Configs", "GeneratorBlockData", $blockKey);

        if ($blockData !== null) {
            $nbtSerializer = new BigEndianNbtSerializer();
            $itemNbt = $nbtSerializer->read(zlib_decode(base64_decode($blockData['item'])))->mustGetCompoundTag();
            $inventoryTag = $itemNbt->getListTag("Inventory");
            if ($inventoryTag !== null) {
                foreach ($inventoryTag as $tag) {
                    $item = Item::nbtDeserialize($tag);
                    $item->setCount($blockData['Stack']);
                    $event->setDrops([$item]);
                }
            }
            FileManager::removeData("/Configs", "GeneratorBlockData", $blockKey);
        }
    }
}