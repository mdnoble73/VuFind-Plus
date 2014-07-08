{strip}
	<div class="result row">
		<div class="col-xs-12 col-sm-3">
			<div class="row">
				<div class="selectTitle col-xs-2">
					{if $section == 'available'}
						<input type="checkbox" name="availableholdselected[]" value="{$record.cancelId}" id="selected{$record.cancelId|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
					{else}
						<input type="checkbox" name="waitingholdselected[]" value="{$record.cancelId}" id="selected{$record.cancelId|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
					{/if}
				</div>
				<div class="col-xs-9 text-center">
					{if $record.recordId}
						<a href="{$path}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}">
					{/if}

					<img src="{$coverUrl}/bookcover.php?id={$record.recordId}&amp;issn={$record.issn}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category.0|escape:"url"}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}"/>
					{if $record.recordId}
						</a>
					{/if}
				</div>
			</div>
		</div>

		<div class="col-xs-12 col-sm-9">
			<div class="row">
				<div class="col-xs-12">
					<strong>
						{if $record.recordId}
							<a href="{$path}/Record/{$record.recordId|escape:"url"}">
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
					</strong>
				</div>
			</div>

			<div class="row">
				<div class="resultDetails col-xs-12 col-md-9">
					{if $record.author}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Author'}</div>
							<div class="col-xs-9 result-value">
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

					{if $record.format}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Format'}</div>
							<div class="col-xs-9 result-value">
								{implode subject=$record.format glue=", "}
							</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-xs-3">{translate text='Pickup'}</div>
						<div class="col-xs-9 result-value">
							{$record.location}
						</div>
					</div>

					{if $showPlacedColumn}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Date Placed'}</div>
							<div class="col-xs-9 result-value">
								{$record.create|date_format}
							</div>
						</div>
					{/if}

					{if $section == 'available'}
						{* Available Hold *}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Available'}</div>
							<div class="col-xs-9 result-value">
								{if $record.availableTime}
									{$record.availableTime|date_format:"%b %d, %Y at %l:%M %p"}
								{else}
									Now
								{/if}
							</div>
						</div>

						<div class="row">
							<div class="result-label col-xs-3">{translate text='Expires'}</div>
							<div class="col-xs-9 result-value">
								{$record.expire|date_format:"%b %d, %Y"}
							</div>
						</div>

					{else}
						{* Unavailable hold *}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Status'}</div>
							<div class="col-xs-9 result-value">
								{if $record.frozen}
								<span class='frozenHold'>
									{/if}{$record.status}
									{if $record.frozen && $showDateWhenSuspending}until {$record.reactivate|date_format}</span>{/if}
								{if strlen($record.freezeMessage) > 0}
									<div class='{if $record.freezeResult == true}freezePassed{else}freezeFailed{/if}'>
										{$record.freezeMessage|escape}
									</div>
								{/if}
							</div>
						</div>

						{if $showPosition}
							<div class="row">
								<div class="result-label col-xs-3">{translate text='Position'}</div>
								<div class="col-xs-9 result-value">
									{$record.position}
								</div>
							</div>
						{/if}
					{/if}
				</div>

				<div class="col-xs-12 col-md-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $section == 'available'}
							<a href="#" onclick="return VuFind.Account.cancelAvailableHold('{$record.cancelId}');" class="btn btn-sm btn-warning">Cancel Hold</a>
						{else}
							<a href="#" onclick="return VuFind.Account.cancelPendingHold('{$record.cancelId}');" class="btn btn-sm btn-warning">Cancel Hold</a>
						{/if}
					</div>

					{* Include standard tools *}
					{* include file='Record/result-tools.tpl' id=$record.id shortId=$record.shortId ratingData=$record.ratingData *}
				</div>
			</div>
		</div>
	</div>
{/strip}