{strip}
	<div id="record{$record.source}_{$record.id|escape}" class="result row">
		<div class="col-xs-12 col-sm-3 col-md-3">
			<div class="row">
				<div class="selectTitle col-md-2">
					<input type="checkbox" name="selected[{$record.renewIndicator}]" class="titleSelect" id="selected{$record.itemid}"/>
				</div>
				<div class="col-md-9 text-center">
					{if $user->disableCoverArt != 1}
						{if $record.id}
							<a href="{$path}/Record/{$record.id|escape:"url"}">
								<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}"/>
							</a>
						{/if}
					{/if}
				</div>
			</div>
		</div>
		<div class="col-xs-12 col-sm-9">
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
					{if $record.author}
						<div class="row">
							<div class="result-label col-md-3">{translate text='Author'}</div>
							<div class="col-md-9 result-value">
								{if is_array($record.author)}
									{foreach from=$record.author item=author}
										<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
									{/foreach}
								{else}
									<a href="{$path}/Author/Home?author={$record.author|escape:"url"}">{$record.author|highlight:$lookfor}</a>
								{/if}
							</div>
						</div>
					{/if}

					{if $record.publicationDate}
						<div class="row">
							<div class="result-label col-md-3">{translate text='Published'}</div>
							<div class="col-md-9 result-value">{$record.publicationDate|escape}</div>
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
							<div class="result-label col-md-3">{translate text='Wait List'}</div>
							<div class="col-md-9 result-value">
								{* Wait List goes here *}
								{$record.holdQueueLength}
							</div>
						</div>
					{/if}
				</div>

				<div class="col-md-3">
					<div class="btn-group btn-group-vertical btn-block">
						<a href="#" onclick="$('#selected{$record.itemid}').attr('checked', 'checked');return VuFind.Account.renewSelectedTitles();" class="btn btn-sm btn-primary">Renew</a>
					</div>

					{* Include standard tools *}
					{* include file='Record/result-tools.tpl' id=$record.id shortId=$record.shortId ratingData=$record.ratingData *}
				</div>
			</div>
		</div>
	</div>
{/strip}