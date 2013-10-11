{strip}
	<div class="row-fluid">
		<div class="span3">
			<div class="row-fluid">
				<div class="selectTitle span2">
					<input type="checkbox" name="econtentHoldSelected[{$record.id}]" value="{$record.recordId}" id="selected{$record.id|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
				</div>
				<div class="span9 text-center">
					{if $record.recordId}
					<a href="{$path}/EcontentRecord/{$record.recordId|escape:"url"}">
						{/if}
						<img src="{$coverUrl}/bookcover.php?id={$record.recordId}&amp;issn={$record.issn}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category.0|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
						{if $record.recordId}
					</a>
					{/if}
				</div>
			</div>
		</div>

		<div class="span9">
			<div class="row-fluid">
				<strong>
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
				</strong>
			</div>

			<div class="row-fluid">
				<div class="resultDetails span9">
					{if $record.author}
						<div class="row-fluid">
							<div class="result-label span3">{translate text='Author'}</div>
							<div class="span9 result-value">
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

					<div class="row-fluid">
						<div class="result-label span3">{translate text='Format'}</div>
						<div class="span9 result-value">
							{implode subject=$record.format glue=", "}
						</div>
					</div>

					<div class="row-fluid">
						<div class="result-label span3">{translate text='Pickup'}</div>
						<div class="span9 result-value">
							{$record.location}
						</div>
					</div>

					{if $showPlacedColumn}
						<div class="row-fluid">
							<div class="result-label span3">{translate text='Date Placed'}</div>
							<div class="span9 result-value">
								{$record.create|date_format}
							</div>
						</div>
					{/if}

					{if $section == 'available'}
					{* Available Hold *}
						<div class="row-fluid">
							<div class="result-label span3">{translate text='Available'}</div>
							<div class="span9 result-value">
								{if $record.availableTime}
									{$record.availableTime|date_format:"%b %d, %Y at %l:%M %p"}
								{else}
									Now
								{/if}
							</div>
						</div>

						<div class="row-fluid">
							<div class="result-label span3">{translate text='Expires'}</div>
							<div class="span9 result-value">
								{$record.expire|date_format:"%b %d, %Y"}
							</div>
						</div>

					{else}
					{* Unavailable hold *}
						<div class="row-fluid">
							<div class="result-label span3">{translate text='Status'}</div>
							<div class="span9 result-value">
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
							<div class="row-fluid">
								<div class="result-label span3">{translate text='Position'}</div>
								<div class="span9 result-value">
									{$record.position}
								</div>
							</div>
						{/if}
					{/if}
				</div>

				<div class="span3">
					<div class="btn-group btn-group-vertical btn-block">
						<a href="#" onclick="$('#selected{$record.itemid}').attr('checked', 'checked');return VuFind.Account.cancelSelectedHolds();" class="btn btn-small">Cancel Hold</a>
					</div>

					{* Include standard tools *}
					{include file='EcontentRecord/result-tools.tpl' id=$record.id shortId=$record.shortId ratingData=$record.ratingData}
				</div>
			</div>
		</div>
	</div>
{/strip}