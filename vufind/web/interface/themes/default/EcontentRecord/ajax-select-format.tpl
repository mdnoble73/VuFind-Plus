<div id="popupboxHeader" class="header">
	{translate text="Select a Format"}
	<a href="#" onclick='hideLightbox();return false;' class="closeIcon">Close <img src="{$path}/images/silk/cancel.png" alt="close" /></a>
</div>
<div id="popupboxContent" class="content">
	<form method="post" action="">
		<div>
			<input type="hidden" name="overdriveId" value="{$overDriveId}"/>
			<label for="loanPeriod">{translate text="Which format would you like?"}</label>
			<select name="formatId" id="formatId">
				{foreach from=$items item=curItem}
					<option value="{$curItem->externalFormatNumeric}">{$curItem->externalFormat|translate}</option>
				{/foreach}
			</select>
			{if $nextAction == 'checkout'}
				<input type="submit" name="submit" value="Check Out" onclick="return checkoutOverDriveItem('{$overDriveId}', $('#formatId :selected').val())"/>
			{else}
				<input type="submit" name="submit" value="Place Hold" onclick="return placeOverDriveHold('{$overDriveId}', $('#formatId :selected').val())"/>
			{/if}
		</div>
	</form>
</div>