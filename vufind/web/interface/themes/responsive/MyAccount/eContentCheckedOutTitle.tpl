{strip}
	<div id="record{$record.source}_{$record.id|escape}" class="result row-fluid">
		<div class="span3">
			<div class="row-fluid">
				<div class="selectTitle span2">
					&nbsp;{* Can't renew eContent titles*}
				</div>
				<div class="span9 text-center">
					{if $user->disableCoverArt != 1}
						{if $record.recordId}
							<a href="{$path}/EcontentRecord/{$record.id|escape:"url"}">
								<img src="{$coverUrl}/bookcover.php?id={$record.id}&amp;econtent=true&amp;size=medium" class="listResultImage img-polaroid" alt="{translate text='Cover Image'}"/>
							</a>
						{/if}
					{/if}
				</div>
			</div>
		</div>
		<div class="span9">
			<div class="row-fluid">
				<strong>
					<a href="{$path}/EcontentRecord/{$record.id}/Home">{$record.title}</a>
				</strong>
			</div>
			<div class="row-fluid">
				<div class="resultDetails span9">
					{if strlen($record.record->author) > 0}
						<div class="row-fluid">
							<div class="result-label span3">{translate text='Author'}</div>
							<div class="span9 result-value">{$record.author}</div>
						</div>
					{/if}

					<div class="row-fluid">
						<div class="result-label span3">{translate text='Source'}</div>
						<div class="span9 result-value">{$record.source}</div>
					</div>

					<div class="row-fluid">
						<div class="result-label span3">{translate text='Checked Out'}</div>
						<div class="span9 result-value">{$record.checkoutdate|date_format}</div>
					</div>

					<div class="row-fluid">
						<div class="result-label span3">{translate text='Expires'}</div>
						<div class="span9 result-value">{$record.duedate|date_format}</div>
					</div>

					<div class="row-fluid">
						<div class="result-label span3">{translate text='Wait List'}</div>
						<div class="span9 result-value">{$record.holdQueueLength}</div>
					</div>
				</div>

				<div class="span3">
					<div class="btn-group btn-group-vertical btn-block">
						{foreach from=$record.links item=link}
							<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="btn btn-small">{$link.text}</a>
						{/foreach}
					</div>

					{* Include standard tools *}
					{include file='EcontentRecord/result-tools.tpl' summId=$record.id id=$record.id shortId=$record.shortId ratingData=$record.ratingData recordUrl=$record.recordUrl showMoreInfo=true showHoldButton=false}
				</div>
			</div>
		</div>
	</div>
{/strip}