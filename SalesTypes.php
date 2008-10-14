<?php
/* $Revision: 1.14 $ */

$PageSecurity = 15;

include('includes/session.inc');
$title = _('Sales Types') . ' / ' . _('Price List Maintenance');
include('includes/header.inc');

if (isset($_POST['SelectedType'])){
	$SelectedType = strtoupper($_POST['SelectedType']);
} elseif (isset($_GET['SelectedType'])){
	$SelectedType = strtoupper($_GET['SelectedType']);
}

if (isset($Errors)) {
	unset($Errors);
}

$Errors = array();

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	$i=1;

	if (strlen($_POST['TypeAbbrev']) > 2) {
		$InputError = 1;
		prnMsg(_('The sales type (price list) code must be two characters or less long'),'error');
		$Errors[$i] = 'SalesType';
		$i++;
	} elseif ($_POST['TypeAbbrev']=='' OR $_POST['TypeAbbrev']==' ' OR $_POST['TypeAbbrev']=='  ') {
		$InputError = 1;
		prnMsg('<BR>' . _('The sales type (price list) code cannot be an empty string or spaces'),'error');
		$Errors[$i] = 'SalesType';
		$i++;
	} elseif (strlen($_POST['Sales_Type']) >20) {
		$InputError = 1;
		echo prnMsg(_('The sales type (price list) description must be twenty characters or less long'),'error');
		$Errors[$i] = 'SalesType';
		$i++;
	} elseif ($_POST['TypeAbbrev']=='AN'){
		$InputError = 1;
		prnMsg (_('The sales type code cannot be AN since this is a system defined abbrevation for any sales type in general ledger interface lookups'),'error');
		$Errors[$i] = 'SalesType';
		$i++;
	}

	if (isset($SelectedType) AND $InputError !=1) {

		$sql = "UPDATE salestypes
			SET sales_type = '" . $_POST['Sales_Type'] . "'
			WHERE typeabbrev = '$SelectedType'";

		$msg = _('The customer/sales/pricelist type') . ' ' . $SelectedType . ' ' .  _('has been updated');
	} elseif ( $InputError !=1 ) {

		// First check the type is not being duplicated

		$checkSql = "SELECT count(*)
			     FROM salestypes
			     WHERE typeabbrev = '" . $_POST['TypeAbbrev'] . "'";

		$checkresult = DB_query($checkSql,$db);
		$checkrow = DB_fetch_row($checkresult);

		if ( $checkrow[0] > 0 ) {
			$InputError = 1;
			prnMsg( _('The customer/sales/pricelist type ') . $_POST['TypeAbbrev'] . _(' already exist.'),'error');
		} else {

			// Add new record on submit

			$sql = "INSERT INTO salestypes
						(typeabbrev,
			 			 sales_type)
				VALUES ('" . str_replace(' ', '', $_POST['TypeAbbrev']) . "',
					'" . $_POST['Sales_Type'] . "')";

			$msg = _('Customer/sales/pricelist type') . ' ' . $_POST["Sales_Type"] .  ' ' . _('has been created');
			$checkSql = "SELECT count(typeabbrev)
			     FROM salestypes";
			$result = DB_query($checkSql, $db);
			$row = DB_fetch_row($result);

		}
	}

	if ( $InputError !=1) {
	//run the SQL from either of the above possibilites
		$result = DB_query($sql,$db);


	// Fetch the default price list.
		$sql = "SELECT confvalue
					FROM config
					WHERE confname='DefaultPriceList'";
		$result = DB_query($sql,$db);
		$PriceListRow = DB_fetch_row($result);
		$DefaultPriceList = $PriceListRow[0];

	// Does it exist
		$checkSql = "SELECT count(*)
			     FROM salestypes
			     WHERE typeabbrev = '" . $DefaultPriceList . "'";
		$checkresult = DB_query($checkSql,$db);
		$checkrow = DB_fetch_row($checkresult);

	// If it doesnt then update config with newly created one.
		if ($checkrow[0] == 0) {
			$sql = "UPDATE config
					SET confvalue='".$_POST['TypeAbbrev']."'
					WHERE confname='DefaultPriceList'";
			$result = DB_query($sql,$db);
			$_SESSION['DefaultPriceList'] = $_POST['TypeAbbrev'];
		}

		prnMsg($msg,'success');

		unset($SelectedType);
		unset($_POST['TypeAbbrev']);
		unset($_POST['Sales_Type']);
	}

} elseif ( isset($_GET['delete']) ) {

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'
	// Prevent delete if saletype exist in customer transactions

	$sql= "SELECT COUNT(*)
	       FROM debtortrans
	       WHERE debtortrans.tpe='$SelectedType'";

	$ErrMsg = _('The number of transactions using this customer/sales/pricelist type could not be retrieved');
	$result = DB_query($sql,$db,$ErrMsg);

	$myrow = DB_fetch_row($result);
	if ($myrow[0]>0) {
		prnMsg(_('Cannot delete this sale type because customer transactions have been created using this sales type') . '<br>' . _('There are') . ' ' . $myrow[0] . ' ' . _('transactions using this sales type code'),'error');

	} else {

		$sql = "SELECT COUNT(*) FROM debtorsmaster WHERE salestype='$SelectedType'";

		$ErrMsg = _('The number of transactions using this Sales Type record could not be retrieved because');
		$result = DB_query($sql,$db,$ErrMsg);
		$myrow = DB_fetch_row($result);
		if ($myrow[0]>0) {
			prnMsg (_('Cannot delete this sale type because customers are currently set up to use this sales type') . '<br>' . _('There are') . ' ' . $myrow[0] . ' ' . _('customers with this sales type code'));
		} else {

			$sql="DELETE FROM salestypes WHERE typeabbrev='$SelectedType'";
			$ErrMsg = _('The Sales Type record could not be deleted because');
			$result = DB_query($sql,$db,$ErrMsg);
			prnMsg(_('Sales type') . ' / ' . _('price list') . ' ' . $SelectedType  . ' ' . _('has been deleted') ,'success');

			$sql ="DELETE FROM prices WHERE prices.typeabbrev='SelectedType'";
			$ErrMsg =  _('The Sales Type prices could not be deleted because');
			$result = DB_query($sql,$db,$ErrMsg);

			prnMsg(' ...  ' . _('and any prices for this sales type / price list were also deleted'),'success');
			unset ($SelectedType);
			unset($_GET['delete']);

		}
	} //end if sales type used in debtor transactions or in customers set up
}

if (!isset($SelectedType)){

/* It could still be the second time the page has been run and a record has been selected for modification - SelectedType will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
then none of the above are true and the list of sales types will be displayed with
links to delete or edit each. These will call the same page again and allow update/input
or deletion of the records*/

	$sql = 'SELECT * FROM salestypes';
	$result = DB_query($sql,$db);

	echo '<CENTER><TABLE BORDER=1>';
	echo "<tr>
		<TH>" . _('Type Code') . "</TH>
		<TH>" . _('Type Name') . "</TH>
	</TR>";

$k=0; //row colour counter

while ($myrow = DB_fetch_row($result)) {
	if ($k==1){
		echo '<TR class="EvenTableRows">';
		$k=0;
	} else {
		echo '<TR class="OddTableRows">';
		$k=1;
	}

	printf("<td>%s</td>
		<td>%s</td>
		<td><a href='%sSelectedType=%s'>" . _('Edit') . "</td>
		<td><a href='%sSelectedType=%s&delete=yes' onclick=\"return confirm('" . _('Are you sure you wish to delete this price list and all the prices it may have set up?') . "');\">" . _('Delete') . "</td>
		</tr>",
		$myrow[0],
		$myrow[1],
		$_SERVER['PHP_SELF'] . '?' . SID, $myrow[0],
		$_SERVER['PHP_SELF'] . '?' . SID, $myrow[0]);
	}
	//END WHILE LIST LOOP
	echo '</table></CENTER>';
}

//end of ifs and buts!
if (isset($SelectedType)) {

	echo '<CENTER><P><A HREF="' . $_SERVER['PHP_SELF'] . '?' . SID . '">' . _('Show All Sales Types Defined') . '</A></CENTER><p>';
}
if (! isset($_GET['delete'])) {

	echo "<FORM METHOD='post' action=" . $_SERVER['PHP_SELF'] . '?' . SID . '>';
	echo '<CENTER><FONT SIZE=4 COLOR=blue><B><U>' . _('Sales Type/Price List Setup') . '</B></U></FONT>';
	echo '<P><TABLE BORDER=1>'; //Main table
	echo '<TD><TABLE>'; // First column


	// The user wish to EDIT an existing type
	if ( isset($SelectedType) AND $SelectedType!='' )
	{

		$sql = "SELECT typeabbrev,
			       sales_type
		        FROM salestypes
		        WHERE typeabbrev='$SelectedType'";

		$result = DB_query($sql, $db);
		$myrow = DB_fetch_array($result);

		$_POST['TypeAbbrev'] = $myrow['typeabbrev'];
		$_POST['Sales_Type']  = $myrow['sales_type'];

		echo "<INPUT TYPE=HIDDEN NAME='SelectedType' VALUE=" . $SelectedType . ">";
		echo "<INPUT TYPE=HIDDEN NAME='TypeAbbrev' VALUE=" . $_POST['TypeAbbrev'] . ">";
		echo "<CENTER><TABLE> <TR><TD>" . _('Type Code') . ":</TD><TD>";

		// We dont allow the user to change an existing type code

		echo $_POST['TypeAbbrev'] . '</TD></TR>';

	} else 	{

		// This is a new type so the user may volunteer a type code

		echo "<CENTER><TABLE><TR><TD>" . _('Type Code') . ":</TD><TD><INPUT TYPE='Text'
				" . (in_array('SalesType',$Errors) ? 'class="inputerror"' : '' ) ." SIZE=3 MAXLENGTH=2 name='TypeAbbrev'></TD></TR>";

	}

	if (!isset($_POST['Sales_Type'])) {
		$_POST['Sales_Type']='';
	}
	echo "<TR><TD>" . _('Sales Type Name') . ":</TD><TD><input type='Text' name='Sales_Type' value='" . $_POST['Sales_Type'] . "'></TD></TR>";

   	echo '</TABLE>'; // close table in first column
   	echo '</TD></TR></TABLE>'; // close main table

	echo '<P><INPUT TYPE=submit NAME=submit VALUE="' . _('Accept') . '"><INPUT TYPE=submit NAME=Cancel VALUE="' . _('Cancel') . '"></CENTER>';

	echo '</FORM>';

} // end if user wish to delete


include('includes/footer.inc');
?>