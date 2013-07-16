<div id="popupboxHeader" class="header">
	{translate text="Select a Format"}
	<a href="#" onclick='hideLightbox();return false;' class="closeIcon">Close <img src="{$path}/images/silk/cancel.png" alt="close" /></a>
</div>
<div id="popupboxContent" class="content">
	<form method="post" data-ajax="false" action="">
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
			<p>Not sure which format to pick?  We have <a onclick="return ajaxLightbox('/Help/eContentHelp?lightbox=true')" href="/Help/eContentHelp">instructions</a> for how to use most formats on common devices <a onclick="return ajaxLightbox('/Help/eContentHelp?lightbox=true')" href="/Help/eContentHelp">here</a>.</p>
		</div>
	</form>
</div>