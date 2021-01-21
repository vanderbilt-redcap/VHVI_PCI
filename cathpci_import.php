<?php
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$module->llog("\$_GET:\n" . print_r($_GET, true));
$module->llog("\$_POST:\n" . print_r($_POST, true));
$module->llog("\$_FILES:\n" . print_r($_FILES, true));

if (!isset($_POST['submit'])) { ?>
<div id="container">
	<form method="post" enctype="multipart/form-data">
		<span>Select a CathPCI workbook to upload:<span>
		<br>
		<input class="mb-1" type="file" name="cathpci_doc" id="cathpci_doc">
		<br>
		<input type="submit" value="Upload" name="submit">
	</form>
</div>
<?php
} else {
	// check for upload errors
	$upload_errors = $module->getFileUploadErrors('cathpci_doc');
	
	if (!empty($upload_errors)) {
		echo "<span>CathPCI workbook failed to upload to REDCap. See errors:</span><br>";
		exit(implode("<br>", $upload_errors));
	} else {
		echo "<span>CathPCI workbook uploaded successfully.</span><br>";
	}
	
	// attempt to load CathPCI workbook
	$err = $module->loadCathPCIWorkbook($_FILES['cathpci_doc']['tmp_name']);
	if (!empty($err)) {
		exit("<span>The file uploaded successfully but the {$module->module_name} was not able to extract patient data from the file.</span><br><span>Error message: $err</span>");
	}
	
	
	require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
}
?>