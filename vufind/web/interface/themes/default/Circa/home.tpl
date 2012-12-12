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
			<div class="sidebarLabel"><input type="checkbox" name="updateIncorrectStatuses" {if $lastUpdateIncorrectStatuses}checked="checked"{/if}/> <label for="updateIncorrectStatuses">Auto correct status</label></div>
			<div class="sidebarLabel"><input type="submit" name="submit" value="Submit Inventory"/></div>
		</div>
		<div id="main-content" class="full-result-content">
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
			
			<label for="barcodes">Enter barcodes one per line.</label>
			<textarea rows="20" cols="20" name="barcodes" id="barcodes"></textarea>
		</div>
	</form>
</div>