<?php
session_start();
if (!array_key_exists("newplayers",$_SESSION)) $_SESSION["newplayers"]=array();

require("docheader.inc");
require("kdb.inc");
require("getvar.inc");
require("match.inc");

$header=new DocHeader();
$header->add_css("table.css");
$header->add_css("bf.css");
$header->set_title("Bordfodbold: Single");
$header->set_html5();

$dbh=MyDatabase::connect("kageliste");

$get=new GetVars();
$get->allowpost=true;

$newmatch=$get->add("new",null,0);
$matchdata=$get->add("date",null,0);
$newplayer=$get->add("newplayer",null,0);
$playerdata=$get->add("name",null,0);
$delete=$get->add("delete",null,0);
$result="";

if ($matchdata) {
  $result=addmatch($get);
  # If match was not read correctly. Return to newmatchform
  if ($result) $newmatch=1;
  # Else just show all the matches with the new match
  else header("Location: index.php");
}

if ($playerdata) {
  $result=addplayer($playerdata);
  if ($result) $newplayer=1;
  else header("Location: ?new=1");
}

if ($newmatch) {
  $header->set_title("Bordfodbold: Ny kamp");
  $header->display();
  headline("Indtast ny kamp");
  if ($result) print("<p class='error'> $result\n");
  newmatchform($get);
  print("</html>\n");
  die();
}

if ($newplayer) {
  $header->set_title("Bordfodbold: Ny spiller");
  $header->display();
  headline("Tilføj ny spiller");
  if ($result) print("<p class='error'> $result\n");
  newplayerform();
  print("</html>\n");
  die();
}

# No action taken. Matchlist shown.

# Reset new player array
$_SESSION["newplayers"]=array();

$header->display();
headline("Single oversigt");
listmatches();
print("</html>\n");

function listmatches() {
  $dbh=Database::get_handle();
  $table=$dbh->kquery("select player, count(*) as kampe, sum(win) as win, sum(win=0) as lose, percent(sum(win)/count(*),1) as winpct from vsingle group by player order by winpct desc");
  $matchvs_player=$dbh->get_single_column("select distinct player from vsingle order by player");
  $matchvs=$dbh->kquery("select player, opponent, count(*) as kampe,sum(win) as win,sum(win=0) as lose, percent(sum(win)/count(*),1) as winpct from vsingle group by player,opponent order by player, opponent");
  $matchlist=$dbh->kquery("select *,time_format(time,'%H:%i') as stime from single where !deleted order by date desc, time desc");

  $versus=array();
  foreach ($matchvs_player as $m) $versus[$m]=array();
  while ($s = $matchvs->fetch_assoc())
    $versus[$s['player']][$s['opponent']]=$s;

  print("<p> <a href='?new=1'> Indtast ny kamp </a>\n");

  # Add span around all player names and implode to use in table header
  $playerheader=implode(" <th> ",array_map(create_function('$a','return("<div><span>".$a."</span></div>");'),$matchvs_player));
  print("\n");
  print("<h3> Stilling totalt </h3>\n");
  print("<table>\n".
        "  <thead>\n".
        "    <tr><th> # <th> Spiller <th> Kampe <th> Vundet <th> Tabt <th> Sejrsrate\n".
        "  </thead>\n".
        "  <tbody>\n");
  $i=0;
  while ($t = $table->fetch_assoc()) {
    $i++;
    printf("    <tr> <td> %2d <td> %-12s <td> %2d <td> %2d <td> %2d <td> %s\n",
      $i,$t["player"],$t["kampe"],$t["win"],$t["lose"],$t["winpct"]);
  }
  print("  </tbody>\n".
        "</table>\n");
  
  print("\n<br>\n\n");

  print("<h3> Indbyrdes stilling </h3>\n");
  print("<table id='tablevs'>\n".
        "  <thead>\n".
        "    <tr> <td> <th> $playerheader\n");
  print("  </thead>\n");
  foreach ($matchvs_player as $p) {
    printf("  <tr> <th> %-10s ",$p);
    foreach ($matchvs_player as $opp) {
      if ($opp == $p) $f="";
      else {
        $data=$versus[$p][$opp];
        $f=sprintf("%2d/%-2d (%s)",$data["win"],$data["kampe"],trim($data["winpct"]));
      }
      printf(" <td> %-16s ",$f);
    }
    print("\n");
  }
  print("</table>\n");

  print("\n<br>\n\n");

  print("<h3> Kampoversigt </h3>\n");
  print("<table>\n".
        "  <thead>\n".
        "    <tr> <th> # <th> Dato <th> Tid <th> Bord <th> Vinder <th> Taber <th colspan='3'> Resultat".
        "  </thead>\n".
        "  <tbody>\n");

  while ($match = $matchlist->fetch_assoc()) {
    printf("    <tr> <td> %3d <td> %s <td> %s <td> %-7s <td> %-10s <td> %-10s <td> %2d <td> - <td> %2d\n",
           $match['id'],$match['date'],$match['stime'],$match['tablebrand'],$match['win'],$match['lose'],$match['goals_win'],$match['goals_lose']);
  }
  print("  </tbody>\n".
        "</table>\n");
}

function headline($subtitle="") {
  print("<h1> Ougar Bordfodbold </h1>\n");
  if ($subtitle) print("<h2> $subtitle </h2>\n");
}

function newmatchform($get) {
  $date  = $get->add("date",null,0);
  $time  = $get->add("time",null,0);
  $table = $get->add("table",null,0);
  $win   = $get->add("win",null,0);
  $lose  = $get->add("lose",null,0);
  $g1    = $get->add("goals_win",null,0);
  $g2    = $get->add("goals_lose",null,0);
  if (!$date) $date=date("Y-m-d");
  if (!$time) $time=date("H:00");
  if (!$table) $table="Tornado";
  if (!$g1) $g1=11;
  if (!$g2) $g2=1000;
  print("<p>\n".
        "<form action='".url()."' method='POST'>\n".
        "  <span class='formname'> Dato:       </span> <input type='text' name='date' value='$date'><br>\n".
        "  <span class='formname'> Tid:        </span> <input type='text' name='time' value='$time'><br>\n".
        "  <span class='formname'> Bord:       </span> <select name='table'> <option value='Tornado'> Tornado <option value='Bonzini'".($table=="Bonzini"?" selected":"")."> Bonzini </select><br>\n".
        "  <span class='formname'> Vinder:     </span> ".playerselect('win','Vinder',$win)."<br>\n".
        "  <span class='formname'> Taber:      </span> ".playerselect('lose','Taber',$lose)."<br>\n".
        "  <span class='formname'> Mål vinder: </span> ".resselect('goals_win',$g1)."<br>\n".
        "  <span class='formname'> Mål taber:  </span> ".resselect('goals_lose',$g2)."<br>\n".
        "  <input type='submit' value='Gem kamp'><br>\n".
        "</<form>\n");
  print("<p><a href='?newplayer=1'>Tilføj spiller</a> &nbsp; &nbsp; <a href='index.php'> Tilbage </a><br>\n");
}

function newplayerform() {
  print("<p>\n".
        "<form action='".url()."' method='GET'>\n".
        "Navn: <input name='name' type='text'><br>\n".
        "<input type='submit' value='Tilføj spiller'>\n".
        "<p><a href='?new=1'> Tilbage </a>\n");
}

function addmatch($get) {

  $dbh=Database::get_handle();
  $playerlist=$dbh->get_single_column("select distinct win as p from single union select distinct lose as p from single order by p");

  $data=array(
    "date"       => $get->add("date",null,0),
    "time"       => $get->add("time",null,0),
    "tablebrand" => $get->add("table",null,0),
    "win"        => $get->add("win",null,0),
    "lose"       => $get->add("lose",null,0),
    "goals_win"   => $get->add("goals_win",null,0),
    "goals_lose"  => $get->add("goals_lose",null,0)
  );
  $match=new Single($data);
  $result=$match->is_valid();

  if ($result) return("Error adding match: $result");
  $match->insert();
}

function addplayer($name) {
  $dbh=Database::get_handle();
  # Change spaces to underscore
  $name=preg_replace("/\s+/","_",trim($name));
  $check=preg_replace("/[^\w_]/","",$name);
  if ($check!=$name) return("Invalid player name");
  $res=$dbh->get_single_value(sprintf("select count(*) from single where win='%s' or lose='%s'",$name,$name));
  if ($res>0) return("Player allready exists");
  # Save new player name in session
  if (in_array($name,$_SESSION['newplayers'])) return("Player allready added");
  $_SESSION['newplayers'][]=$name;
  return(false);
}

function playerselect($name, $title="", $default="") {
  $dbh=Database::get_handle();
  $players=$dbh->get_single_column("select distinct win as p from single union select lose as p from single order by p");
  if ($title) $options="<option value=''> $title ";
  else $options="";
  foreach ($players as $p) {
    $s=($default===$p?" selected":"");
    $options.="<option value='$p'$s> $p ";
  }
  foreach ($_SESSION["newplayers"] as $newp) {
    $s=($default===$p?" selected":"");
    $options.="<option value='$newp'$s> $newp ";
  }
  return("<select name='$name'>$options</select>");
}

function resselect($name,$default=0) {
  $options="<option value=''> ";
  for ($i=0;$i<25;$i++)
    $options.="<option value='$i'".($default==$i?" selected":"")."> $i";
  return("<select name='$name'>$options</select>");
}

function url() {
  return(strtok($_SERVER["REQUEST_URI"],'?'));
}

?>
