{strip}
	{* Overall hold*}
	<div class="result row">
		{* Cover column *}
		<div class="col-xs-12 col-sm-3">
			<div class="row">
				<div class="selectTitle col-xs-2">
					{if $record.cancelable}
						{if $section == 'available'}
							{* TODO: Determine is difference between availableholdselected & waitingholdselected is necessary *}
							<input type="checkbox" name="availableholdselected[]" value="{$record.cancelId}" id="selected{$record.cancelId|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
						{else}
							<input type="checkbox" name="waitingholdselected[]" value="{$record.cancelId}" id="selected{$record.cancelId|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
						{/if}
					{/if}
				</div>
				<div class="col-xs-9 text-center">
					{if $record.recordId}
						<a href="{$record.link}">
					{/if}

					<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}"/>
					{if $record.recordId}
						</a>
					{/if}
				</div>
			</div>
		</div>

		{* Details Column*}
		<div class="col-xs-12 col-sm-9">
			{* Title *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					{if $record.recordId}
						<a href="{$record.link}" class="result-title notranslate">
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
			</div>

			{* 2 column row to show information and then actions*}
			<div class="row">
				{* Information column author, format, etc *}
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
						<div class="result-label col-xs-3">{translate text='On Hold For'}</div>
						<div class="col-xs-9 result-value">
							{$record.user}
						</div>
					</div>

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

						{if $record.expire}
							<div class="row">
								<div class="result-label col-xs-3">{translate text='Expires'}</div>
								<div class="col-xs-9 result-value">
									{$record.expire|date_format:"%b %d, %Y"}
								</div>
							</div>
						{/if}
					{else}
						{* Unavailable hold *}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Status'}</div>
							<div class="col-xs-9 result-value">
								{if $record.frozen}
								<span class='frozenHold'>
									{/if}{$record.status}
									{if $record.frozen && $showDateWhenSuspending} until {$record.reactivate}</span>{/if}
								{if strlen($record.freezeMessage) > 0}
									<div class='{if $record.freezeResult == true}freezePassed{else}freezeFailed{/if}'>
										{$record.freezeMessage|escape}
									</div>
								{/if}
							</div>
						</div>

						{if $showPosition && $record.position}
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
							{if $record.cancelable}
								{*<button onclick="return VuFind.Account.cancelAvailableHold('{$record.cancelId}', '{$record.shortId}');" class="btn btn-sm btn-warning">Cancel Hold</button>*}
								<button onclick="return VuFind.Account.cancelHold('{$record.cancelId}');" class="btn btn-sm btn-warning">Cancel Hold</button>
							{/if}
						{else}
							{if $record.cancelable}
								{*<button onclick="return VuFind.Account.cancelPendingHold('{$record.cancelId}', '{$record.shortId}');" class="btn btn-sm btn-warning">Cancel Hold</button>*}
								<button onclick="return VuFind.Account.cancelHold('{$record.cancelId}');" class="btn btn-sm btn-warning">Cancel Hold</button>
							{/if}
							{if $record.frozen}
								<button onclick="return VuFind.Account.thawHold('{$record.cancelId}', this);" class="btn btn-sm btn-default">{translate text="Thaw Hold"}</button>
							{elseif $record.freezeable}
								<button onclick="return VuFind.Account.freezeHold('{$record.cancelId}', {if $suspendRequiresReactivationDate}true{else}false{/if}, this);" class="btn btn-sm btn-default">{translate text="Freeze Hold"}</button>
							{/if}
							{if $record.locationUpdateable}
								<button onclick="return VuFind.Account.changeHoldPickupLocation('{$record.cancelId}');" class="btn btn-sm btn-default">Change Pickup Loc.</button>
							{/if}
						{/if}
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}