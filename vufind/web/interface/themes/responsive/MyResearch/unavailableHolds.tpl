{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
	</div>

	<div id="main-content" class="col-md-9">
		{if $user->cat_username}
			{if $profile.web_note}
				<div id="web_note" class="text-info text-center well well-small">{$profile.web_note}</div>
			{/if}

			<h3>{translate text='Titles On Hold'}</h3>
			{if $userNoticeFile}
				{include file=$userNoticeFile}
			{/if}

			{assign var=sectionKey value='unavailable'}
			{* Check to see if there is data for the section *}
			<div class='holdSectionBody'>
				{if is_array($recordList.$sectionKey) && count($recordList.$sectionKey) > 0}

					{* Form to update holds at one time *}
					<div id='holdsWithSelected{$sectionKey}Top' class='holdsWithSelected{$sectionKey}'>
						<form id='withSelectedHoldsFormTop{$sectionKey}' action='{$fullPath}'>
							<div>
								<input type="hidden" name="withSelectedAction" value="" />
								<input type="hidden" name="section" value="unavailable" />
								<div id='holdsUpdateSelected{$sectionKey}'>
									{if $allowFreezeHolds}
										{if $showDateWhenSuspending}
											Suspend until (MM/DD/YYYY):
											<input type="text" size="10" name="suspendDateTop" id="suspendDateTop" value="" />
											<script type="text/javascript">{literal}
												$(function() {
													$( "#suspendDateTop" ).datepicker({ minDate: 0, showOn: "both", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
												});{/literal}
											</script>
										{/if}
										<input type="submit" class="button" name="freezeSelected" value="Freeze Selected" title="Freezing a hold prevents the hold from being filled, but keeps your place in queue. This is great if you are going on vacation or want to space out your holds." onclick="return freezeSelectedHolds();"/>
										<input type="submit" class="button" name="thawSelected" value="Thaw Selected" title="Thawing the hold allows the hold to be filled again." onclick="return thawSelectedHolds();"/>
									{/if}
									<input type="submit" class="button" name="cancelSelected" value="Cancel Selected" onclick="return cancelSelectedHolds();"/>
									<input type="submit" class="button" id="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" name="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" value="Export to Excel" />
								</div>
							</div>
						</form> {* End with selected controls for holds *}
					</div>

					<div id="pager" class="pager">
						{if $pageLinks.all}<div class="myAccountPagination pagination">Page: {$pageLinks.all}</div>{/if}

						<span id="recordsPerPage">
						Records Per Page:
						<select id="pagesize" class="pagesize" onchange="changePageSize()">
							<option value="10" {if $recordsPerPage == 10}selected="selected"{/if}>10</option>
							<option value="25" {if $recordsPerPage == 25}selected="selected"{/if}>25</option>
							<option value="50" {if $recordsPerPage == 50}selected="selected"{/if}>50</option>
							<option value="75" {if $recordsPerPage == 75}selected="selected"{/if}>75</option>
							<option value="100" {if $recordsPerPage == 100}selected="selected"{/if}>100</option>
						</select>
						</span>
						<div class='sortOptions'>
							{translate text='Sort'}
							<select name="accountSort" id="sort{$sectionKey}" onchange="changeAccountSort($(this).val());">
							{foreach from=$sortOptions item=sortDesc key=sortVal}
								<option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected="selected"{/if}>{translate text=$sortDesc}</option>
							{/foreach}
							</select>
							Hide Covers <input type="checkbox" onclick="$('.imageColumn').toggle();"/>
						</div>
					</div>

					{* Make sure there is a break between the form and the table *}
					<div class='clearer'></div>

					<table class="myAccountTable" id="holdsTable{$sectionKey}">
						<thead>
							<tr>
								<th><input id='selectAll{$sectionKey}' type='checkbox' onclick="toggleCheckboxes('.titleSelect{$sectionKey}', $(this).attr('checked'));" title="Select All/Deselect All"/></th>
								<th>{translate text='Title'}</th>
								<th>{translate text='Format'}</th>
								{if $showPlacedColumn}
								<th>{translate text='Placed'}</th>
								{/if}
								<th>{translate text='Pickup'}</th>
								{if $showPosition}
								<th>{translate text='Position'}</th>
								{/if}
								<th>{translate text='Status'}</th>
								<th>{translate text='Rating'}</th>
							</tr>
						</thead>

						<tbody>
							{foreach from=$recordList.$sectionKey item=record name="recordLoop"}
								{if ($smarty.foreach.recordLoop.iteration % 2) == 0}
									<tr id="record{$record.recordId|escape}" class="result alt record{$smarty.foreach.recordLoop.iteration}">
								{else}
									<tr id="record{$record.recordId|escape}" class="result record{$smarty.foreach.recordLoop.iteration}">
								{/if}

								<td class="titleSelectCheckedOut myAccountCell">
									<input type="checkbox" name="waitingholdselected[]" value="{$record.cancelId}" id="selected{$record.cancelId|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
								</td>

								<td class="myAccountCell">
									{if $user->disableCoverArt != 1}
									<div class="imageColumn">
										<div id='descriptionPlaceholder{$record.recordId|escape}' style='display:none'></div>
										{if $record.recordId}
										<a href="{$path}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$record.recordId|escape:"url"}">
										{/if}
										<img src="{$coverUrl}/bookcover.php?id={$record.recordId}&amp;issn={$record.issn}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category.0|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
										{if $record.recordId}
										</a>
										{/if}
									</div>
									{/if}

									<div class="myAccountTitleDetails">
										<div class="resultItemLine1">
											{if $record.recordId}
											<a href="{$path}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">
											{/if}
											{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}
											{if $record.recordId}
											</a>
											{/if}
											{if $record.title2}
												<div class="searchResultSectionInfo">
													{$record.title2|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
												</div>
												{/if}
										</div>

										<div class="resultItemLine2">
											{if $record.author}
												{translate text='by'}
												{if is_array($record.author)}
													{foreach from=$summAuthor item=author}
														<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
													{/foreach}
												{else}
													<a href="{$path}/Author/Home?author={$record.author|escape:"url"}">{$record.author|highlight:$lookfor}</a>
												{/if}
											{/if}

											{if $record.publicationDate}{translate text='Published'} {$record.publicationDate|escape}{/if}
										</div>
									</div>
								</td>

								<td class="myAccountCell">
									{if is_array($record.format)}
										{foreach from=$record.format item=format}
											{translate text=$format}
										{/foreach}
									{else}
										{translate text=$record.format}
									{/if}
								</td>

								{if $showPlacedColumn}
								<td class="myAccountCell">
									{$record.create|date_format}
								</td>
								{/if}

								<td class="myAccountCell">
									{$record.location}
								</td>

								{if $showPosition}
									<td class="myAccountCell">
										{$record.position}
									</td>
								{/if}

								<td class="myAccountCell">
									{if $record.frozen}
										<span class='frozenHold'>
									{/if}{$record.status}
									{if $record.frozen && $showDateWhenSuspending}until {$record.reactivate|date_format}</span>{/if}
									{if strlen($record.freezeMessage) > 0}
										<div class='{if $record.freezeResult == true}freezePassed{else}freezeFailed{/if}'>
											{$record.freezeMessage|escape}
										</div>
									{/if}
								</td>

								<td class="myAccountCell">
									<div class="resultActions">
										<div class="rate{$record.shortId|escape} stat">
											{* Let the user rate this title *}
											{include file="Record/title-rating.tpl" ratingClass="" recordId=$record.id shortId=$record.shortId ratingData=$record.ratingData}

											{if $showComments}
												{assign var=id value=$record.recordId}
												{assign var=shortId value=$record.shortId}
												{include file="Record/title-review.tpl"}
											{/if}
										</div>

										{if $record.recordId != -1}
										<script type="text/javascript">
											$(document).ready(function(){literal} { {/literal}
													resultDescription('{$record.recordId}','{$record.recordId}');
											{literal} }); {/literal}
										</script>
										{/if}
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>

				{* Code to handle updating multiple holds at one time *}
				<div class='holdsWithSelected{$sectionKey}'>
					<form id='withSelectedHoldsFormBottom{$sectionKey}' action='{$fullPath}'>
						<div>
							<input type="hidden" name="withSelectedAction" value="" />
							<div id='holdsUpdateSelected{$sectionKey}Bottom' class='holdsUpdateSelected{$sectionKey}'>
								{if $allowFreezeHolds}
									{if $showDateWhenSuspending}
										Suspend until (MM/DD/YYYY):
										<input type="text" size="10" name="suspendDateBottom" id="suspendDateBottom" value="" />
										<script type="text/javascript">{literal}
											$(function() {
												$( "#suspendDateBottom" ).datepicker({ minDate: 0, showOn: "both", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
											});{/literal}
										</script>
									{/if}
									<input type="submit" class="button" name="freezeSelected" value="Freeze Selected" title="Freezing a hold prevents the hold from being filled, but keeps your place in queue. This is great if you are going on vacation or want to space out your holds." onclick="return freezeSelectedHolds();"/>
									<input type="submit" class="button" name="thawSelected" value="Thaw Selected" title="Thawing the hold allows the hold to be filled again." onclick="return thawSelectedHolds();"/>
								{/if}
								<input type="submit" class="button" name="cancelSelected" value="Cancel Selected" onclick="return cancelSelectedHolds();"/>
								{if $allowChangeLocation}
									<div id='holdsUpdateBranchSelection'>
										Change Pickup Location for Selected Items to:
										<select name="withSelectedLocation" id="withSelectedLocation">
											{foreach from=$pickupLocations item=locationLabel key=locationId}
												<option value="{$locationId}" {if $locationId == $resource.currentPickupId}selected="selected"{/if}>{$locationLabel}</option>
											{/foreach}
										</select>
										<input type="submit" name="updateSelected" value="Go" onclick="return updateSelectedHolds();"/>
									</div>
								{/if}
								<input type="submit" class="button" id="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" name="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" value="Export to Excel">
							</div>
						</div>
					</form>
					{if $allowFreezeHolds}
						<p class="note">Note: titles can only be frozen if they are in Pending status.</p>
					{/if}
				</div>
			{else} {* Check to see if records are available *}
				{translate text='You do not have any holds that are not available yet'}.
			{/if}
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
