<?php

include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');

$InputError = 0;

if (isset($_POST['FromDate']) and !Is_Date($_POST['FromDate'])) {
	$msg = _('The date from must be specified in the format') . ' ' . $_SESSION['DefaultDateFormat'];
	$InputError = 1;
	unset($_POST['FromDate']);
}
if (isset($_POST['ToDate']) and !Is_Date($_POST['ToDate'])) {
	$msg = _('The date to must be specified in the format') . ' ' . $_SESSION['DefaultDateFormat'];
	$InputError = 1;
	unset($_POST['ToDate']);
}

if (!isset($_POST['FromDate']) or !isset($_POST['ToDate'])) {

	$Title = _('Order Status Report');
	include('includes/header.inc');

	if ($InputError == 1) {
		prnMsg($msg, 'error');
	}

	echo '<p class="page_title_text noPrint" ><img src="' . $RootPath . '/css/' . $Theme . '/images/transactions.png" title="' . $Title . '" alt="" />' . ' ' . _('Order Status Report') . '</p>';

	echo '<form onSubmit="return VerifyForm(this);" method="post" class="noPrint" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection">
			<tr>
				<td>' . _('Enter the date from which orders are to be listed') . ':</td>
				<td><input type="text" class="date" alt="' . $_SESSION['DefaultDateFormat'] . '" name="FromDate" required="required" minlength="1" maxlength="10" size="10" value="' . Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, Date('m'), Date('d') - 1, Date('y'))) . '" /></td>
			</tr>';
	echo '<tr>
			<td>' . _('Enter the date to which orders are to be listed') . ':</td>
			<td><input type="text" class="date" alt="' . $_SESSION['DefaultDateFormat'] . '" name="ToDate" required="required" minlength="1" maxlength="10" size="10" value="' . Date($_SESSION['DefaultDateFormat']) . '" /></td>
		</tr>';
	echo '<tr>
			<td>' . _('Inventory Category') . '</td>
			<td>';

	$SQL = "SELECT categorydescription, categoryid FROM stockcategory WHERE stocktype<>'D' AND stocktype<>'L'";
	$Result = DB_query($SQL);


	echo '<select required="required" minlength="1" name="CategoryID">';
	echo '<option selected="selected" value="All">' . _('Over All Categories') . '</option>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
	}
	echo '</select></td></tr>';

	echo '<tr>
			<td>' . _('Inventory Location') . ':</td><td><select required="required" minlength="1" name="Location">';
	if ($_SESSION['RestrictLocations'] == 0) {
		$SQL = "SELECT locationname,
						loccode
					FROM locations";
		echo '<option selected="selected" value="All">' . _('All Locations') . '</option>';
	} else {
		$SQL = "SELECT locationname,
						loccode
					FROM locations
					INNER JOIN www_users
						ON locations.loccode=www_users.defaultlocation
					WHERE www_users.userid='" . $_SESSION['UserID'] . "'";
	}

	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '</select></td></tr>';

	echo '<tr>
			<td>' . _('Back Order Only') . ':</td>
			<td><select required="required" minlength="1" name="BackOrders">
					<option selected="selected" value="Yes">' . _('Only Show Back Orders') . '</option>
					<option value="No">' . _('Show All Orders') . '</option>
				</select>
			</td>
		</tr>
		</table>
		<br />
		<div class="centre">
			<input type="submit" name="Go" value="' . _('Create PDF') . '" />
		</div>';
	echo '</div>
		  </form>';

	include('includes/footer.inc');
	exit;
} else {
	include('includes/ConnectDB.inc');
	include('includes/PDFStarter.php');
	$pdf->addInfo('Title', _('Order Status Report'));
	$pdf->addInfo('Subject', _('Orders from') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' ' . $_POST['ToDate']);
	$line_height = 12;
	$PageNumber = 1;
	$TotalDiffs = 0;
}


if ($_POST['CategoryID'] == 'All' and $_POST['Location'] == 'All') {
	$SQL = "SELECT salesorders.orderno,
				  salesorders.debtorno,
				  salesorders.branchcode,
				  salesorders.customerref,
				  salesorders.orddate,
				  salesorders.fromstkloc,
				  salesorders.printedpackingslip,
				  salesorders.datepackingslipprinted,
				  salesorderdetails.stkcode,
				  stockmaster.description,
				  stockmaster.units,
				  stockmaster.decimalplaces,
				  salesorderdetails.quantity,
				  salesorderdetails.qtyinvoiced,
				  salesorderdetails.completed,
				  debtorsmaster.name,
				  custbranch.brname,
				  locations.locationname
			 FROM salesorders
				 INNER JOIN salesorderdetails
				 ON salesorders.orderno = salesorderdetails.orderno
				 INNER JOIN stockmaster
				 ON salesorderdetails.stkcode = stockmaster.stockid
				 INNER JOIN debtorsmaster
				 ON salesorders.debtorno=debtorsmaster.debtorno
				 INNER JOIN custbranch
				 ON custbranch.debtorno=salesorders.debtorno
				 AND custbranch.branchcode=salesorders.branchcode
				 INNER JOIN locations
				 ON salesorders.fromstkloc=locations.loccode
			 WHERE salesorders.orddate >='" . FormatDateForSQL($_POST['FromDate']) . "'
				  AND salesorders.orddate <='" . FormatDateForSQL($_POST['ToDate']) . "'
			 AND salesorders.quotation=0";

} elseif ($_POST['CategoryID'] != 'All' and $_POST['Location'] == 'All') {
	$SQL = "SELECT salesorders.orderno,
				  salesorders.debtorno,
				  salesorders.branchcode,
				  salesorders.customerref,
				  salesorders.orddate,
				  salesorders.fromstkloc,
				  salesorders.printedpackingslip,
				  salesorders.datepackingslipprinted,
				  salesorderdetails.stkcode,
				  stockmaster.description,
				  stockmaster.units,
				  stockmaster.decimalplaces,
				  salesorderdetails.quantity,
				  salesorderdetails.qtyinvoiced,
				  salesorderdetails.completed,
				  debtorsmaster.name,
				  custbranch.brname,
				  locations.locationname
			 FROM salesorders
				 INNER JOIN salesorderdetails
				 ON salesorders.orderno = salesorderdetails.orderno
				 INNER JOIN stockmaster
				 ON salesorderdetails.stkcode = stockmaster.stockid
				 INNER JOIN debtorsmaster
				 ON salesorders.debtorno=debtorsmaster.debtorno
				 INNER JOIN custbranch
				 ON custbranch.debtorno=salesorders.debtorno
				 AND custbranch.branchcode=salesorders.branchcode
				 INNER JOIN locations
				 ON salesorders.fromstkloc=locations.loccode
			 WHERE stockmaster.categoryid ='" . $_POST['CategoryID'] . "'
				  AND orddate >='" . FormatDateForSQL($_POST['FromDate']) . "'
				  AND orddate <='" . FormatDateForSQL($_POST['ToDate']) . "'
			 AND salesorders.quotation=0";


} elseif ($_POST['CategoryID'] == 'All' and $_POST['Location'] != 'All') {
	$SQL = "SELECT salesorders.orderno,
				  salesorders.debtorno,
				  salesorders.branchcode,
				  salesorders.customerref,
				  salesorders.orddate,
				  salesorders.fromstkloc,
				  salesorders.printedpackingslip,
				  salesorders.datepackingslipprinted,
				  salesorderdetails.stkcode,
				  stockmaster.description,
				  stockmaster.units,
				  stockmaster.decimalplaces,
				  salesorderdetails.quantity,
				  salesorderdetails.qtyinvoiced,
				  salesorderdetails.completed,
				  debtorsmaster.name,
				  custbranch.brname,
				  locations.locationname
			 FROM salesorders
				 INNER JOIN salesorderdetails
				 ON salesorders.orderno = salesorderdetails.orderno
				 INNER JOIN stockmaster
				 ON salesorderdetails.stkcode = stockmaster.stockid
				 INNER JOIN debtorsmaster
				 ON salesorders.debtorno=debtorsmaster.debtorno
				 INNER JOIN custbranch
				 ON custbranch.debtorno=salesorders.debtorno
				 AND custbranch.branchcode=salesorders.branchcode
				 INNER JOIN locations
				 ON salesorders.fromstkloc=locations.loccode
			 WHERE salesorders.fromstkloc ='" . $_POST['Location'] . "'
				  AND salesorders.orddate >='" . FormatDateForSQL($_POST['FromDate']) . "'
				  AND salesorders.orddate <='" . FormatDateForSQL($_POST['ToDate']) . "'
			 AND salesorders.quotation=0";


} elseif ($_POST['CategoryID'] != 'All' and $_POST['location'] != 'All') {

	$SQL = "SELECT salesorders.orderno,
				  salesorders.debtorno,
				  salesorders.branchcode,
				  salesorders.customerref,
				  salesorders.orddate,
				  salesorders.fromstkloc,
				  salesorders.printedpackingslip,
				  salesorders.datepackingslipprinted,
				  salesorderdetails.stkcode,
				  stockmaster.description,
				  stockmaster.units,
				  stockmaster.decimalplaces,
				  salesorderdetails.quantity,
				  salesorderdetails.qtyinvoiced,
				  salesorderdetails.completed,
				  debtorsmaster.name,
				  custbranch.brname,
				  locations.locationname
			 FROM salesorders
				 INNER JOIN salesorderdetails
				 ON salesorders.orderno = salesorderdetails.orderno
				 INNER JOIN stockmaster
				 ON salesorderdetails.stkcode = stockmaster.stockid
				 INNER JOIN debtorsmaster
				 ON salesorders.debtorno=debtorsmaster.debtorno
				 INNER JOIN custbranch
				 ON custbranch.debtorno=salesorders.debtorno
				 AND custbranch.branchcode=salesorders.branchcode
				 INNER JOIN locations
				 ON salesorders.fromstkloc=locations.loccode
			 WHERE stockmaster.categoryid ='" . $_POST['CategoryID'] . "'
				  AND salesorders.fromstkloc ='" . $_POST['Location'] . "'
				  AND salesorders.orddate >='" . FormatDateForSQL($_POST['FromDate']) . "'
				  AND salesorders.orddate <='" . FormatDateForSQL($_POST['ToDate']) . "'
			 AND salesorders.quotation=0";
}

if ($_POST['BackOrders'] == 'Yes') {
	$SQL .= " AND salesorderdetails.quantity-salesorderdetails.qtyinvoiced >0";
}

//Add salesman role control
if ($_SESSION['SalesmanLogin'] != '') {
	$SQL .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
}

$SQL .= " ORDER BY salesorders.orderno";

$Result = DB_query($SQL, '', '', false, false); //dont trap errors here

if (DB_error_no() != 0) {
	include('includes/header.inc');
	echo '<br />' . _('An error occurred getting the orders details');
	if ($debug == 1) {
		echo '<br />' . _('The SQL used to get the orders that failed was') . '<br />' . $SQL;
	}
	include('includes/footer.inc');
	exit;
} elseif (DB_num_rows($Result) == 0) {
	$Title = _('Order Status Report - No Data');
	include('includes/header.inc');
	prnMsg(_('There were no orders found in the database within the period from') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' ' . $_POST['ToDate'] . '. ' . _('Please try again selecting a different date range'), 'info');
	include('includes/footer.inc');
	exit;
}

include('includes/PDFOrderStatusPageHeader.inc');

$OrderNo = 0;
/*initialise */

while ($MyRow = DB_fetch_array($Result)) {

	$pdf->line($XPos, $YPos, $Page_Width - $Right_Margin, $YPos);

	$YPos -= $line_height;
	/*Set up headings */
	/*draw a line */

	if ($MyRow['orderno'] != $OrderNo) {
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 2, $YPos, 40, $FontSize, _('Order'), 'left');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 40, $YPos, 150, $FontSize, _('Customer'), 'left');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 190, $YPos, 110, $FontSize, _('Branch'), 'left');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 300, $YPos, 60, $FontSize, _('Ord Date'), 'left');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 360, $YPos, 60, $FontSize, _('Location'), 'left');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 420, $YPos, 80, $FontSize, _('Status'), 'left');

		$YPos -= $line_height;

		/*draw a line */
		$pdf->line($XPos, $YPos, $Page_Width - $Right_Margin, $YPos);
		$pdf->line($XPos, $YPos - $line_height * 2, $XPos, $YPos + $line_height * 2);
		$pdf->line($Page_Width - $Right_Margin, $YPos - $line_height * 2, $Page_Width - $Right_Margin, $YPos + $line_height * 2);


		if ($YPos - (2 * $line_height) < $Bottom_Margin) {
			/*Then set up a new page */
			$PageNumber++;
			include('includes/PDFOrderStatusPageHeader.inc');
			$OrderNo = 0;
		}
		/*end of new page header  */
		$YPos -= $line_height;

		$LeftOvers = $pdf->addTextWrap($Left_Margin + 2, $YPos, 40, $FontSize, $MyRow['orderno'], 'left');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 40, $YPos, 150, $FontSize, html_entity_decode($MyRow['name'], ENT_QUOTES, 'UTF-8'), 'left');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 190, $YPos, 110, $FontSize, $MyRow['brname'], 'left');

		$LeftOvers = $pdf->addTextWrap($Left_Margin + 300, $YPos, 60, $FontSize, ConvertSQLDate($MyRow['orddate']), 'left');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 360, $YPos, 80, $FontSize, $MyRow['locationname'], 'left');

		if ($MyRow['printedpackingslip'] == 1) {
			$PackingSlipPrinted = _('Printed') . ' ' . ConvertSQLDate($MyRow['datepackingslipprinted']);
		} else {
			$PackingSlipPrinted = _('Not yet printed');
		}

		$LeftOvers = $pdf->addTextWrap($Left_Margin + 420, $YPos, 100, $FontSize, $PackingSlipPrinted, 'left');
		$YPos -= $line_height;
		$pdf->line($XPos, $YPos, $Page_Width - $Right_Margin, $YPos);

		$YPos -= ($line_height);

		/*Its not the first line */
		$OrderNo = $MyRow['orderno'];
		$LeftOvers = $pdf->addTextWrap($Left_Margin, $YPos, 60, $FontSize, _('Code'), 'left');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 60, $YPos, 120, $FontSize, _('Description'), 'left');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 180, $YPos, 60, $FontSize, _('Ordered'), 'right');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 240, $YPos, 60, $FontSize, _('Invoiced'), 'right');
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 320, $YPos, 60, $FontSize, _('Outstanding'), 'center');
		$YPos -= ($line_height);

	}

	$LeftOvers = $pdf->addTextWrap($Left_Margin, $YPos, 60, $FontSize, $MyRow['stkcode'], 'left');
	$LeftOvers = $pdf->addTextWrap($Left_Margin + 60, $YPos, 120, $FontSize, $MyRow['description'], 'left');
	$LeftOvers = $pdf->addTextWrap($Left_Margin + 180, $YPos, 60, $FontSize, locale_number_format($MyRow['quantity'], $MyRow['decimalplaces']), 'right');
	$LeftOvers = $pdf->addTextWrap($Left_Margin + 240, $YPos, 60, $FontSize, locale_number_format($MyRow['qtyinvoiced'], $MyRow['decimalplaces']), 'right');

	if ($MyRow['quantity'] > $MyRow['qtyinvoiced']) {
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 320, $YPos, 60, $FontSize, locale_number_format($MyRow['quantity'] - $MyRow['qtyinvoiced'], $MyRow['decimalplaces']), 'right');
	} else {
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 320, $YPos, 60, $FontSize, _('Complete'), 'left');
	}

	$YPos -= ($line_height);
	if ($YPos - (2 * $line_height) < $Bottom_Margin) {
		/*Then set up a new page */
		$PageNumber++;
		include('includes/PDFOrderStatusPageHeader.inc');
		$OrderNo = 0;
	}
	/*end of new page header  */
}
/* end of while there are delivery differences to print */
$pdf->OutputD($_SESSION['DatabaseName'] . '_OrderStatus_' . date('Y-m-d') . '.pdf');
$pdf->__destruct();
?>