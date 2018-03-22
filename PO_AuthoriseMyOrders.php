<?php
include ('includes/session.php');

$Title = _('Authorise Purchase Orders');

include ('includes/header.php');

echo '<p class="page_title_text" ><img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/transactions.png" title="' . $Title . '" alt="" />' . ' ' . $Title . '</p>';

$EmailSQL = "SELECT email FROM www_users WHERE userid='" . $_SESSION['UserID'] . "'";
$EmailResult = DB_query($EmailSQL);
$EmailRow = DB_fetch_array($EmailResult);

if (isset($_POST['UpdateAll'])) {
	foreach ($_POST as $Key => $Value) {
		if (mb_substr($Key, 0, 6) == 'Status') {
			$OrderNo = mb_substr($Key, 6);
			$Status = $_POST['Status' . $OrderNo];
			$Comment = date($_SESSION['DefaultDateFormat']) . ' - ' . _('Authorised by') . ' <a href="mailto:' . $EmailRow['email'] . '">' . $_SESSION['UserID'] . '</a><br />' . html_entity_decode($_POST['comment'], ENT_QUOTES, 'UTF-8');
			$SQL = "UPDATE purchorders
					SET status='" . $Status . "',
						stat_comment='" . $Comment . "',
						authoriser='" . $_SESSION['UserID'] . "',
						allowprint=1
					WHERE orderno='" . $OrderNo . "'";
			$Result = DB_query($SQL);
		}
	}
}

/* Retrieve the purchase order header information
*/
$SQL = "SELECT purchorders.*,
			suppliers.suppname,
			suppliers.currcode,
			www_users.realname,
			www_users.email,
			currencies.decimalplaces AS currdecimalplaces
		FROM purchorders INNER JOIN suppliers
			ON suppliers.supplierid=purchorders.supplierno
		INNER JOIN currencies
			ON suppliers.currcode=currencies.currabrev
		INNER JOIN www_users
			ON www_users.userid=purchorders.initiator
	WHERE status='Pending'";
$Result = DB_query($SQL);

echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table>
		<thead>
			<tr>
				<th class="SortedColumn">' . _('Order Number') . '</th>
				<th class="SortedColumn">' . _('Supplier') . '</th>
				<th class="SortedColumn">' . _('Date Ordered') . '</th>
				<th class="SortedColumn">' . _('Initiator') . '</th>
				<th>' . _('Delivery Date') . '</th>
				<th>' . _('Status') . '</th>
			</tr>
		</thead>';

echo '<tbody>';
while ($MyRow = DB_fetch_array($Result)) {

	$AuthSQL = "SELECT authlevel FROM purchorderauth
				WHERE userid='" . $_SESSION['UserID'] . "'
				AND currabrev='" . $MyRow['currcode'] . "'";

	$AuthResult = DB_query($AuthSQL);
	$myauthrow = DB_fetch_array($AuthResult);
	$AuthLevel = $myauthrow['authlevel'];

	$OrderValueSQL = "SELECT sum(unitprice*quantityord) as ordervalue
				   	FROM purchorderdetails
					WHERE orderno='" . $MyRow['orderno'] . "'";

	$OrderValueResult = DB_query($OrderValueSQL);
	$MyOrderValueRow = DB_fetch_array($OrderValueResult);
	$OrderValue = $MyOrderValueRow['ordervalue'];

	if ($AuthLevel >= $OrderValue) {
		echo '<tr>
				<td>' . $MyRow['orderno'] . '</td>
				<td>' . $MyRow['suppname'] . '</td>
				<td>' . ConvertSQLDate($MyRow['orddate']) . '</td>
				<td><a href="mailto:' . $MyRow['email'] . '">' . $MyRow['realname'] . '</td>
				<td>' . ConvertSQLDate($MyRow['deliverydate']) . '</td>
				<td>
					<select required="required" name="Status' . $MyRow['orderno'] . '">
						<option selected="selected" value="Pending">' . _('Pending') . '</option>
						<option value="Authorised">' . _('Authorised') . '</option>
						<option value="Rejected">' . _('Rejected') . '</option>
						<option value="Cancelled">' . _('Cancelled') . '</option>
					</select>
				</td>
			</tr>';
		echo '<input type="hidden" name="comment" value="' . htmlspecialchars($MyRow['stat_comment'], ENT_QUOTES, 'UTF-8') . '" />';
		$LineSQL = "SELECT purchorderdetails.*,
					stockmaster.description,
					stockmaster.decimalplaces
				FROM purchorderdetails
				LEFT JOIN stockmaster
				ON stockmaster.stockid=purchorderdetails.itemcode
			WHERE orderno='" . $MyRow['orderno'] . "'";
		$LineResult = DB_query($LineSQL);

		echo '<tr>
				<td></td>
				<td colspan="5" align="left">
					<table align="left">
						<thead>
							<tr>
								<th class="SortedColumn">' . _('Product') . '</th>
								<th>' . _('Quantity Ordered') . '</th>
								<th>' . _('Currency') . '</th>
								<th>' . _('Price') . '</th>
								<th>' . _('Line Total') . '</th>
							</tr>
						</thead>';

		echo '<tbody>';
		while ($LineRow = DB_fetch_array($LineResult)) {
			if ($LineRow['decimalplaces'] != NULL) {
				$DecimalPlaces = $LineRow['decimalplaces'];
			} else {
				$DecimalPlaces = 2;
			}
			echo '<tr>
					<td>' . $LineRow['description'] . '</td>
					<td class="number">' . locale_number_format($LineRow['quantityord'], $DecimalPlaces) . '</td>
					<td>' . $MyRow['currcode'] . '</td>
					<td class="number">' . locale_number_format($LineRow['unitprice'], $MyRow['currdecimalplaces']) . '</td>
					<td class="number">' . locale_number_format($LineRow['unitprice'] * $LineRow['quantityord'], $MyRow['currdecimalplaces']) . '</td>
				</tr>';
		} // end while order line detail
		echo '</tbody>
			</table>
		</td>
	</tr>';
	}
} //end while header loop
echo '</tbody>';
echo '</table>';
echo '<div class="centre">
		<input type="submit" name="UpdateAll" value="' . _('Update') . '" />
	</div>
	</form>';
include ('includes/footer.php');
?>