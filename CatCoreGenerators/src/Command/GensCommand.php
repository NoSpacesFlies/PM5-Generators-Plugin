<?php

namespace ILoveFlies\CatCoreGenerators\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\utils\DyeColor;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\NBT;
use ILoveFlies\CatCoreGenerators\CatCoreAPIs\FileManager;

class GensCommand extends Command {

    private array $ores = ['Coal', 'Iron', 'Diamond', 'Emerald'];
    private array $types = ['ore', 'auto'];

    public function __construct() {
        parent::__construct("gens", "Get generators", "§cUsage: /gens (ore|auto) (type) (amount)");
        $this->setPermission("ccg.command.gens");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return;
        }

        if (count($args) < 3) {
            $sender->sendMessage("§cUsage: /gens (ore|auto) (type) (amount)");
            return;
        }

        $type = $args[0];
        $ore = $args[1];
        $amount = intval($args[2]);

        if (!in_array($type, $this->types)) {
            $sender->sendMessage("§cInvalid type §7>> §bore|auto");
            return;
        }

        if (!in_array($ore, $this->ores)) {
            $sender->sendMessage("§cInvalid ore type. -> Coal|Iron|Diamond|Emerald");
            return;
        }

        $folderName = "/Configs/GeneratorItems";
        $fileName = ucfirst($ore) . "Generator";

        $serializedItem = FileManager::getData($folderName, $fileName, $type);
        if ($serializedItem === null) {
            foreach ($this->types as $type) {
                foreach ($this->ores as $ore) {
                    $this->createAndStoreItem($type, $ore, $folderName);
                }
            }
            $sender->sendMessage("§6Refreshed Generators file! use command next time to get.");
            return;
        }

        $nbtSerializer = new BigEndianNbtSerializer();
        # [Ignore Warning]
        $itemNbt = $nbtSerializer->read(zlib_decode(base64_decode($serializedItem)))->getTag()->getTag("Inventory")->getValue()[0];
        $item = Item::nbtDeserialize($itemNbt);

        for ($i = 0; $i < $amount; $i++) {
            $sender->getInventory()->addItem(clone $item);
        }

        $sender->sendMessage("§aSuccess");
    }

    private function createAndStoreItem(string $type, string $ore, string $folderName): void {
        $item = VanillaBlocks::GLAZED_TERRACOTTA();
        $color = match ($ore) {
            'Coal' => DyeColor::BROWN(),
            'Iron' => DyeColor::WHITE(),
            'Diamond' => DyeColor::LIGHT_BLUE(),
            'Emerald' => DyeColor::GREEN(),
        };
        $item->setColor($color);
        $item = $item->asItem();
        $item->setCustomName("§r§c" . ucfirst($type) . " §e" . ucfirst($ore) . " §aGenerator");

        $tier = match ($ore) {
            'Coal' => 'I',
            'Iron' => 'II',
            'Diamond' => 'III',
            'Emerald' => 'IV',
        };

        $lore = $type === 'ore' ? [
            "§r§7Place and wait for ore to spawn up",
            "§r§8*",
            "§r§7Tier: §c" . $tier,
            "§r§8*",
            "§r§7Type: §c" . ucfirst($type) . "."
        ] : [
            "§r§7Place Chest above to collect items",
            "§r§8*",
            "§r§7Tier: §c" . $tier,
            "§r§8*",
            "§r§7Type: §c" . ucfirst($type) . "."
        ];
        $item->setLore($lore);

        $nbtSerializer = new BigEndianNbtSerializer();
        $serializedItem = zlib_encode($nbtSerializer->write(new TreeRoot(CompoundTag::create()
            ->setTag("Inventory", new ListTag([$item->nbtSerialize(-1)], NBT::TAG_Compound))
        )), ZLIB_ENCODING_GZIP);

        $fileName = ucfirst($ore) . "Generator";
        FileManager::setData($folderName, $fileName, $type, base64_encode($serializedItem));
    }
}
