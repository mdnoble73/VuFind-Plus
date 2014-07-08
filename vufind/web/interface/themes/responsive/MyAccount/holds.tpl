{strip}
	{if $user->cat_username}
		{if $profile.web_note}
			<div id="web_note" class="text-info text-center well well-small">{$profile.web_note}</div>
		{/if}

		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}

		{* Check to see if there is data for the section *}
		<div class='holdSectionBody'>
			{if $libraryHoursMessage}
				<div class='libraryHours alert alert-success'>{$libraryHoursMessage}</div>
			{/if}

			{foreach from=$recordList item=sectionData key=sectionKey}
				<h3>{if $sectionKey == 'available'}Holds Ready For Pickup{else}Pending Holds{/if}</h3>
				<p class="alert alert-info">
					{if $sectionKey == 'available'}
						These titles have arrived at the library or are available online for you to use.
					{else}
						These titles are currently checked out to other patrons.  We will notify you via e-mail, phone, or print when a titles is available based on your preferences.
					{/if}
				</p>
				{if is_array($recordList.$sectionKey) && count($recordList.$sectionKey) > 0}
					{* Make sure there is a break between the form and the table *}
					<br/>
					<div class="striped">
						{foreach from=$recordList.$sectionKey item=record name="recordLoop"}
							{if $record.holdSource == 'ILS'}
								{include file="MyAccount/ilsHold.tpl" record=$record section=$sectionKey}
							{elseif $record.holdSource == 'OverDrive'}
								{include file="MyAccount/overdriveHold.tpl" record=$record section=$sectionKey}
							{elseif $record.holdSource == 'eContent'}
								{include file="MyAccount/eContentHold.tpl" record=$record section=$sectionKey}
							{else}
								<div class="row">
									Unknown record source {$record.checkoutSource}
								</div>
							{/if}
						{/foreach}
					</div>

					{* Code to handle updating multiple holds at one time *}
					<br/>
					<div class='holdsWithSelected{$sectionKey}'>
						<form id='withSelectedHoldsFormBottom{$sectionKey}' action='{$fullPath}'>
							<div>
								<input type="hidden" name="withSelectedAction" value="" />
								<div id='holdsUpdateSelected{$sectionKey}Bottom' class='holdsUpdateSelected{$sectionKey}'>
									<input type="submit" class="btn btn-sm btn-warning" name="cancelSelected" value="Cancel Selected" onclick="return VuFind.Account.cancelSelectedHolds();"/>
									<input type="submit" class="btn btn-sm btn-default" id="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}Bottom" name="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" value="Export to Excel" />
								</div>
							</div>
						</form>
					</div>
				{else} {* Check to see if records are available *}
					{if $sectionKey == 'available'}
						{translate text='You do not have any holds that are ready to be picked up.'}.
					{else}
						{translate text='You do not have any pending holds.'}.
					{/if}

				{/if}
			{/foreach}
		</div>
		<script type="text/javascript">
			$(document).ready(function() {literal} { {/literal}
				$("#holdsTableavailable").tablesorter({literal}{cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 3: {sorter : 'date'}, 4: {sorter : 'date'}, 7: { sorter: false} } }{/literal});
			{literal} }); {/literal}
		</script>
	{else} {* Check to see if user is logged in *}
		You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
	{/if}
{/strip}