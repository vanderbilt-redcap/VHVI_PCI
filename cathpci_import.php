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
	$import_info = [];
	$import_info['id'] = $module->generateImportID();
	
	echo "<span>Starting import process (Import ID: " . $import_info['id'] . ")</span><br>";
	
	// check for upload errors
	$upload_errors = $module->getFileUploadErrors('cathpci_doc');
	
	if (!empty($upload_errors)) {
		echo "<span>CathPCI workbook failed to upload to REDCap. See errors:</span><br>";
		$import_info['upload_errors'] = json_encode($upload_errors);
		$module->log('import_info', $import_info);
		exit(implode("<br>", $upload_errors));
	} else {
		echo "<span>CathPCI workbook uploaded successfully.</span><br>";
	}
	
	// load CathPCI workbook
	$err = $module->loadCathPCIWorkbook($_FILES['cathpci_doc']['tmp_name']);
	$import_info['wb_path'] = $_FILES['cathpci_doc']['tmp_name'];
	if (!empty($err)) {
		$import_info['wb_load_error'] = $err;
		$module->log('import_info', $import_info);
		exit("<span>The file uploaded successfully but the {$module->module_name} was not able to extract patient data from the file.</span><br><span>Error message: $err</span>");
	}
	
	// build field_map (also column_names and fields_mapped count)
	$err = $module->buildFieldMap();
	if (!empty($err)) {
		$import_info['field_map_build_error'] = $err;
		$module->log('import_info', $import_info);
		exit("<span>The {$module->module_name} loaded the CathPCI workbook but was not able to build the field map.</span><br><span>Error message: $err</span>");
	} else {
		echo "<span>Built column to field map.</span><br>";
	}
	$import_info['field_map'] = json_encode($module->field_map);
	$import_info['column_names'] = json_encode($module->column_names);
	$import_info['fields_mapped'] = $module->fields_mapped;
	
	// allow admins to show/hide field mapping table
	$field_map_html_table = $module->getFieldMapHTMLTable();
	echo "<br><button id='toggle_field_map' onclick='$(\"#field_map\").toggle()'>Toggle Field Map Table</button><br>$field_map_html_table<br>";
	
	// count chunks
	$chunk_count = $module->getChunkCount();
	if (!is_numeric($chunk_count)) {
		$import_info['chunk_count_error'] = $chunk_count;
		$module->log('import_info', $import_info);
		exit("<span>$chunk_count</span>");
	} else {
		echo "<span>Counted $chunk_count chunks (maximum of 100 rows in each chunk) in uploaded CathPCI workbook.</span><br>";
	}
	$import_info['chunk_count'] = $chunk_count;
	$module->log('import_info', $import_info);
	
	echo "<br><h6>Import Results Table</h6>
	<table id='import_results'>
		<thead>
			<tr>
				<th>Row #</th>
				<th>New Record ID</th>
				<th>Import Result</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>";
	
	require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	?>
	<script type='text/javascript'>
		<?= "var CathPCI = {
			import_id: '" . $import_info['id'] . "',
			chunk_count: $chunk_count,
			import_chunk_url: '" . $module->getUrl('cathpci_import_ajax.php') . "',
			import_css_url: '" . $module->getUrl('css/import.css') . "'
		};"; ?>
	</script>
	<script type='text/javascript' src='<?= $module->getUrl('js/import.js'); ?>'></script>
	<script type='text/javascript' src='//cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js'></script>
<?php
}
?>