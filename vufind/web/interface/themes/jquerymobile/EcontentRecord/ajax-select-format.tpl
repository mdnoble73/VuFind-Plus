<div data-role="dialog">
	<div data-role="header" data-theme="d" data-position="inline">
		<h1>{translate text="Select a Format"}</h1>
	</div>
	<div data-role="content">
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
					<input type="submit" name="submit" value="Check Out" onclick="return checkoutOverDriveItem('{$overDriveId}', $('#formatId :selected').val());" data-ajax="false" data-rel="external"/>
				{else}
					<input type="submit" name="submit" value="Place Hold" onclick="return placeOverDriveHold('{$overDriveId}', $('#formatId :selected').val());" data-ajax="false" data-rel="external"/>
				{/if}
			</div>
		</form>
	</div>
</div>