<?php
namespace Vanderbilt\VHVI_PCI;

require('vendor/autoload.php');
class VHVI_PCI extends \ExternalModules\AbstractExternalModule {
	
	private $longVarSubstrings = [
		'health_insurance_payment_source',
		'lvef_diagnostic_left_heart_cath',
		'percutaneous_coronary_intervention',
		'concomitant_procedures_performed',
		'arterial_access_closure_method',
		'pre_procedure',
		'post_procedure',
		'cardiovascular_instability',
		'mechanical_ventricular_support',
		'solid_organ_transplant_type',
		'intervention_type_this_hospitalization',
	];
	
	private $shortVarSubstrings = [
		'hi_pay_src',
		'lvef_diag_lhcath',
		'pci',
		'conc_pp',
		'arterial_ac_method',
		'prepx',
		'postpx',
		'ci',
		'mech_vent_supp',
		'sot_type',
		'int_type',
	];
	
	private $header_row_index = 4;
	private $last_column_letters = "MF";
	
	function __construct() {
		parent::__construct();
		
		$this->pid = $this->getProjectId();
		
		// cache project field names
		$this->vhvi_field_names = \REDCap::getFieldNames();
	}
	
	// returns nothing, loads workbook property (or throw exception)
	function loadCathPCIWorkbook($workbook_filepath) {
		// ensure filename for workbook passed in exists
		if (!file_exists($workbook_filepath)) {
			throw new \Exception("Tried to create a CathPCI instance from non-existing file at: '$workbook_filepath'");
		}
		
		// create PHPSpreadsheet reader
		$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
		if (get_class($reader) != 'PhpOffice\PhpSpreadsheet\Reader\Xlsx') {
			throw new \Exception("VHVI_PCI module failed to create PHPSpreadsheet reader object");
		}
		
		// set to read data only (ignore formatting etc)
		$reader->setReadDataOnly(true);
		
		// read workbook into mem
		try {
			$this->workbook = $reader->load($workbook_filepath);
		} catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
			throw new \Exception("Error loading CathPCI workbook from filename '$workbook_filepath': " . $e->getMessage());
		}
		
		if (!$this->workbook) {
			throw new \Exception("Failed to create CathPCI workbook object");
		}
		if (get_class($this->workbook) != "PhpOffice\PhpSpreadsheet\Spreadsheet") {
			throw new \Exception("Failed to properly create CathPCI workbook object");
		}
		$this->workbook->setActiveSheetIndex(0);
		
		// set active sheet to first worksheet in workbook
		$this->sheet = $this->workbook->getActiveSheet();
	}
	
	// process rows in first worksheet, creating or updating patient records as needed
	// returns status string
	function processWorkbook() {
		$header_row = $this->header_row_index;
		$last_col = $this->last_column_letters;
		$returnStatus = "<h6>Beginning CathPCI workbook import process.</h6><br>";
		
		// build column-field map
		$column_name_row_data = $this->sheet->rangeToArray("A$header_row:$last_col$header_row")[0];
		$returnStatus .= "<span>Building field map.</span><br><br>";
		$this->buildColumnFieldMap($column_name_row_data);
		
		// print table showing column to field map
		// $returnStatus .= $this->printFieldMapTable();
		
		$lastRow = $this->sheet->getHighestRow();
		if ($lastRow < $header_row) {
			return "<span>CathPCI workbook import failure: Expected workbook to have at least $header_row rows on first sheet</span><br>";
		}
		if ($lastRow == $header_row) {
			return "<span>CathPCI workbook import complete: Module detected no patient records</span><br>";
		}
		
		$returnStatus .= "<span>Detected " . ($lastRow - $header_row) . " rows of patient data.</span><br>";
		
		$record_data = [];
		// for ($row_i = ($header_row + 1); $row_i <= $lastRow; $row_i++) {
		for ($row_i = ($header_row + 1); $row_i <= ($header_row + 1000); $row_i++) {
			$returnStatus .= "Processing row $row_i: ";
			$row_data = $this->sheet->rangeToArray("A$row_i:$last_col$row_i")[0];
			
			// fieldify row data
			$record_data[] = $this->rowToPatientData($row_data);
			
			// save
			// $returnStatus .= $this->savePatientData($record);
		}
		
		$results = \REDCap::saveData($this->pid, 'json', json_encode($record_data));
		
		$returnStatus .= "<br><h6>Finished processing workbook</h6><br>";
		
		return $returnStatus;
	}
	
	// returns status string
	function buildColumnFieldMap($header_row_data) {
		if (empty($this->vhvi_field_names)) {
			throw new \Exception("VHVI PCI module can't build column-field map for workbook until project field names are cached.");
		}
		
		$this->column_names = [];
		$this->field_map = [];
		$this->fields_mapped = 0;
		foreach ($header_row_data as $col_index => $field_name) {
			if (empty($field_name)) {
				break;
			}
			
			$this->column_names[$col_index] = $field_name;
			
			// convert column name to what the REDCap variable name would be
			$rc_field_name = $this->convertVariableName($field_name);
			
			// set field_map slot if applicable
			if (in_array($rc_field_name, $this->vhvi_field_names, true)) {
				$this->field_map[$col_index] = $rc_field_name;
				$this->fields_mapped++;
			} else {
				// the module is designed to handle field_map being a sparse array
			}
		}
		
		if (empty($this->field_map)) {
			throw new \Exception("VHVI PCI was unable to build a map of column names to REDCap project variable names because none of the expected column names were present.");
		}
	}
	
	function printFieldMapTable() {
		if (empty($this->field_map)) {
			throw new \Exception("VHVI PCI module can't print column-field comparison table for workbook until the column-field map is created.");
		}
		$table = "<table>
	<thead>
		<tr>
			<th>Column Label</th>
			<th>REDCap Variable</th>
		</tr>
	</thead>
	<tbody>";
	
	foreach ($this->column_names as $index => $col_label) {
		$rc_variable = $this->field_map[$index];
		$classtxt = (empty($rc_variable)) ? " class='missing-field'" : '';
		
		$table .= "<tr>
			<td$classtxt>$col_label</td>
			<td$classtxt>$rc_variable</td>
		</tr>";
	}
	
	$table .= "	</tbody>
</table><br>";
		
		return $table;
	}
	
	function rowToPatientData(&$row_data) {
		// create record object
		$new_patient_record = new \stdClass();
		
		// use row cell values to fill record object in preparation for saveData call
		$new_patient_record->{$this->getRecordIdField()} = $this->getAvailableRecordId();
		foreach ($this->field_map as $col_index => $rc_var_name) {
			$new_patient_record->$rc_var_name = $row_data[$col_index];
		}
		return $new_patient_record;
	}
	
	// creates new record, saves to db by prepared INSERT statement
	function savePatientData($patient_data) {
		$results = \REDCap::saveData($this->pid, 'json', json_encode([$patient_data]));
		if (!empty($results['errors'])) {
			$errs = implode(' ', $results['errors']);
			return "\tREDCap FAILED to save row data to a new record. Errors: " . print_r($errs, true) . "<br>";
		} else {
			return "\tREDCap saved row data to new record. Record ID: " . $patient_data->{$this->getRecordIdField()} . "<br>";
		}
	}
	
	// take a column name and make it redcap variable name compatible (lowercase letters, numbers, underscores only, <= 100 chars)
	function convertVariableName($cathpci_var_name) {
		// 1 lower case
		$rc_var = strtolower($cathpci_var_name);
		
		// 2 encode <=, > to le, gt
		$rc_var = str_replace("<=", "le", $rc_var);
		$rc_var = str_replace(">", "gt", $rc_var);
		
		// 3 replace non-alphanumeric with underscore
		$rc_var = preg_replace('/[^[:digit:][:lower:]]/', '_', $rc_var);
		$rc_var = preg_replace('/_+/', '_', $rc_var);
		
		// 4 compress specific substrings
		$rc_var = str_replace($this->longVarSubstrings, $this->shortVarSubstrings, $rc_var);
		
		// 5 trim underscores
		$rc_var = trim($rc_var, '_');
		
		// 6 truncate to 26 chars
		$rc_var = substr($rc_var, 0, 26);
		
		return $rc_var;
	}
	
	function getAvailableRecordId() {
		if (empty($this->current_max_record)) {
			$rid_field_name = $this->getRecordIdField();
			$pid = $this->pid;
			$q = db_query("SELECT MAX(CAST(value AS SIGNED)) AS max_record FROM redcap_data WHERE project_id='$pid' AND field_name='$rid_field_name'");
			$result = db_fetch_assoc($q);
			echo "result: " . print_r($result, true) . "<br>";
			$max_record = $result['max_record'];
			if (empty($max_record)) {
				$this->current_max_record = '1';
			} else {
				$this->current_max_record = $max_record + 1;
			}
		} else {
			$this->current_max_record++;
		}
		
		return $this->current_max_record;
	}
	
	function llog($text) {
		// allows only local (not test/prod) logging
		if (!file_exists($this->getModulePath() . 'able_test.php')) {
			return;
		}
		
		// create new file first time this is run, then append for additional calls
		if (isset($this->ran_llog)) {
			file_put_contents("C:/vumc/log.txt", "$text\n", FILE_APPEND);
		} else {
			$this->ran_llog = true;
			file_put_contents("C:/vumc/log.txt", "VHVI_PCI llog created: " . date('c') . "\n$text\n");
		}
	}
}