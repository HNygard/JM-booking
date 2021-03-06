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

/*
	JM-booking - week display
*/

include_once("glob_inc.inc.php");


if(isset($_GET['room']))
{
	$room=(int)$_GET['room'];
	$selected_room = $room;
}

// Got a week
if (isset($_GET['week']) && isset($_GET['year']))
{
	$_GET['week'] = (int)$_GET['week'];
	$_GET['year'] = (int)$_GET['year'];
	
	$thistime = mktime(0,0,0,1,1,$_GET['year']) + (60*60*24*7*($_GET['week']-1));
	$thisweek = date('W', $thistime);
	// Search for the right date...
	while($thisweek != $_GET['week'])
	{
		
		echo $thisweek.' - ';
		$thistime = $thistime + (60*60*24); // add one day
		$thisweek = date('W', $thistime);
	}
	$_GET['day']	= date('d', $thistime);
	$_GET['month']	= date('m', $thistime);
	$_GET['year']	= date('Y', $thistime);
}

# If we don't know the right date then use today:
if (!isset($_GET['day']) or !isset($_GET['month']) or !isset($_GET['year'])){
	$day   = date("d",time());
	$month = date("m",time());
	$year  = date("Y",time());
}
else {
# Make the date valid if day is more then number of days in month:
	$day=(int)$_GET['day'];
	$month=(int)$_GET['month'];
	$year=(int)$_GET['year'];
	while (!checkdate($month, $day, $year))
		$day--;
}

# Set the date back to the previous $weekstarts day (Sunday, if 0):
$time = mktime(0, 0, 0, $month, $day, $year);
$weekday = (date("w", $time) - $weekstarts + 7) % 7;
if ($weekday > 0){
	$timeNew = $time - $weekday * 86400;
	$time=$timeNew;
	$day   = date("d", $timeNew);
	$month = date("m", $timeNew);
	$year  = date("Y", $timeNew);
}

# print the page header
print_header($day, $month, $year, $area);

# Start and end of week:
$week_midnight = mktime(0, 0, 0, $month, $day, $year);
$week_start = $time;
$week_end = mktime(23, 59, 59, $month, $day+6, $year);


$selectedType = 'week';
$selected = date('W', mktime(0, 0, 0, $month, $day, $year));
$thisWeek = $selected;


include "roomlist.php";

#y? are year, month and day of the previous week.
#t? are year, month and day of the next week.

$i= mktime(0,0,0,$month,$day-7,$year);
$yy = date("Y",$i);
$ym = date("m",$i);
$yd = date("d",$i);

$i= mktime(0,0,0,$month,$day+7,$year);
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);

$Q_room = mysql_query("select id as room_id, room_name from `mrbs_room` where area_id = '".$area."' and hidden = 'false'");
$rooms = array();
while($R_room = mysql_fetch_assoc($Q_room))
	$rooms[$R_room['room_id']]			= $R_room['room_name'];

if($room != 0)
{
	$rooms = array();
	$rooms[$room] = getRoom($room);
}

#Show Go to week before and after links
echo '<table width="100%" class="hiddenprint"><tr><td>';
echo '<a href="week.php?year='.$yy.'&month='.$ym.'&day='.$yd.'&area='.$area.'&room='.$room.'">&lt;&lt; '.
_("go to last week").
	"</a></td><td align=center><a href=\"week.php?area=$area&room=$room\">",
	_("go to this week"),
	"</a></td><td align=right><a href=\"week.php?year=$ty&month=$tm&day=$td&area=$area&room=$room\">",
	_("go to next week"),
	"&gt;&gt;</a></td></tr></table>";

$weekdays = array();
$daystart = $week_start;
$i = 1;
while(date('W', $daystart) == date('W', $week_start))
{
	$weekdays[$i] = $daystart;
	$daystart += 86400;
	$i++;
}

echo '<table width="100%">'.chr(10);
foreach ($weekdays as $daynum => $weekday)
{
	echo '<tr>'.chr(10);
	if($daynum == 6 || $daynum == 7)
		echo ' <td style="background-color: #FFFFCC;">'.chr(10);
	else
		echo ' <td>'.chr(10);
	echo '<a class="graybg" href="day.php?year='.date('Y',$weekday).'&amp;month='.date('m',$weekday).'&amp;day='.date('d',$weekday).'&amp;area='.$area.'&amp;room='.$room.'">';
	echo '<b>'.ucfirst(strtolower(parseDate(strftime("%A", $weekday)))).'</b>';
	echo '<br>'. ucfirst(strtolower(parseDate(strftime("%d. %B", $weekday))));
	echo '</td>'.chr(10);
	if($daynum == 6 || $daynum == 7)
		echo ' <td style="background-color: #FFFFCC;">'.chr(10);
	else
		echo ' <td>'.chr(10);
	$entries = array();
	$timed_entries = array();
	foreach ($rooms as $room_id => $room)
	{
		$start	= mktime(0, 0, 0, date('m', $weekday), date('d', $weekday), date('Y', $weekday));
		$end	= mktime(23, 59, 59, date('m', $weekday), date('d', $weekday), date('Y', $weekday));
		$events_room = checktime_Room ($start, $end, $area, $room_id);
		if(isset($events_room[$room_id]))
		{
			foreach ($events_room[$room_id] as $entry_id)
			{
				$event = getEntry ($entry_id);
				if(count($event))
				{
					/*if($event['time_start'] < $start)
					{
						$event['entry_name'] .= ' ('._('started').' '.date('H:i d-m-Y', $event['time_start']).')';
						$event['time_start'] = $start;
					}*/
					$a = '';
					if($event['time_start'] < $start)
					{
						$a .= _('started').' '.date('H:i d-m-Y', $event['time_start']);
						$event['time_start'] = $start;
					}
					if($event['time_end'] > $end)
					{
						if($a != '')
							$a .= ', ';
						$a .= 'slutter '.date('H:i d-m-Y', $event['time_end']);
						$event['time_end'] = $end;
					}
					if($a != '')
						$event['entry_name'] .= ' ('.$a.')';
					//$event['time_start'] = round_t_down($event['time_start'], $resolution);
					$timed_entries[$event['time_start']][$event['entry_id']] = $event['entry_id'];
					$entries[$event['entry_id']] = $event;
				}
			}
		}
	}
	echo '<table width="100%" cellspacing="0" style="border-collapse: collapse;">';
	echo '<tr><td class="dayplan"><b>'._('Time').'</b></td><td class="dayplan"><b>'._('Room').'</b></td><td class="dayplan"><b>'._('C/A').'</b></td><td class="dayplan" width="100%"><b>'._('What').'</b></td></tr>';
	if(!count($entries))
		echo '<tr><td class="dayplan"><b>00:00-23:59</b></td><td class="dayplan">&nbsp;</td><td class="dayplan">&nbsp;</td><td class="dayplan"><font color="gray"><i>'._('Nothing').'</i></font></td></tr>';
	else
	{
		$last_time = $start;
		ksort($timed_entries);
		foreach ($timed_entries as $t => $thisentries)
		{
			foreach($thisentries as $entry_id)
			{
				if($last_time < $t)
				{
					echo '<tr><td class="dayplan"><b>'.date('H:i', $last_time).'-'.date('H:i', $t).'</b></td><td class="dayplan">&nbsp;</td><td class="dayplan">&nbsp;</td><td class="dayplan"><font color="gray"><i>'._('Nothing').'</i></font></td></tr>';
				}
				echo '<tr><td class="dayplan"><b>'.date('H:i', $entries[$entry_id]['time_start']).'-'.date('H:i', $entries[$entry_id]['time_end']).'</b></td><td class="dayplan">';
				// Rooms
				$room_name = array();
				if(!count($entries[$entry_id]['room_id']))
					echo '<i>'._('Whole area').'</i>';
				else
				{
					$Any_rooms = false;
					foreach ($entries[$entry_id]['room_id'] as $rid)
					{
						if($rid != '0')
						{
							$Any_rooms = true;
							$room_tmp = getRoom($rid);
							if(count($room_tmp))
								$room_name[] = str_replace(' ', '&nbsp;', $room_tmp['room_name']);
						}
					}
					if(!$Any_rooms)
						echo '<i>'.str_replace(' ', '&nbsp;', _('Whole area')).'</i>';
					else
						echo implode(', ', $room_name);
				}
				echo '</td>';
				echo '<td class="dayplan"><font size="1">';
				echo $entries[$entry_id]['num_person_child'].'&nbsp;/&nbsp;'.$entries[$entry_id]['num_person_adult'];
				echo '</font></td>';
				echo '<td class="dayplan"><a href="entry.php?entry_id='.$entry_id.'">'.$entries[$entry_id]['entry_name'].'</a></td></tr>';
				if($last_time < $entries[$entry_id]['time_end'])
					$last_time = $entries[$entry_id]['time_end'];
			}
		}
	}
	echo '</table>'.chr(10);
	echo '</td>'.chr(10);
	echo '</tr>'.chr(10);
	echo '<tr><td colspan="2"><hr></td></tr>';
}
echo '</table>'.chr(10);
?>