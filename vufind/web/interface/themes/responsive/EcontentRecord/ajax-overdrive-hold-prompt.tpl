<form method="post" data-ajax="false" action="" id="overdriveHoldPromptsForm">
	<div>
		<input type="hidden" name="overdriveId" value="{$overDriveId}"/>
		{if $promptForFormat}
			<label for="loanPeriod">{translate text="Which format would you like?"}</label>
			<select name="formatId" id="formatId">
				{foreach from=$items item=curItem}
					<option value="{$curItem->externalFormatNumeric}">{$curItem->externalFormat|translate}</option>
				{/foreach}
			</select>

			<p>Not sure which format to pick?  We have <a onclick="return VuFind.Account.ajaxLightbox('/Help/eContentHelp?lightbox=true', false);" href="/Help/eContentHelp">instructions</a> for how to use most formats on common devices <a onclick="return VuFind.Account.ajaxLightbox('/Help/eContentHelp?lightbox=true', false)" href="/Help/eContentHelp">here</a>.</p>
		{elseif $formatId}
			<input type="hidden" name="formatId" value="{$formatId}" />
		{/if}
		{if $promptForEmail}
			<label for="overdriveEmail">{translate text="Enter an e-mail to be notified when the title is ready for you."}</label>
			<br/>
			<input type="text" class="email" name="overdriveEmail" value="{$overdriveEmail}" size="40" maxlength="250"/>
			<br/>
			<input type="checkbox" name="promptForOverdriveEmail"><label for="promptForOverdriveEmail">Remember this e-mail.</label>
		{else}
			<input type="hidden" name="overdriveEmail" value="{$overdriveEmail}"/>
		{/if}
		<br/>
		<input type="submit" name="submit" value="Place Hold" onclick="return VuFind.OverDrive.processOverDriveHoldPrompts()"/>
	</div>
</form>
