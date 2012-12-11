<div id="page-content" class="content">
	<div id="main-content">
		<form name='placeHoldForm' id='placeHoldForm' action="{$path}/MyResearch/HoldMultiple" method="post">
			<div>
				{if $fromCart}
					<input type="hidden" name="fromCart" value="true" />
				{/if}
				{if $holdDisclaimer}
					<div id="holdDisclaimer">{$holdDisclaimer}</div>
				{/if}
				
				{foreach from=$ids item=id}
					 <input type="hidden" name="selected[{$id|escape:url}]" value="on" />
				{/foreach}
				{if (!isset($profile)) }
					<div id ='loginUsernameRow' class='loginFormRow'>
						<div class='loginLabel'>{translate text='Username'}: </div>
						<div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
					</div>
					<div id ='loginPasswordRow' class='loginFormRow'>
						<div class='loginLabel'>{translate text='Password'}: </div>
						<div class='loginField'><input type="password" name="password" id="password" size="15"/></div>
					</div>
					<div id='loginSubmitButtonRow' class='loginFormRow'>
						<input id="loginButton" type="button" onclick="GetPreferredBranches('{$id|escape}');" value="Login"/>
					</div>
				{/if}
				<div id='holdOptions' {if (!isset($profile)) }style='display:none'{/if}>
					<div class='pickupLocationOptions'>
						<b>{translate text="I want to pick this up at"}: </b>
						<select name="campus" id="campus">
							{if count($pickupLocations) > 0}
								{foreach from=$pickupLocations item=location}
									<option value="{$location->code}" {if $location->selected == "selected"}selected="selected"{/if}>{$location->displayName}</option>
								{/foreach}
							{else} 
								<option>placeholder</option>
							{/if}
						</select>
					</div>
					{if $showHoldCancelDate == 1}
						<div id='cancelHoldDate'><b>{translate text="Automatically cancel this hold if not filled by"}:</b>
							<input type="text" name="canceldate" id="canceldate" size="10" {if $defaultNotNeededAfterDays}value="{$defaultNotNeededAfterDays}"{/if}>
							<br /><i>If this date is reached, the hold will automatically be cancelled for you.	This is a great way to handle time sensitive materials for term papers, etc. If not set, the cancel date will automatically be set 6 months from today.</i>
						</div>
					{/if}
					<br />
					<input type="hidden" name="holdType" value="hold"/>
					<input type="submit" name="submit" id="requestTitleButton" value="{translate text='Request This Title'}" {if (!isset($profile))}disabled="disabled"{/if}/>
					<input type="checkbox" name="autologout" {if $inLibrary == true}checked="checked"{/if}/> Log me out after requesting the item. 
				</div>
			</div>
		</form>
	</div>
</div>
<script	type="text/javascript">
	{literal}
	$(function() {
		$( "#canceldate" ).datepicker({ minDate: 0, showOn: "button", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
	});
	{/literal}
</script>