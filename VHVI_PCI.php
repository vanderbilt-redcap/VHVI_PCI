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
	
	function __construct() {
		parent::__construct();
		
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
		// build column-field map
		$column_name_row_data = $this->sheet->rangeToArray('A4:MF4')[0];
		$this->buildColumnFieldMap($column_name_row_data);
		
		$lastRow = $this->sheet->getHighestRow();
		if ($lastRow < 5) {
			return "CathPCI workbook import failure: Expected workbook to have at least 5 rows on first sheet";
		}
		if ($lastRow == 5) {
			return "CathPCI workbook import complete: Module detected no patient records";
		}
		
		$returnStatus = "Beginning CathPCI workbook import process.\n";
		$returnStatus .= "Detected " . ($lastRow - 5) . " rows of patient data.\n";
		
		// for ($row_i = 6; $row_i <= $lastRow; $row_i++) {
		for ($row_i = 6; $row_i <= 10; $row_i++) {
			$returnStatus .= "Processing row $row_i:\n";
			$row_data = $this->sheet->rangeToArray('A' . $row_i . ':AK' . $row_i)[0];
			
			// fieldify row data
			$pati_data = $this->rowToPatientData($row_data);
			
			// $returnStatus .= "\t" . print_r($pati_data, true);
		}
		
		return $returnStatus;
	}
	
	function buildColumnFieldMap($header_row_data) {
		if (empty($this->vhvi_field_names)) {
			throw new \Exception("VHVI PCI module can't build column-field map for workbook until project field names are cached.");
		}
		
		$this->field_map = [];
		$this->fields_mapped = 0;
		foreach ($header_row_data as $col_index => $field_name) {
			if (empty($field_name)) {
				break;
			}
			
			// convert column name to what the REDCap variable name would be
			$rc_field_name = $this->convertVariableName($field_name);
			
			if (in_array($rc_field_name, $this->vhvi_field_names, true)) {
				$this->field_map[$col_index] = $rc_field_name;
				$this->fields_mapped++;
			} else {
				// the module is designed to handle field_map being a sparse array
			}
		}
	}
	
	function rowToPatientData(&$row_data) {
		$pati_data = new \stdClass();
		$pati_data->fields_not_matched = [];
		foreach ($this->field_column_map as $field_name => &$cols_arr) {
			if (count($cols_arr) > 1) {
				foreach ($cols_arr as $col_index) {
					$pati_data->$field_name[] = $row_data[$col_index];
				}
			} else {
				$pati_data->$field_name = $row_data[$cols_arr[0]];
			}
		}
		return $pati_data;
	}
	
	function uploadedPatientData($pati_data) {
		
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