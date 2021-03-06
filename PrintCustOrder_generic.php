<?php
include ('includes/session.php');
include ('includes/SQL_CommonFunctions.php');

//Get Out if we have no order number to work with
if (!isset($_GET['TransNo']) or $_GET['TransNo'] == "") {
	$Title = _('Select Order To Print');
	include ('includes/header.php');
	prnMsg(_('Select an Order Number to Print before calling this page'), 'error');
	echo '<table class="table_index">
			<tr>
				<td class="menu_group_item">
					<ul>
						<li><a href="', $RootPath, '/SelectSalesOrder.php">', _('Outstanding Sales Orders'), '</a></li>
						<li><a href="', $RootPath, '/SelectCompletedOrder.php">', _('Completed Sales Orders'), '</a></li>
					</ul>
				</td>
			</tr>
		</table>';
	include ('includes/footer.php');
	exit();
}

/*retrieve the order details from the database to print */
$ErrMsg = _('There was a problem retrieving the order header details for Order Number') . ' ' . $_GET['TransNo'] . ' ' . _('from the database');

$SQL = "SELECT salesorders.debtorno,
    		salesorders.customerref,
			salesorders.comments,
			salesorders.orddate,
			salesorders.deliverto,
			salesorders.deladd1,
			salesorders.deladd2,
			salesorders.deladd3,
			salesorders.deladd4,
			salesorders.deladd5,
			salesorders.deladd6,
			salesorders.deliverblind,
			debtorsmaster.name,
			debtorsmaster.address1,
			debtorsmaster.address2,
			debtorsmaster.address3,
			debtorsmaster.address4,
			debtorsmaster.address5,
			debtorsmaster.address6,
			shippers.shippername,
			salesorders.printedpackingslip,
			salesorders.datepackingslipprinted,
			locations.locationname,
			salesorders.fromstkloc
		FROM salesorders
		INNER JOIN debtorsmaster
			ON salesorders.debtorno=debtorsmaster.debtorno
		INNER JOIN shippers
			ON salesorders.shipvia=shippers.shipper_id
		INNER JOIN locations
			ON salesorders.fromstkloc=locations.loccode
		INNER JOIN locationusers
			ON locationusers.loccode=locations.loccode
			AND locationusers.userid='" . $_SESSION['UserID'] . "'
			AND locationusers.canview=1
		WHERE salesorders.orderno='" . $_GET['TransNo'] . "'";
if ($_SESSION['SalesmanLogin'] != '') {
	$SQL.= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
}
$Result = DB_query($SQL, $ErrMsg);

//if there are no rows, there's a problem.
if (DB_num_rows($Result) == 0) {
	$Title = _('Print Packing Slip Error');
	include ('includes/header.php');
	prnMsg(_('Unable to Locate Order Number') . ' : ' . $_GET['TransNo'] . ' ', 'error');
	echo '<table class="table_index">
			<tr>
				<td class="menu_group_item">
					<li><a href="', $RootPath, '/SelectSalesOrder.php">', _('Outstanding Sales Orders'), '</a></li>
					<li><a href="', $RootPath, '/SelectCompletedOrder.php">', _('Completed Sales Orders'), '</a></li>
				</td>
			</tr>
		</table>';

	include ('includes/footer.php');
	exit();
} elseif (DB_num_rows($Result) == 1) {
	/*There is only one order header returned - thats good! */

	$MyRow = DB_fetch_array($Result);
	/* Place the deliver blind variable into a hold variable to used when
	 producing the packlist */
	$DeliverBlind = $MyRow['deliverblind'];
	if ($MyRow['printedpackingslip'] == 1 and ($_GET['Reprint'] != 'OK' or !isset($_GET['Reprint']))) {
		$Title = _('Print Packing Slip Error');
		include ('includes/header.php');
		echo '<p>';
		prnMsg(_('The packing slip for order number') . ' ' . $_GET['TransNo'] . ' ' . _('has previously been printed') . '. ' . _('It was printed on') . ' ' . ConvertSQLDate($MyRow['datepackingslipprinted']) . '<br />' . _('This check is there to ensure that duplicate packing slips are not produced and dispatched more than once to the customer'), 'warn');
		echo '<a href="', $RootPath, '/PrintCustOrder.php?TransNo=', urlencode($_GET['TransNo']), '&Reprint=OK">', _('Do a Re-Print'), ' (', _('On Pre-Printed Stationery'), ') ', _('Even Though Previously Printed'), '</a><p>', '<a href="', $RootPath, '/PrintCustOrder_generic.php?TransNo=', urlencode($_GET['TransNo']), '&Reprint=OK">', _('Do a Re-Print'), ' (', _('Plain paper'), ' - ', _('A4'), ' ', _('landscape'), ') ', _('Even Though Previously Printed'), '</a>';

		echo _('Or select another Order Number to Print');
		echo '<table class="table_index">
				<tr>
					<td class="menu_group_item">
						<li><a href="' . $RootPath . '/SelectSalesOrder.php">' . _('Outstanding Sales Orders') . '</a></li>
						<li><a href="' . $RootPath . '/SelectCompletedOrder.php">' . _('Completed Sales Orders') . '</a></li>
					</td>
				</tr>
			</table>';

		include ('includes/footer.php');
		exit;
	} //packing slip has been printed.
	
}

/*retrieve the order details from the database to print */

/* Then there's an order to print and its not been printed already (or its been flagged for reprinting)
 LETS GO */

$PaperSize = 'A4_Landscape';
include ('includes/PDFStarter.php');
//$PDF->selectFont('./fonts/Helvetica.afm');
$PDF->addInfo('Title', _('Customer Laser Packing Slip'));
$PDF->addInfo('Subject', _('Laser Packing slip for order') . ' ' . $_GET['TransNo']);
$FontSize = 12;
$line_height = 24;
$PageNumber = 1;
$Copy = 'Office';

$ListCount = 0;

for ($i = 1;$i <= 2;$i++) {
	/*Print it out twice one copy for customer and one for office */
	if ($i == 2) {
		$PageNumber = 1;
		$PDF->newPage();
	}
	/* Now ... Has the order got any line items still outstanding to be invoiced */
	$ErrMsg = _('There was a problem retrieving the order details for Order Number') . ' ' . $_GET['TransNo'] . ' ' . _('from the database');

	$SQL = "SELECT salesorderdetails.stkcode,
					stockmaster.description,
					salesorderdetails.quantity,
					salesorderdetails.qtyinvoiced,
					salesorderdetails.unitprice,
					salesorderdetails.narrative,
					stockmaster.mbflag,
					stockmaster.decimalplaces,
					stockmaster.grossweight,
					stockmaster.volume,
					stockmaster.units,
					stockmaster.controlled,
					stockmaster.serialised,
					pickreqdetails.qtypicked,
					pickreqdetails.detailno,
					custitem.cust_part,
					custitem.cust_description,
					locstock.bin
				FROM salesorderdetails
				INNER JOIN stockmaster
				ON salesorderdetails.stkcode=stockmaster.stockid
				INNER JOIN locstock
				ON stockmaster.stockid = locstock.stockid
				LEFT OUTER JOIN pickreq
				ON pickreq.orderno=salesorderdetails.orderno
				AND pickreq.closed=0
				LEFT OUTER JOIN pickreqdetails
				ON pickreqdetails.prid=pickreq.prid
				AND pickreqdetails.orderlineno=salesorderdetails.orderlineno
				LEFT OUTER JOIN custitem
				ON custitem.debtorno='" . $MyRow['debtorno'] . "'
				AND custitem.stockid=salesorderdetails.stkcode
				WHERE locstock.loccode = '" . $MyRow['fromstkloc'] . "'
				AND salesorderdetails.orderno='" . $_GET['TransNo'] . "'";
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) > 0) {
		/*Yes there are line items to start the ball rolling with a page header */
		include ('includes/PDFOrderPageHeader_generic.php');

		$Volume = 0;
		$Weight = 0;
		while ($MyRow2 = DB_fetch_array($Result)) {

			$ListCount++;
			$Volume+= $MyRow2['quantity'] * $MyRow2['volume'];
			$Weight+= $MyRow2['quantity'] * $MyRow2['grossweight'];

			$DisplayQty = locale_number_format($MyRow2['quantity'], $MyRow2['decimalplaces']);
			$DisplayPrevDel = locale_number_format($MyRow2['qtyinvoiced'], $MyRow2['decimalplaces']);
			if ($MyRow2['qtypicked'] > 0) {
				$DisplayQtySupplied = locale_number_format($MyRow2['qtypicked'], $MyRow2['decimalplaces']);
			} else {
				$DisplayQtySupplied = locale_number_format($MyRow2['quantity'] - $MyRow2['qtyinvoiced'], $MyRow2['decimalplaces']);
			}

			$LeftOvers = $PDF->addTextWrap($XPos, $YPos, 127, $FontSize, $MyRow2['stkcode'], 'left');
			$LeftOvers = $PDF->addTextWrap(147, $YPos, 255, $FontSize, $MyRow2['description'], 'left');
			$LeftOvers = $PDF->addTextWrap(400, $YPos, 85, $FontSize, $DisplayQty, 'right');
			$LeftOvers = $PDF->addTextWrap(487, $YPos, 85, $FontSize, $MyRow2['units'], 'left');
			$LeftOvers = $PDF->addTextWrap(527, $YPos, 70, $FontSize, $MyRow2['bin'], 'left');
			$LeftOvers = $PDF->addTextWrap(593, $YPos, 85, $FontSize, $DisplayQtySupplied, 'right');
			$LeftOvers = $PDF->addTextWrap(692, $YPos, 85, $FontSize, $DisplayPrevDel, 'right');

			if ($_SESSION['AllowOrderLineItemNarrative'] == 1) {
				// Prints salesorderdetails.narrative:
				$FontSize2 = $FontSize * 0.8; // Font size to print salesorderdetails.narrative.
				$Width2 = $Page_Width - $Right_Margin - 145; // Width to print salesorderdetails.narrative.
				$LeftOvers = trim($MyRow2['narrative']);
				//**********
				$LeftOvers = str_replace('\n', ' ', $LeftOvers); // Replaces line feed character.
				$LeftOvers = str_replace('\r', '', $LeftOvers); // Delete carriage return character
				$LeftOvers = str_replace('\t', '', $LeftOvers); // Delete tabulator character
				//**********
				while (mb_strlen($LeftOvers) > 1) {
					$YPos-= $FontSize2;
					if ($YPos < ($Bottom_Margin)) { // Begins new page.
						include ('includes/PDFOrderPageHeader_generic.inc');
					}
					$LeftOvers = $PDF->addTextWrap(147, $YPos, $Width2, $FontSize2, $LeftOvers);
				}
			}

			if ($YPos - $line_height <= 50) {
				/* We reached the end of the page so finsih off the page and start a newy */
				$PageNumber++;
				include ('includes/PDFOrderPageHeader_generic.php');
			} //end if need a new page headed up
			else {
				/*increment a line down for the next line item */
				$YPos-= ($line_height);
			}
			if ($MyRow2['cust_part'] > '') {
				$LeftOvers = $PDF->addTextWrap($XPos, $YPos, 127, $FontSize, $MyRow2['cust_part'], 'right');
				$LeftOvers = $PDF->addTextWrap(147, $YPos, 255, $FontSize, $MyRow2['cust_description']);
				if ($YPos - $line_height <= 50) {
					/* We reached the end of the page so finsih off the page and start a newy */
					$PageNumber++;
					include ('includes/PDFOrderPageHeader_generic.php');
				} //end if need a new page headed up
				else {
					/*increment a line down for the next line item */
					$YPos-= ($line_height);
				}
			}
			if ($MyRow2['mbflag'] == 'A') {
				/*Then its an assembly item - need to explode into it's components for packing list purposes */
				$SQL = "SELECT bom.component,
								bom.quantity,
								stockmaster.description,
								stockmaster.decimalplaces
						FROM bom
						INNER JOIN stockmaster
							ON bom.component=stockmaster.stockid
						WHERE bom.parent='" . $MyRow2['stkcode'] . "'
							AND bom.effectiveafter <= '" . date('Y-m-d') . "'
							AND bom.effectiveto > '" . date('Y-m-d') . "'";
				$ErrMsg = _('Could not retrieve the components of the ordered assembly item');
				$AssemblyResult = DB_query($SQL, $ErrMsg);
				$LeftOvers = $PDF->addTextWrap($XPos, $YPos, 150, $FontSize, _('Assembly Components:-'));
				$YPos-= ($line_height);
				/*Loop around all the components of the assembly and list the quantity supplied */
				while ($ComponentRow = DB_fetch_array($AssemblyResult)) {
					$DisplayQtySupplied = locale_number_format($ComponentRow['quantity'] * ($MyRow2['quantity'] - $MyRow2['qtyinvoiced']), $ComponentRow['decimalplaces']);
					$LeftOvers = $PDF->addTextWrap($XPos, $YPos, 127, $FontSize, $ComponentRow['component']);
					$LeftOvers = $PDF->addTextWrap(147, $YPos, 255, $FontSize, $ComponentRow['description']);
					$LeftOvers = $PDF->addTextWrap(503, $YPos, 85, $FontSize, $DisplayQtySupplied, 'right');
					if ($YPos - $line_height <= 50) {
						/* We reached the end of the page so finsih off the page and start a newy */
						$PageNumber++;
						include ('includes/PDFOrderPageHeader_generic.php');
					} //end if need a new page headed up
					else {
						/*increment a line down for the next line item */
						$YPos-= ($line_height);
					}
				} //loop around all the components of the assembly
				
			}
			if ($MyRow2['controlled'] == '1') {
				$ControlLabel = _('Lot') . ':';
				if ($MyRow2['serialised'] == 1) {
					$ControlLabel = _('Serial') . ':';
				}
				$sersql = "SELECT serialno,
									moveqty
							FROM pickserialdetails
							WHERE pickserialdetails.detailno='" . $MyRow2['detailno'] . "'";
				$serresult = DB_query($sersql, $ErrMsg);
				while ($myser = DB_fetch_array($serresult)) {
					$LeftOvers = $PDF->addTextWrap($XPos, $YPos, 127, $FontSize, $ControlLabel, 'right');
					$LeftOvers = $PDF->addTextWrap(147, $YPos, 255, $FontSize, $myser['serialno'], 'left');
					$LeftOvers = $PDF->addTextWrap(147, $YPos, 255, $FontSize, $myser['moveqty'], 'right');
					if ($YPos - $line_height <= 50) {
						/* We reached the end of the page so finsih off the page and start a newy */
						$PageNumber++;
						include ('includes/PDFOrderPageHeader_generic.php');
					} //end if need a new page headed up
					else {
						/*increment a line down for the next line item */
						$YPos-= ($line_height);
					}
				} //while loop on myser
				
			} //controlled
			
		} //end while there are line items to print out
		
	}
	/*end if there are order details to show on the order*/

	if ($Copy != 'Customer') {
		$LeftOvers = $PDF->addTextWrap(375, 20, 150, $FontSize, 'Accepted/Received By:', 'left');
		$PDF->line(500, 20, 650, 20);
		$LeftOvers = $PDF->addTextWrap(675, 20, 50, $FontSize, 'Date:', 'left');
		$PDF->line(710, 20, 785, 20);
	}

	$LeftOvers = $PDF->addTextWrap(17, 20, 100, $FontSize, 'Volume: ' . round($Volume) . ' GA', 'left');
	$LeftOvers = $PDF->addTextWrap(147, 20, 200, $FontSize, 'Weight: ' . round($Weight) . ' LB (approximate)', 'left');

	$Copy = 'Customer';
	$Volume = 0;
	$Weight = 0;

}
/*end for loop to print the whole lot twice */

if ($ListCount == 0) {
	$Title = _('Print Packing Slip Error');
	include ('includes/header.php');
	echo '<p>' . _('There were no outstanding items on the order to deliver') . '. ' . _('A packing slip cannot be printed') . '<br /><a href="' . $RootPath . '/SelectSalesOrder.php">' . _('Print Another Packing Slip/Order') . '</a>
			<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
	include ('includes/footer.php');
	exit;
} else {
	$PDF->OutputD($_SESSION['DatabaseName'] . '_PackingSlip_' . $_GET['TransNo'] . '_' . date('Y-m-d') . '.pdf');
	$PDF->__destruct();
	$SQL = "UPDATE salesorders SET printedpackingslip=1,
									datepackingslipprinted=CURRENT_DATE
				WHERE salesorders.orderno='" . $_GET['TransNo'] . "'";
	$Result = DB_query($SQL);
}

?>