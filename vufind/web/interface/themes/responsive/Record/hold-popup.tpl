<div id="page-content" class="content">
	<form name='placeHoldForm' id='placeHoldForm' action="{$path}/Record/{$id|escape:"url"}/Hold" method="post" class="form">
		<input type="hidden" name="id" id="id" value="{$id}"/>
		<fieldset>
			<div class="holdsSummary">
				<input type="hidden" name="holdCount" id="holdCount" value="1"/>
				<div class="alert alert-warning" id="overHoldCountWarning" {if !$showOverHoldLimit}style="display:none"{/if}>Warning: You have reached the maximum of <span class='maxHolds'>{$maxHolds}</span> holds for your account.  You must cancel a hold before you can place a hold on this title.</div>
				<div id='holdError' class="pageWarning" style='display: none'></div>
			</div>
			{if $holdDisclaimer}
				<div id="holdDisclaimer">{$holdDisclaimer}</div>
			{/if}
			<p class="alert alert-info">
				Holds allow you to request that a title be delivered to your home library.
				Once the title arrives at your library you will be sent an e-mail, receive a phone call, or receive a postcard informing you that the title is ready for you.
				You will then have 8 days to pickup the title from your home library.
			</p>
			{* Responsive theme enforces that the user is always logged in before getting here*}
			<div id='holdOptions'>
				<div id='pickupLocationOptions' class="form-group">
					<label class='control-label' for="campus">{translate text="I want to pick this up at"}: </label>
					<div class='controls'>
						<select name="campus" id="campus" class="form-control">
							{if count($pickupLocations) > 0}
								{foreach from=$pickupLocations item=location}
									<option value="{$location->code}" {if $location->selected == "selected"}selected="selected"{/if}>{$location->displayName}</option>
								{/foreach}
							{else} 
								<option>placeholder</option>
							{/if}
						</select>
					</div>
				</div>
				{if $showHoldCancelDate == 1}
					<div id='cancelHoldDate' class='form-group"'>
						<label class='control-label' for="canceldate">{translate text="Automatically cancel this hold if not filled by"}:</label>
						<div class="input-append date controls" id="cancelDatePicker" data-date-format="mm/dd/yyyy" {if $defaultNotNeededAfterDays}data-date="{$defaultNotNeededAfterDays}"{/if}>
							<input type="text" name="canceldate" id="canceldate" size="10" {if $defaultNotNeededAfterDays}value="{$defaultNotNeededAfterDays}"{/if}>
							<span class="add-on"><i class="icon-calendar"></i></span>
						</div>
						<div class='loginFormRow'>
							<i>If this date is reached, the hold will automatically be cancelled for you.	This is a great way to handle time sensitive materials for term papers, etc. If not set, the cancel date will automatically be set 6 months from today.</i>
						</div>
					</div>
				{/if}
				<br />
				<div class="form-group">
					<label for="autologout" class="checkbox"><input type="checkbox" name="autologout" id="autologout" {if $inLibrary == true}checked="checked"{/if}/> Log me out after requesting the item.</label>
					<input type="hidden" name="holdType" value="hold" />

				</div>
			</div>
		</fieldset>
	</form>
</div>
<script	type="text/javascript">
	{literal}
	$(function() {
		$( "#cancelDatePicker" ).datepicker();
	});
	{/literal}
</script>

