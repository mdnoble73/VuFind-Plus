{strip}
<div class="contents">
	<form action='{$path}/MyAccount/HoldItems' method="POST" class="form">
		<input type='hidden' name='id' id='id' value='{$id}' />
		<input type='hidden' name='campus' id='campus' value='{$campus}' />
		<div class='alert alert-warning'>Your hold request needs additional information.</div>
		<ol class='hold_result_details'>
			<select id="selectedItem" name="selectedItem" class="form-control">
				<option class='hold_item' value="-1">Select an item</option>
				{foreach from=$items item=item_data}
					<option class='hold_item' value="{$item_data.itemNumber}">{$item_data.location}- {$item_data.callNumber} - {$item_data.status}</option>
				{/foreach}
			</select>
		</ol>
	</form>
</div>
{/strip}
