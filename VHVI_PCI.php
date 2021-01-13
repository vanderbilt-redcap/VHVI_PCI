<?php
namespace Vanderbilt\VHVI_PCI;

class VHVI_PCI extends \ExternalModules\AbstractExternalModule {
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

require('vendor/autoload.php');
// $module = new VHVI_PCI();

class CathPCI {
	// each field key has an array of column indices that contain field data (or pieces)
	private $field_column_map = [
		'last_name' => [0],
		'last_name' => [1],
		'mrn' => [3],
		'gender' => [4],
		'race' => [5, 6, 7],
		'hypertension' => [8],
		'dyslipidemia' => [9],
		'prior_mi' => [10],
		'prior_pci' => [11],
		'height_cm' => [12],
		'weight_kg' => [13],
		'cabg' => [16],
		'diabetes' => [17],
		'dialysis_current' => [18],
		'hf' => [19],
		'lvef_cath' => [20],
		'access' => [21],
		'sbp' => [22],
		'contrast' => [23],
		'cr_prepx' => [24],
		'hemoglobin_prepx' => [25],
		'cr_discharge' => [34],
		'status_discharge' => [35]
	];
	
	function __construct($workbook_filename) {
		// ensure filename for workbook passed in exists
		if (!file_exists($workbook_filename)) {
			throw new \Exception("Tried to create a CathPCI instance from non-existing file at: '$workbook_filename'");
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
			$this->workbook = $reader->load($workbook_filename);
		} catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
			throw new \Exception("Error loading CathPCI workbook from filename '$workbook_filename': " . $e->getMessage());
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
	function processRows() {
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
		for ($row_i = 6; $row_i <= 6; $row_i++) {
			$returnStatus .= "Processing row $row_i:\n";
			$row_data = $this->sheet->rangeToArray('A' . $row_i . ':AK' . $row_i)[0];
			
			// fieldify row data
			$pati_data = $this->rowToPatientData($row_data);
			
			$returnStatus .= "\t" . print_r($pati_data, true);
		}
		
		return $returnStatus;
	}
	
	function rowToPatientData(&$row_data) {
		$pati_data = new \stdClass();
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
}