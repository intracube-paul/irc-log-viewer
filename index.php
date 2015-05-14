<?php
/**
 * apertus° IRC log viewer
 * 
 * Copyright (C) 2013 Sebastian Pichelhofer
 * 
 * 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */


ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

echo "<!-- Server time returned by date(): " . date('c') . "-->";

function utcDateTime() {
    // substitute for date() function. returns shifted time by $time_offset amount
    $args = func_get_args();
    $time_offset = '-0 hours';
    if(!isset($args[1])) {
        $args[1] = time();
    }
    return date($args[0], strtotime($time_offset, $args[1]));
}

function logLink($logroot, $dir, $file) {
    // returns formatted <a class="loglink" href="index.php?day= ...> line
    $fullpath = $logroot.'/'.$dir.'/'.$file;
    $file_size = round(filesize($fullpath) / 1024, 2);
    $d = date('d', strtotime(substr($file, 4, -4)));
    $m = date('m', strtotime(substr($file, 4, -4)));
    $y = date('Y', strtotime(substr($file, 4, -4)));
    
    echo "<a class=\"loglink\" href=\"index.php?day=".$d."&month=".$m."&year=".$y."\">".date('l, d\<\s\u\p\>S\<\/\s\u\p\> - ', strtotime(substr($file, 4, -4))).round(filesize($fullpath) / 1024, 2) . ' kB</a><br>' . PHP_EOL;
    
}

function message ($arrayvalue) {
        $arrayvalue[0] = "";
        $arrayvalue[1] = "";
        $arrayvalue[2] = "";
        $arrayvalue[3] = "";
        $temp = implode (" ", $arrayvalue);
        return $temp;
}

function colorhash ($nick, $colornicks) {
        $temp = "";
        $test = "";
        // is the nick in the list of predefined colors?
        if (array_key_exists($nick, $colornicks)) {
                return $colornicks[$nick];
        } else {
                // if the nick is not in the list take first 3 letters of nick and apply some magic to generate a color
                for($i = 0; $i <= 2; $i++) {
                        $temp .= ord(substr(strtoupper(md5($nick)), $i, 1))-48;
                        // 0 = 48
                        // Z = 90
                        $test .= ord(substr(strtoupper(md5($nick)), $i, 1))-48;
                }
                //return round((($temp / 126) * 255));
                return $temp;
        }
}
function filenamedate($input) {
        // format: logs/2013-06/LOG_2013-06-23.txt
        preg_match_all("'LOG_([0-9][0-9][0-9][0-9])-([0-9][0-9])-([0-9][0-9]).txt'", $input, $logdate);
        $logdate1['day'] = $logdate[3][0];
        $logdate1['month'] = $logdate[2][0];
        $logdate1['year'] = $logdate[1][0];
        return $logdate1;
}

//Load colors for nicknames
require ("nicks.php");

$showoverview = false;
if ((!isset($_GET['day'])) || (!isset($_GET['month'])) || (!isset($_GET['year']))) {
        $showoverview = true;
}
$day = null;
if (isset($_GET['day']))
        $day = $_GET['day'];
if (!is_numeric($day)) { 
        $day = utcDateTime('d');
        $month = utcDateTime('m');
        $year = utcDateTime('Y');
}

$month = null;
if (isset($_GET['month']))
        $month = $_GET['month'];
if (!is_numeric($month)) { 
        $day = utcDateTime('d');
        $month = utcDateTime('m');
        $year = utcDateTime('Y');
}

$year = null;
if (isset($_GET['year']))
        $year = $_GET['year'];
if (!is_numeric($year)) { 
        $day = utcDateTime('d');
        $month = utcDateTime('m');
        $year = utcDateTime('Y');
}

?>

<head>
<title>apertus&deg; IRC logs</title>
<style>
        @font-face {
                font-family: 'Droid Sans Mono';
                font-style: normal;
                font-weight: 400;
                src: local('Droid Sans Mono'), url(droidsansmono.woff) format('woff');
        }
        body {
                line-height:120%;
                background-color:#f5f5f5;
                background: url("https://www.apertus.org/sites/all/themes/apertus_bootstrap/images/grain-eee.png") repeat scroll 0 0 rgba(0, 0, 0, 0);
                font-family: 'Droid Sans Mono', courier;
                font-size:0.9em;
        }
        a.loglink {     
                color:#222a44;
        }
        .nick { 
                font-weight:bold;
                text-align:right;
                padding-right:5px;
        }
        .content {
                padding-left:7px;
                border-left:1px solid #999;
        }
        .quit {
                color:#888 !important;
        }
        .join {
                color:#888 !important;
        }
        .nickchange {
                color:#888 !important;
        }
        .line-index {
                color:#AAA;
                font-size:0.7em;
        }
        .line-index a{
                color:#AAA;
        }
        hr {
                border-bottom: 1px solid #DDDDDD;
                border-top:none;
                border-left:none;
                border-right:none;
                margin-bottom: 1em;
                clear:both;
        }
        td.irclog  {
                padding-left:15px;
                margin-top:1px;
                margin-bottom:1px;
        }
        table.irclog {
                font-size:0.9em;
        }
        .even {
                background-color: rgba(0, 0, 0, 0.06);
        }
        .odd {
                background-color: rgba(0, 0, 0, 0.02);
        }
        form {
                display:inline;
                padding:0;
                margin:0;
                clear:none;
        }
        .monthoverview {
                vertical-align: top;
                display:inline-block;
                padding-right:50px;
                padding-bottom:100px;
        }
</style>
<meta http-equiv="refresh" content="300">
</head>
<body>

<?php
$date = $year."-".$month."-".$day;
$logroot = "LOG";
$filename = $logroot."/".$year."-".$month."/"."LOG_".$year."-".sprintf("%02s", $month)."-".sprintf("%02s", $day).".txt";

echo "Current Time: ".utcDateTime('H:i') . ' (UTC)<br>' . PHP_EOL;

if ($showoverview) {
    echo "<h1>IRC Channel Logs</h1>" . PHP_EOL;
    echo "<h2>#apertus@irc.freenode.net</h2>" . PHP_EOL;
    echo "<h5>latest month listed first</h5>" . PHP_EOL;

    $logdir_count = 0;

    if(is_dir($logroot)) {
        // scan root log directory and sort descending
        $logdirs = array_reverse(scandir($logroot));
        foreach ($logdirs as $dir) {
            // exclude . and .. dirs
            if ($logdir_count < 80 && $dir != "." && $dir != "..") {
                // check log directories are in 'YYYY-MM' format or else ignore
                if (preg_match('/[0-9][0-9][0-9][0-9]-[0-1][0-9]/', $dir)) {
                    echo "<div class=\"monthoverview\"><h3>" . date('F, Y', strtotime($dir)) . "</h3>" . PHP_EOL;
                    // scan files in each month dir
                    $listing = scandir($logroot.'/'.$dir);
                    foreach ($listing as $file) {
                        if ($file != "." && $file != "..") {
                            // check log files are in 'LOG_YYYY-MM-DD.txt' format or else ignore
                            if (preg_match('/^LOG_[0-9][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9](.txt)/', $file)) {
                                logLink($logroot, $dir, $file);
                            }
                        }
                    }
                    echo "</div>" . PHP_EOL;
                }
                $logdir_count++;
            }
        }
    }
} else {

?>
<h1>#apertus IRC Channel Logs</h1>
<h2><?php echo $year."/".$month."/".$day; ?></h2>
<h3>Timezone: UTC</h3>

<?php 
$day_now = utcDateTime('d');
$month_now = utcDateTime('m');
$year_now = utcDateTime('Y');
$daylast = utcDateTime('d', strtotime($date .' -1 day'));
$monthlast = utcDateTime('m', strtotime($date .' -1 day'));
$yearlast = utcDateTime('Y', strtotime($date .' -1 day'));
$daynext = utcDateTime('d', strtotime($date .' +1 day'));
$monthnext = utcDateTime('m', strtotime($date .' +1 day'));
$yearnext = utcDateTime('Y', strtotime($date .' +1 day'));
echo "<form style=\"float:left;\" action=\"index.php\" method=\"get\">
        <input type=\"hidden\" name=\"day\" value=\"".$daylast."\" />
        <input type=\"hidden\" name=\"month\" value=\"".$monthlast."\" />
        <input type=\"hidden\" name=\"year\" value=\"".$yearlast."\" />
        <input type=\"submit\" value=\"&larr; Previous Day\" />
</form>" . PHP_EOL;

echo "<form style=\"float:right;\" action=\"index.php\" method=\"get\">
        <input type=\"hidden\" name=\"day\" value=\"".$daynext."\" />
        <input type=\"hidden\" name=\"month\" value=\"".$monthnext."\" />
        <input type=\"hidden\" name=\"year\" value=\"".$yearnext."\" />
        <input type=\"submit\" value=\"Next Day &rarr;\" />
</form>" . PHP_EOL;
?>
<div style="margin:auto; width:200px; text-align:center;">
        <form action="index.php" ><input type="submit" value="Day Selection"></form>
        <form action="index.php" >
                <?php
                        echo "<input type=\"hidden\" name=\"day\" value=\"".$day_now."\" />
                        <input type=\"hidden\" name=\"month\" value=\"".$month_now."\" />
                        <input type=\"hidden\" name=\"year\" value=\"".$year_now."\" />";
                ?>
                <input type="submit" value="Today">
        </form>
</div>
<hr />
<table class="irclog" cellpadding="0" cellspacing="0">
<?php
$line_index = 1;
if (!file_exists($filename)) {
        exit ("file not found");
}
$handle = fopen($filename, "r");
if ($handle) {
        while (($line = fgets($handle)) !== false) {    
                $tags = explode(" ", $line);
                $timestamp = $tags[0];
                $tag = $tags[1];
                $nick = $tags[3];
                $message = $tags[4];
                
                switch ($tag) {
                        case "M": echo "<tr class=\"message";
                        break;
                        
                        case "P": echo "<tr class=\"quit";
                        break;
                        
                        case "Q": echo "<tr class=\"quit";
                        break;
                        
                        case "N": echo "<tr class=\"nickchange";
                        break;
                        
                        case "J": echo "<tr class=\"join";
                        break;
                        
                        case "T": echo "<tr class=\"topic";
                        break;
                }
                
                // Even/Odd Rows
                if (($tag == "M") || ($tag == "P") || ($tag == "Q") || ($tag == "N") || ($tag == "J") || ($tag == "T")) {
                        if ($line_index %2) {
                                echo " even\">";
                        } else {
                                echo " odd\">";
                        }
                }
                
                // Line Index
                echo "<td class=\"irclog\"><div class=\"line-index\"><a href=\"#".$line_index."\" name=\"".$line_index."\">".$line_index++."</a></div></td>";

                // Timestamp
                switch ($tag) {
                        case "A":
                        case "M": 
                                echo "<td class=\"irclog\"><div style=\"color: hsl(".colorhash($nick, $colornicks).", 100%, 30%);\" class=\"date\">".date("H:i", $timestamp)."</div></td>";
                                break;
                        
                        default: echo "<td class=\"irclog\"><div class=\"date\">".date("H:i", $timestamp)."</div></td>";
                        break;
                }
                
                // Nick
                $nicksettopic = false;
                if ($nick == "*") {
                        $nick = "Topic";
                } else {
                        $nicksettopic = true;
                }
                switch ($tag) {
                        case "M": echo "<td class=\"irclog nick\"><div style=\"color: hsl(".colorhash($nick, $colornicks).", 100%, 30%);\" class=\"nick\">".htmlentities($nick, ENT_QUOTES)."</div></td>";
                        break;
                        
                        case "A": echo "<td class=\"irclog nick\"><div style=\"color: hsl(".colorhash($nick, $colornicks).", 100%, 30%);\" class=\"nick\">".htmlentities($nick, ENT_QUOTES)."</div></td>";
                        break;
                        
                        default: echo "<td class=\"irclog nick\"><div class=\"nick\">".htmlentities($nick, ENT_QUOTES)."</div></td>";
                        break;
                }
                
                //Message
                switch ($tag) {
                        case "M": echo "<td><div class=\"content\" style=\"color: hsl(".colorhash($nick, $colornicks).", 100%, 30%);\">".htmlentities(message($tags), ENT_QUOTES);
                        break;
                        
                        case "P": echo "<td><div class=\"content\"> left the channel";
                        break;
                        
                        case "Q": echo "<td><div class=\"content\"> left the channel";
                        break;
                        
                        case "T": 
                                if ($nicksettopic) {
                                        echo "<td><div class=\"content\"> has set the topic";
                                } else {
                                        echo "<td><div class=\"content\"> ".htmlentities(message($tags), ENT_QUOTES);
                                }
                                break;
                        
                        case "N": echo "<td><div class=\"content\"> changed nick to: ".htmlentities(message($tags), ENT_QUOTES);
                        break;
                        
                        case "A": echo "<td><div class=\"content\" style=\"color: hsl(".colorhash($nick, $colornicks).", 100%, 30%);\"><i>".htmlentities(message($tags), ENT_QUOTES)."</i>";
                        break;
                        
                        case "J": echo "<td><div class=\"content\"> joined the channel";
                        break;
                }
                // debug
                //echo "<br /><br />".$line;
                
                echo "</div></td></tr>" . PHP_EOL;
                
                
                 
        }
} else {
        // error opening the file.
}
echo "</table><br />";

echo "<form style=\"float:left;\" action=\"index.php\" method=\"get\">
        <input type=\"hidden\" name=\"day\" value=\"".$daylast."\" />
        <input type=\"hidden\" name=\"month\" value=\"".$monthlast."\" />
        <input type=\"hidden\" name=\"year\" value=\"".$yearlast."\" />
        <input type=\"submit\" value=\"&larr; Previous Day\" />
</form>";

echo "<form style=\"float:right;\" action=\"index.php\" method=\"get\">
        <input type=\"hidden\" name=\"day\" value=\"".$daynext."\" />
        <input type=\"hidden\" name=\"month\" value=\"".$monthnext."\" />
        <input type=\"hidden\" name=\"year\" value=\"".$yearnext."\" />
        <input type=\"submit\" value=\"Next Day &rarr;\" />
</form>";
?>
<div style="margin:auto; width:200px; text-align:center;">
        <form action="index.php" ><input type="submit" value="Day Selection"></form>
        <form action="index.php" >
                <?php
                        echo "<input type=\"hidden\" name=\"day\" value=\"".$day_now."\" />
                        <input type=\"hidden\" name=\"month\" value=\"".$month_now."\" />
                        <input type=\"hidden\" name=\"year\" value=\"".$year_now."\" />";
                ?>
                <input type="submit" value="Today">
        </form>
</div>
<br />
<?php
}
?>
</body>