<?php
include ('includes/session.php');
include ('includes/SQL_CommonFunctions.php');
$Title = _('Register a Patient');
include ('includes/header.php');

if (isset($_POST['FileNumber'])) {
	$FileNumber = $_POST['FileNumber'];
} elseif (isset($_GET['FileNumber'])) {
	$FileNumber = $_GET['FileNumber'];
} else {
	unset($FileNumber);
}

echo '<p class="page_title_text">
		<img class="page_title_icon" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/user.png" title="', _('Search'), '" alt="" />', $Title, '
	</p>';

if (isset($_POST['Create'])) {

	$InputError = 0;

	$_POST['Name'] = $_POST['FirstName'] . ' ' . $_POST['LastName'];

	if ($_SESSION['AutoPatientNo'] == 0) {
		$SQL = "SELECT debtorno FROM debtorsmaster WHERE debtorno='" . $_POST['FileNumber'] . "'";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) != 0) {
			$InputError = 1;
			$msg[] = _('That file number has already been used for another patient. Please select another file number.');
		}
	}

	if (($_SESSION['AutoDebtorNo'] === 0) and (mb_strlen($_POST['FileNumber']) == 0)) {
		$InputError = 1;
		$msg[] = _('You must input a file number');
	}

	if (mb_strlen($_POST['Name']) == 0) {
		$InputError = 1;
		$msg[] = _('You must input the name of the patient you are registering');
	}

	if (mb_strlen($_POST['DateOfBirth']) == 0) {
		$InputError = 1;
		$msg[] = _('You must input the date of birth of the patient');
	}

	if (mb_strlen($_POST['SalesType']) == 0) {
		$InputError = 1;
		$msg[] = _('Please select a price list ');
	}

	if (mb_strlen($_POST['Gender']) == 0) {
		$InputError = 1;
		$msg[] = _('Please select the gender of the patient');
	}

	if ($InputError == 1) {
		foreach ($msg as $message) {
			prnMsg($message, 'error');
		}
	} else {

		$SalesAreaSQL = "SELECT areacode FROM areas";
		$SalesAreaResult = DB_query($SalesAreaSQL);
		$SalesAreaRow = DB_fetch_array($SalesAreaResult);

		$SalesManSQL = "SELECT salesmancode FROM salesman";
		$SalesManResult = DB_query($SalesManSQL);
		$SalesManRow = DB_fetch_array($SalesManResult);

		if ($_SESSION['AutoPatientNo'] > 0) {
			/* system assigned, sequential, numeric */
			if ($_SESSION['AutoPatientNo'] == 1) {
				$_POST['FileNumber'] = GetNextTransNo(520);
			}
		}

		$Result = DB_Txn_Begin();

		$SQL = "INSERT INTO care_person (hospital_file_nr,
										date_reg,
										title,
										name_first,
										name_last,
										date_birth,
										blood_group,
										civil_status,
										sex
									) VALUES (
										'" . $_POST['FileNumber'] . "',
										NOW(),
										'" . $_POST['Title'] . "',
										'" . $_POST['FirstName'] . "',
										'" . $_POST['LastName'] . "',
										'" . FormatDateForSQL($_POST['DateOfBirth']) . "',
										'" . $_POST['BloodGroup'] . "',
										'" . $_POST['Marital'] . "',
										'" . $_POST['Gender'] . "'
									)";
		$ErrMsg = _('There was a problem inserting the patient record because');
		$DbgMsg = _('The SQL used to insert the patient record was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		$SQL = "INSERT INTO debtorsmaster (debtorno,
										name,
										address1,
										address2,
										address3,
										address4,
										address5,
										address6,
										currcode,
										salestype,
										paymentterms)
									VALUES (
										'" . $_POST['FileNumber'] . "',
										'" . $_POST['Name'] . "',
										'" . $_POST['Address1'] . "',
										'" . $_POST['Address2'] . "',
										'" . $_POST['Address3'] . "',
										'" . $_POST['Address4'] . "',
										'" . $_POST['Address5'] . "',
										'" . $_POST['Address6'] . "',
										'" . $_SESSION['CompanyRecord']['currencydefault'] . "',
										'" . $_POST['SalesType'] . "',
										'20'
									)";
		$ErrMsg = _('There was a problem inserting the debtors record because');
		$DbgMsg = _('The SQL used to insert the debtors record was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		$SQL = "INSERT INTO custbranch (branchcode,
										debtorno,
										brname,
										area,
										salesman,
										phoneno,
										defaultlocation,
										taxgroupid)
									VALUES (
										'CASH',
										'" . $_POST['FileNumber'] . "',
										'CASH',
										'" . $SalesAreaRow['areacode'] . "',
										'" . $SalesManRow['salesmancode'] . "',
										'" . $_POST['Telephone'] . "',
										'" . $_SESSION['DefaultFactoryLocation'] . "',
										'1'
									)";
		$ErrMsg = _('There was a problem inserting the branch record because');
		$DbgMsg = _('The SQL used to insert the branch record was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		if ($_POST['Insurance'] != '') {
			$SQL = "INSERT INTO custbranch (branchcode,
											debtorno,
											brname,
											area,
											salesman,
											phoneno,
											defaultlocation,
											taxgroupid)
										VALUES (
											'" . $_POST['Insurance'] . "',
											'" . $_POST['FileNumber'] . "',
											'" . $_POST['Insurance'] . "',
											'" . $SalesAreaRow['areacode'] . "',
											'" . $SalesManRow['salesmancode'] . "',
											'" . $_POST['Telephone'] . "',
											'" . $_SESSION['DefaultFactoryLocation'] . "',
											'1'
										)";
			$Result = DB_query($SQL);
			$ErrMsg = _('There was a problem inserting the branch record because');
			$DbgMsg = _('The SQL used to insert the branch record was');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		}
		$Result = DB_Txn_Commit();
		prnMsg(_('The patient') . ' ' . $_POST['FileNumber'] . ' ' . _('has been successfully registered'), 'success');

		echo '<div class="centre">
				<a href="', $RootPath, '/KCMCInpatientAdmission.php?SelectedPatient=', $_POST['FileNumber'], '">', _('Admit as Inpatient'), '</a>
				<a href="', $RootPath, '/KCMCOutpatientAdmission.php?SelectedPatient=', $_POST['FileNumber'], '">', _('Admit as Outpatient'), '</a>
			</div>';

		unset($_POST['FileNumber']);
		unset($_POST['Name']);
		unset($_POST['Address1']);
		unset($_POST['Address2']);
		unset($_POST['Address3']);
		unset($_POST['Address4']);
		unset($_POST['Address5']);
		unset($_POST['Address6']);
		unset($_POST['SalesType']);
		unset($_POST['DateOfBirth']);
		unset($_POST['Gender']);
	}
}

echo '<form action="', $_SERVER['PHP_SELF'], '" method="post">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

if (!isset($FileNumber)) {

	$_POST['Title'] = 1;
	$_POST['FirstName'] = '';
	$_POST['LastName'] = '';
	$_POST['Address1'] = '';
	$_POST['Address2'] = '';
	$_POST['Address3'] = '';
	$_POST['Address4'] = '';
	$_POST['Address5'] = '';
	$_POST['Address6'] = '';
	$_POST['Telephone'] = '';
	$_POST['DateOfBirth'] = Date($_SESSION['DefaultDateFormat']);
	$_POST['SalesType'] = '';
	$_POST['Gender'] = 'm';
	$_POST['Citizenship'] = $_SESSION['CountryOfOperation'];

	echo '<fieldset>
			<legend>', _('Register a new patient'), '</legend>';

	if ($_SESSION['AutoPatientNo'] == 0) {
		echo '<field>
				<label for="FileNumber">', _('File Number'), ':</label>
				<input type="text" size="10" name="FileNumber" value="', $FileNumber, '" />
				<fieldhelp>', _('A unique ID for this patient.'), '</fieldhelp>
			</field>';
	}
}

$Titles = array(1 => _('Mr'), 2 => _('Ms'), 3 => _('Miss'), 4 => _('Mrs'), 5 => _('Dr'));
echo '<field>
		<label for="Title">', _('Title'), ':</label>
		<select autofocus="autofocus" name="Title">';
foreach ($Titles as $Key => $Value) {
	if ($Key == $_POST['Title']) {
		echo '<option selected="selected" value="', $Key, '">', $Value, '</option>';
	} else {
		echo '<option value="', $Key, '">', $Value, '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('The patients title.'), '</fieldhelp>
</field>';

echo '<field>
		<label for="FirstName">', _('First Name'), ':</label>
		<input type="text" size="20" name="FirstName" value="', $_POST['FirstName'], '" />
		<fieldhelp>', _('The patients first name'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="LastName">', _('Last Name'), ':</label>
		<input type="text" size="20" name="LastName" value="', $_POST['LastName'], '" />
		<fieldhelp>', _('The patients last, or family name'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="Address1">', _('Address'), ':</label>
		<input type="text" size="50" name="Address1" value="', $_POST['Address1'], '" />
		<fieldhelp>', _('The first line of the patients address'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="Address2">&nbsp;</label>
		<input type="text" size="50" name="Address2" value="', $_POST['Address2'], '" />
		<fieldhelp>', _('The second line of the patients address'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="Address3">&nbsp;</label>
		<input type="text" size="50" name="Address3" value="', $_POST['Address3'], '" />
		<fieldhelp>', _('The third line of the patients address'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="Address4">&nbsp;</label>
		<input type="text" size="50" name="Address4" value="', $_POST['Address4'], '" />
		<fieldhelp>', _('The fourth line of the patients address'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="Address5">&nbsp;</label>
		<input type="text" size="50" name="Address5" value="', $_POST['Address5'], '" />
		<fieldhelp>', _('The fifth line of the patients address'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="Address6">&nbsp;</label>
		<input type="text" size="20" name="Address6" value="', $_POST['Address6'], '" />
		<fieldhelp>', _('The sixth line of the patients address'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="Telephone">', _('Telephone Number'), ':</label>
		<input type="tel" size="20" name="Telephone" value="', $_POST['Telephone'], '" />
		<fieldhelp>', _('A contact telephone number for this patient'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="DateOfBirth">', _('Date Of Birth'), ':</label>
		<input type="text" placeholder="', $_SESSION['DefaultDateFormat'], '" class="date" name="DateOfBirth" maxlength="10" size="10" value="', $_POST['DateOfBirth'], '" />
	</field>';

$Result = DB_query("SELECT typeabbrev, sales_type FROM salestypes");
if (DB_num_rows($Result) == 0) {
	$DataError = 1;
	echo '<a href="SalesTypes.php?" target="_parent">Setup Types</a>';
	echo '<field>
			<td colspan=2>' . prnMsg(_('No sales types/price lists defined'), 'error') . '</td>
		</field>';
} else {
	echo '<field>
			<label for="SalesType">', _('Price List'), ':</label>
			<select name="SalesType">';
	echo '<option value=""></option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['SalesType']) and $_POST['SalesType'] == $MyRow['typeabbrev']) {
			echo '<option selected="selected" value="', $MyRow['typeabbrev'], '">' . $MyRow['sales_type'], '</option>';
		} else {
			echo '<option value="', $MyRow['typeabbrev'], '">', $MyRow['sales_type'], '</option>';
		}
	} //end while loopre
	echo '</select>
		<fieldhelp>', _('The price list to apply for this patient'), '</fieldhelp>
	</field>';
}

$Gender['m'] = _('Male');
$Gender['f'] = _('Female');
echo '<field>
		<label for="Gender">', _('Gender'), ':</label>
		<select name="Gender">';
echo '<option value=""></option>';
foreach ($Gender as $Code => $Name) {
	if (isset($_POST['Gender']) and $_POST['Gender'] == $Code) {
		echo '<option selected="selected" value="', $Code, '">', $Name, '</option>';
	} else {
		echo '<option value="', $Code, '">', $Name, '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('The gender of this patient'), '</fieldhelp>
</field>';

$MaritalStatus = array('Single' => _('Single'), 'Married' => _('Married'), 'Sep/Div' => _('Separated/Divorced'), 'Widowed' => _('Widowed'), 'Civil' => _('Civil Partnership'));
echo '<field>
		<label for="Marital">', _('Marital Status'), ':</label>
		<select name="Marital">';
foreach ($MaritalStatus as $Key => $Value) {
	if ($Key == $_POST['Marital']) {
		echo '<option selected="selected" value="', $Key, '">', $Value, '</option>';
	} else {
		echo '<option value="', $Key, '">', $Value, '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('The patients marital status.'), '</fieldhelp>
</field>';

include ('includes/CountriesArray.php');
echo '<field>
		<label for="Citizenship">', _('Citizen of'), ':</label>
		<select required="required" name="Citizenship">';
foreach ($CountriesArray as $CountryEntry => $CountryName) {
	if (isset($_POST['Citizenship']) and ($_POST['Citizenship'] == $CountryEntry)) {
		echo '<option selected="selected" value="', $CountryEntry, '">', $CountryName, '</option>';
	} else {
		echo '<option value="', $CountryEntry, '">', $CountryName, '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('The country where this patient is a citizen.'), '</fieldhelp>
</field>';

$BloodGroup = array('A', 'B', 'AB', 'O');
echo '<field>
		<label for="BloodGroup">', _('Blood Group'), ':</label>
		<select name="BloodGroup">';
echo '<option value=""></option>';
foreach ($BloodGroup as $Group) {
	if (isset($_POST['BloodGroup']) and $_POST['BloodGroup'] == $Group) {
		echo '<option selected="selected" value="', $Group, '">', $Group, '</option>';
	} else {
		echo '<option value="', $Group, '">', $Group, '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('The blood group of this patient'), '</fieldhelp>
</field>';

$SQL = "SELECT debtorno,
				name
			FROM debtorsmaster
			WHERE typeid='" . $_SESSION['InsuranceDebtorType'] . "'";
$Result = DB_query($SQL);

if (DB_num_rows($Result) > 0) {
	echo '<field>
			<label for="Insurance">', _('Insurance Company'), ':</label>
			<select name="Insurance">';
	echo '<option value=""></option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['Insurance']) and $_POST['Insurance'] == $MyRow['debtorno']) {
			echo '<option selected="selected" value="', $MyRow['debtorno'], '">', $MyRow['name'], '</option>';
		} else {
			echo '<option value="', $MyRow['debtorno'], '">', $MyRow['name'], '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('The insurance company, if any, that this patient belongs to'), '</fieldhelp>
	</field>';
}

if (isset($_POST['Insurance']) and $_POST['Insurance'] != '') {
	$SQL = "SELECT salesmancode,
					salesmanname,
					smantel,
					smanfax
				FROM salesman";
	$Result = DB_query($SQL);

	echo '<field>
			<td>' . _('Employer Company') . ':</td>
			<td><select name="Employer">';
	echo '<option value=""></option>';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['salesmancode'] . '">' . $MyRow['salesmanname'] . '</option>';
	}
	echo '</select>
				</td>
			</field>';
}

echo '</fieldset>';
echo '<div class="centre">
		<input type="submit" name="Create" value="' . ('Register the patient') . '" />
	</div>';
echo '</form>';

include ('includes/footer.php');
?>