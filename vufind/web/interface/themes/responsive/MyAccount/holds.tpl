{strip}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>

	<div id="main-content" class="col-md-9">
		{if $user->cat_username}
			{if $profile.web_note}
				<div id="web_note" class="text-info text-center well well-small">{$profile.web_note}</div>
			{/if}

			<h3>{translate text='Holds Ready For Pickup'}</h3>
			{if $userNoticeFile}
				{include file=$userNoticeFile}
			{/if}


			{* Check to see if there is data for the section *}
			<div class='holdSectionBody'>
				{if $libraryHoursMessage}
					<div class='libraryHours'>{$libraryHoursMessage}</div>
				{/if}

				{foreach from=$recordList item=sectionData key=sectionKey}
					{if is_array($recordList.$sectionKey) && count($recordList.$sectionKey) > 0}
						{* Form to update holds at one time *}
						<div id='holdsWithSelected{$sectionKey}Top' class='holdsWithSelected{$sectionKey}'>
							<form id='withSelectedHoldsFormTop{$sectionKey}' action='{$fullPath}'>
								<div>
									<input type="hidden" name="withSelectedAction" value="" />
									<div id='holdsUpdateSelected{$sectionKey}'>
										<input type="submit" class="btn" name="cancelSelected" value="Cancel Selected" onclick="return cancelSelectedHolds();"/>
										<input type="submit" class="btn" id="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}Top" name="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" value="Export to Excel" />
									</div>
								</div>
							</form> {* End with selected controls for holds *}
						</div>

						<div id="pager" class="pager">
							<div class='sortOptions'>
								Hide Covers <input type="checkbox" onclick="$('.imageColumn').toggle();"/>
							</div>
						</div>

						{* Make sure there is a break between the form and the table *}
						<div class='clearer'></div>

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
					</div>

					{* Code to handle updating multiple holds at one time *}
					<div class='holdsWithSelected{$sectionKey}'>
						<form id='withSelectedHoldsFormBottom{$sectionKey}' action='{$fullPath}'>
							<div>
								<input type="hidden" name="withSelectedAction" value="" />
								<div id='holdsUpdateSelected{$sectionKey}Bottom' class='holdsUpdateSelected{$sectionKey}'>
									<input type="submit" class="btn" name="cancelSelected" value="Cancel Selected" onclick="return cancelSelectedHolds();"/>
									<input type="submit" class="btn" id="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}Bottom" name="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" value="Export to Excel" />
								</div>
							</div>
						</form>
					</div>
				{else} {* Check to see if records are available *}
					{translate text='You do not have any holds that are ready to be picked up'}.
				{/if}
			{/foreach}
		</div>
		<script type="text/javascript">
			$(document).ready(function() {literal} { {/literal}
				$("#holdsTableavailable").tablesorter({literal}{cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 3: {sorter : 'date'}, 4: {sorter : 'date'}, 7: { sorter: false} } }{/literal});
			{literal} }); {/literal}
		</script>
		{else} {* Check to see if user is logged in *}
			You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
		{/if}
	</div>
</div>
{/strip}