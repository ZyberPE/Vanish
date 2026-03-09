<?php

namespace ZyberPE\Vanish;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerItemPickupEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {

    private array $vanished = [];
    private Config $config;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function isVanished(Player $player): bool {
        return isset($this->vanished[$player->getName()]);
    }

    public function setVanish(Player $player, bool $state): void {

        if($state){
            $this->vanished[$player->getName()] = true;

            foreach(Server::getInstance()->getOnlinePlayers() as $p){
                if(!$p->hasPermission("vanish.see")){
                    $p->hidePlayer($player);
                }
            }

            $player->setSilent(true);
            $player->setInvisible(true);

        }else{

            unset($this->vanished[$player->getName()]);

            foreach(Server::getInstance()->getOnlinePlayers() as $p){
                $p->showPlayer($player);
            }

            $player->setSilent(false);
            $player->setInvisible(false);
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {

        if(!$sender instanceof Player){
            return true;
        }

        if(!$sender->hasPermission("vanish.use")){
            $sender->sendMessage($this->config->getNested("messages.no-permission"));
            return true;
        }

        if(!isset($args[0])){
            $sender->sendMessage($this->config->getNested("messages.usage"));
            return true;
        }

        switch(strtolower($args[0])){

            case "on":

                if(!$this->isVanished($sender)){
                    $this->setVanish($sender,true);
                    $sender->sendMessage($this->config->getNested("messages.vanish-on"));
                }

            break;

            case "off":

                if($this->isVanished($sender)){
                    $this->setVanish($sender,false);
                    $sender->sendMessage($this->config->getNested("messages.vanish-off"));
                }

            break;

            default:
                $sender->sendMessage($this->config->getNested("messages.usage"));
        }

        return true;
    }

    public function onDamage(EntityDamageEvent $event): void {

        $entity = $event->getEntity();

        if($entity instanceof Player){

            if($this->isVanished($entity)){
                $event->cancel();
            }
        }
    }

    public function onPickup(PlayerItemPickupEvent $event): void {

        if($this->isVanished($event->getPlayer())){
            $event->cancel();
        }
    }

    public function onBreak(BlockBreakEvent $event): void {

        if($this->isVanished($event->getPlayer())){
            $event->cancel();
        }
    }

    public function onPlace(BlockPlaceEvent $event): void {

        if($this->isVanished($event->getPlayer())){
            $event->cancel();
        }
    }

    public function onInventoryOpen(InventoryOpenEvent $event): void {

        $player = $event->getPlayer();

        if($this->isVanished($player)){
            $event->cancel();
        }
    }
}
