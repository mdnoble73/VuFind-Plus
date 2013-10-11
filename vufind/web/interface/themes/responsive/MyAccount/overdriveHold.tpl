{strip}
	<div class="row-fluid" id="overDriveHold_{$record.overDriveId}">
		<div class="span3">
			<div class="row-fluid">
				<div class="selectTitle span2">
					&nbsp;
				</div>
				<div class="span9 text-center">
					{if $record.recordId}
					<a href="{$path}/EcontentRecord/{$record.recordId|escape:"url"}">
						{/if}
						<img src="{$coverUrl}/bookcover.php?id={$record.recordId}&amp&econtent&size=medium" class="listResultImage" alt="{translate text='Cover Image'}"/>
						{if $record.recordId}
					</a>
					{/if}
				</div>
			</div>
		</div>

		<div class="span9">
			<div class="row-fluid">
				<strong>
					{if $record.recordId != -1}
					<a href="{$path}/EcontentRecord/{$record.recordId|escape:"url"}" class="title">
						{/if}
						{if !$record.title}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation}{/if}
						{if $record.recordId != -1}
					</a>
					{/if}
					{if $record.subTitle}
						<div class="searchResultSectionInfo">
							{$record.subTitle|removeTrailingPunctuation}
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

					{if $section == 'available'}
					{* Available Hold *}
						<div class="row-fluid">
							<div class="result-label span3">{translate text='Notification Sent'}</div>
							<div class="span9 result-value">
								{if $record.notificationDate}
									{$record.notificationDate|date_format:"%b %d, %Y at %l:%M %p"}
								{else}
									Now
								{/if}
							</div>
						</div>

						<div class="row-fluid">
							<div class="result-label span3">{translate text='Expires'}</div>
							<div class="span9 result-value">
								{$record.expirationDate|date_format:"%b %d, %Y"}
							</div>
						</div>

					{else}
						{* Unavailable hold *}
						<div class="row-fluid">
							<div class="result-label span3">{translate text='Position'}</div>
							<div class="span9 result-value">
								{$record.holdQueuePosition} out of {$record.holdQueueLength}
							</div>
						</div>
					{/if}
				</div>

				<div class="span3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $section == 'available'}
							<a href="#" onclick="return VuFind.OverDrive.checkoutOverDriveItemOneClick('{$record.overDriveId}');" class="btn btn-small">Cancel Hold</a>
						{/if}
						<a href="#" onclick="return VuFind.OverDrive.cancelOverDriveHold('{$record.overDriveId}');" class="btn btn-small">Cancel Hold</a>
					</div>

					{* Include standard tools *}
					{include file='EcontentRecord/result-tools.tpl' summId=$record.recordId summShortId=$record.shortId ratingData=$record.ratingData recordUrl=$record.recordUrl showMoreInfo=true showHoldButton=false}
				</div>
			</div>
		</div>
	</div>
{/strip}