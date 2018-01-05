<?php
require_once("baseclass.inc");
require_once("kdb.inc");

class Single extends Baseclass {

  public $id;
  public $date;
  public $time;
  public $tablebrand;
  public $win;
  public $lose;
  public $goals_win;
  public $goals_lose;


  private function valid_date() {
    $fields=explode("-",$this->date);
    if (count($fields)!=3) return("Cannot parse date. Must be YYYY-MM-DD");
    if (!checkdate($fields[1],$fields[2],$fields[0])) return("Invalid date. Must be YYYY-MM-DD");
    if ($fields[0]<100) $this->date="20".$this->date;
    return(false);
  }
  private function valid_time() {
    if (!preg_match("/^[0-9]+:[0-9]{1,2}$/",trim($this->time))) return("Invalid time (must be xx:xx)");
    return(false);
  }
  private function valid_result() {
    if (filter_var($this->goals_win,  FILTER_VALIDATE_INT, array("options" => array("min_range"=>1, "max_range"=>99))) === false) return("Invalid win score");
    if (filter_var($this->goals_lose, FILTER_VALIDATE_INT, array("options" => array("min_range"=>0, "max_range"=>99))) === false) return("Invalid loser score");
    if ($this->goals_win <= $this->goals_lose) return("Winner score not bigger than loser score");
    return(false);
  }
  private function valid_table() {
    if ($this->tablebrand=="Tornado") return(false);
    if ($this->tablebrand=="Bonzini") return(false);
    return("Invalid table brand");
  }
  private function valid_players() {
    $dbh=Database::get_handle();
    $players=$dbh->get_single_column("select distinct win as p from single union select lose as p from single order by p");
    $players=array_merge($players, $_SESSION["newplayers"]);
    if (!in_array($this->win,$players)) return("Unknown winner: {$this->win}");
    if (!in_array($this->lose,$players)) return("Unknown loser: {$this->lose}");
    if ($this->win == $this->lose) return("Winner and loser can't be same player");
    return(false);
  }
  public function is_valid() {
    if($a=$this->valid_date())    return($a);
    if($a=$this->valid_time())    return($a);
    if($a=$this->valid_table())   return($a);
    if($a=$this->valid_players()) return($a);
    if($a=$this->valid_result())  return($a);
    return(false);
  }

  public function insert() {
    $dbh=Database::get_handle();
    $sql=sprintf("insert into single (date,time,tablebrand,win,lose,goals_win,goals_lose) values ('%s','%s','%s','%s','%s',%d,%d)",
      $this->date,$this->time,$this->tablebrand,$this->win,$this->lose,$this->goals_win,$this->goals_lose);
    $dbh->kquery($sql);
  }

}

?>
