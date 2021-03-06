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
	Administration of users
*/

require "libs/editor.class.php";
$section = 'users';

include "include/admin_top.php";

if(isset($_GET['editor']))
{
	// Editor extention
	class editor_user extends editor
	{
		function processInput_password ($var, $input) {
			if($input == '')
				return '';
			elseif($input == 'NoT_jEt_sEt')
			{
				$this->vars[$var]['DBQueryPerform'] = false;
				return 'NoT_jEt_sEt';
			}
			else
				return md5($input);
		}
		
		function checkInput_password ($var) {
			if($var['value'] == '') {
				$this->error_input[] = _('No password was supplied.');
				return false;
			} else {
				return true;
			}
		}
	}
	
	$id = 0;
	if(isset($_GET['id']) && is_numeric($_GET['id']))
		$id = (int)$_GET['id'];
	if(isset($_POST['id']) && is_numeric($_POST['id']))
		$id = (int)$_POST['id'];
	
	if($id <= 0)
	{
		$editor = new editor_user('users', $_SERVER['PHP_SELF'].'?editor=1');
		//$editor->setHeading(_('New user'));
		$editor->setHeading('Ny bruker');
		$editor->setSubmitTxt(_('Add'));
		if(authGetUserLevel(getUserID()) < $user_level)
		{
			showAccessDenied($day, $month, $year, $area, true);
			exit ();
		}
	}
	else
	{
		$editor = new editor_user('users', $_SERVER['PHP_SELF'].'?editor=1', $id);
		//$editor->setHeading(_('Edit user'));
		$editor->setHeading('Endre bruker');
		$editor->setSubmitTxt(_('Change'));
		
		if(authGetUserLevel(getUserID()) < $user_level && $id != $userinfo['user_id'])
		{
			showAccessDenied($day, $month, $year, $area, true);
			exit ();
		}
	}
	
	$editor->setDBFieldID('user_id');
	$editor->showID (TRUE);
	$editor->makeNewField('user_name_short', 'Innloggingsnavn / initialer', 'text');
	$editor->makeNewField('user_password', _('Password').'*', 'password', array('defaultValue' => 'NoT_jEt_sEt', 'noDB' => true));
	$editor->setFieldProcessor ('user_password', 'password');
	$editor->setFieldChecker ('user_password', 'password');
	$editor->makeNewField('user_name', _('Username'), 'text');
	$editor->makeNewField('user_email', _('E-mail'), 'text');
	$editor->makeNewField('user_phone', _('Phone'), 'text');
	$editor->makeNewField('user_position', 'Stilling', 'text');
	
	$editor->makeNewField('user_invoice', 'Tilgang til faktura', 'boolean');
	
	//$editor->makeNewField('user_area_default', _('Default area'), 'select', array('defaultValue' => $area['area_id']));
	$editor->makeNewField('user_area_default', 'Standard bygg', 'select');
	$Q_area = mysql_query("select id as area_id, area_name from `mrbs_area` order by `area_name`");
	while($R_area = mysql_fetch_assoc($Q_area))
		$editor->addChoice('user_area_default', $R_area['area_id'], $R_area['area_name']);
	
	/* Disabled until implementet
	
	// TODO: Implement
	$editor->makeNewField('user_areas', 'Tilgang til', 'checkbox', array('defaultValue' => -1));
	$Q_area = mysql_query("select id as area_id, area_name from `mrbs_area` order by `area_name`");
	$editor->addChoice('user_areas', -1, _('All areas'));
	while($R_area = mysql_fetch_assoc($Q_area))
		$editor->addChoice('user_areas', $R_area['area_id'], $R_area['area_name']);
	*/
	
	$editor->getDB();
	
	if(isset($_POST['editor_submit']))
	{
		if($editor->input($_POST))
		{
			if($editor->performDBquery())
			{
				// Redirect
				header('Location: '.$_SERVER['PHP_SELF']);
				exit();
			}
			else
			{
				echo 'Error occured while performing query on database:<br>'.chr(10),
				//echo '<b>Error:</b> '.$editor->error();
				exit();
			}
		}
	}
	
	include "include/admin_middel.php";
	$editor->printEditor();
	//echo '* = '._('Password won\'t be changed unless you type in a new one.');
	echo '* = Passordet blir bare endret hvis det blir skrevet inn ett nytt ett';
}
else
{
	include "include/admin_middel.php";
	
	echo '<script src="js/jquery-1.3.2.min.js" type="text/javascript"></script>'.chr(10);
	echo '<script src="js/hide_unhide.js" type="text/javascript"></script>'.chr(10);
	
	echo '<h1>'._('Users').'</h1>';
	// Add
	if(authGetUserLevel(getUserID()) >= $user_level)
		echo iconHTML('user_add').' <a href="'.$_SERVER['PHP_SELF'].'?editor=1">'._('New user').'</a><br>'.chr(10);
	
	echo iconHTML('phone').' <a href="telefonliste.php">Telefonliste</a><br><br>'.chr(10);
	
	// List of users
	echo '<h2>'._('List of users').'</h2>'.chr(10);
	$Q_users = mysql_query("select user_id from `users` order by `user_name`");
	if(!mysql_num_rows($Q_users))
		echo _('No users found.');
	else
	{
		echo '<a href="javascript:void();" class="showAll">Vis info p� alle / Ikke vis info p� alle</a>';
		echo '<table class="prettytable">'.chr(10);
		echo '	<tr>'.chr(10);
		echo '		<th>ID</th>'.chr(10);
		echo '		<th>Bruker</th>'.chr(10);
		echo '		<th>Login</th>'.chr(10);
		echo '		<th>Accesslevel</th>'.chr(10);
		echo '		<th>Info</th>'.chr(10);
		echo '		<th>Valg</th>'.chr(10);
		echo '		<th>Grupper som brukeren er medlem av</th>'.chr(10);
		echo '	</tr>'.chr(10).chr(10);
		while($R_user = mysql_fetch_assoc($Q_users))
		{
			$user = getUser($R_user['user_id'], true);
			echo '	<tr>'.chr(10);
			
			echo '		<td>'.$user['user_id'].'</td>';
			
			echo '		<td>'.
					'<a href="user.php?user_id='.$user['user_id'].'">'.
					iconHTML('user').' '.
					$user['user_name'].'</a>'.
				'</td>'.chr(10);
			
			echo '		<td>'.
					$user['user_name_short'].
				'</td>'.chr(10);
			
			echo '		<td>'.
					iconHTML('lock').' '.
					$user['user_accesslevel'].
				'</td>'.chr(10);
			
			echo '		<td>'.
					'<div class="showButton" id="buttonId'.$user['user_id'].'"><a href="javascript:void();">Vis / Ikke vis</a></div>'.
					'<div class="showField" id="fieldId'.$user['user_id'].'" style="display:none;">'.
					'Telefon: '.$user['user_phone'].'<br>'.
					'E-post: '.$user['user_email'].'<br>'.
					'Stilling: '.$user['user_position'].'<br>';
				$area_user = getArea($user['user_area_default']);
				if(!count($area_user))
					$area_user['area_name'] = 'IKKE FUNNET'; 
				echo 'Standard bygg: '.$area_user['area_name'].'<br>';
				echo 'Fakturatilgang: ';
				if($user['user_invoice'])	echo 'ja';
				else						echo 'nei';
				'</div></td>'.chr(10);
			
			echo '		<td>'.
					'<a href="'.$_SERVER['PHP_SELF'].'?editor=1&amp;id='.$user['user_id'].'">'.
					iconHTML('user_edit').' '.
					'Endre</a>'.
				'</td>'.chr(10);
			
			echo '		<td>';
			if(count($user) && count($user['groups']))
			{
				echo '<ul style="margin: 0;">'.chr(10);
				foreach($user['groups'] as $gid)
				{
					$group = getGroup($gid);
					if(count($group))
						echo '<li>'.$group['group_name'].'</li>'.chr(10);
				}
				echo '</ul>'.chr(10);
			}
			echo '</td>'.chr(10);
			echo '	</tr>'.chr(10).chr(10);
			//echo '- <br>'.chr(10);
		}
		echo '</table>'.chr(10);
	}
}

echo '</td>
</tr>
</table>
</HTML>';