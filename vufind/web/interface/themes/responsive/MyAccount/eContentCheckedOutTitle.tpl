{strip}
	<div id="record{$record.source}_{$record.id|escape}" class="result row">
		<div class="col-xs-12 col-sm-3">
			<div class="row">
				<div class="selectTitle col-xs-2">
					&nbsp;{* Can't renew eContent titles*}
				</div>
				<div class="col-xs-10 text-center">
					{if $user->disableCoverArt != 1}
						{if $record.recordId}
							<a href="{$record.recordUrl}">
								<img src="{$record.bookcoverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}"/>
							</a>
						{/if}
					{/if}
				</div>
			</div>
		</div>
		<div class="col-xs-12 col-sm-9">
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<a href="{$record.recordUrl}" class="result-title notranslate">{$record.title}</a>
				</div>
			</div>
			<div class="row">
				<div class="resultDetails col-xs-9">
					{if strlen($record.record->author) > 0}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Author'}</div>
							<div class="col-xs-9 result-value">{$record.author}</div>
						</div>
					{/if}

					{*
					<div class="row">
						<div class="result-label col-xs-3">{translate text='Source'}</div>
						<div class="col-xs-9 result-value">{$record.source}</div>
					</div>
					*}

					<div class="row">
						<div class="result-label col-xs-3">{translate text='Checked Out'}</div>
						<div class="col-xs-9 result-value">{$record.checkoutdate|date_format}</div>
					</div>

					{if $recordType == 'RestrictedEContent'}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Expires'}</div>
							<div class="col-xs-9 result-value">{$record.duedate|date_format}</div>
						</div>

						<div class="row">
							<div class="result-label col-xs-3">{translate text='Wait List'}</div>
							<div class="col-xs-9 result-value">{$record.holdQueueLength}</div>
						</div>
					{/if}

					<div class="row">
						{foreach from=$record.items item=eContentItem key=index}
							<div class="result-label col-xs-3">{$eContentItem.format}</div>
							<div class="col-xs-9 result-value">
								{foreach from=$eContentItem.actions item=link}
									{if $link.showInFormats || !$link.showInSummary}
										<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick && strlen($link.onclick) > 0}onclick="{$link.onclick}"{/if} class="btn btn-xs btn-primary">{$link.title}</a>&nbsp;
									{/if}
								{/foreach}
							</div>
						{/foreach}
					</div>
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