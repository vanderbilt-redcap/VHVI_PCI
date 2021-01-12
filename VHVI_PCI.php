<?php
namespace Vanderbilt\VHVI_PCI;
class VHVI_PCI extends \ExternalModules\AbstractExternalModule {
	function llog($text) {
		// only works on my local
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