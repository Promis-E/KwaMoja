<?php
/*The supplier transaction uses the SuppTrans class to hold the information about the invoice or credit note
the SuppTrans class contains an array of GRNs objects - containing details of GRNs for invoicing/crediting and also
an array of GLCodes objects - only used if the AP - GL link is effective */

include ('includes/DefineSuppTransClass.php');

/* Session started in header.php for password checking and authorisation level check */
include ('includes/session.php');

$Title = _('Supplier Transaction General Ledger Analysis');

$ViewTopic = 'AccountsPayable';
$BookMark = 'SuppTransGLAnalysis';
include ('includes/header.php');

if (!isset($_SESSION['SuppTrans'])) {
	prnMsg(_('To enter a supplier invoice or credit note the supplier must first be selected from the supplier selection screen') . ', ' . _('then the link to enter a supplier invoice or supplier credit note must be clicked on'), 'info');
	echo '<br /><a href="' . $RootPath . '/SelectSupplier.php">' . _('Select a supplier') . '</a>';
	include ('includes/footer.php');
	exit;
	/*It all stops here if there aint no supplier selected and transaction initiated ie $_SESSION['SuppTrans'] started off*/
}

/*If the user hit the Add to transaction button then process this first before showing  all GL codes on the transaction otherwise it wouldnt show the latest addition*/

if (isset($_POST['AddGLCodeToTrans']) and $_POST['AddGLCodeToTrans'] == _('Enter GL Line')) {
	$InputError = False;
	if ($_POST['GLCode'] == '') {
		$_POST['GLCode'] = $_POST['AcctSelection'];
	}

	if ($_POST['GLCode'] == '') {
		prnMsg(_('You must select a general ledger code from the list below'), 'warn');
		$InputError = True;
	}

	if (!isset($_POST['Tag'])) {
		$_POST['Tag'][0] = 0;
	}

	$SQL = "SELECT accountcode,
					accountname
				FROM chartmaster
				WHERE accountcode='" . $_POST['GLCode'] . "'
					AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0 and $_POST['GLCode'] != '') {
		prnMsg(_('The account code entered is not a valid code') . '. ' . _('This line cannot be added to the transaction') . '.<br />' . _('You can use the selection box to select the account you want'), 'error');
		$InputError = True;
	} else if ($_POST['GLCode'] != '') {
		$MyRow = DB_fetch_row($Result);
		$GLActName = $MyRow[1];
		if (!is_numeric(filter_number_format($_POST['Amount']))) {
			prnMsg(_('The amount entered is not numeric') . '. ' . _('This line cannot be added to the transaction'), 'error');
			$InputError = True;
		} elseif ($_POST['JobRef'] != '') {
			$SQL = "SELECT contractref FROM contracts WHERE contractref='" . $_POST['JobRef'] . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) == 0) {
				prnMsg(_('The contract reference entered is not a valid contract, this line cannot be added to the transaction'), 'error');
				$InputError = True;
			}
		}
	}

	if ($InputError == False) {
		$_SESSION['SuppTrans']->GetTaxes();
		$TotalTax = 0;
		foreach ($_SESSION['SuppTrans']->Taxes as $Taxes) {
			$TotalTax+= $_POST['Tax' . $Taxes->TaxCalculationOrder];
			$GLTaxes[$Taxes->TaxCalculationOrder] = $_POST['Tax' . $Taxes->TaxCalculationOrder];
			$GLTaxNames[$Taxes->TaxCalculationOrder] = $Taxes->TaxAuthDescription;
		}
		$_SESSION['SuppTrans']->Add_GLCodes_To_Trans($_POST['GLCode'], $GLActName, filter_number_format($_POST['Amount']), $GLTaxes, $GLTaxNames, $_POST['Narrative'], $_POST['Tag']);
		unset($_POST['GLCode']);
		unset($_POST['Amount']);
		unset($_POST['JobRef']);
		unset($_POST['Narrative']);
		unset($_POST['AcctSelection']);
		unset($_POST['Tag']);
		unset($_POST['Tax']);
	}
}

if (isset($_GET['Delete'])) {
	$_SESSION['SuppTrans']->Remove_GLCodes_From_Trans($_GET['Delete']);
}

if (isset($_GET['Edit'])) {
	$_POST['GLCode'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->GLCode;
	$_POST['AcctSelection'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->GLCode;
	$_POST['Amount'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->Amount;
	//	$_POST['JobRef'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->JobRef;
	$_POST['Narrative'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->Narrative;
	$_POST['Tag'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->Tag;
	$GLTaxAmounts = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->Tax;
	$_SESSION['SuppTrans']->Remove_GLCodes_From_Trans($_GET['Edit']);
} else {
	$GLTaxAmounts = array();
	foreach ($_SESSION['SuppTrans']->Taxes as $Taxes) {
		$GLTaxAmounts[$Taxes->TaxCalculationOrder] = 0;
	}
}

/*Show all the selected GLCodes so far from the SESSION['SuppInv']->GLCodes array */
if ($_SESSION['SuppTrans']->InvoiceOrCredit == 'Invoice') {
	echo '<p class="page_title_text" >
			<img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/transactions.png" title="' . _('General Ledger') . '" alt="" />' . ' ' . _('General Ledger Analysis of Invoice From') . ' ' . $_SESSION['SuppTrans']->SupplierName;
} else {
	echo '<p class="page_title_text" >
			<img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/transactions.png" title="' . _('General Ledger') . '" alt="" />' . ' ' . _('General Ledger Analysis of Credit Note From') . ' ' . $_SESSION['SuppTrans']->SupplierName;
}

$SupplierCodeSQL = "SELECT defaultgl FROM suppliers WHERE supplierid='" . $_SESSION['SuppTrans']->SupplierID . "'";
$SupplierCodeResult = DB_query($SupplierCodeSQL);
$SupplierCodeRow = DB_fetch_row($SupplierCodeResult);
echo '<table>
		<thead>
			<tr>
				<th class="SortedColumn">' . _('Account') . '</th>
				<th class="SortedColumn">' . _('Name') . '</th>
				<th class="SortedColumn">' . _('Amount') . '<br />(' . $_SESSION['SuppTrans']->CurrCode . ')</th>
				<th class="SortedColumn">' . _('Tax') . '<br />(' . $_SESSION['SuppTrans']->CurrCode . ')</th>
				<th>' . _('Narrative') . '</th>
				<th>' . _('Tag') . '</th>
				<th colspan="2">&nbsp;</th>
			</tr>
		</thead>';
$TotalGLValue = 0;
$TotalTaxes = array();
$i = 0;
echo '<tbody>';
foreach ($_SESSION['SuppTrans']->GLCodes as $EnteredGLCode) {
	$TagDescription = '';
	foreach ($EnteredGLCode->Tag as $Tag) {
		$TagSQL = "SELECT tags.tagdescription
					FROM tags
					WHERE tags.tagref='" . $Tag . "'";
		$TagResult = DB_query($TagSQL);
		$TagRow = DB_fetch_array($TagResult);
		if ($Tag == 0) {
			$TagDescription.= '0 - None<br />';
		} else {
			$TagDescription.= $Tag . ' - ' . $TagRow['tagdescription'] . '<br />';
		}
	}

	$TaxDescription = '';
	foreach ($EnteredGLCode->Tax as $ID => $Tax) {
		if (!isset($TotalTaxes[$ID])) {
			$TotalTaxes[$ID] = 0;
		}
		$TaxDescription.= $EnteredGLCode->TaxDescriptions[$ID] . ' - ' . locale_number_format($Tax, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '<br />';
		$TotalTaxes[$ID]+= $Tax;
	}

	echo '<tr>
			<td valign="top" class="text">' . $EnteredGLCode->GLCode . '</td>
			<td valign="top" class="text">' . $EnteredGLCode->GLActName . '</td>
			<td valign="top" class="number">' . locale_number_format($EnteredGLCode->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
			<td valign="top" class="number">' . $TaxDescription . '</td>
			<td valign="top" class="text">' . $EnteredGLCode->Narrative . '</td>
			<td valign="top" class="text">' . $TagDescription . '</td>
			<td valign="top"><a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?Edit=' . $EnteredGLCode->Counter . '">' . _('Edit') . '</a></td>
			<td valign="top"><a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?Delete=' . $EnteredGLCode->Counter . '">' . _('Delete') . '</a></td>
		</tr>';

	$TotalGLValue+= $EnteredGLCode->Amount;

}
echo '</tbody>';

$TotalTaxDescription = '';
foreach ($TotalTaxes as $ID => $Tax) {
	$TotalTaxDescription.= $EnteredGLCode->TaxDescriptions[$ID] . ' - ' . locale_number_format($Tax, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '<br />';
}

echo '<tr>
		<td colspan="2" class="number">' . _('Total') . ':</td>
		<td class="number">' . locale_number_format($TotalGLValue, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
		<td class="number">' . $TotalTaxDescription . '</td>
		<td colspan="4">&nbsp;</td>
	</tr>
	</table>';

if ($_SESSION['SuppTrans']->InvoiceOrCredit == 'Invoice') {
	echo '<br />
		<div class="centre">
			<a href="' . $RootPath . '/SupplierInvoice.php?SupplierID=', $_SESSION['SuppTrans']->SupplierID, '">' . _('Back to Invoice Entry') . '</a>
		</div>';
} else {
	echo '<br />
		<div class="centre">
			<a href="' . $RootPath . '/SupplierCredit.php?SupplierID=', $_SESSION['SuppTrans']->SupplierID, '">' . _('Back to Credit Note Entry') . '</a>
		</div>';
}

/*Set up a form to allow input of new GL entries */
echo '<form action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<br />
	<table>';
if (!isset($_POST['GLCode'])) {
	$_POST['GLCode'] = $SupplierCodeRow[0];
}

echo '<tr>
		<td>' . _('Account Code') . ':</td>
		<td><input type="text" name="GLCode" size="12" required="required" maxlength="11" value="' . $_POST['GLCode'] . '" />
		<input type="hidden" name="JobRef" value="" /></td>
	</tr>';
echo '<tr>
	<td>' . _('Account Selection') . ':
		<br />(' . _('If you know the code enter it above') . '
		<br />' . _('otherwise select the account from the list') . ')</td>
	<td><select name="AcctSelection" onchange="return assignComboToInput(this,' . 'GLCode' . ')">';

if (!isset($_POST['AcctSelection']) or $_POST['AcctSelection'] == '') {
	$_POST['AcctSelection'] = $SupplierCodeRow[0];
}
$SQL = "SELECT chartmaster.accountcode,
			   chartmaster.accountname
		FROM chartmaster
		INNER JOIN glaccountusers
			ON glaccountusers.accountcode=chartmaster.accountcode
			AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canupd=1
		WHERE language='" . $_SESSION['ChartLanguage'] . "'
		ORDER BY accountcode";
$Result = DB_query($SQL);
echo '<option value=""></option>';
while ($MyRow = DB_fetch_array($Result)) {
	if (isset($_POST['AcctSelection']) and $MyRow['accountcode'] == $_POST['AcctSelection']) {
		echo '<option selected="selected" value="';
	} else {
		echo '<option value="';
	}
	echo $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - ' . htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false) . '</option>';
}

echo '</select>
	</td>
	</tr>';

if (!isset($_POST['Amount'])) {
	$_POST['Amount'] = 0;
	$_POST['Tax'] = 0;
}

echo '<tr>
		<td>', _('Amount'), ' (', $_SESSION['SuppTrans']->CurrCode, '):</td>
		<td><input type="text" class="number" name="Amount" size="12" required="required" maxlength="11" value="' . locale_number_format($_POST['Amount'], $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" /></td>
	</tr>';

foreach ($_SESSION['SuppTrans']->Taxes as $Taxes) {
	echo '<tr>
			<td>', $Taxes->TaxAuthDescription, ' (', $_SESSION['SuppTrans']->CurrCode, '):</td>
			<td><input type="text" class="number" name="Tax', $Taxes->TaxCalculationOrder, '" size="12" required="required" maxlength="11" value="' . locale_number_format($GLTaxAmounts[$Taxes->TaxCalculationOrder], $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" /></td>
		</tr>';
}

if (!isset($_POST['Narrative'])) {
	$_POST['Narrative'] = '';
}

echo '<tr>
		<td>' . _('Select Tag') . ':</td>
		<td><select name="Tag[]" multiple="multiple">';

$SQL = "SELECT tagref,
			tagdescription
		FROM tags
		ORDER BY tagdescription";

$Result = DB_query($SQL);
echo '<option value="0">0 - ' . _('None') . '</option>';
while ($MyRow = DB_fetch_array($Result)) {
	if (isset($_POST['Tag']) and in_array($MyRow['tagref'], $_POST['Tag'])) {
		echo '<option selected="selected" value="' . $MyRow['tagref'] . '">' . $MyRow['tagref'] . ' - ' . $MyRow['tagdescription'] . '</option>';
	} else {
		echo '<option value="' . $MyRow['tagref'] . '">' . $MyRow['tagref'] . ' - ' . $MyRow['tagdescription'] . '</option>';
	}
}
echo '</select>
		</td>
	</tr>';

echo '<tr>
		<td>' . _('Narrative') . ':</td>
		<td><textarea name="Narrative" cols="40" rows="2">' . $_POST['Narrative'] . '</textarea></td>
	</tr>
	</table>
	<br />';

echo '<div class="centre">
		<input type="submit" name="AddGLCodeToTrans" value="' . _('Enter GL Line') . '" />
	</div>';

echo '</form>';
include ('includes/footer.php');
?>