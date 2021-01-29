if (typeof CathPCI != 'undefined') {
	// make datatables
	CathPCI.field_map_dt = $('#field_map table').DataTable({
		pageLength: 25
	});
	CathPCI.import_results_dt = $('table#import_results').DataTable({
		pageLength: 25
	});
	
	CathPCI.init = function() {
		this.chunks_loaded = 0;
		if (this.chunk_count > 0) {
			this.askServerToImportNextChunk();
		}
	}
	CathPCI.askServerToImportNextChunk = function() {
		var next_chunk_index = this.chunks_loaded + 1;
		console.log('asking server to import chunk #' + next_chunk_index);
		
		this.addLoadingMessage();
		
		var jqxhr = $.ajax({
			method: 'POST',
			url: this.import_chunk_url,
			data: {
				import_id: this.import_id,
				which_chunk: next_chunk_index
			}
		});
		
		jqxhr.done(this.receivedImportResponse);
		jqxhr.fail(function(response) {
			CathPCI.removeLoadingMessage();
			CathPCI.response = response
			$('div#center').append("<br><span>An error has occurred while importing the next chunk. Please contact the maintainer for this module.</span><br><p>" + response + "</p>")
		});
	}
	CathPCI.addLoadingMessage = function() {
		// console.log('adding loading message');
		$('div#center').append("\
		<div class='chunk_loading'>\
			<div class='row card'>\
				<div class='col-6'>\
					<h6 class='card-title'>Importing next chunk of workbook rows...</h6>\
					<div class='loader card-body'></div>\
					<br>\
					<span>Closing this window or tab will stop the import process</span>\
				</div>\
			</div>\
		</div>")
	}
	CathPCI.removeLoadingMessage = function() {
		// console.log('removing loading message');
		$('div.chunk_loading').remove()
	}
	CathPCI.receivedImportResponse = function(response) {
		CathPCI.removeLoadingMessage();
		CathPCI.response = response
		
		// prepare chunk import message table row
		var row_class = response.success ? 'success' : 'failure';
		var import_message = "All rows in chunk imported successfully";
		if (row_class == 'failure') {
			import_message = "Chunk failed to import";
			if (typeof(response.msg.errors) == 'array') {
				import_message += ": " + response.msg.errors.join();
			}
		}
		
		// add chunk import message to table
		CathPCI.chunks_loaded = CathPCI.chunks_loaded + 1
		CathPCI.import_results_dt.row.add([CathPCI.chunks_loaded, import_message]).draw();
		
		if (CathPCI.chunks_loaded < CathPCI.chunk_count) {
			CathPCI.askServerToImportNextChunk();
		} else {
			console.log("CathPCI.chunks_loaded == CathPCI.chunk_count");
		}
	}
}

$(document).ready(function() {
	// append stylesheets
	$('head').append('<link rel="stylesheet" type="text/css" href="' + CathPCI.import_css_url + '">');
	$('head').append('<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.23/css/jquery.dataTables.min.css">');
	
	if (typeof CathPCI != 'undefined') {
		CathPCI.init();
	}
});