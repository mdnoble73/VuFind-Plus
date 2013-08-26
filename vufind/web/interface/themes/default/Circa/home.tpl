{strip}
<div id="page-content" class="content">
	{if $error}<p class="error">{$error}</p>{/if} 
	<form action="" method="post">
		
		<h1>Circa Inventory</h1>
		<div id="sidebar">
			<div class="sidebarLabel"><label for="login">Login</label>:</div>
			<div class="sidebarValue"><input type="text" name="login" id="login" value="{$lastLogin}"/> </div>
			<div class="sidebarLabel"><label for="password1">Password</label>:</div>
			<div class="sidebarValue"><input type="password" name="password1" id="password1" value="{$lastPassword1}"/></div>
			<div class="sidebarLabel"><label for="initials">Initials</label>:</div>
			<div class="sidebarValue"><input type="text" name="initials" id="initials" value="{$lastInitials}"/> </div>
			<div class="sidebarLabel"><label for="password2">Password</label>:</div>
			<div class="sidebarValue"><input type="password" name="password2" id="password2" value="{$lastPassword2}"/></div>
			<div class="sidebarLabel"><input type="checkbox" name="updateIncorrectStatuses" id="updateIncorrectStatuses" {if $lastUpdateIncorrectStatuses}checked="checked"{/if}/> <label for="updateIncorrectStatuses">Auto correct status</label></div>
			{*
			<div class="sidebarLabel"><input type="submit" name="submit" value="Submit Inventory"/></div>
			*}
		</div>
		<div id="main-content" class="full-result-content">
			{*
			{if $results}
				<div id="circaResults" {if $results.success == false}class="error"{/if}>
					{if $results.success == false}
						Error processing inventory.  {$results.message}
					{else}
						Successfully processed inventory. 
						<table>
							<thead>
								<tr>
									<th>Barcode</th><th>Title</th><th>Call Number</th><th>Result</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$results.barcodes item=barcodeInfo key=barcode}
									<tr>
										<td>{$barcode}</td>
										<td>{$barcodeInfo.title}</td>
										<td>{$barcodeInfo.callNumber}</td>
										<td style="color:{if $barcodeInfo.needsAdditionalProcessing}red{else}green{/if}">
											{$barcodeInfo.inventoryResult}
										</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					{/if}
				</div>
			{/if}
			*}
			
			<label for="barcodes">Enter barcodes one per line.</label>
			<textarea rows="10" cols="20" name="barcodes" id="barcodes"></textarea>

			<h2>Inventory Results</h2>
			<table id="inventoryResults">
				<thead>
				<tr>
					<th>Barcode</th><th>Title</th><th>Call Number</th><th>Result</th>
				</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
	</form>
</div>
{/strip}
{literal}
<script type="text/javascript">
	var lastKeyPress = new Date().getTime();
	$(document).ready(
		function(){
			setInterval(processInventory, 5000);
		}
	);
	$('#barcodes').keyup(function (){
		lastKeyPress = new Date().getTime();
	});

	function processInventory(){
		//Make sure we aren't in the process of entering text.
		// The last key up should have been at least half a second ago.
		var curTime = new Date().getTime();
		if (curTime - lastKeyPress < 500){
			return;
		}
		var barcodesCtrl = $('#barcodes');
		//Read barcodes to process from barcodes field
		var barcodeData = barcodesCtrl.val();
		//Remove the barcode(s) from the field
		barcodesCtrl.val("");

		var login = $("#login").val();
		var password = $("#password1").val();
		var initials = $("#initials").val();
		var password2 = $("#password2").val();
		var updateIncorrectStatuses = $("#updateIncorrectStatuses").attr('checked') ? true : false;

		if (barcodeData.length > 0){
			var barcodes = barcodeData.match(/[^\r\n]+/g);
			var barcodeStr = "";
			for (var i = 0; i < barcodes.length; i++){
				barcodeStr += "&barcodes[]=" + barcodes[i];
			}
			//Submit each barcode using AJAX
			url = path + "/Circa/AJAX?method=UpdateInventoryForBarcode" + barcodeStr;
			url += "&login=" + login;
			url += "&password=" + password;
			url += "&initials=" + initials;
			url += "&password2=" + password2;
			url += "&updateIncorrectStatuses=" + updateIncorrectStatuses;
			//Display results
			$.getJSON(url, function(data){
				if (!data.success){
					addInventoryRow(data.barcode, null, null, data.message);
				}else{
					for(var barcode in data.barcodes){
						var barcodeData = data.barcodes[barcode];
						addInventoryRow(barcode, barcodeData.title, barcodeData.callNumber, barcodeData.inventoryResult);
					}

				}
			});
		}
	}

	function addInventoryRow(barcode, title, callNumber, inventoryResult) {
		var rowData = '<tr>';
		rowData += '<td>' + barcode + '</td>';
		if (title) {
			rowData += '<td>' + title + '</td>';
		} else {
			rowData += '<td></td>';
		}
		if (callNumber) {
			rowData += '<td>' + callNumber + '</td>';
		} else {
			rowData += '<td></td>';
		}
		rowData += '<td>' + inventoryResult + '</td>';
		rowData += '</tr>';
		var table = $('#inventoryResults');
		var tableBody = table.children('tbody');
		if (tableBody.children().length == 0) {
			tableBody.html(rowData);
		} else {
			var firstRow = tableBody.children("tr:first");
			firstRow.before(rowData);
		}
	}

</script>
{/literal}