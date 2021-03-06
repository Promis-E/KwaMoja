<?php
include ('includes/session.php');

$Title = _('Stock Check Sheets Entry');

include ('includes/header.php');

echo '<form name="EnterCountsForm" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post" enctype="multipart/form-data">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/inventory.png" title="', _('Inventory Adjustment'), '" alt="" />', ' ', $Title, '
	</p>';

if (!isset($_POST['Action']) and !isset($_GET['Action'])) {
	$_GET['Action'] = 'Enter';
}
if (isset($_POST['Action'])) {
	$_GET['Action'] = $_POST['Action'];
}

if ($_GET['Action'] != 'View' and $_GET['Action'] != 'Enter') {
	$_GET['Action'] = 'Enter';
}

if ($_GET['Action'] == 'View') {
	echo '<a href="', $RootPath, '/StockCounts.php?&amp;Action=Enter">', _('Resuming Entering Counts'), '</a>', _('Viewing Entered Counts'), '<br />';
} else {
	echo '<td>', _('Entering Counts'), '<a href="', $RootPath, '/StockCounts.php?&amp;Action=View">', _('View Entered Counts'), '</a>';
}

$FieldHeadings = array('StockCode', //  0 'STOCKCODE',
'QtyCounted', //  1 'QTYCOUNTED',
'Reference'
//  2 'REFERENCE'
);

if (isset($_GET['gettemplate'])) {

	// clean up any previous outputs
	ob_clean();

	header('Content-Type: application/force-download');
	header('Content-Type: application/octet-stream');
	header('Content-Type: application/download');

	// disposition / encoding on response body
	header('Content-Disposition: attachment; filename=ImportTemplate.csv');
	header('Content-Transfer-Encoding: binary');

	echo '"' . implode('","', $FieldHeadings) . '"';

	// exit cleanly to prevent any unwanted outputs
	exit;
}

if ($_GET['Action'] == 'Enter') {

	if (isset($_POST['EnterCounts'])) {

		$Added = 0;
		// Arbitrary number of 10 hard coded as default as originally used - should there be a setting?
		if (isset($_POST['RowCount'])) {
			$Counter = $_POST['RowCount'];
		} else {
			$Counter = 10;
		}
		for ($i = 1;$i <= $Counter;$i++) {
			$InputError = False; //always assume the best to start with
			$Quantity = 'Qty_' . $i;
			$BarCode = 'BarCode_' . $i;
			$StockId = 'StockID_' . $i;
			$Reference = 'Ref_' . $i;
			$Container = 'Container_' . $i;

			if (isset($_POST[$BarCode]) and strlen($_POST[$BarCode]) > 0) {
				$SQL = "SELECT stockmaster.stockid
								FROM stockmaster
								WHERE stockmaster.barcode='" . $_POST[$BarCode] . "'";

				$ErrMsg = _('Could not determine if the part being ordered was a kitset or not because');
				$DbgMsg = _('The sql that was used to determine if the part being ordered was a kitset or not was ');
				$KitResult = DB_query($SQL, $ErrMsg, $DbgMsg);
				$MyRow = DB_fetch_array($KitResult);

				$_POST[$StockId] = strtoupper($MyRow['stockid']);
			}

			if (isset($_POST[$StockId]) and mb_strlen($_POST[$StockId]) > 0) {
				if (!is_numeric($_POST[$Quantity])) {
					prnMsg(_('The quantity entered for line') . ' ' . $i . ' ' . _('is not numeric') . ' - ' . _('this line was for the part code') . ' ' . $_POST[$StockId] . '. ' . _('This line will have to be re-entered'), 'warn');
					$InputError = True;
				}
				$SQL = "SELECT stockid FROM stockcheckfreeze WHERE stockid='" . $_POST[$StockId] . "'";
				$Result = DB_query($SQL);
				if (DB_num_rows($Result) == 0) {
					prnMsg(_('The stock code entered on line') . ' ' . $i . ' ' . _('is not a part code that has been added to the stock check file') . ' - ' . _('the code entered was') . ' ' . $_POST[$StockId] . '. ' . _('This line will have to be re-entered'), 'warn');
					$InputError = True;
				}

				if (!isset($_POST[$Container]) or $_POST[$Container] == '') {
					$_POST[$Container] = $_POST['Location'];
				}

				if ($InputError == False) {
					$Added++;
					$SQL = "INSERT INTO stockcounts (stockid,
									loccode,
									container,
									qtycounted,
									reference)
								VALUES ('" . $_POST[$StockId] . "',
									'" . $_POST['Location'] . "',
									'" . $_POST[$Container] . "',
									'" . $_POST[$Quantity] . "',
									'" . $_POST[$Reference] . "')";

					$ErrMsg = _('The stock count line number') . ' ' . $i . ' ' . _('could not be entered because');
					$EnterResult = DB_query($SQL, $ErrMsg);
				}
			}
		} // end of loop
		prnMsg($Added . _(' Stock Counts Entered'), 'success');
		unset($_POST['EnterCounts']);
		unset($_POST['Location']);
	} else if (isset($_FILES['userfile']) and $_FILES['userfile']['name']) {
		//initialize
		$FieldTarget = count($FieldHeadings);
		$InputError = 0;

		//check file info
		$FileName = $_FILES['userfile']['name'];
		$TempName = $_FILES['userfile']['tmp_name'];
		$FileSize = $_FILES['userfile']['size'];

		//get file handle
		$FileHandle = fopen($TempName, 'r');

		//get the header row
		$headRow = fgetcsv($FileHandle, 10000, ",", '"'); // Modified to handle " "" " enclosed csv - useful if you need to include commas in your text descriptions
		//check for correct number of fields
		if (count($headRow) != count($FieldHeadings)) {
			prnMsg(_('File contains ' . count($headRow) . ' columns, expected ' . count($FieldHeadings) . '. Try downloading a new template.'), 'error');
			fclose($FileHandle);
			include ('includes/footer.php');
			exit;
		}

		//test header row field name and sequence
		$head = 0;
		foreach ($headRow as $headField) {
			if (mb_strtoupper($headField) != mb_strtoupper($FieldHeadings[$head])) {
				prnMsg(_('File contains incorrect headers ' . mb_strtoupper($headField) . ' != ' . mb_strtoupper($FieldHeadings[$head]) . '. Try downloading a new template.'), 'error'); //Fixed $FieldHeadings from $headings
				fclose($FileHandle);
				include ('includes/footer.php');
				exit;
			}
			$head++;
		}

		//start database transaction
		DB_Txn_Begin();

		//loop through file rows
		$row = 1;
		while (($MyRow = fgetcsv($FileHandle, 10000, ",")) !== false) {

			//check for correct number of fields
			$fieldCount = count($MyRow);
			if ($fieldCount != $FieldTarget) {
				prnMsg(_($FieldTarget . ' fields required, ' . $fieldCount . ' fields received'), 'error');
				fclose($FileHandle);
				include ('includes/footer.php');
				exit;
			}

			// cleanup the data (csv files often import with empty strings and such)
			$StockID = mb_strtoupper($MyRow[0]);
			foreach ($MyRow as & $value) {
				$value = trim($value);
			}

			//first off check if the item is in freeze
			$SQL = "SELECT stockid FROM stockcheckfreeze WHERE stockid='" . $StockID . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) == 0) {
				$InputError = 1;
				prnMsg(_('Stock item "' . $StockID . '" is not a part code that has been added to the stock check file'), 'warn');
			}

			//next validate inputs are sensible
			if (mb_strlen($MyRow[2]) > 20) {
				$InputError = 1;
				prnMsg(_('The reference field must be 20 characters or less long'), 'error');
			} else if (!is_numeric($MyRow[1])) {
				$InputError = 1;
				prnMsg(_('The quantity counted must be numeric'), 'error');
			} else if ($MyRow[1] < 0) {
				$InputError = 1;
				prnMsg(_('The quantity counted must be zero or a positive number'), 'error');
			}

			if ($InputError != 1) {

				//attempt to insert the stock item
				$SQL = "INSERT INTO stockcounts (stockid,
									loccode,
									qtycounted,
									reference)
								VALUES ('" . $MyRow[0] . "',
									'" . $_POST['Location'] . "',
									'" . $MyRow[1] . "',
									'" . $MyRow[2] . "')";

				$ErrMsg = _('The stock count line number') . ' ' . $i . ' ' . _('could not be entered because');
				$DbgMsg = _('The SQL that was used to add the item failed was');
				$EnterResult = DB_query($SQL, $ErrMsg, $DbgMsg, true);

				if (DB_error_no() != 0) {
					$InputError = 1;
					prnMsg(_($EnterResult), 'error');
				}
			}

			if ($InputError == 1) { //this row failed so exit loop
				break;
			}
			$row++;
		}

		if ($InputError == 1) { //exited loop with errors so rollback
			prnMsg(_('Failed on row ' . $row . '. Batch import has been rolled back.'), 'error');
			DB_Txn_Rollback();
		} else { //all good so commit data transaction
			DB_Txn_Commit();
			prnMsg(_('Batch Import of') . ' ' . $FileName . ' ' . _('has been completed. All transactions committed to the database.'), 'success');
		}

		fclose($FileHandle);
	} // end of if import file button hit
	$CatsResult = DB_query("SELECT DISTINCT stockcategory.categoryid,
								categorydescription
							FROM stockcategory INNER JOIN stockmaster
								ON stockcategory.categoryid=stockmaster.categoryid
							INNER JOIN stockcheckfreeze
								ON stockmaster.stockid=stockcheckfreeze.stockid");

	if (DB_num_rows($CatsResult) == 0) {
		prnMsg(_('The stock check sheets must be run first to create the stock check. Only once these are created can the stock counts be entered. Currently there is no stock check to enter counts for'), 'error');
		echo '<div class="center">
				<a href="', $RootPath, '/StockCheck.php">', _('Create New Stock Check'), '</a>
			</div>';
	} else {
		echo '<fieldset>
				<legend>', _('Stock sheet criteria'), '</legend>
				<field>
					<th colspan="3">', _('Stock Check Counts at Location'), ':<select name="Location">';
		$SQL = "SELECT DISTINCT locationname,
						locations.loccode
						FROM locations
						INNER JOIN stockcheckfreeze
							ON locations.loccode=stockcheckfreeze.loccode
						INNER JOIN locationusers
							ON locationusers.loccode=locations.loccode
							AND locationusers.userid='" . $_SESSION['UserID'] . "'
							AND locationusers.canupd=1";
		$Result = DB_query($SQL);
		while ($MyRow = DB_fetch_array($Result)) {

			if (isset($_POST['Location']) and $MyRow['loccode'] == $_POST['Location']) {
				echo '<option selected="selected" value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
			} else {
				echo '<option value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
			}
		}
		echo '</select>&nbsp;<input type="submit" name="EnterByCat" value="', _('Enter By Category'), '" /><select name="StkCat" onChange="ReloadForm(EnterCountsForm.EnterByCat)" >';

		echo '<option value="">', _('Not Yet Selected'), '</option>';

		while ($MyRow = DB_fetch_array($CatsResult)) {
			if (isset($_POST['StkCat']) and $_POST['StkCat'] == $MyRow['categoryid']) {
				echo '<option selected="selected" value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
			} else {
				echo '<option value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
			}
		}
		echo '</select>
			</field>';

		echo '<h1>OR</h1>';

		echo '<field>
				<th colspan="3">
					<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
					' . _('Upload file') . ': <input name="userfile" type="file" />
					<input type="submit" value="' . _('Send File') . '" />
				</th>
				<td><a href="StockCounts.php?gettemplate=1">Get Import Template</a></td>
			</field>';

		echo '</fieldset>';

		$RowCount = 0;

		if (isset($_POST['Location']) and $_POST['Location'] != '') {

			$SQL = "SELECT id FROM container WHERE parentid='" . $_POST['Location'] . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) > 0) {
				$WarehouseDefined = true;
			} else {
				$WarehouseDefined = false;
			}

			echo '<table>';
			if (isset($_POST['EnterByCat'])) {

				$StkCatResult = DB_query("SELECT categorydescription FROM stockcategory WHERE categoryid='" . $_POST['StkCat'] . "'");
				$StkCatRow = DB_fetch_row($StkCatResult);

				echo '<tr>
						<th colspan="5">', _('Entering Counts For Stock Category'), ': ', $StkCatRow[0], '</th>
					</tr>
					<tr>
						<th>', _('Stock Code'), '</th>
						<th>', _('Description'), '</th>
						<th>', _('Quantity'), '</th>';
				if ($WarehouseDefined) {
					echo '<th>', _('Container'), '</th>';
				}
				echo '<th>', _('Reference'), '</th>
					</tr>';
				$StkItemsResult = DB_query("SELECT stockcheckfreeze.stockid,
													description
											FROM stockcheckfreeze INNER JOIN stockmaster
											ON stockcheckfreeze.stockid=stockmaster.stockid
											WHERE categoryid='" . $_POST['StkCat'] . "'
											ORDER BY stockcheckfreeze.stockid");

				$RowCount = 1;
				while ($StkRow = DB_fetch_array($StkItemsResult)) {
					echo '<tr class="striped_row">
							<td><input type="hidden" name="StockID_', $RowCount, '" value="', $StkRow['stockid'], '" />', $StkRow['stockid'], '</td>
							<td>', $StkRow['description'], '</td>
							<td><input type="text" class="number" name="Qty_', $RowCount, '" maxlength="10" size="10" /></td>';
					if ($WarehouseDefined) {
						$ContainerSQL = "SELECT id, name FROM container WHERE location='" . $_POST['Location'] . "' AND putaway=1";
						$ContainerResult = DB_query($ContainerSQL);
						echo '<td>
								<select name="Container_', $RowCount, '">';
						while ($MyContainerRow = DB_fetch_array($ContainerResult)) {
							if (isset($_POST['Container_' . $RowCount]) and $_POST['Container_' . $RowCount] == $MyContainerRow['id']) {
								echo '<option selected="selected" value="', $MyContainerRow['id'], '">', $MyContainerRow['name'], '</option>';
							} else {
								echo '<option value="', $MyContainerRow['id'], '">', $MyContainerRow['name'], '</option>';
							}
						}
						echo '</select>
							</td>';
					}
					echo '<td><input type="text" name="Ref_', $RowCount, '" maxlength="20" size="20" /></td>
						</tr>';
					$RowCount++;
				}

			} else {

				echo '<tr>
						<th>', _('Bar Code'), '</th>
						<th>', _('Stock Code'), '</th>
						<th>', _('Quantity'), '</th>';
				if ($WarehouseDefined) {
					echo '<th>', _('Container'), '</th>';
				}
				echo '<th>', _('Reference'), '</th>
					</tr>';

				for ($RowCount = 1;$RowCount <= 10;$RowCount++) {

					echo '<tr class="striped_row">
							<td><input type="text" name="BarCode_', $RowCount, '" maxlength="20" size="20" /></td>
							<td><input type="text" name="StockID_', $RowCount, '" maxlength="20" size="20" /></td>
							<td><input type="text" class="number" name="Qty_', $RowCount, '" maxlength="10" size="10" /></td>';
					if ($WarehouseDefined) {
						$ContainerSQL = "SELECT id, name FROM container WHERE location='" . $_POST['Location'] . "' AND putaway=1";
						$ContainerResult = DB_query($ContainerSQL);
						echo '<td>
								<select name="Container_', $RowCount, '">';
						while ($MyContainerRow = DB_fetch_array($ContainerResult)) {
							if (isset($_POST['Container_' . $RowCount]) and $_POST['Container_' . $RowCount] == $MyContainerRow['id']) {
								echo '<option selected="selected" value="', $MyContainerRow['id'], '">', $MyContainerRow['name'], '</option>';
							} else {
								echo '<option value="', $MyContainerRow['id'], '">', $MyContainerRow['name'], '</option>';
							}
						}
						echo '</select>
							</td>';
					}
					echo '<td><input type="text" name="Ref_', $RowCount, '" maxlength="20" size="20" /></td>
						</tr>';

				}
			}

			echo '</table>';
		}

		echo '<div class="centre">
				<input type="submit" name="EnterCounts" value="', _('Enter Above Counts'), '" />
				<input type="hidden" name="RowCount" value="', $RowCount, '" />
			</div>';
	} // there is a stock check to enter counts for
	//END OF action=ENTER
	
} elseif ($_GET['Action'] == 'View') {

	if (isset($_POST['DEL']) and is_array($_POST['DEL'])) {
		foreach ($_POST['DEL'] as $id => $val) {
			if ($val == 'on') {
				$SQL = "DELETE FROM stockcounts WHERE id='" . $id . "'";
				$ErrMsg = _('Failed to delete StockCount ID #') . ' ' . $i;
				$EnterResult = DB_query($SQL, $ErrMsg);
				prnMsg(_('Deleted Id #') . ' ' . $id, 'success');
			}
		}
	}

	//START OF action=VIEW
	$SQL = "SELECT stockcounts.*,
					canupd
				FROM stockcounts
				INNER JOIN locationusers
					ON locationusers.loccode=stockcounts.loccode
					AND locationusers.userid='" . $_SESSION['UserID'] . "'
					AND locationusers.canview=1";
	$Result = DB_query($SQL);

	echo '<input type="hidden" name="Action" value="View" />';
	echo '<table cellpadding="2">
			<tr>
				<th>', _('Stock Code'), '</th>
				<th>', _('Location'), '</th>
				<th>', _('Container'), '</th>
				<th>', _('Qty Counted'), '</th>
				<th>', _('Reference'), '</th>
				<th>', _('Delete?'), '</th>
			</tr>';
	while ($MyRow = DB_fetch_array($Result)) {
		$SQL = "SELECT locationname FROM locations WHERE loccode='" . $MyRow['loccode'] . "'";
		$LocationResult = DB_query($SQL);
		$LocationRow = DB_fetch_array($LocationResult);

		$SQL = "SELECT name FROM container WHERE id='" . $MyRow['container'] . "'";
		$ContainerResult = DB_query($SQL);
		$ContainerRow = DB_fetch_array($ContainerResult);
		echo '<tr class="striped_row">
				<td>', $MyRow['stockid'], '</td>
				<td>', $MyRow['loccode'], ' - ', $LocationRow['locationname'], '</td>
				<td>', $MyRow['container'], ' - ', $ContainerRow['name'], '</td>
				<td class="number">', $MyRow['qtycounted'], '</td>
				<td>', $MyRow['reference'], '</td>
				<td>';
		if ($MyRow['canupd'] == 1) {
			echo '<input type="checkbox" name="DEL[', $MyRow['id'], ']" maxlength="20" size="20" />';
		}
		echo '</td>
			</tr>';

	}
	echo '</table>
			<div class="centre">
				<input type="submit" name="SubmitChanges" value="', _('Save Changes'), '" />
			</div>';

	//END OF action=VIEW
	
}

echo '</form>';
include ('includes/footer.php');

?>