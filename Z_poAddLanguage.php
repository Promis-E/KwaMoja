<?php
/* Steve Kitchen/Kaill */

include ('includes/session.php');

/* Was the Cancel button pressed the last time through ? */

if (isset($_POST['cancel'])) {
	header('Location:' . $RootPath . '/Z_poAdmin.php');
	exit;
}

$Title = _('New Language'); // _('Add a New Language to the System')
$ViewTopic = 'SpecialUtilities';
$BookMark = 'Z_poAddLanguage'; // Anchor's id in the manual's html document.
include ('includes/header.php');

/* Your webserver user MUST have read/write access to here,	otherwise you'll be wasting your time */
echo '<div class="toplink">
		<a href="' . $RootPath . '/Z_poAdmin.php">' . _('Back to the translation menu') . '</a>
	</div>';

echo '<p class="page_title_text"><img alt="" src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/maintenance.png" title="' . _('Add a New Language to the System') . '" />' . ' ' . _('Add a New Language to the System') . '</p>';

echo '<div class="page_help_text">' . _('Utility to create a new language file') . _('Current language is') . ' ' . $_SESSION['Language'] . '</div>';

$DefaultLanguage = 'en_GB'; // The default language is English-United Kingdom (British English).
$PathToDefault = './locale/' . $DefaultLanguage . '.utf8/LC_MESSAGES/messages.pot';

if (isset($_POST['submit']) and isset($_POST['NewLanguage'])) {

	if (mb_strlen($_POST['NewLanguage']) < 5 or mb_strlen($_POST['NewLanguage']) > 5 or mb_substr($_POST['NewLanguage'], 2, 1) != '_') {

		prnMsg(_('Languages must be in the format of a two character country code an underscore _ and a two character language code in upper case'), 'error');
	} else {

		/*Make sure the language characters are in upper case*/

		$_POST['NewLanguage'] = mb_substr($_POST['NewLanguage'], 0, 3) . mb_strtoupper(mb_substr($_POST['NewLanguage'], 3, 2));

		echo '<div class="centre">';
		echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		/* check for directory existence */

		if (!file_exists('./locale/' . $_POST['NewLanguage'])) {
			prnMsg(_('Attempting to create the new language file') . '.....<br />', 'info', ' ');
			$Result = mkdir('./locale/' . $_POST['NewLanguage'] . '.utf8');
			$Result = mkdir('./locale/' . $_POST['NewLanguage'] . '.utf8/LC_MESSAGES');
		} else {
			prnMsg(_('This language cannot be added because it already exists!'), 'error');
			echo '</form>';
			include ('includes/footer.php');
			exit;
		}

		$PathToNewLanguage = './locale/' . $_POST['NewLanguage'] . '.utf8/LC_MESSAGES/messages.po';
		$Result = copy($PathToDefault, $PathToNewLanguage);

		prnMsg(_('Done. You should now change to your newly created language from the user settings link above. Then you can edit the new language file header and use the language module editor to translate the system strings'), 'info');

		echo '</form>';
		include ('includes/footer.php');
		exit;
	}

}

prnMsg(_('This utility will create a new language and a new language translation file for it from the system default') . '<br /><br />' . _('If the language already exists then you cannot recreate it'), 'info', _('PLEASE NOTE'));
echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<table>
		<tr>
			<td>' . _('Full code of the new language in the format en_US') . '</td>
			<td><input type="text" size="5" name="NewLanguage" /></td>
		</tr>
	</table>';

echo '<input type="submit" name="submit" value="' . _('Proceed') . '" />';
echo '</form>';

include ('includes/footer.php');

?>