<?php
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
echo "<br>";
echo "\$_GET: ";
echo "<br>";
print_r($_GET);
echo "<br>";
echo "<br>";
echo "\$_POST: ";
echo "<br>";
print_r($_POST);
echo "<br>";
echo "<br>";
echo "\$_FILES: ";
echo "<br>";
print_r($_FILES);
echo "<br>";

$target_url = "http://localhost/redcap/redcap_v10.6.4/ExternalModules/?prefix=VHVI_PCI_Registry&page=cathpci_upload&pid=73";
?>
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
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
?>