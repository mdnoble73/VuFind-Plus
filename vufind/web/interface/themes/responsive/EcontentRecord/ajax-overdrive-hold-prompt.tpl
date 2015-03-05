<form method="post" action="" id="overdriveHoldPromptsForm" class="form">
	<div>
		<input type="hidden" name="overdriveId" value="{$overDriveId}"/>
		{if $promptForEmail}
			<div class="form-group">
				<label for="overdriveEmail" class="control-label">{translate text="Enter an e-mail to be notified when the title is ready for you."}</label>
				<input type="text" class="email form-control" name="overdriveEmail" value="{$overdriveEmail}" size="40" maxlength="250"/>
				</div>
			<div class="checkbox">
				<label for="promptForOverdriveEmail" class="control-label"><input type="checkbox" name="promptForOverdriveEmail" id="promptForOverdriveEmail"/> Remember this e-mail.</label>
			</div>
		{else}
			<input type="hidden" name="overdriveEmail" value="{$overdriveEmail}"/>
		{/if}
	</div>
</form>
