<?php

/*
JM-booking
Copyright (C) 2007-2010  Jaermuseet <http://www.jaermuseet.no>
Contact: <hn@jaermuseet.no> 
Project: <http://github.com/hnJaermuseet/JM-booking>

Based on ARBS, Advanced Resource Booking System, copyright (C) 2005-2007 
ITMC der TU Dortmund <http://sourceforge.net/projects/arbs/>. ARBS is based 
on MRBS by Daniel Gardner <http://mrbs.sourceforge.net/>.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


//room has been registred before if it was set in _GET
if (!isset($room))
{
	$room = 0;
}
else
{
	$theROOM = getRoom($room);
	if(!count($theROOM))
		$room = 0;
	elseif($theROOM['area_id'] != $area)
		$room = 0;
}

if($room == 0)
	$theROOM = array (
				'room_id'			=> 0,
				'room_name'			=> _('Whole area'),
				'area_id'			=> $area
			);

if (!isset($pview))
	$pview = '';

if (basename($_SERVER['PHP_SELF']) == 'day.php' || basename($_SERVER['PHP_SELF']) == 'day2.php')
	$thisFile = 'day.php';
elseif (basename($_SERVER['PHP_SELF']) == 'month.php')
	$thisFile = 'month.php';
else
	$thisFile = 'week.php';

if ( $pview != 1 )
{
	# Table with areas, rooms, minicals.
	echo '<table height="140" width="100%" class="hiddenprint"><tr>';
	$this_area_name = "";
	$this_room_name = "";
	$infolink="";
	# Show all areas
	echo "<td width=\"200\">".
	'<img src="./img/icons/house.png" style="border: 0px solid black; vertical-align: middle;"> '.
	"<u>" . _("Areas") . "</u><br>";
}

$sql = "select id as area_id, area_name from mrbs_area order by area_name";
$res = mysql_query($sql);
if (mysql_num_rows($res)) {
	while($row = mysql_fetch_assoc($res))
	{
		if ( $pview != 1 )
		{
			echo '<a href="'.$thisFile.'?year='.$year.'&month='.$month.'&day='.$day.'&area='.$row['area_id'].'">';
		}
		if ($row['area_id'] == $area) {
			$this_area_name = htmlspecialchars($row['area_name']);
			if ( $pview != 1 )
			{
				echo "<font color=\"red\">$this_area_name</font></a><br>\n";
			}
		}
		elseif ( $pview != 1 )
		{
			echo htmlspecialchars($row['area_name']) . "</a><br>\n";
		}
	}
}

echo "<br>";
//print_company_image();
//echo "<br><br>",$startpage;

?>
<br><br>
<?php
if ( $pview != 1) {
        echo "</td>\n";
}
$cID=0;
echo "<td width=\"200\"><u>".
'<img src="./img/icons/shape_square.png" style="border: 0px solid black; vertical-align: middle;"> '.
_("Device"), "</u><br>";

echo "<a href=\"".$thisFile."?year=$year&month=$month&day=$day&area=$area&room=0\">";
if($room == 0)
	echo '<font color="red">'._('Whole area').'</font>';
else
	echo _('Whole area');
echo '</a><br>'.chr(10);

$i = 1;
$Q_room = mysql_query("SELECT id, room_name FROM mrbs_room WHERE area_id=$area AND hidden='false' ORDER BY room_name");
while($R_room = mysql_fetch_assoc($Q_room))
{
	if ($pview!=1 && $i>0&&$i%6==0)
		echo "</td><td width=200><br>";
	
	echo "<a href=\"".$thisFile."?year=$year&month=$month&day=$day&area=$area&room=".$R_room['id']."\">";
	
	$this_room_name = htmlspecialchars($R_room['room_name']);
	if ($R_room['id'] == $room)
		echo "<font color=\"red\">$this_room_name</font></a><br>\n";
	else
		echo $this_room_name. "</a><br>\n";
	$i++;
}

if($thisFile == 'week.php')
{
	// Headings:
	echo '</td><td style="padding: 10px 10px 10px 10px;">'.chr(10);
	echo '<h1 align=center>'._('Week').' '.$thisWeek.'</h1>'.chr(10);
	echo '<h3 align=center>'.$this_area_name.' - '.$theROOM['room_name'].'</h3>'.chr(10);
}
elseif($thisFile == 'day.php')
{
	// Headings:
	echo '</td><td style="padding: 10px 10px 10px 10px;">'.chr(10);
	echo '<h1 align=center>'.ucfirst(strftime("%A", $am7)).', '.strtolower(strftime("%d %B %Y", $am7)).'</h1>'.chr(10);
	echo '<h3 align=center>'.$this_area_name.' - '.$theROOM['room_name'].'</h3>'.chr(10);
}
elseif($thisFile == 'month.php')
{
	// Headings:
	echo '</td><td style="padding: 10px 10px 10px 10px;">'.chr(10);
	echo '<h1 align=center>'.ucfirst(strtolower(parseDate(strftime("%B %Y", $monthstart)))).'</h1>'.chr(10);
	echo '<h3 align=center>'.$this_area_name.' - '.$theROOM['room_name'].'</h3>'.chr(10);
}

/* ## ADDING CALENDAR ## */
$print_in_top = TRUE;
echo '</td><td align="right">'.chr(10);

include("trailer.inc.php");


if ( $pview != 1 )
{
	echo "</td>\n";
	echo "</tr></table>\n";
}

echo '<table class="print" width="100%">'.chr(10);
echo '<tr><td><b>'._('Area').':</b> '.$this_area_name.', <b>'._('Room').':</b> '.$theROOM['room_name'].'</td></tr>'.chr(10);
echo '<tr><td>'._('Data collected/printed').' '.date('H:i:s d-m-Y').' '._('by').' '.$login['user_name'].'</td></tr>'.chr(10);

echo '<tr><td>'._('Type of view').': ';
if($thisFile == 'day.php')			echo _('day');
elseif($thisFile == 'week.php')		echo _('week');
elseif($thisFile == 'month.php')	echo _('month');
else								echo _('unknown');
echo '</td></tr>'.chr(10);

echo '<tr><td>&nbsp;</td></tr>'.chr(10);

echo '<tr><td>';
if($thisFile == 'week.php')			echo '<h1>'._('Week').' '.$thisWeek.':</h1>';
elseif($thisFile == 'day.php')		echo '<h1>'.parseDate(strftime("%A", $am7)).' '.strtolower(parseDate(strftime("%d %B %Y", $am7))).':</h1>';
elseif($thisFile == 'month.php')	echo '<h1>'.ucfirst(strtolower(parseDate(strftime("%B %Y", $monthstart)))).':</h1>'.chr(10);
echo '</td></tr>'.chr(10);

echo '</table>';
?>