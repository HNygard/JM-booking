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

require "glob_inc.inc.php";

if(!isset($_GET['customer_id']) || !is_numeric($_GET['customer_id']))
{
	echo _('Error: Customer ID is invalid.');
	exit();
}

$customer = getCustomer($_GET['customer_id']);
if(!count($customer))
{
	echo _('Error: Customer not found.');
	exit();
}

print_header($day, $month, $year, $area);

echo '<h1>'._('Customer').': '.$customer['customer_name'].'</h1>'.chr(10).chr(10);

if($customer['slettet'])
	echo '<div class="error" style="width: 500px;">Denne kunden er slettet. Kun visning tilgjengelig.</div>';
else
	echo '<a href="customer_edit.php?customer_id='.$customer['customer_id'].'&amp;returnToCustomerView=1">'.
	'<img src="./img/icons/group_edit.png" style="border: 0px solid black; vertical-align: middle;"> '.
	_('Edit customer').'</a><br><br>'.chr(10);

echo '<b>Kundeid:</b> '.$customer['customer_id'].'<br>'.chr(10);
echo '<b>'._('Customer name').':</b> '.$customer['customer_name'].'<br>'.chr(10);
echo '<b>'._('Type of customer').':</b> ';
if($customer['customer_type'] == 'person') echo _('Private person');
if($customer['customer_type'] == 'firm') echo _('School, company, organization, etc');
echo '<br>'.chr(10);
echo '<b>'._('Municipal').':</b> ';
if($customer['customer_municipal'] != '')
	echo $customer['customer_municipal'].' ('.$customer['customer_municipal_num'].')<br>'.chr(10);
else
	echo '<span style="color: gray;">'._('Non selected').'</span><br>'.chr(10);

echo '<h2>'._('Phone number(s)').'</h2>'.chr(10);
echo _('See also any entries on this customer that might contain phone numbers.').'<br><br>'; 
if(count($customer['customer_phone']))
{
	echo '<table style="border-collapse: collapse;">'.chr(10);
	echo '	<tr>'.chr(10);
	echo '		<td class="border"><b>'._('Number').'</b></td>'.chr(10);
	echo '		<td class="border"><b>'._('Name').'</b></td>'.chr(10);
	echo '	</tr>'.chr(10);
	foreach ($customer['customer_phone'] as $phone)
	{
		echo '	<tr>'.chr(10);
		echo '		<td class="border">'.$phone['phone_num'].'</td>'.chr(10);
		echo '		<td class="border">'.$phone['phone_name'].'</td>'.chr(10);
		echo '	</tr>'.chr(10);
	}
	echo '</table>'.chr(10);
}
else
	echo _('No phone numbers.');

echo '<h2>'._('Address(es)').'</h2>'.chr(10); 
if(count($customer['customer_address']))
{
	echo '<table style="border-collapse: collapse;">'.chr(10);
	echo '	<tr>'.chr(10);
	echo '		<td class="border"><b>'._('Name').'</b></td>'.chr(10);
	echo '		<td class="border"><b>'._('Address').'</b></td>'.chr(10);
	echo '		<td class="border"><b>'._('Invoice address?').'</b></td>'.chr(10);
	echo '	</tr>'.chr(10);
	foreach ($customer['customer_address'] as $address)
	{
		echo '	<tr>'.chr(10);
		echo '		<td class="border">'.$address['address_info'].'</td>'.chr(10);
		
		echo '		<td class="border">';
		echo nl2br($address['address_full']).chr(10);
		echo '</td>'.chr(10);
		
		echo '		<td class="border">';
		if($customer['customer_address_id_invoice'] == $address['address_id'])
			echo _('Yes');
		else
			echo _('No');
		echo '</td>'.chr(10);
		echo '	</tr>'.chr(10);
	}
	echo '</table>'.chr(10);
}
else
	echo _('No addresses.');

echo '<h2>'._('Entries for').' '.$customer['customer_name'].'</h2>'.chr(10);
filterMakeAlternatives();
$filters = array();
$filters = addFilter($filters, 'customer_id', $customer['customer_id']);
filterLink($filters);	echo '<br><br>'.chr(10).chr(10);
$SQL = genSQLFromFilters($filters, 'entry_id').' order by time_start';
$Q_next_entries = mysql_query($SQL);

if(!mysql_num_rows($Q_next_entries))
	echo '<i>'._('No entries found for this customer').'</i>'.chr(10);
else
{
	echo '<table style="border-collapse: collapse;">'.chr(10);
	echo ' <tr>'.chr(10);
	echo '  <td class="border"><b>'._('Starts').'</b></td>'.chr(10);
	echo '  <td class="border"><b>'._('Name').'</b></td>'.chr(10);
	echo '  <td class="border"><b>'._('Where').'</b></td>'.chr(10);
	echo '  <td class="border"><b>'._('Contact person').'</b></td>'.chr(10);
	echo '  <td class="border"><b>'._('Phone').'</b></td>'.chr(10);
	echo '  <td class="border"><b>'._('E-mail').'</b></td>'.chr(10);
	echo ' </tr>'.chr(10);
	while($R_entry = mysql_fetch_assoc($Q_next_entries))
	{
		$entry = getEntry($R_entry['entry_id']);
		if(count($entry))
		{
			echo ' <tr>'.chr(10);
			echo '  <td class="border"><b>'.date('d-m-Y H:i', $entry['time_start']).'</b></td>'.chr(10);
			echo '  <td class="border"><a href="entry.php?entry_id='.$entry['entry_id'].'">'.$entry['entry_name'].'</a></td>'.chr(10);
			echo '  <td class="border">';
			$area = getArea($entry['area_id']);
			if(count($area))
				echo $area['area_name'].' - ';
			$rooms = array();
			foreach ($entry['room_id'] as $rid)
			{
				if($rid == '0')
					$rooms[] = _('Whole area');
				else
				{
					$room = getRoom($rid);
					if(count($room))
						$rooms[] = $room['room_name'];
				}
			}
			echo implode(', ', $rooms);
			echo '</td>'.chr(10);
			echo '  <td class="border">'.$entry['contact_person_name'].'</td>'.chr(10);
			echo '  <td class="border">'.$entry['contact_person_phone'].'</td>'.chr(10);
			echo '  <td class="border">'.$entry['contact_person_email'].'</td>'.chr(10);
			echo ' </tr>'.chr(10);
		}
	}
	echo '</table>'.chr(10);
}

?>