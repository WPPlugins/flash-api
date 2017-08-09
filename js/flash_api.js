jQuery(document).ready(function($) {

	$(document).ready(function() {

		function generate() {

			var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
			var http = $('flash_api_url').val();

			var string_length = 8;

			var randomstring = '';

			for (var i=0; i<string_length; i++) {

				var rnum = Math.floor(Math.random() * chars.length);

				randomstring += chars.substring(rnum,rnum+1);

			}

			return MD5(http + randomstring);

		}
		
		function updateCategoryText(val) {
			var txt = (val.length > 0) ? val : 'Codex Category';
			$('#cdx_cat').text(txt);
		}

		
		// Generate Button Click
		$('#generate').click(function () { 

			$('#flash_api_key').val(generate());

		});
		
		// Codex Category Change
		$('#flash_api_tag').keyup(function () {
			updateCategoryText($('#flash_api_tag').val());
		});
		$('#flash_api_tag').change(function () {
			updateCategoryText($('#flash_api_tag').val());
		});		

	})

});