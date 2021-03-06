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

if(!isset($_GET['entry_id']))
{
	echo 'Error. No entry_id given.';
	exit();
}

$entry = getEntry($_GET['entry_id']);

if(!count($entry))
{
	echo 'Error. Entry not found.';
	exit();
}

if(isset($_POST['confirm_tpl']))
{
	$emails = array();
	if(isset($_POST['emails']) && is_array($_POST['emails']))
	{
		foreach ($_POST['emails'] as $emailnum)
		{
			if(isset($_POST['email'. $emailnum]) && $_POST['email'. $emailnum] != '')
				$emails[] = $_POST['email'. $emailnum];
		}
	}
	
	$confirm_tpl = '';
	if(isset($_POST['confirm_tpl']))
	{
		$confirm_tpl = $_POST['confirm_tpl'];
	}
	
	$confirm_comment = '';
	if(isset($_POST['confirm_comment']))
		$confirm_comment = slashes(htmlspecialchars($_POST['confirm_comment'],ENT_QUOTES));
	
	
	$smarty = new Smarty;
	if(isset($_POST['emailTypePDF']) && $_POST['emailTypePDF'] == '1') {
		$confirm_pdf = 1;
		$smarty->assign('confirm_pdf', true);
	}
	else {
		$confirm_pdf = 0;
		$smarty->assign('confirm_pdf', false);
	}
	templateAssignEntry('smarty', $entry);
	templateAssignSystemvars('smarty');
	//echo $confirm_tpl;
	$confirm_txt = templateFetchFromVariable('smarty', htmlspecialchars_decode($confirm_tpl,ENT_QUOTES));
	$confirm_pdf_txt = '';
	$confirm_pdf_tpl = '';
	
	/* ## PDF ## */
	if($confirm_pdf == 1)
	{
		$confirm_pdf_txt = $confirm_txt;
		$confirm_pdf_tpl = $confirm_tpl;
		if($confirm_pdf_txt != '')
		{
			$confirm_pdffile = 'bekreftelse-'.date('Ymd-His').'-'.$entry['entry_id'].'.pdf';
			/*
			$pdf = new HTML2FPDF();
			$pdf->DisplayPreferences('HideWindowUI');
			$pdf->AddPage();
			$pdf->WriteHTML($confirm_txt);
			$pdf->Output($entry_confirm_pdf_path.'/'.$confirm_pdffile);
			*/
			require_once("libs/dompdf/dompdf_config.inc.php");
		
			$dompdf = new DOMPDF();
			$dompdf->set_paper('A4');
			$dompdf->load_html($confirm_pdf_txt);
			$dompdf->render();
			file_put_contents($entry_confirm_pdf_path.'/'.$confirm_pdffile, $dompdf->output());
			//$dompdf->stream($entry_confirm_pdf_path.'/'.$confirm_pdffile);
		}
		else
		{
			$confirm_pdffile = '';
		}
		
		
		// Getting plain mailbody
		$smarty2 = new Smarty;
		templateAssignEntry('smarty', $entry);
		templateAssignSystemvars('smarty');
		$confirm_txt = $smarty2->fetch('file:mail-entry-confirm-pdfbody.tpl');
		$confirm_tpl = file_get_contents('templates/mail-entry-confirm-pdfbody.tpl');
				
	} else {
		$confirm_pdffile = '';
	}
	
	// For testing:
	//echo '<a href="'.$entry_confirm_pdf_path.'/'.$confirm_pdffile.'">'.iconFiletype('pdf').' PDF-fil</a>'; exit();
	
	/* ## ATTACHMENTS ## */
	$attachments = array();
	//$attachments[123] = getAttachment(123); // Array
	if(isset($_POST['attachment']) && is_array($_POST['attachment']))
	{
		foreach ($_POST['attachment'] as $att_id)
		{
			$att_id = (int)$att_id;
			$attachment = getAttachment($att_id);
			if(count($attachment)) {
				$log_data['att'.$att_id] = $att_id;
				$attachments[$att_id] = $attachment;
			}
			else
				$log_data['att_faild'.$att_id] = $att_id;
		}
	}
	
	$rev_num = $entry['rev_num']+1;
	mysql_query("UPDATE `entry` SET `confirm_email` = '1', `time_last_edit` = '".time()."', `rev_num` = '$rev_num' WHERE `entry_id` = '".$entry['entry_id']."' LIMIT 1 ;");
	
	// Insert to get confirmation ID
	mysql_query("INSERT INTO `entry_confirm` (
				`confirm_id` ,
				`entry_id` ,
				`rev_num` ,
				`user_id` ,
				`confirm_time` ,
				`confirm_to` ,
				`confirm_txt` ,
				`confirm_tpl` ,
				`confirm_pdf` ,
				`confirm_pdf_tpl` ,
				`confirm_pdf_txt` ,
				`confirm_pdffile`,
				`confirm_comment`
			)
			VALUES (
				NULL , 
				'".$entry['entry_id']."', 
				'".$rev_num."', 
				'".$login['user_id']."', 
				'".time()."', 
				'".serialize($emails)."', 
				'".slashes(htmlspecialchars($confirm_txt,ENT_QUOTES))."', 
				'".slashes(htmlspecialchars($confirm_tpl,ENT_QUOTES))."', 
				'".$confirm_pdf."',
				'".slashes(htmlspecialchars($confirm_pdf_tpl,ENT_QUOTES))."', 
				'".slashes(htmlspecialchars($confirm_pdf_txt,ENT_QUOTES))."', 
				'".$confirm_pdffile."',
				'".$confirm_comment."'
			);");
	
	if(mysql_errno()) {
		echo mysql_error();
		exit();
	}
	
	// Generating $log_data
	$log_data = array();
	$log_data['confirm_id'] = mysql_insert_id();
	if($confirm_comment != '')
		$log_data['confirm_comment'] = $confirm_comment;
	$i = 0;
	foreach($emails as $email)
	{
		// Sending email
		if($confirm_pdf == '1')
		{
			if(emailSendConfirmationPDF ($entry, $email, $confirm_pdffile, $attachments, $confirm_txt))
				$log_data['emailPDF'.$i] = $email;
			else
				$log_data['emailPDF_faild'.$i] = $email;
		}
		else
		{
			if(emailSendConfirmation ($entry, $email, $confirm_txt))
				$log_data['email'.$i] = $email;
			else
				$log_data['email_faild'.$i] = $email;
		}
		$i++;
	}
	
	if(!newEntryLog($entry['entry_id'], 'edit', 'confirm', $rev_num, $log_data))
	{
		echo _('Can\'t log the changes for the entry.');
		exit();
	}
	
	
	// Log usage of attachments
	foreach($attachments as $att)
	{
		mysql_query("
		INSERT INTO `entry_confirm_usedatt` (
			`confirm_id` ,
			`att_id` ,
			`timeused`
		)
		VALUES (
			'".$log_data['confirm_id']."', 
			'".$att['att_id']."', 
			'".time()."'
		);");
	}
	/*
	if(isset($_POST['save_template']) && $_POST['save_template'] == '1')
	{
		// Saving template as $_POST['save_template_as']
		$save_template_as = '';
		if(isset($_POST['save_template_as'])) {
			$save_template_as = slashes(htmlspecialchars(strip_tags($_POST['save_template_as']),ENT_QUOTES));
		}
		mysql_query("INSERT INTO `template` (
				`template_id` ,
				`template` ,
				`template_name` ,
				`template_type`,
				`template_time_last_edit`
			)
			VALUES (
				NULL , 
				'".slashes(htmlspecialchars($confirm_tpl,ENT_QUOTES))."', 
				'$save_template_as', 
				'confirm',
				'".time()."'
			);");
	}*/
	
	header('Location: entry.php?entry_id='.$entry['entry_id']);
	exit();
}

print_header($day, $month, $year, $area);

echo '<h1>Send bekreftelse p� '.$entry['entry_name'].'</h1>'.chr(10).chr(10);

echo '- <a href="entry.php?entry_id='.$entry['entry_id'].'">'._('Back to entry').'</a> ('._('Will not send a confirmation').')<br><br>';

echo '<script src="js/jquery-1.3.2.min.js" type="text/javascript"></script>'.chr(10);
echo '<script src="js/jquery.blockUI.js" type="text/javascript"></script>'.chr(10);
echo '<script src="js/entry-confirm.js" type="text/javascript"></script>'.chr(10);

echo '<h2>'._('Template').'</h2>'.chr(10);
echo '<div style="margin-left: 20px;">';
echo 'Velg en lagret mal:<br>';
$Q_template = mysql_query("select template_id, template_name from `template` where template_type = 'confirm'");
if(mysql_num_rows($Q_template))
{
	echo '<select onchange="useTemplate(this.options[this.selectedIndex].value);">'.chr(10);
	echo '<option value="0">'._('Non selected').'</option>'.chr(10);
	while ($R_tpl = mysql_fetch_assoc($Q_template))
	{
		echo '<option value="'.$R_tpl['template_id'].'">'.$R_tpl['template_name'].'</option>'.chr(10);
	}
	echo '</select>'.chr(10);
}
else
{
	echo '<select><option>'._('No template').'</option></select>'.chr(10);
}
echo '<br><br>'.chr(10);
echo '</div>'; // Must end div, if not the <form> isn't correct
echo '<form name="entry_confirm" method="post" action="'.$_SERVER['PHP_SELF'].'?entry_id='.$entry['entry_id'].'">'.chr(10).chr(10);


echo '<div style="margin-left: 20px;">';
echo '<textarea cols="85" rows="15" name="confirm_tpl" id="confirm_tpl"></textarea><br><br>'.chr(10);

echo '<label><input type="radio" name="emailTypePDF" value="1" checked="checked"> - '. iconFiletype('pdf').' Send som PDF-vedlegg (andre vedlegg ogs� mulig)</label><br>';
echo '<label><input type="radio" name="emailTypePDF" value="0"> - Send som ren tekst direkte i e-posten (vedlegg ikke mulig)</label><br>';
echo '<br><br>'.chr(10);
echo '</div>';


/* ## SEND TIL ## */
echo '<h2>'._('Send to').'</h2>'.chr(10);
echo '<div style="margin-left: 20px;">';
echo '<table width="600"><tr><td>';
echo 'Hvis ingen epost-adresser er avkrysset eller rutene er tomme, s� vil bookingen bare bli merkert med bekreftelse sendt, men ingen eposter sendes ut.';
echo '<br><br>';
echo '</td></tr></table>'.chr(10);

$i = 0;
echo '<table id="emailTable">'.chr(10);
foreach ($entry['contact_person_email2'] as $email)
{
	$i++;
	echo '<tr><td><input type="checkbox" name="emails[]" value="'.$i.'" checked="checked"></td><td>'.
	'<input type="text" name="email'.$i.'" value="'.$email.'"></td></tr>'.chr(10);
}
$i++;
echo '<tr><td><input type="checkbox" name="emails[]" value="'.$i.'" checked="checked"></td><td>'.
	'<input type="text" name="email'.$i.'" value=""></td></tr>'.chr(10);

echo '</table>'.chr(10);
echo '<input type="button" onclick="addEmailField();" value="'._('Add field').'" class="ui-button ui-state-default ui-corner-all">'.chr(10);

echo '</div>';

echo '<br><br><br>'.chr(10);
/*
echo '<b>'._('Save template').'</b><br>'.chr(10);
echo '<table width="600"><tr><td>';
echo _('If you want to keep the template made above, check the box below and write in a name.').chr(10);
echo '</td></tr></table>'.chr(10);
echo '<input type="checkbox" name="save_template" value="1"> '.
	'<input type="text" name="save_template_as" value=""> - '._('Save template as').'<br><br>'.chr(10);
*/


/* ## VEDLEGG ## */
echo '<h2>Vedlegg</h2>';
echo '<div style="margin-left: 20px;" id="emailAttachment">';
echo 'Filene m� lastes opp fra egen side under <i>Administrasjon</i><br><br>';

echo '<div style="border:2px solid #DDDDDD; margin-bottom:1em; padding:0.8em;">';
echo '<div id="noAttachmentsSelected" style="display: none; padding: 5px;"><i>Ingen vedlegg valgt</i></div>';

$SQL = "
SELECT
	a.*
FROM
	`entry_confirm_attachment` a, `programs_defaultattachment` p, `entry_type_defaultattachment` e
WHERE
	(a.att_id = p.att_id OR a.att_id = e.att_id)
	AND
		p.program_id = '".$entry['program_id']."'
	AND
		e.area_id = '".$entry['area_id']."'
	AND
		e.entry_type_id = '".$entry['entry_type_id']."'
;
		";
$Q_att = mysql_query("
SELECT
	a.att_id, a.att_filename_orig
FROM
	`entry_confirm_attachment` a, `programs_defaultattachment` p
WHERE
	a.att_id = p.att_id	AND
	p.program_id = '".$entry['program_id']."'
;
		");
$atts = array();
while($att = mysql_fetch_assoc($Q_att))
{
	if(!isset($atts[$att['att_id']]))
		$atts[$att['att_id']] = $att['att_filename_orig'];
}
$Q_att = mysql_query("
SELECT	a.att_id, a.att_filename_orig
FROM	`entry_confirm_attachment` a, `entry_type_defaultattachment` e
WHERE
	a.att_id = e.att_id	AND
	e.entry_type_id = '".$entry['entry_type_id']."' AND
	e.area_id = '".$entry['area_id']."'
;
		");
while($att = mysql_fetch_assoc($Q_att))
{
	if(!isset($atts[$att['att_id']]))
		$atts[$att['att_id']] = $att['att_filename_orig'];
}

natcasesort($atts);

echo '<ul id="vedlegg">';
foreach($atts as $att_id => $a)
{
	$att = getAttachment($att_id);
	if(count($att))
		echo '<li id="vedleggValgt'.$att['att_id'].'">'.
			'<input type="hidden" value="'.$att['att_id'].'" name="attachment[]"/>'.
			iconFiletype($att['att_filetype']).' '.$att['att_filename_orig'].' ('.smarty_modifier_file_size($att['att_filesize']).')'.
			'<input type="button" class="attSelected" style="font-size: 10px;" value="Fjern" onclick="removeAttachment('.$att['att_id'].');"/>'.
			'</li>';
}
echo '</ul>';
echo '</div>';
echo '<input type="button" id="velgVedlegg" class="ui-button ui-state-default ui-corner-all" value="Velg fil(er)">';
echo '</div>';

// Disabled:
echo '<div style="width: 400px; margin-left: 20px; display:none; font-size: 14px;" class="error" id="emailAttachmentDisabled">'.
	'Vedlegg er ikke mulig n�r sendingstype er ren tekst.<br>Velg PDF for vedlegg.</div>';


/* ## SEND BEKREFTELSE ## */
echo '<h2>Send bekreftelse</h2>';
echo '<div style="margin-left: 20px;">';
echo '<div class="notice" id="noPDF" style="display:none;">'.
	'<h2>Ingen mal valgt</h2>'.
	'Du har ikke valgt mal og PDF-vedlegg blir da ikke sendt (eller e-posten blir tom).<br>'.
	'Er du sikker p� at du vil fortsette?<br><br>'.
	'<label><input type="checkbox" id="nopdf_confirm"> Ja, jeg vil fortsette</label>'.
	'</div>'.chr(10).chr(10);
echo '<div class="notice" id="failedEmail" style="display:none;">'.
	'<h2>Feil med en eller flere e-poster</h2>'.
	'Du har en eller flere e-post-adressene med feil. Sjekk disse over.<br>'.
	//'Er du sikker p� at du vil fortsette?<br><br>'.
	//'<label><input type="checkbox" id="nopdf_confirm"> Ja, jeg vil fortsette</label>'.
	'</div>'.chr(10).chr(10);
echo '<input type="text" name="confirm_comment" size="20"> - '.
	_('Internal comment') .' (vil ligge i loggen)<br><br><br>'.chr(10);
echo '<input type="submit" value="'._('Send confirmation').'" style="font-size: 18px;"
 class="ui-button ui-state-default ui-corner-all"><br><br><br><br><br>'.chr(10);
echo '</div>';

echo '</form>'.chr(10);

require "include/attachmentSelector.php";
?>