
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
		this.response = response
		console.log('response', response)
		
		this.chunks_loaded = this.chunks_loaded + 1
		$('div#center').append("<p>Receieved response for importing first chunk</p>")
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