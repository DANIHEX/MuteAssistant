<?php

declare(strict_types = 1);

namespace muteassistant\command;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat as TF;
use muteassistant\Main;
use muteassistant\form\Form;

class MainCommand extends PluginCommand implements CommandExecutor {

  public $plugin;
  public $form;

  public function __construct(Main $plugin){
    $this->plugin = $plugin;
    $this->form = new Form($plugin);
  }

  public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool {
    if($sender instanceof Player){
      if($sender->hasPermission("muteassistant.command.mute")){
        $this->form->openMainForm($sender);
        /**
         * NOTE
         * a command like this: "/say hello" have 2 arguments
         * so our first argument will be the second argument
         */
        /* if(count($args) == 2 and $args[1] == "arg1"){
          // some code...
        } else {
          // usage hint...
        } */
      } else {
        $sender->sendMessage(Main::ERROR . "You don't have permission to use this command");
      }
    } else {
      $sender->sendMessage(Main::ERROR . "You can use this command only in-game");
    }
    return true;
  }

}
