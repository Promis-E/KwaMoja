<?php
/* $Revision: 1.0 $ */

$PageSecurity = 15;

include ('includes/session.php');

$Title = _('Pay Period Section');

include ('includes/header.php');

if (isset($_GET['PayPeriodID'])) {
	$PayPeriodID = $_GET['PayPeriodID'];
} elseif (isset($_POST['PayPeriodID'])) {

	$PayPeriodID = $_POST['PayPeriodID'];
} else {
	unset($PayPeriodID);
}

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (strpos($_POST['PayPeriodName'], '&') > 0 or strpos($_POST['PayPeriodName'], "'") > 0) {
		$InputError = 1;
		prnMsg(_('The Pay Period description cannot contain the character') . " '&' " . _('or the character') . " '", 'error');
	}
	if (trim($_POST['PayPeriodName']) == '') {
		$InputError = 1;
		prnMsg(_('The Pay Period description may not be empty'), 'error');
	}

	if (strlen($PayPeriodID) == 0) {
		$InputError = 1;
		prnMsg(_('The Pay Period Code cannot be empty'), 'error');
	}

	if ($InputError != 1) {

		if (!isset($_POST['New'])) {

			$SQL = "UPDATE prlpayperiod SET payperioddesc='" . DB_escape_string($_POST['PayPeriodName']) . "',
							numberofpayday='" . DB_escape_string($_POST['NumberOfPayday']) . "'
						WHERE payperiodid = '$PayPeriodID'";

			$ErrMsg = _('The pay period could not be updated because');
			$DbgMsg = _('The SQL that was used to update the pay period but failed was');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
			prnMsg(_('The pay period master record for') . ' ' . $PayPeriodID . ' ' . _('has been updated'), 'success');

		} else { //its a new pay period
			$SQL = "INSERT INTO prlpayperiod (payperiodid,
							payperioddesc,
							numberofpayday)
					 VALUES ('$PayPeriodID',
						'" . DB_escape_string($_POST['PayPeriodName']) . "',
						'" . DB_escape_string($_POST['NumberOfPayday']) . "')";

			$ErrMsg = _('The pay period') . ' ' . $_POST['PayPeriodName'] . ' ' . _('could not be added because');
			$DbgMsg = _('The SQL that was used to insert the pay period but failed was');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg);

			prnMsg(_('A new pay period for') . ' ' . $_POST['PayPeriodName'] . ' ' . _('has been added to the database'), 'success');

			unset($PayPeriodID);
			unset($_POST['PayPeriodName']);
			unset($_POST['NumberOfPayday']);

		}

	} else {

		prnMsg(_('Validation failed') . _('no updates or deletes took place'), 'warn');

	}

} elseif (isset($_POST['delete']) and $_POST['delete'] != '') {

	//the link to delete a selected record was clicked instead of the submit button
	$CancelDelete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'SuppTrans' , PurchOrders, SupplierContacts
	if ($CancelDelete == 0) {
		$SQL = "DELETE FROM prlpayperiod WHERE payperiodid='$PayPeriodID'";
		$Result = DB_query($SQL);
		prnMsg(_('Pay Period record for') . ' ' . $PayPeriodID . ' ' . _('has been deleted'), 'success');
		unset($PayPeriodID);
		unset($_SESSION['PayPeriodID']);
	} //end if Delete paypayperiod
	
}

if (!isset($PayPeriodID)) {

	/*If the page was called without $SupplierID passed to page then assume a new supplier is to be entered show a form with a Supplier Code field other wise the form showing the fields with the existing entries against the supplier will show for editing with only a hidden SupplierID field*/

	echo "<form method='post' ACTION='" . basename(__FILE__) . "?" . SID . "'>";

	echo "<input type='hidden' name='New' value='Yes'>";

	echo '<table>';
	echo '<tr><td>' . _('Pay Period Code') . ':</td><td><input type="text" name="PayPeriodID" size=5 maxlength=4></td></tr>';
	echo '<tr><td>' . _('Pay Description') . ':</td><td><input type="text" name="PayPeriodName" size=16 maxlength=15></td></tr>';
	echo '<tr><td>' . _('Number of Pay Day') . ':</td><td><input type="text" name="NumberOfPayday" size=12 maxlength=11></td></tr>';
	//	echo '</select></td></tr>';
	echo '</select></td></tr></table><p><input type="submit" name="submit" value="' . _('Insert New Pay Period') . '">';
	echo '</form>';

	$SQL = "SELECT payperiodid,
			payperioddesc,
			numberofpayday
			FROM prlpayperiod
			ORDER BY payperiodid";

	$ErrMsg = _('Could not get pay period because');
	$Result = DB_query($SQL, $ErrMsg);

	echo '<table border=1>';
	echo "<tr>
		<th>" . _('Pay Code') . "</th>
		<th>" . _('Pay Description') . "</th>
		<th>" . _('Number of Payday') . "</th>
	</tr>";

	$k = 0; //row colour counter
	while ($MyRow = DB_fetch_row($Result)) {

		if ($k == 1) {
			echo "<TR BGCOLOR='#CCCCCC'>";
			$k = 0;
		} else {
			echo "<TR BGCOLOR='#EEEEEE'>";
			$k++;
		}
		echo '<td>' . $MyRow[0] . '</td>';
		echo '<td>' . $MyRow[1] . '</td>';
		echo '<td>' . $MyRow[2] . '</td>';
		echo '<td><A HREF="' . basename(__FILE__) . '?' . SID . '&PayPeriodID=' . $MyRow[0] . '">' . _('Edit') . '</A></td>';
		echo '<td><A HREF="' . basename(__FILE__) . '?' . SID . '&PayPeriodID=' . $MyRow[0] . '&delete=1">' . _('Delete') . '</A></td>';
		echo '</tr>';

	} //END WHILE LIST LOOP
	echo '</table><p>';

} else {

	//PayPeriodID exists - either passed when calling the form or from the form itself
	echo "<form method='post' ACTION='" . basename(__FILE__) . "?" . SID . "'>";
	echo '<table>';

	//if (!isset($_POST['New'])) {
	if (!isset($_POST['New'])) {
		$SQL = "SELECT payperiodid,
				payperioddesc,
				numberofpayday
			FROM prlpayperiod
			WHERE payperiodid = '$PayPeriodID'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		$_POST['PayPeriodName'] = $MyRow['payperioddesc'];
		$_POST['NumberOfPayday'] = $MyRow['numberofpayday'];
		echo "<input type=HIDDEN name='PayPeriodID' value='$PayPeriodID'>";

	} else {
		// its a new supplier being added
		echo "<input type=HIDDEN name='New' value='Yes'>";
		echo '<tr><td>' . _('Pay Period Code') . ':</td><td><input type="text" name="PayPeriodID" value="', $PayPeriodID, '" size=5 maxlength=4></td></tr>';
	}
	echo "<tr><td>" . _('Pay Description') . ':' . '</td><td><input type="text" name="PayPeriodName" size=16 maxlength=15 value="' . $_POST['PayPeriodName'] . '"></td></tr>';
	echo "<tr><td>" . _('Number of Pay Day') . ':' . '</td><td><input type="text" name="NumberOfPayday" size=12 maxlength=11 value="' . $_POST['NumberOfPayday'] . '"></td></tr>';
	echo '</select></td></tr>';

	if (isset($_POST['New'])) {
		echo '</table><P><input type="submit" name="submit" value="' . _('Add These New Pay Period Details') . '"></form>';
	} else {
		echo '</table><P><input type="submit" name="submit" value="' . _('Update Pay Period') . '">';
		echo '<P><FONT COLOR=red><B>' . _('WARNING') . ': ' . _('There is no second warning if you hit the delete button below') . '. ' . _('However checks will be made to ensure before the deletion is processed') . '<BR></FONT></B>';
		echo '<input type="submit" name="delete" value="' . _('Delete Pay Period') . '" onclick=\"return confirm("' . _('Are you sure you wish to delete this pay period?') . '");\"></form>';
	}

} // end of main ifs
include ('includes/footer.php');
?>