<?php
/* $Revision: 1.0 $ */

$PageSecurity = 10;
include ('includes/session.php');
$Title = _('View Payroll Deductions');

include ('includes/header.php');

if (isset($_GET['Counter'])) {
	$Counter = $_GET['Counter'];
} elseif (isset($_POST['Counter'])) {
	$Counter = $_POST['Counter'];
} else {
	unset($Counter);
}

if (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	$CancelDelete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'SuppTrans' , PurchOrders, SupplierContacts
	if ($CancelDelete == 0) {
		$SQL = "DELETE FROM prlloandeduction WHERE counterindex='$Counter'";
		$Result = DB_query($SQL);
		prnMsg(_('Deduction record for') . ' ' . $Counter . ' ' . _('has been deleted'), 'success');
		unset($Counter);
		unset($_SESSION['Counter']);
	} //end if Delete paypayperiod
	
}

if (!isset($Counter)) {
	$SQL = "SELECT  	prlloandeduction.counterindex,
						prlloandeduction.payrollid,
						prlloandeduction.employeeid,
						prlloandeduction.loantableid,
						prlloandeduction.amount,
						prlloantable.loantableid,
						prlloantable.loantabledesc,
						prlemployeemaster.employeeid,
						prlemployeemaster.lastname,
						prlemployeemaster.firstname

		FROM prlloandeduction,prlloantable,prlemployeemaster
		WHERE prlloandeduction.loantableid = prlloantable.loantableid
		AND prlloandeduction.employeeid = prlemployeemaster.employeeid
		ORDER BY counterindex";
	$ErrMsg = _('The ot could not be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);

	echo '<table border=1>';
	echo "<tr>
		<th>" . _('Index') . "</th>
		<th>" . _('Pay ID ') . "</th>
		<th>" . _('Employee Name') . "</th>
		<th>" . _('Loan Type') . "</th>
		<th>" . _('Amount') . "</th>
	</tr>";
	$k = 0;
	while ($MyRow = DB_fetch_array($Result)) {

		//alternateTableRowColor($k);
		if ($k == 1) {
			echo "<TR BGCOLOR='#CCCCCC'>";
			$k = 0;
		} else {
			echo "<TR BGCOLOR='#EEEEEE'>";
			$k++;
		}
		echo "<td>" . $MyRow["counterindex"] . "</td>
				<td>" . $MyRow["payrollid"] . "</td>
				<td>" . $MyRow["employeeid"] . " - " . $MyRow["lastname"] . ", " . $MyRow["firstname"] . "</td>
				<td>" . $MyRow["loantableid"] . " - " . $MyRow["loantabledesc"] . "</td>
				<td>" . $MyRow["amount"] . "</td></tr>";
	} //END WHILE LIST LOOP
	
}
echo '</table>';
//end of ifs and buts!
include ('includes/footer.php');
?>