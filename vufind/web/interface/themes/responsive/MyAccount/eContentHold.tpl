{strip}
	<div class="row">
		<div class="col-md-3">
			<div class="row">
				<div class="selectTitle col-md-2">
					<input type="checkbox" name="econtentHoldSelected[{$record.id}]" value="{$record.recordId}" id="selected{$record.id|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
				</div>
				<div class="col-md-9 text-center">
					{if $record.recordId}
					<a href="{$record.recordUrl|escape:"url"}">
						{/if}
						<img src="{$record.bookcoverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}"/>
						{if $record.recordId}
					</a>
					{/if}
				</div>
			</div>
		</div>

		<div class="col-md-9">
			<div class="row">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				{if $record.recordId}
				<a href="{$record.recordUrl}" class="result-title notranslate">
					{/if}
					{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}
					{if $record.recordId}
				</a>
				{/if}
			</div>

			<div class="row">
				<div class="resultDetails col-md-9">
					{if $record.author}
						<div class="row">
							<div class="result-label col-md-3">{translate text='Author'}</div>
							<div class="col-md-9 result-value">
								<a href="{$path}/Author/Home?author={$record.author|escape:"url"}">{$record.author|highlight:$lookfor}</a>
							</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-md-3">{translate text='Format'}</div>
						<div class="col-md-9 result-value">
							{implode subject=$record.format glue=", "}
						</div>
					</div>

					{if $showPlacedColumn}
							<div class="result-label col-md-3">{translate text='Date Placed'}</div>
							<div class="col-md-9 result-value">
								{$record.create|date_format}
							</div>
						</div>
					{/if}

					{if $section == 'available'}
					{* Available Hold *}
						<div class="row">
							<div class="result-label col-md-3">{translate text='Available'}</div>
							<div class="col-md-9 result-value">
								{if $record.availableTime}
									{$record.availableTime|date_format:"%b %d, %Y at %l:%M %p"}
								{else}
									Now
								{/if}
							</div>
						</div>

						<div class="row">
							<div class="result-label col-md-3">{translate text='Expires'}</div>
							<div class="col-md-9 result-value">
								{$record.expire|date_format:"%b %d, %Y"}
							</div>
						</div>

					{else}
					{* Unavailable hold *}
						<div class="row">
							<div class="result-label col-md-3">{translate text='Status'}</div>
							<div class="col-md-9 result-value">
								{if $record.frozen}
								<span class='frozenHold'>
									{/if}{$record.status|ucfirst}
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
								<div class="result-label col-md-3">{translate text='Position'}</div>
								<div class="col-md-9 result-value">
									{$record.position}
								</div>
							</div>
						{/if}
					{/if}
				</div>

				<div class="col-xs-3">
					<div class="btn-group btn-group-vertical btn-block">
						{foreach from=$record.links item=link}
							<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="btn btn-sm btn-default">{$link.text}</a>
						{/foreach}
					</div>

					{* Include standard tools *}
					{* include file='EcontentRecord/result-tools.tpl' summId=$record.id id=$record.id shortId=$record.shortId ratingData=$record.ratingData recordUrl=$record.recordUrl showMoreInfo=true showHoldButton=false *}
				</div>
			</div>
		</div>
	</div>
{/strip}