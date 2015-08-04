<?php
/* $Id: AnalysisHorizontalPosition.php 7268 2015-04-19 14:57:47Z rchacon $*/
/* Horizontal analysis of statement of financial position. */

function RelativeVariation($CurrentPeriod, $PreviousPeriod) {
	// Calculates the relative variation between current and previous periods. Uses percent in locale number format.
	if ($PreviousPeriod <> 0) {
		return locale_number_format(($CurrentPeriod - $PreviousPeriod) * 100 / $PreviousPeriod, $_SESSION['CompanyRecord']['decimalplaces']) . '%';
	} else {
		return _('N/A');
	}
}

include('includes/session.inc');
$Title = _('Horizontal Analysis of Statement of Financial Position'); // Screen identification.
$ViewTopic = 'GeneralLedger'; // Filename's id in ManualContents.php's TOC.
$BookMark = 'AnalysisHorizontalPosition'; // Anchor's id in the manual's html document.
include('includes/SQL_CommonFunctions.inc');
include('includes/AccountSectionsDef.inc'); // This loads the $Sections variable

if (!isset($_POST['BalancePeriodEnd']) or isset($_POST['SelectADifferentPeriod'])) {

	/*Show a form to allow input of criteria for TB to show */
	include('includes/header.inc');
	echo '<p class="page_title_text">
			<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" title="', _('Print Horizontal analysis of statement of financial position'), '" /> ', // Icon title.
			_('Horizontal Analysis of Statement of Financial Position'),
		'</p>'; // Page title.

	echo '<div class="page_help_text">', _('Horizontal analysis (also known as trend analysis) is a financial statement analysis technique that shows changes in the amounts of corresponding financial statement items over a period of time. It is a useful tool to evaluate trend situations.'), '<br />', _('The statements for two periods are used in horizontal analysis. The earliest period is used as the base period. The items on the later statement are compared with items on the statement of the base period. The changes are shown both in currency (absolute variation) and percentage (relative variation).'), '<br />', _('webERP is an "accrual" based system (not a "cash based" system).  Accrual systems include items when they are invoiced to the customer, and when expenses are owed based on the supplier invoice date.'), '</div>',
			'<form method="post" action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '">',
				'<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />',
				'<table class="selection">
					<tr>
						<td>', _('Select the balance date'), ':</td>
						<td><select required="required" name="BalancePeriodEnd">';

	$PeriodNo = GetPeriod(Date($_SESSION['DefaultDateFormat']));
	$SQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $PeriodNo . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$LastDateInPeriod = $MyRow[0];

	$SQL = "SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC";
	$Periods = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Periods)) {
		if ($MyRow['periodno'] == $PeriodNo) {
			echo '<option selected="selected" value="', $MyRow['periodno'], '">', ConvertSQLDate($LastDateInPeriod), '</option>';
		} else {
			echo '<option value="', $MyRow['periodno'], '">', ConvertSQLDate($MyRow['lastdate_in_period']), '</option>';
		}
	}
	echo '</select>
			</td>
		</tr>';

	echo '<tr>
			<td>', _('Detail or summary'), ':</td>
			<td>
				<select name="Detail" required="required" title="', _('Selecting Summary will show on the totals at the account group level'), '" >
					<option value="Summary">', _('Summary'), '</option>
					<option selected="selected" value="Detailed">', _('All Accounts'), '</option>
				</select>
			</td>
		</tr>
		<tr>
			 <td>', _('Show all accounts including zero balances'), '</td>
			 <td>
				<input name="ShowZeroBalances" title="', _('Check this box to display all accounts including those accounts with no balance'), '" type="checkbox" />
			</td>
		</tr>
	</table>';

	echo '<div class="centre noprint">
			<button name="ShowBalanceSheet" type="submit" value="', _('Show on Screen (HTML)'), '">
				<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/gl.png" /> ', _('Show on Screen (HTML)'), '
			</button>
			<button formaction="index.php?Application=GL" type="submit">
				<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/previous.png" /> ', _('Return'), '
			</button>', // "Return" button.
		'</div>
	</form>';

	/*Now do the posting while the user is thinking about the period to select */
	include('includes/GLPostings.inc');

} else {
	include('includes/header.inc');

	$RetainedEarningsAct = $_SESSION['CompanyRecord']['retainedearnings'];

	$SQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $_POST['BalancePeriodEnd'] . "'";
	$PrdResult = DB_query($SQL);
	$MyRow = DB_fetch_row($PrdResult);
	$BalanceDate = ConvertSQLDate($MyRow[0]);

	// Calculate B/Fwd retained earnings:
	$SQL = "SELECT Sum(CASE WHEN chartdetails.period='" . $_POST['BalancePeriodEnd'] . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS accumprofitbfwd,
					Sum(CASE WHEN chartdetails.period='" . ($_POST['BalancePeriodEnd'] - 12) . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS lyaccumprofitbfwd
				FROM chartmaster
				INNER JOIN accountgroups
					ON chartmaster.group_ = accountgroups.groupname
				INNER JOIN chartdetails
					ON chartmaster.accountcode= chartdetails.accountcode
				WHERE accountgroups.pandl=1";

	$AccumProfitResult = DB_query($SQL, _('The accumulated profits brought forward could not be calculated by the SQL because'));

	$AccumProfitRow = DB_fetch_array($AccumProfitResult);
	/*should only be one row returned */

	$SQL = "SELECT accountgroups.sectioninaccounts,
					accountgroups.groupname,
					accountgroups.parentgroupname,
					chartdetails.accountcode,
					chartmaster.accountname,
					Sum(CASE WHEN chartdetails.period='" . $_POST['BalancePeriodEnd'] . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS balancecfwd,
					Sum(CASE WHEN chartdetails.period='" . ($_POST['BalancePeriodEnd'] - 12) . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS lybalancecfwd
				FROM chartmaster
				INNER JOIN accountgroups
					ON chartmaster.group_ = accountgroups.groupname
				INNER JOIN chartdetails
					ON chartmaster.accountcode= chartdetails.accountcode
				WHERE accountgroups.pandl=0
				GROUP BY accountgroups.groupname,
						chartdetails.accountcode,
						chartmaster.accountname,
						accountgroups.parentgroupname,
						accountgroups.sequenceintb,
						accountgroups.sectioninaccounts
				ORDER BY accountgroups.sectioninaccounts,
						accountgroups.sequenceintb,
						accountgroups.groupname,
						chartdetails.accountcode";

	$AccountsResult = DB_query($SQL, _('No general ledger accounts were returned by the SQL because'));

	// Page title as IAS 1, numerals 10 and 51:
	include_once('includes/CurrenciesArray.php'); // Array to retrieve currency name.
	echo '<div id="Report">', // Division to identify the report block.
			'<p class="page_title_text">
				<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/gl.png" title="', _('Horizontal Analysis of Statement of Financial Position'), '" /> ', // Icon title.
				_('Horizontal Analysis of Statement of Financial Position'), '<br />', // Page title, reporting statement.
				stripslashes($_SESSION['CompanyRecord']['coyname']), '<br />', // Page title, reporting entity.
				_('as at'), ' ', $BalanceDate, '<br />', // Page title, reporting period.
				_('All amounts stated in'), ': ', _($CurrencyName[$_SESSION['CompanyRecord']['currencydefault']]), '
			</p>'; // Page title, reporting presentation currency and level of rounding used.

	echo '<table class="scrollable">
			<thead>
				<tr>';
	if ($_POST['Detail'] == 'Detailed') { // Detailed report:
		echo '<th class="text">', _('Account'), '</th>
			<th class="text">', _('Account Name'), '</th>';
	} else { // Summary report:
		echo '<th class="text" colspan="2">', _('Summary'), '</th>';
	}
	echo '<th class="number">', $BalanceDate, '</th>
			<th class="number">', _('Last Year'), '</th>
			<th class="number">', _('Absolute variation'), '</th>
			<th class="number">', _('Relative variation'), '</th>
		</tr>
	</thead>
	<tbody>'; // thead used in conjunction with tbody enable scrolling of the table body independently of the header and footer. Also, when printing a large table that spans multiple pages, these elements can enable the table header to be printed at the top of each page.

	$k = 0; //row colour counter
	$Section = '';
	$SectionBalance = 0;
	$SectionBalanceLY = 0;

	$LYCheckTotal = 0;
	$CheckTotal = 0;

	$ActGrp = '';
	$Level = 0;
	$ParentGroups = array();
	$ParentGroups[$Level] = '';
	$GroupTotal = array(
		0
	);
	$LYGroupTotal = array(
		0
	);

	$j = 0; //row counter

	while ($MyRow = DB_fetch_array($AccountsResult)) {
		$AccountBalance = $MyRow['balancecfwd'];
		$LYAccountBalance = $MyRow['lybalancecfwd'];

		if ($MyRow['accountcode'] == $RetainedEarningsAct) {
			$AccountBalance += $AccumProfitRow['accumprofitbfwd'];
			$LYAccountBalance += $AccumProfitRow['lyaccumprofitbfwd'];
		}

		if ($MyRow['groupname'] != $ActGrp AND $ActGrp != '') {
			if ($MyRow['parentgroupname'] != $ActGrp) {
				while ($MyRow['groupname'] != $ParentGroups[$Level] AND $Level > 0) {
					if ($_POST['Detail'] == 'Detailed') {
						echo '<tr>
								<td colspan="2">&nbsp;</td>
								<td><hr /></td>
								<td><hr /></td>
								<td><hr /></td>
								<td><hr /></td>
							</tr>';
					}
					echo '<tr>
							<td colspan="2"><i>', $ParentGroups[$Level], '</i></td>
							<td class="number">', locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format($LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format($GroupTotal[$Level] - $LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', RelativeVariation($GroupTotal[$Level], $LYGroupTotal[$Level]), '</td>
						</tr>';
					$GroupTotal[$Level] = 0;
					$LYGroupTotal[$Level] = 0;
					$ParentGroups[$Level] = '';
					$Level--;
					$j++;
				}
				if ($_POST['Detail'] == 'Detailed') {
					echo '<tr>
							<td colspan="2">&nbsp;</td>
							<td><hr /></td>
							<td><hr /></td>
							<td><hr /></td>
							<td><hr /></td>
						</tr>';
				}
				echo '<tr>
						<td colspan="2">', $ParentGroups[$Level], '</td>
						<td class="number">', locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format($LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format($GroupTotal[$Level] - $LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', RelativeVariation($GroupTotal[$Level], $LYGroupTotal[$Level]), '</td>
					</tr>';
				$GroupTotal[$Level] = 0;
				$LYGroupTotal[$Level] = 0;
				$ParentGroups[$Level] = '';
				$j++;
			}
		}
		if ($MyRow['sectioninaccounts'] != $Section) {

			if ($Section != '') {
				if ($_POST['Detail'] == 'Detailed') {
					echo '<tr>
							<td colspan="2">&nbsp;</td>
							<td><hr /></td>
							<td><hr /></td>
							<td><hr /></td>
							<td><hr /></td>
						</tr>';
				} else {
					echo '<tr>
							<td colspan="2">&nbsp;</td>
							<td><hr /></td>
							<td><hr /></td>
							<td><hr /></td>
							<td><hr /></td>
						</tr>';
				}
				echo '<tr>
						<td colspan="2"><h2>', $Sections[$Section], '</h2></td>
						<td class="number"><h2>', locale_number_format($SectionBalance, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
						<td class="number"><h2>', locale_number_format($SectionBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
						<td class="number"><h2>', locale_number_format($SectionBalance - $SectionBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
						<td class="number"><h2>', RelativeVariation($SectionBalance, $SectionBalanceLY), '</h2></td>
					</tr>';
				$j++;
			}
			$SectionBalanceLY = 0;
			$SectionBalance = 0;
			$Section = $MyRow['sectioninaccounts'];
			if ($_POST['Detail'] == 'Detailed') {
				echo '<tr>
						<td colspan="6"><h2>', $Sections[$MyRow['sectioninaccounts']], '</h2></td>
					</tr>';
			}
		}

		if ($MyRow['groupname'] != $ActGrp) {

			if ($ActGrp != '' AND $MyRow['parentgroupname'] == $ActGrp) {
				$Level++;
			}

			if ($_POST['Detail'] == 'Detailed') {
				$ActGrp = $MyRow['groupname'];
				echo '<tr>
						<td colspan="6"><h3>', $MyRow['groupname'], '</h3></td>
					</tr>';
			}
			$GroupTotal[$Level] = 0;
			$LYGroupTotal[$Level] = 0;
			$ActGrp = $MyRow['groupname'];
			$ParentGroups[$Level] = $MyRow['groupname'];
			$j++;
		}

		$SectionBalanceLY += $LYAccountBalance;
		$SectionBalance += $AccountBalance;
		for ($i = 0; $i <= $Level; $i++) {
			$LYGroupTotal[$i] += $LYAccountBalance;
			$GroupTotal[$i] += $AccountBalance;
		}
		$LYCheckTotal += $LYAccountBalance;
		$CheckTotal += $AccountBalance;

		if ($_POST['Detail'] == 'Detailed') {
			if (isset($_POST['ShowZeroBalances']) OR (!isset($_POST['ShowZeroBalances']) AND (round($AccountBalance, $_SESSION['CompanyRecord']['decimalplaces']) <> 0 OR round($LYAccountBalance, $_SESSION['CompanyRecord']['decimalplaces']) <> 0))) {
				if ($k == 1) {
					echo '<tr class="OddTableRows">';
					$k = 0;
				} else {
					echo '<tr class="EvenTableRows">';
					$k++;
				}
				echo '<td><a href="', $RootPath, '/GLAccountInquiry.php?Period=', $_POST['BalancePeriodEnd'], '&amp;Account=', $MyRow['accountcode'], '">', $MyRow['accountcode'], '</a></td>
						<td>', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), '</td>
						<td class="number">', locale_number_format($AccountBalance, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format($LYAccountBalance, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format($AccountBalance - $LYAccountBalance, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', RelativeVariation($AccountBalance, $LYAccountBalance), '</td>
					</tr>';
				$j++;
			}
		}
	}
	//end of loop

	while ($MyRow['groupname'] != $ParentGroups[$Level] AND $Level > 0) {
		if ($_POST['Detail'] == 'Detailed') {
			echo '<tr>
					<td colspan="2">&nbsp;</td>
					<td><hr /></td>
					<td><hr /></td>
					<td><hr /></td>
					<td><hr /></td>
				</tr>';
		}
		echo '<tr>
				<td colspan="2"><i>', $ParentGroups[$Level], '</i></td>
				<td class="number">', locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($GroupTotal[$Level] - $LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', RelativeVariation($GroupTotal[$Level], $LYGroupTotal[$Level]), '</td>
			</tr>';
		$Level--;
	}
	if ($_POST['Detail'] == 'Detailed') {
		echo '<tr>
				<td colspan="2">&nbsp;</td>
				<td><hr /></td>
				<td><hr /></td>
				<td><hr /></td>
				<td><hr /></td>
			</tr>';
	}
	echo '<tr>
			<td colspan="2">', $ParentGroups[$Level], '</td>
			<td class="number">', locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			<td class="number">', locale_number_format($LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			<td class="number">', locale_number_format($GroupTotal[$Level] - $LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			<td class="number">', RelativeVariation($GroupTotal[$Level], $LYGroupTotal[$Level]), '</td>
		</tr>';

	echo '<tr>
			<td colspan="2">&nbsp;</td>
			<td><hr /></td>
			<td><hr /></td>
			<td><hr /></td>
			<td><hr /></td>
		</tr>';

	echo '<tr>
			<td colspan="2"><h2>', $Sections[$Section], '</h2></td>
			<td class="number"><h2>', locale_number_format($SectionBalance, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', locale_number_format($SectionBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', locale_number_format($SectionBalance - $SectionBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', RelativeVariation($SectionBalance, $SectionBalanceLY), '</h2></td>
		</tr>';

	$Section = $MyRow['sectioninaccounts'];

	if (isset($MyRow['sectioninaccounts']) and $_POST['Detail'] == 'Detailed') {
		echo '<tr>
				<td colspan="6"><h2>', $Sections[$MyRow['sectioninaccounts']], '</h2></td>
			</tr>';
	}
	echo '<tr>
			<td colspan="2">&nbsp;</td>
			<td><hr /></td>
			<td><hr /></td>
			<td><hr /></td>
			<td><hr /></td>
		</tr>';

	echo '<tr>
			<td colspan="2"><h2>', _('Check Total'), '</h2></td>
			<td class="number"><h2>', locale_number_format($CheckTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', locale_number_format($LYCheckTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', locale_number_format($CheckTotal - $LYCheckTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', RelativeVariation($CheckTotal, $LYCheckTotal), '</h2></td>
		</tr>';

	echo '<tr>
			<td colspan="2">&nbsp;</td>
			<td><hr /></td>
			<td><hr /></td>
			<td><hr /></td>
			<td><hr /></td>
		</tr>';

	echo '</tbody>', // See comment at the begin of the table.
		'</table>
		</div>';

	echo '<form method="post" action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '">
			<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
			<input type="hidden" name="BalancePeriodEnd" value="', $_POST['BalancePeriodEnd'], '" />
			<div class="centre noprint">
				<button onclick="javascript:window.print()" type="button">
					<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" /> ', _('Print This'), '
				</button>
				<button name="SelectADifferentPeriod" type="submit" value="', _('Select A Different Period'), '">
					<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/gl.png" /> ', _('Select A Different Period'), '
				</button>
				<button formaction="index.php?Application=GL" type="submit">
					<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/previous.png" /> ', _('Return'), '
				</button>
			</div>
		</form>';
}

include('includes/footer.inc');
?>