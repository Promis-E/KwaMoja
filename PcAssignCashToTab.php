<?php
include ('includes/session.php');
$Title = _('Assignment of Cash to Petty Cash Tab');
/* Manual links before header.php */
$ViewTopic = 'PettyCash';
$BookMark = 'CashAssignment';
include ('includes/header.php');

if (isset($_POST['SelectedTabs'])) {
	$SelectedTabs = mb_strtoupper($_POST['SelectedTabs']);
} elseif (isset($_GET['SelectedTabs'])) {
	$SelectedTabs = mb_strtoupper($_GET['SelectedTabs']);
}

if (isset($_POST['SelectedIndex'])) {
	$SelectedIndex = $_POST['SelectedIndex'];
} elseif (isset($_GET['SelectedIndex'])) {
	$SelectedIndex = $_GET['SelectedIndex'];
}

if (isset($_POST['Days'])) {
	$Days = $_POST['Days'];
} elseif (isset($_GET['Days'])) {
	$Days = $_GET['Days'];
}

if (isset($_POST['Cancel'])) {
	unset($SelectedTabs);
	unset($SelectedIndex);
	unset($Days);
	unset($_POST['Amount']);
	unset($_POST['Notes']);
	unset($_POST['Receipt']);
}

if (isset($_POST['Process'])) {
	if ($SelectedTabs == '') {
		prnMsg(_('You Must First Select a Petty Cash Tab To Assign Cash'), 'error');
		unset($SelectedTabs);
	}
}

if (isset($_POST['Go'])) {
	$InputError = 0;
	if ($Days <= 0) {
		$InputError = 1;
		prnMsg(_('The number of days must be a positive number'), 'error');
		$Days = 30;
	}
}

if (isset($_POST['submit'])) {
	//initialise no input errors assumed initially before we test
	$InputError = 0;

	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_add.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
		</p>';

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	if ($_POST['Amount'] == 0) {
		$InputError = 1;
		prnMsg('<br />' . _('The Amount must be input'), 'error');
	}

	$SQLLimit = "SELECT pctabs.tablimit,
						pctabs.currency,
						currencies.decimalplaces
					FROM pctabs,
						currencies
					WHERE pctabs.currency = currencies.currabrev
						AND pctabs.tabcode='" . $SelectedTabs . "'";

	$ResultLimit = DB_query($SQLLimit);
	$Limit = DB_fetch_array($ResultLimit);

	if (($_POST['CurrentAmount']) > $Limit['tablimit']) {
		$InputError = 1;
		prnMsg(_('Cash NOT assigned because PC tab current balance is over its cash limit of') . ' ' . locale_number_format($Limit['tablimit'], $Limit['decimalplaces']) . ' ' . $Limit['currency'], 'error');
		prnMsg(_('Report expenses before being allowed to assign more cash or ask the administrator to increase the limit'), 'error');
	}

	if ($InputError != 1 and (($_POST['CurrentAmount'] + $_POST['Amount']) > $Limit['tablimit'])) {
		prnMsg(_('Cash assigned but PC tab current balance is over its cash limit of') . ' ' . locale_number_format($Limit['tablimit'], $Limit['decimalplaces']) . ' ' . $Limit['currency'], 'warning');
		prnMsg(_('Report expenses before being allowed to assign more cash or ask the administrator to increase the limit'), 'warning');
	}

	if ($InputError != 1 and isset($SelectedIndex)) {

		$SQL = "UPDATE pcashdetails
				SET date = '" . FormatDateForSQL($_POST['Date']) . "',
					amount = '" . filter_number_format($_POST['Amount']) . "',
					authorized = '0000-00-00',
					notes = '" . $_POST['Notes'] . "',
					receipt = '" . $_POST['Receipt'] . "'
				WHERE counterindex = '" . $SelectedIndex . "'";
		$Msg = _('Assignment of cash to PC Tab') . ' ' . $SelectedTabs . ' ' . _('has been updated');

	} elseif ($InputError != 1) {
		// Add new record on submit
		$SQL = "INSERT INTO pcashdetails
					(counterindex,
					tabcode,
					date,
					codeexpense,
					amount,
					authorized,
					posted,
					notes,
					receipt)
			VALUES (NULL,
					'" . $_POST['SelectedTabs'] . "',
					'" . FormatDateForSQL($_POST['Date']) . "',
					'ASSIGNCASH',
					'" . filter_number_format($_POST['Amount']) . "',
					'0000-00-00',
					'0',
					'" . $_POST['Notes'] . "',
					'" . $_POST['Receipt'] . "'
					)";
		$Msg = _('Assignment of cash to PC Tab') . ' ' . $_POST['SelectedTabs'] . ' ' . _('has been created');
	}

	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		$Result = DB_query($SQL);
		prnMsg($Msg, 'success');
		unset($_POST['SelectedExpense']);
		unset($_POST['Amount']);
		unset($_POST['Notes']);
		unset($_POST['Receipt']);
		unset($_POST['SelectedTabs']);
		unset($_POST['Date']);
	}

} elseif (isset($_GET['delete'])) {

	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_add.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
		</p>';
	$SQL = "DELETE FROM pcashdetails
		WHERE counterindex='" . $SelectedIndex . "'";
	$ErrMsg = _('The assignment of cash record could not be deleted because');
	$Result = DB_query($SQL, $ErrMsg);
	prnMsg(_('Assignment of cash to PC Tab') . ' ' . $SelectedTabs . ' ' . _('has been deleted'), 'success');
	unset($_GET['delete']);
}

if (!isset($SelectedTabs)) {

	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedTabs will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
	then none of the above are true and the list of sales types will be displayed with
	links to delete or edit each. These will call the same page again and allow update/input
	or deletion of the records*/
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_add.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
		</p>';

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	$SQL = "SELECT tabcode
			FROM pctabs
			WHERE assigner='" . $_SESSION['UserID'] . "'
			ORDER BY tabcode";

	$Result = DB_query($SQL);

	echo '<fieldset>
			<legend>', _('Select Tab'), '</legend>';

	echo '<field>
			<label for="SelectedTabs">', _('Petty Cash Tab To Assign Cash'), ':</label>
			<select name="SelectedTabs">';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['SelectTabs']) and $MyRow['tabcode'] == $_POST['SelectTabs']) {
			echo '<option selected="selected" value="', $MyRow['tabcode'], '">', $MyRow['tabcode'], '</option>';
		} else {
			echo '<option value="', $MyRow['tabcode'], '">', $MyRow['tabcode'], '</option>';
		}
	}
	echo '</select>
		</field>';

	echo '</fieldset>'; // close main table
	echo '<div class="centre">
			<input type="submit" name="Process" value="', _('Accept'), '" />
			<input type="reset" name="Cancel" value="', _('Cancel'), '" />
		</div>';

	echo '</form>';
}

//end of ifs and buts!
if (isset($_POST['Process']) or isset($SelectedTabs)) {

	if (!isset($_POST['submit'])) {
		echo '<p class="page_title_text">
				<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_add.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
			</p>';
	}
	echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Select another tab'), '</a>
		</div>';

	if (!isset($_GET['edit']) or isset($_POST['GO'])) {

		if (isset($_POST['Cancel'])) {
			unset($_POST['Amount']);
			unset($_POST['Date']);
			unset($_POST['Notes']);
			unset($_POST['Receipt']);
		}

		if (!isset($Days)) {
			$Days = 30;
		}

		/* Retrieve decimal places to display */
		$SqlDecimalPlaces = "SELECT decimalplaces
					FROM currencies,pctabs
					WHERE currencies.currabrev = pctabs.currency
						AND tabcode='" . $SelectedTabs . "'";
		$Result = DB_query($SqlDecimalPlaces);
		$MyRow = DB_fetch_array($Result);
		$CurrDecimalPlaces = $MyRow['decimalplaces'];

		$SQL = "SELECT counterindex,
						tabcode,
						date,
						codeexpense,
						amount,
						authorized,
						posted,
						notes,
						receipt
					FROM pcashdetails
					WHERE tabcode='" . $SelectedTabs . "'
						AND date >=DATE_SUB(CURDATE(), INTERVAL " . $Days . " DAY)
					ORDER BY date,
							 counterindex ASC";
		$Result = DB_query($SQL);

		echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
		echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
		echo '<table>
				<tr>
					<th colspan="8">', _('Detail Of PC Tab Movements For Last'), ':
						<input type="hidden" name="SelectedTabs" value="', $SelectedTabs, '" />
						<input type="text" class="number" name="Days" value="', $Days, '" required="required" maxlength="3" size="4" />' . _('Days') . '
					</th>
				</tr>
				<tr>
					<th>', _('Date'), '</th>
					<th>', _('Expense Code'), '</th>
					<th>', _('Amount'), '</th>
					<th>', _('Authorised'), '</th>
					<th>', _('Notes'), '</th>
					<th>', _('Receipt'), '</th>
				</tr>';

		while ($MyRow = DB_fetch_array($Result)) {
			$SQLdes = "SELECT description
					FROM pcexpenses
					WHERE codeexpense='" . $MyRow['codeexpense'] . "'";

			$ResultDes = DB_query($SQLdes);
			$Description = DB_fetch_array($ResultDes);

			if (!isset($Description['0'])) {
				$Description['0'] = 'ASSIGNCASH';
			}

			if (($MyRow['authorized'] == '0000-00-00') and ($Description['0'] == 'ASSIGNCASH')) {
				// only cash assignations NOT authorized can be modified or deleted
				echo '<tr class="striped_row">
						<td>', ConvertSQLDate($MyRow['date']), '</td>
						<td>', $Description['0'], '</td>
						<td class="number">', locale_number_format($MyRow['amount'], $CurrDecimalPlaces), '</td>
						<td>', ConvertSQLDate($MyRow['authorized']), '</td>
						<td>', $MyRow['notes'], '</td>
						<td>', $MyRow['receipt'], '</td>
						<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedIndex=', $MyRow['counterindex'], '&amp;SelectedTabs=', $SelectedTabs, '&amp;Days=', $Days, '&amp;edit=yes">', _('Edit'), '</a></td>
						<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedIndex=', $MyRow['counterindex'], '&amp;SelectedTabs=', $SelectedTabs, '&amp;Days=', $Days, '&amp;delete=yes" onclick="return MakeConfirm(\'', _('Are you sure you wish to delete this code and the expense it may have set up?'), '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
					</tr>';
			} else {
				echo '<tr class="striped_row">
						<td>', ConvertSQLDate($MyRow['date']), '</td>
						<td>', $Description['0'], '</td>
						<td class="number">', locale_number_format($MyRow['amount'], $CurrDecimalPlaces), '</td>
						<td>', ConvertSQLDate($MyRow['authorized']), '</td>
						<td>', $MyRow['notes'], '</td>
						<td>', $MyRow['receipt'], '</td>
					</tr>';
			}
		}
		//END WHILE LIST LOOP
		$SQLamount = "SELECT sum(amount)
					FROM pcashdetails
					WHERE tabcode='" . $SelectedTabs . "'";

		$ResultAmount = DB_query($SQLamount);
		$Amount = DB_fetch_array($ResultAmount);

		if (!isset($Amount['0'])) {
			$Amount['0'] = 0;
		}

		echo '<tr class="total_row">
				<td colspan="2" style="text-align:right"><b>', _('Current balance'), ':</b></td>
				<td class="number">', locale_number_format($Amount['0'], $CurrDecimalPlaces), '</td>
			</tr>';

		echo '</table>
			</form>';
	}

	if (!isset($_GET['delete'])) {

		if (!isset($Amount['0'])) {
			$Amount['0'] = 0;
		}

		echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
		echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
		if (isset($_GET['edit'])) {

			/* Retrieve decimal places to display */
			$SqlDecimalPlaces = "SELECT decimalplaces
						FROM currencies,pctabs
						WHERE currencies.currabrev = pctabs.currency
							AND tabcode='" . $SelectedTabs . "'";
			$Result = DB_query($SqlDecimalPlaces);
			$MyRow = DB_fetch_array($Result);
			$CurrDecimalPlaces = $MyRow['decimalplaces'];

			$SQL = "SELECT * FROM pcashdetails
				WHERE counterindex='" . $SelectedIndex . "'";

			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);

			$_POST['Date'] = ConvertSQLDate($MyRow['date']);
			$_POST['SelectedExpense'] = $MyRow['codeexpense'];
			$_POST['Amount'] = $MyRow['amount'];
			$_POST['Notes'] = $MyRow['notes'];
			$_POST['Receipt'] = $MyRow['receipt'];

			echo '<input type="hidden" name="SelectedTabs" value="', $SelectedTabs, '" />';
			echo '<input type="hidden" name="SelectedIndex" value="', $SelectedIndex, '" />';
			echo '<input type="hidden" name="CurrentAmount" value="', $Amount[0], '" />';
			echo '<input type="hidden" name="Days" value="', $Days, '" />';
		}

		/* Ricard: needs revision of this date initialization */
		if (!isset($_POST['Date'])) {
			$_POST['Date'] = Date($_SESSION['DefaultDateFormat']);
		}

		echo '<fieldset>'; //Main table
		if (isset($_GET['SelectedIndex'])) {
			echo '<legend>', _('Update Cash Assignment'), '</legend>';
		} else {
			echo '<legend>', _('New Cash Assignment'), '</legend>';
		}

		echo '<field>
				<label for="Date">', _('Cash Assignation Date'), ':</label>
				<input type="text" class="date" name="Date" size="10" required="required" maxlength="10" value="', $_POST['Date'], '" />
			</field>';

		if (!isset($_POST['Amount'])) {
			$_POST['Amount'] = 0;
		}
		echo '<field>
				<label for="Amount">', _('Amount'), ':</label>
				<input type="text" class="number" name="Amount" size="12" required="required" maxlength="11" value="', locale_number_format($_POST['Amount'], $CurrDecimalPlaces), '" /></td>
			</field>';

		if (!isset($_POST['Notes'])) {
			$_POST['Notes'] = '';
		}
		echo '<field>
				<label for="Notes">', _('Notes'), ':</label>
				<input type="text" name="Notes" size="50" maxlength="49" value="', $_POST['Notes'], '" /></td>
			</field>';

		if (!isset($_POST['Receipt'])) {
			$_POST['Receipt'] = '';
		}
		echo '<field>
				<label for="Receipt">', _('Receipt'), ':</label>
				<input type="text" name="Receipt" size="50" maxlength="49" value="', $_POST['Receipt'], '" /></td>
			</field>';

		echo '</fieldset>'; // close main table
		echo '<input type="hidden" name="CurrentAmount" value="', $Amount['0'], '" />';
		echo '<input type="hidden" name="SelectedTabs" value="', $SelectedTabs, '" />';
		echo '<input type="hidden" name="Days" value="', $Days, '" />';

		echo '<div class="centre">
				<input type="submit" name="submit" value="', _('Accept'), '" />
				<input type="submit" name="Cancel" value="', _('Cancel'), '" />
			</div>';

		echo '</form>';

	} // end if user wish to delete
	
}

include ('includes/footer.php');
?>