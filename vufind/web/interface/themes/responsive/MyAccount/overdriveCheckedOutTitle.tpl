{strip}
<div id="overdrive_{$record.recordId|escape}" class="result row">
	<div class="col-md-3">
		<div class="row">
			<div class="selectTitle col-md-2">
				&nbsp;{* Can't renew overdrive titles*}
			</div>
			<div class="col-md-9 text-center">
				{if $user->disableCoverArt != 1}
					{if $record.recordId}
						<a href="{$record.recordUrl|escape:"url"}">
							<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}"/>
						</a>
					{/if}
				{/if}
			</div>
		</div>
	</div>
	<div class="col-md-9">
		<div class="row">
			<strong>
				{if $record.recordId != -1}<a href="{$record.recordUrl}">{/if}{$record.title}{if $record.recordId == -1}OverDrive Record {$record.overDriveId}{/if}{if $record.recordId != -1}</a>{/if}
				{if $record.subTitle}<br/>{$record.subTitle}{/if}

			</strong>
		</div>
		<div class="row">
			<div class="resultDetails col-md-9">
				{if strlen($record.author) > 0}
					<div class="row">
						<div class="result-label col-md-3">{translate text='Author'}</div>
						<div class="col-md-9 result-value">{$record.author}</div>
					</div>
				{/if}

				<div class="row">
					<div class="result-label col-md-3">{translate text='Expires'}</div>
					<div class="col-md-9 result-value">{$record.expiresOn|replace:' ':'&nbsp;'}</div>
				</div>

				<div class="row">
					<div class="result-label col-md-3">{translate text='Download'}</div>

					<div class="col-md-9 result-value">
						{if $record.formatSelected}
							You downloaded the <strong>{$record.selectedFormat.name}</strong> format of this title.
						{else}
							<div class="form-inline">
								<label for="downloadFormat_{$record.overDriveId}">Select one format to download.</label>
								<select name="downloadFormat_{$record.overDriveId}" id="downloadFormat_{$record.overDriveId}" class="input-medium">
									<option value="-1">Select a Format</option>
									{foreach from=$record.formats item=format}
										<option value="{$format.id}">{$format.name}</option>
									{/foreach}
								</select>
								<a href="#" onclick="VuFind.OverDrive.selectOverDriveDownloadFormat('{$record.overDriveId}')" class="btn btn-sm btn-primary">Download</a>
							</div>
						{/if}
					</div>
				</div>
			</div>

			<div class="col-md-3">
				<div class="btn-group btn-group-vertical btn-block">
					{if $record.overdriveRead}
						<a href="#" onclick="return VuFind.OverDrive.followOverDriveDownloadLink('{$record.overDriveId}', 'ebook-overdrive')" class="btn btn-sm btn-primary">Read&nbsp;Online</a>
					{/if}
					{if $record.formatSelected}
						<a href="#" onclick="return VuFind.OverDrive.followOverDriveDownloadLink('{$record.overDriveId}', '{$record.selectedFormat.format}')" class="btn btn-sm btn-primary">Download&nbsp;Again</a>
					{/if}

					{if $record.earlyReturn}
						<a href="#" onclick="return VuFind.OverDrive.returnOverDriveTitle('{$record.overDriveId}', '{$record.transactionId}');" class="btn btn-sm btn-warning">Return&nbsp;Now</a>
					{/if}
				</div>

				{* Include standard tools *}
				{* include file='EcontentRecord/result-tools.tpl' summId=$record.recordId id=$record.recordId shortId=$record.shortId ratingData=$record.ratingData recordUrl=$record.recordUrl showHoldButton=false *}
			</div>
		</div>
	</div>
</div>
{/strip}