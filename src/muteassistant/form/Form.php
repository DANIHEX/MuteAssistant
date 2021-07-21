<?php

declare(strict_types = 1);

namespace muteassistant\form;

use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\form\Form as PocketmineForm;
use muteassistant\Main;
use muteassistant\lib\jojoe77777\FormAPI\SimpleForm as SF;
use muteassistant\lib\jojoe77777\FormAPI\CustomForm as CF;

class Form implements PocketmineForm {

  public $plugin;
  public $data = [];
  public $l = [];
  public $target = [];

  public function __construct(Main $plugin){
    $this->plugin = $plugin;
  }

  public function handleResponse(Player $player, $data) : void {
    
  }

  public function jsonSerialize(){
    return $this->data;
  }

  public function resort(array $array, bool $type = false) : array {
    $a = $array;
    arsort($a);
    if($type) return array_reverse($a);
    return array_reverse(array_values($a));
  }

  public function openErrorForm(Player $player, string $title = "Error", string $content = "Something went wrong!", array $buttons = []){
    $this->data = [];
    $this->data["type"] = "form";
    $this->data["title"] = $title;
    $this->data["content"] = $content . str_repeat("\n", 15);
    $this->data["buttons"] = $buttons;
    $player->sendForm($this);
  }

  public function openMainForm(Player $player){
    $form = new SF(function (Player $player, $data = null){
      $result = $data;
      if($result === null) return true;
      switch($result){
        case 0:
          $this->openMuteForm($player);
        break;
        case 1:
          $this->openMuteByNameForm($player);
        break;
        case 2:
          $this->openMuteListForm($player);
        break;
      }
    });
    $form->setTitle("Mute Assistant Menu");
    $form->setContent("Choose an option:");
    $form->addButton("Mute");
    $form->addButton("Mute By Name");
    //$form->addButton("Mute By Stick");
    $form->addButton("Mute List");
    //$form->addButton("Settings");
    $form->sendToPlayer($player);
    return $form;
  }

  public function openMuteForm(Player $player){
      $this->l = [];
    foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
      $this->l[] = $p->getName();
    }
    $form = new CF(function (Player $player, array $data = null){
      if($data == null) return true;
      $a = $this->resort($this->l);
      $target = $this->plugin->getServer()->getPlayerExact($a[$data[1]]);
      if($a[$data[1]] === $player->getName()){
        $player->sendMessage(Main::ERROR . "You can not mute your self");
      }
      if($target instanceof Player){
        if($target->hasPermission("muteassistant.command.mute") or $target->isOp()){
          $player->sendMessage(Main::ERROR . "You can not mute server staff");
        } else {
          if($data[3]){
            $this->plugin->mute($player, $target, $data[2], $data[4], $data[5], $data[6]);
          } else {
            $this->plugin->mute($player, $target, $data[2], "N/A", "N/A", "N/A");
          }
        }
      } else {
        $player->sendMessage(TF::RED . "Player not found!");
      }
      $this->l = [];
    });
    $form->setTitle("Mute Form");
    $form->addLabel("Fill the form and submit to mute your target player:\n\n");
    $form->addDropdown("Target:", $this->resort($this->l));
    $form->addInput("Reason:", "type here...");
    $form->addToggle("Prement:", false);
    $form->addSlider("Day(s)", 0, 30, 1, 0);
    $form->addSlider("Hour(s)", 0, 24, 1, 0);
    $form->addSlider("Minutes(s)", 0, 60, 2, 2);
    $form->sendToPlayer($player);
    return $form;
  }

  public function openMuteByNameForm(Player $player, bool $type = false){
    $form = new CF(function (Player $player, array $data = null){
      if($data == null) return true;
      if(empty($data[1]) or empty($data[2])){
        $this->openMuteByNameForm($player, true);
      }
      if(!file_exists($this->plugin->getServer()->getDataPath() . "players/" . strtolower($data[1]) . ".dat")){
        $player->sendMessage(Main::ERROR . "Target player does not exist");
      }
      $target = $this->plugin->getServer()->getPlayerExact($data[1]);
      if($data[1] == $player->getName()){
        $player->sendMessage(Main::ERROR . "You can not mute your self");
      }
      if($target instanceof Player){
        if($target->hasPermission("muteassistant.command.mute") or $target->isOp()){
          $player->sendMessage(Main::ERROR . "You can not mute server staff");
        } else {
          if($data[3]){
            $this->plugin->mute($player, $target, $data[2], $data[4], $data[5], $data[6]);
          } else {
            $this->plugin->mute($player, $target, $data[2], "N/A", "N/A", "N/A");
          }
        }
      } else {
        $player->sendMessage(TF::RED . "Player not found!");
      }
    });
    switch($type){
      case true:
        $form->addLabel(TF::RED . "Inputs can not be empty!\n\n");
      break;
      case false:
        $form->addLabel("Fill the form and submit to mute your target player:\n\n");
      break;
    }
    $form->setTitle("Mute Form");
    $form->addInput("Target:", "type here...");
    $form->addInput("Reason:", "type here...");
    $form->addToggle("Prement:", false);
    $form->addSlider("Day(s)", 0, 30, 1, 0);
    $form->addSlider("Hour(s)", 0, 24, 1, 0);
    $form->addSlider("Minutes(s)", 0, 60, 2, 2);
    $form->sendToPlayer($player);
    return $form;
  }

  public function openMuteListForm(Player $player){
    if(isset($this->target[$player->getName()])) unset($this->target[$player->getName()]);
    $form = new SF(function (Player $player, $data = null){
      if($data === null) return true;
      $this->target[$player->getName()] = $data;
      $this->openMuteInfoForm($player);
    });
    $form->setContent("Choose a player from list to see it's info:");
    $db = $this->plugin->db->query("SELECT * FROM mutelist;");
    $list = $db->fetchArray(SQLITE3_ASSOC);
    if(empty($list)){
      $this->openErrorForm($player, "Mute List", "Mute list is empty");
      return true;
    }
    $db = $this->plugin->db->query("SELECT * FROM mutelist;");
    $i = -1;
    while($r = $db->fetchArray(SQLITE3_ASSOC)){
      $j = $i + 1;
      $mp = $r["player"];
      $form->addButton($mp, -1, "", $mp);
      $i = $i + 1;
    }
    $form->sendToPlayer($player);
    return $form;
  }

  public function openMuteInfoForm(Player $player){
    $form = new SF(function (Player $player, $data = null){
      $result = $data;
      if($result === null) return true;
      switch($result){
        case 0:
          $tpn = $this->target[$player->getName()];
          $db = $this->plugin->db->query("SELECT * FROM mutelist WHERE player = '$tpn';");
          $r = $db->fetchArray(SQLITE3_ASSOC);
          if(!empty($r)){
            $this->plugin->db->query("DELETE FROM mutelist WHERE player = '$tpn';");
            $player->sendMessage(Main::INFO . $tpn . " unmuted!");
          } else {
            $player->sendMessage(Main::ERROR . "Something went wrong!");
          }
          unset($this->target[$player->getName()]);
        break;
      }
    });
    $form->setTitle($this->target[$player->getName()] . "'s Mute Info");
    $t = $this->target[$player->getName()];
    $db = $this->plugin->db->query("SELECT * FROM mutelist WHERE player = '$t';");
    $data = $db->fetchArray(SQLITE3_ASSOC);
    if(!empty($data)){
      if($data["time"] < 0){
        $form->setContent("Days left: N/A\nHours left: N/A\nMinutes left: N/A\nSeconds: N/A\nMuted by " . TF::GREEN . $data["staff"] . TF::WHITE . " for the reason:\n" . $data["reason"] . str_repeat("\n", 8));
      } else {
        $rt = $data["time"] - time();
        $d = floor($rt / 86400);
        $hs = $rt % 86400;
        $h = floor($hs / 3600);
        $ms = $hs % 3600;
        $m = floor($ms / 60);
        $rs = $ms % 60;
        $s = ceil($rs);
        $form->setContent("Days left: " . $d . "\nHours left: " . $h . "\nMinutes left: " . $m . "\nSeconds: " . $s . "\nMuted by " . TF::GREEN . $data["staff"] . TF::WHITE . " for the reason:\n" . $data["reason"] . str_repeat("\n", 8));
      }
      $form->addButton("UNMUTE");
    } else {
      $player->sendMessage(Main::ERROR . "Something went wrong!");
    }
    $form->sendToPlayer($player);
    return $form;
  }

}
