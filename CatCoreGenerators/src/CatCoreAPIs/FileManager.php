<?php

declare(strict_types=1);

namespace ILoveFlies\CatCoreGenerators\CatCoreAPIs;

use JsonException;
use pocketmine\utils\Config;
use pocketmine\Server;

class FileManager
{
    public static function getData(string $folderName, string $fileName, ?string $key = null, $value = null) : mixed{
        $plugin = Server::getInstance()->getPluginManager()->getPlugin("CatCoreGenerators");
        $filePath = $plugin->getDataFolder() . $folderName . "/" . $fileName . ".yml";
        $config = new Config($filePath, Config::YAML);
        if ($key !== null) {
            return $config->get($key, $value);
        }

        return $config->getAll();
    }



    public static function setData(string $folderName, string $fileName, $key, $value): void
    {
        $plugin = Server::getInstance()->getPluginManager()->getPlugin("CatCoreGenerators");
        $filePath = $plugin->getDataFolder() . $folderName . "/" . $fileName . ".yml";
        $config = new Config($filePath, Config::YAML);
        $config->set($key, $value);
        try {
            $config->save();
        } catch (JsonException) {
        }
    }

    public static function removeData(string $folderName, string $fileName, $key): void
    {
        $plugin = Server::getInstance()->getPluginManager()->getPlugin("CatCoreGenerators");
        $filePath = $plugin->getDataFolder() . $folderName . "/" . $fileName . ".yml";
        $config = new Config($filePath, Config::YAML);
        $config->remove($key);
        try {
            $config->save();
        } catch (JsonException) {
        }
    }
}