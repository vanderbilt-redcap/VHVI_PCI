
if (typeof CathPCI != 'undefined') {
	// make datatables
	CathPCI.field_map_dt = $('#field_map table').DataTable();
	CathPCI.import_results_dt = $('table#import_results').DataTable();
	
	CathPCI.init = function() {
		this.chunks_loaded = 0;
		if (this.chunk_count > 0) {
			this.askServerToImportNextChunk();
		}
	}
	CathPCI.askServerToImportNextChunk = function() {
		var next_chunk_index = this.chunks_loaded + 1;
		console.log('asking server to import chunk #' + next_chunk_index);
		console.log('sending request to: ' + this.import_chunk_url);
		
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
			this.response = response
			console.log('fail response', response)
			$('div#center').append("<br><span>An error has occurred while importing the next chunk. Please contact the maintainer for this module.</span><br><p>" + data + "</p>")
		});
		jqxhr.always(this.removeLoadingMessage);
	}
	CathPCI.addLoadingMessage = function() {
		$('div#center').append("<div class='chunk_loading'><span>Importing next chunk of workbook rows...</span><div class='loader'></div></div>")
	}
	CathPCI.removeLoadingMessage = function() {
		$('div.chunk_loading').remove()
	}
	CathPCI.receivedImportResponse = function(response) {
		CathPCI.response = response
		console.log('response', response)
		
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
		// debugger;
		CathPCI.import_results_dt.row.add([CathPCI.chunks_loaded, import_message]).draw();
		// $("table#import_results tbody").append("<tr><td class='" + row_class + "'>" + CathPCI.chunks_loaded + "</td><td></td></tr>");
		
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