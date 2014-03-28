{strip}
	{if $user->cat_username}
		{if $profile.web_note}
			<div id="web_note" class="alert alert-info text-center">{$profile.web_note}</div>
		{/if}

		<h3>{translate text='Checked Out Titles'}</h3>
		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}

		{if $libraryHoursMessage}
			<div class='libraryHours alert alert-success'>{$libraryHoursMessage}</div>
		{/if}
		{if $transList}
			<form id="renewForm" action="{$path}/MyResearch/RenewMultiple">
				<div id="pager" class="navbar form-inline text-center">
					<label for="accountSort" class="control-label">{translate text='Sort by'}:&nbsp;
						<select name="accountSort" id="sort" class="input-medium" onchange="changeAccountSort($(this).val());">
						{foreach from=$sortOptions item=sortDesc key=sortVal}
							<option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected="selected"{/if}>{translate text=$sortDesc}</option>
						{/foreach}
						</select>
					</label>

					<label for="hideCovers" class="control-label checkbox  pull-right"> Hide Covers <input id="hideCovers" type="checkbox" onclick="$('.imageColumn').toggle();"/></label>
				</div>

				<div class="btn-group">
					<a href="#" onclick="return VuFind.Account.renewSelectedTitles();" class="btn">Renew Selected Items</a>
					<a href="{$path}/MyResearch/RenewAll" class="btn">Renew All</a>
					<a href="{$path}/MyResearch/CheckedOut?exportToExcel" class="btn" id="exportToExcelTop" >Export to Excel</a>
				</div>

				<div class="striped">
					{foreach from=$transList item=checkedOutTitle name=checkedOutTitleLoop key=checkedOutKey}
						{if $checkedOutTitle.checkoutSource == 'ILS'}
							{include file="MyAccount/ilsCheckedOutTitle.tpl" record=$checkedOutTitle}
						{elseif $checkedOutTitle.checkoutSource == 'OverDrive'}
							{include file="MyAccount/overdriveCheckedOutTitle.tpl" record=$checkedOutTitle}
						{elseif $checkedOutTitle.checkoutSource == 'eContent'}
							{include file="MyAccount/eContentCheckedOutTitle.tpl" record=$checkedOutTitle}
						{else}
							<div class="row">
								Unknown record source {$checkedOutTitle.checkoutSource}
							</div>
						{/if}
					{/foreach}
				</div>

				<div class="btn-group">
					<a href="#" onclick="return VuFind.Account.renewSelectedTitles();" class="btn">Renew Selected Items</a>
					<a href="{$path}/MyResearch/RenewAll" class="btn">Renew All</a>
					<a href="{$path}/MyResearch/CheckedOut?exportToExcel" class="btn" id="exportToExcelBottom" >Export to Excel</a>
				</div>
			</form>
			
		{else}
			{translate text='You do not have any items checked out'}.
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
	{/if}
{/strip}