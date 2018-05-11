<?php

namespace EinGoogleUser;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;

class Report extends PluginBase implements Listener {
	
	public $prefix = "§8[§bVaronPE§8]";
	public $report = array();
	
	public function onEnable(){
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder()."Reports");
		
		$this->getLogger()->info($this->prefix."§aReportSystem funkoniert!");
		
		$this->report = array();
		
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new ReportBlock($this), 20);
	}
	public function onJoin(PlayerJoinEvent $event){
		$this->report[$event->getPlayer()->getName()] = 0;
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args):bool {
		
		if(strtolower($cmd->getName()) == "report"){
			if(isset($args[0])){
				
				if((strtolower($args[0]) == "list") && ($sender->isOP() || $p->hasPermission("report.team"))){
					
					$sender->sendMessage($this->prefix." §8[§bVaronPE§8]");
					
					$files = scandir($this->getDataFolder()."Reports");
					
					foreach($files as $report){
						$report = str_replace(".yml", "", $report);
						if($report != "." && $report != ".."){
							$sender->sendMessage("§7- §f".$report);
						}
					}
					$sender->sendMessage(" ");
					$sender->sendMessage($this->prefix."§7Zum lesen eines Reportes benutze:§f /report read");
				}
				elseif((strtolower($args[0]) == "delete") && ($sender->isOP() || $p->hasPermission("report.team"))){
					if(isset($args[1])){
						$reportID = (int) $args[1];
						if($reportID != 0){
							
							if(file_exists($this->getDataFolder()."Reports/".$reportID.".yml")){
								
								unlink($this->getDataFolder()."Reports/".$reportID.".yml");
								
								$sender->sendMessage($this->prefix."§7Du hast den Report mit der ID §6".$reportID." §cgelöscht!");
								
							} else {
								$sender->sendMessage($this->prefix."§7Die Report ID: §6".$reportID." §7, wurde  nicht gefunden! §f/report list");
							}
							
						} else {
							$sender->sendMessage($this->prefix.$reportID." ist keine ID");
						}
						
					} else {
						$sender->sendMessage($this->prefix."/report delete <reportID>");
					}
				}
				elseif((strtolower($args[0]) == "read") && ($sender->isOP() || $p->hasPermission("report.team"))){
					if(isset($args[1])){
						$reportID = (int) $args[1];
						
						if($reportID != 0){
						
							if(file_exists($this->getDataFolder()."Reports/".$reportID.".yml")){
								
								$report = new Config($this->getDataFolder()."Reports/".$reportID.".yml", Config::YAML);
								
								$reporter = $report->get("ReportSender");
								$reportet = $report->get("Reportet");
								$reason = $report->get("Grund");
								
								$sender->sendMessage("§8@==§bVaronPE==@");
								$sender->sendMessage("§fReportSender: §8".$reporter);
								$sender->sendMessage("§fHacker: §6".$reportet);
								$sender->sendMessage("§fGrund: §e".$reason);
								$sender->sendMessage("§7@==§bVaronPE==@");
								
							} else {
								$sender->sendMessage($this->prefix."die Report ID: ".$reportID." , existiert nicht ! ->/report list");
							}
						} else {
							$sender->sendMessage($this->prefix.$reportID." ist keine ID");
						}
					} else {
						$sender->sendMessage($this->prefix."/report read <reportID>");
					}
				} else {
					if(isset($args[1])){
						if(file_exists($this->getServer()->getDataPath()."players/".strtolower($args[0]).".dat")){
						$player = $args[0];
						
						$reportID = 1;
						$files = scandir($this->getDataFolder()."Reports");
							foreach($files as $filename){
								if($filename != "." && $filename != ".."){
								$report = (int) str_replace("Report", "", $filename);
								$report = (int) str_replace(".yml", "", $report);
								
								if($report >= $reportID){
									$report++;
									$reportID = $report;
								}
								}
							}
						
						if(file_exists($this->getDataFolder()."Reports/".$reportID.".yml")){
							$sender->sendMessage($this->prefix."§cDiese ID ist schon vergeben");
						} else {
							if($this->report[$sender->getName()] <= 0){
								$newReport = new Config($this->getDataFolder()."Reports/".$reportID.".yml", Config::YAML);
								
								$reason = implode(" ", $args);
								$worte = explode(" ", $reason);
								unset($worte[0]);
								$reason = implode(" ", $worte);
								
								
								$newReport->set("ReportSender", strtolower($sender->getName()));
								$newReport->set("Reportet", strtolower($args[0]));
								$newReport->set("Grund", $reason);
								$newReport->save();
								
								$this->report[$sender->getName()] = 600;
								$sender->sendMessage($this->prefix."§7Du hast nun §8".strtolower($args[0])." §7reportet! §eVielen Dank :D");
								
								foreach($this->getServer()->getOnlinePlayers() as $p){
									if($p->isOP()){
									if($player->hasPermission("report.team")) {
										$p->sendMessage($this->prefix."§6".strtolower($sender->getName())." §ahat einen neuen Report abgesendet!");
										}
									}
								}
								$this->getLogger()->info($this->prefix."§6".strtolower($sender->getName())." §ahat einen neuen Report abgesendet!");
							} else {
								if($this->report[$sender->getName()] <= 60){
									$rest = $this->report[$sender->getName()];
									$sender->sendMessage($this->prefix."§7Du kannst erst in ".$rest." Sekunden wieder jemanden Reporten");
								} else {
									$rest = round($this->report[$sender->getName()] /60);
									$sender->sendMessage($this->prefix."§cDu kannst erst in ".$rest." Minuten wieder jemanden Reporten!");
								}
							}
						}
					} else {
						$sender->sendMessage($this->prefix."§cSpieler existiert nicht!");
					}
					} else {
						$sender->sendMessage($this->prefix."/report <player> <grund>");
					}
					
				}
			} else {
				$sender->sendMessage($this->prefix."/report <Player | Read | List | Delete>");
			}
		}
		return true;
	}
	
}
class ReportBlock extends PluginTask {
	
	public function __construct($plugin) {
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
	
	public function onRun($tick) {
		
		foreach($this->plugin->getServer()->getOnlinePlayers() as $reporter){
			$name = $reporter->getName();
			
			if(!isset($this->plugin->report[$name])){
				$this->plugin->report[$name] = 0;
			}
			
			$reportTimer = $this->plugin->report[$name];
			if($reportTimer > 0){
				$reportTimer--;
				$this->plugin->report[$name] = $reportTimer;
			}
		}
	}
}
