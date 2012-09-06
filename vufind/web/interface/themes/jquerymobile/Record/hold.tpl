<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
<div data-role="page" id="Record-view">
	{include file="header.tpl"}
	<div class="record" data-role="content">
		<form name="placeHoldForm" id='placeHoldForm' action="{$path}/Record/{$id|escape:"url"}/Hold" method="post" data-ajax="true">
			{if $holdDisclaimer}
				<div id="holdDisclaimer">{$holdDisclaimer}</div>
			{/if}
			{if (!isset($profile)) }
			<div data-role="fieldcontain">
				<label for="username" >{translate text='Username'}:</label>
				<input type="text" name="username" id="username" size="40"><br/>
				
				<label for="password" >{translate text='Password'}:</label>
				<input type="password" name="password" id="password" size="40"><br/>
				<a href="#" id="loginButton" data-role="button" onclick="GetPreferredBranches('{$id|escape}');return false;" >Login</a>
			</div>
			{/if}
			<div data-role="fieldcontain">
				<div id='holdOptions' {if (!isset($profile)) }style='display:none'{/if}>
					<div id='pickupLocationOptions'>
						<label for="campus">{translate text="I want to pick this up at"}:</label>
						<select name="campus" id="campus" data-role="none">
							{foreach from=$pickupLocations item=location key=value}
								<option value="{$location->code}">{$location->displayName}</option>
							{/foreach}
						</select>
					</div>
					{if $showHoldCancelDate == 1}
						<div id='cancelHoldDate'>
							<label for="canceldate">{translate text="Automatically cancel this hold if not filled by"}:</label>
							<input type="text" name="canceldate" id="canceldate" size="10">
							<br /><i>If this date is reached, the hold will automatically be cancelled for you.	This is a great way to handle time sensitive materials for term papers, etc. If not set, the cancel date will automatically be set 6 months from today.</i>
						</div>
					{/if}
					<br />
					<input type="hidden" name="holdType" value="hold">
					<a href="#" data-role="button" id="requestTitleButton" {if (!isset($profile))}disabled="disabled"{/if} onclick="document.placeHoldForm.submit();" >{translate text='Request This Title'}</a>
				</div>
			</div>
		</form>
	</div>		
	{include file="footer.tpl"}
</div>
{if $showHoldCancelDate == 1}
<script type="text/javascript">
	{literal}
	$(function() {
		$("#cancelHoldDate").datepicker({ minDate: 0, showOn: "button", buttonImage: "{/literal}{$path}{literal}/interface/themes/marmot/images/silk/calendar.png", numberOfMonths: 2, buttonImageOnly: true});
	});
	{/literal}
</script>
{/if}