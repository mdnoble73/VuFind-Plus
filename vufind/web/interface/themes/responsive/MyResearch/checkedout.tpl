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
			
			<h3>{translate text='Your Checked Out Items'}</h3>
			{if $userNoticeFile}
				{include file=$userNoticeFile}
			{/if}
			
			{if $libraryHoursMessage}
				<div class='libraryHours alert alert-success'>{$libraryHoursMessage}</div>
			{/if}
			{if $transList}
				
				<form id="renewForm" action="{$path}/MyResearch/RenewMultiple">
					<div id="pager" class="navbar form-inline text-center">
						{if $pageLinks.all}<div class="myAccountPagination pagination pull-left">Page: {$pageLinks.all}</div>{/if}
						
						<label for="pagesize" class="control-label pull-left">Records Per Page:&nbsp;
							<select id="pagesize" class="pagesize input-mini" onchange="changePageSize()">
								<option value="10" {if $recordsPerPage == 10}selected="selected"{/if}>10</option>
								<option value="25" {if $recordsPerPage == 25}selected="selected"{/if}>25</option>
								<option value="50" {if $recordsPerPage == 50}selected="selected"{/if}>50</option>
								<option value="75" {if $recordsPerPage == 75}selected="selected"{/if}>75</option>
								<option value="100" {if $recordsPerPage == 100}selected="selected"{/if}>100</option>
							</select>
						</label>

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
						{foreach from=$transList item=record name="recordLoop"}
							<div id="record{$record.id|escape}" class="result row">
							<div class="col-md-3">
								<div class="row">
									<div class="selectTitle col-md-2">
										<input type="checkbox" name="selected[{$record.renewIndicator}]" class="titleSelect" id="selected{$record.itemid}" />
									</div>
									<div class="col-md-9 text-center">
										{if $user->disableCoverArt != 1}
											{if $record.id}
												<a href="{$path}/Record/{$record.id|escape:"url"}" id="descriptionTrigger{$record.id|escape:"url"}">
													<img src="{$coverUrl}/bookcover.php?id={$record.id}&amp;isn={$record.isbn|@formatISBN}&amp;size=medium&amp;upc={$record.upc}&amp;issn={$record.issn}&amp;category={$record.format_category.0|escape:"url"}" class="listResultImage img-polaroid" alt="{translate text='Cover Image'}"/>
												</a>
											{/if}
										{/if}
									</div>
								</div>
							</div>



							<div class="col-md-9">
								<div class="row">
									<strong>
										{if $record.id}
										<a href="{$path}/Record/{$record.id|escape:"url"}" class="title">
											{/if}
											{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}
											{if $record.id}
										</a>
										{/if}
										{if $record.title2}
											<div class="searchResultSectionInfo">
												{$record.title2|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
											</div>
										{/if}
									</strong>
								</div>
								<div class="row">
									<div class="resultDetails col-md-9">
										<div class="row">
											{if $record.author}
												<div class="result-label col-md-3">{translate text='Author'}</div>
												<div class="col-md-9 result-value">
													{if is_array($record.author)}
														{foreach from=$summAuthor item=author}
															<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
														{/foreach}
													{else}
														<a href="{$path}/Author/Home?author={$record.author|escape:"url"}">{$record.author|highlight:$lookfor}</a>
													{/if}
												</div>
											{/if}
										</div>

										{if $record.publicationDate}
											<div class="row">
												<div class="result-label col-md-3">{translate text='Published'}<div class="result-label col-md-3">
												<div class="col-md-9 result-value">{$record.publicationDate|escape}<div class="col-md-9 result-value"></div>
											</div>
										{/if}

										{if $showOut}
											<div class="row">
												<div class="result-label col-md-3">{translate text='Checked Out'}</div>
												<div class="col-md-9 result-value">{$record.checkoutdate|date_format}</div>
											</div>
										{/if}

										<div class="row">
											<div class="result-label col-md-3">{translate text='Due'}</div>
											<div class="col-md-9 result-value">
												{$record.duedate|date_format}
												{if $record.overdue}
													<span class='text-error'><strong> OVERDUE</strong></span>
												{elseif $record.daysUntilDue == 0}
													<span class='text-warning'> (Due today)</span>
												{elseif $record.daysUntilDue == 1}
													<span class='text-warning'> (Due tomorrow)</span>
												{elseif $record.daysUntilDue <= 7}
													<span class='text-warning'> (Due in {$record.daysUntilDue} days)</span>
												{/if}
												{if $record.fine}
													<span class='text-error'><strong> FINE {$record.fine}</strong></span>
												{/if}
											</div>
										</div>

										{if $showRenewed}
											<div class="row">
												<div class="result-label col-md-3">{translate text='Renewed'}</div>
												<div class="col-md-9 result-value">
													{$record.renewCount} times
													{if $record.renewMessage}
														<div class='alert {if $record.renewResult == true}alert-success{else}alert-error{/if}'>
															{$record.renewMessage|escape}
														</div>
													{/if}
												</div>
											</div>
										{/if}

											{if $showWaitList}
												<div class="row">
													<div class="result-label col-md-3">{translate text='Wait LIst'}</div>
													<div class="col-md-9 result-value">
														{* Wait List goes here *}
														{$record.holdQueueLength}
													</div>
												</div>
											{/if}
									</div>

									<div class="col-md-3">
										{* Let the user rate this title *}
										{include file='Record/result-tools.tpl' id=$record.id shortId=$record.shortId summTitle=$summTitle ratingData=$record.ratingData recordUrl=$summUrl}
									</div>
								</div>
							</div>
						</div>
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
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
{/strip}