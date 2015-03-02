{strip}
<div class="content">
	<form action='{$path}/MyAccount/HoldItems' method="POST" class="form">
		<input type='hidden' name='id' id='id' value='{$id}' />
		<input type='hidden' name='campus' id='campus' value='{$campus}' />
		{if count($items) == 0}
			<div class='alert alert-danger'>{$message}</div>
		{else}
			<div class='alert alert-warning'>Please select the item you would like to place a hold on.</div>
			<ol class='hold_result_details'>
				<select id="selectedItem" name="selectedItem" class="form-control">
					{if array_key_exists('-1', $items) == false}
						<option class='hold_item' value="-1">Select an item</option>
					{/if}
					{foreach from=$items item=item_data}
						<option class='hold_item' value="{$item_data.itemNumber}">
							{$item_data.location}
							{if $item_data.itemType} - {$item_data.itemType} {/if}
							{if $item_data.callNumber} - {$item_data.callNumber} {/if}
							{if $item_data.volInfo} - {$item_data.volInfo} {/if}
							{if $item_data.status} - {$item_data.status}{/if}
						</option>
					{/foreach}
				</select>
			</ol>
		{/if}
	</form>
</div>
{/strip}