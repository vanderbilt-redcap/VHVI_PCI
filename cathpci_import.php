<?php
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$module->llog("catpci_import.php");
$module->llog("\$_GET:\n" . print_r($_GET, true));
$module->llog("\$_POST:\n" . print_r($_POST, true));
$module->llog("\$_FILES:\n" . print_r($_FILES, true));

?>

<?php if (!isset($_POST['submit'])) { ?>
<div id="container">
	<form method="post" enctype="multipart/form-data">
		<span>Select a CathPCI workbook to upload:<span>
		<br>
		<input class="mb-1" type="file" name="cathpci_doc" id="cathpci_doc">
		<br>
		<input type="submit" value="Upload" name="submit">
	</form>
</div>
<?php } else { 
	require 'vendor/autoload.php';
	try {
		$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
		$workbook = $reader->load($_FILES["cathpci_doc"]["tmp_name"]);
	} catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
		// \REDCap::logEvent("DPP import failure", "PhpSpreadsheet library errors -> " . print_r($e, true) . "\n", null, $rid, $eid, PROJECT_ID);
		exit("There was an issue loading the CathPCI workbook: $e");
	}
	$workbook_loaded = true;
?>
<span>Imported workbook</span>
<br>
<?php 
	if ($workbook_loaded) {
		echo "<span>Workbook loaded successfully</span>";
	}
} ?>
<?php
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
?>