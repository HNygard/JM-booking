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

include_once("glob_inc.inc.php");

if(!$login['user_invoice_setready'])
{
	echo 'No access!';
}

if(!isset($_GET['entry_id']))
	$entry = array();
else
	$entry = getEntry($_GET['entry_id']);
 

if(isset($_GET['set_okey']))
{
	// Do the changes...
	if(!entrySetReady($entry))
	{
		echo 'Feil oppsto. Kontakt systemansvarlig.';
		exit();
	}
	
	header('Location: entry.php?entry_id='.$entry['entry_id']);
	exit();
}

$day	= date('d', $entry['time_start']);
$month	= date('m', $entry['time_start']);
$year	= date('Y', $entry['time_start']);
$area	= $entry['area_id'];


print_header($day, $month, $year, $area);

$smarty = new Smarty;

templateAssignEntry('smarty', $entry);
templateAssignSystemvars('smarty');
$smarty->display('file:invoice_setready.tpl');

?>