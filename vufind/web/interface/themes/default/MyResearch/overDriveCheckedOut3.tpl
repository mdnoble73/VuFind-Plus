{strip}
<script type="text/javascript" src="{$path}/services/MyResearch/ajax.js"></script>
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>

	<div id="main-content">
	{if $user}
		{if $profile.web_note}
			<div id="web_note">{$profile.web_note}</div>
		{/if}

		<div class="myAccountTitle">{translate text='Your Checked Out Items In OverDrive'}</div>
		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}

		{if count($overDriveCheckedOutItems) > 0}
			<div id='overdriveMediaConsoleInfo'>
				<p>
					Need help opening your title?  We have <a href="{$path}/Help/eContentHelp" onclick="return ajaxLightbox('{$path}/Help/eContentHelp?lightbox=true')">step by step instructions</a> for most formats and devices <a href="{$path}/Help/eContentHelp" onclick="return ajaxLightbox('{$path}/Help/eContentHelp?lightbox=true')">here</a>.<br/>
					If you still need help after following the instructions, please fill out this <a href="{$path}/Help/eContentSupport" onclick="return showEContentSupportForm()">support form</a>.
				</p>
				<img src="{$path}/images/overdrive.png" width="125" height="42" alt="Powered by Overdrive" class="alignleft"/>
				<p><b>To access OverDrive Audio Books on all devices and eBooks on most devices, you will need the <a href="http://www.overdrive.com/software/omc/">OverDrive&reg; Media Console&trade;</a></b>.
					If you do not already have the OverDrive Media Console, you may download it <a href="http://www.overdrive.com/software/omc/">here</a>.</p>
				<div class="clearer">&nbsp;</div>
				<img src="{$path}/images/160x41_Get_Adobe_Digital_Editions.png" alt="Get Adobe Digital Editions" class="alignleft"/>
				<p><b>To access eBooks on PCs and Macs, you will need a copy of <a href="http://www.adobe.com/products/digital-editions/download.html">Adobe&reg; Digital Editions</a></b>.
					If you do not already have a copy of Adobe Digital Editions, you may download it <a href="http://www.adobe.com/products/digital-editions/download.html">here</a>.</p>
				<div class="clearer">&nbsp;</div>
			</div>

			{if $overDriveCheckedOutItems}
				<div class='sortOptions'>
					Hide Covers <input type="checkbox" onclick="$('.imageColumnOverdrive').toggle();"/>
				</div>
			{/if}
			<table class="myAccountTable">
				<thead>
					<tr><th class='imageColumnOverdrive'></th><th>Title</th><th>Expires</th><th>Rating</th><th></th></tr>
				</thead>
				<tbody>
				{foreach from=$overDriveCheckedOutItems item=record}
					<tr>
						<td {if $record.numRows}rowspan="{$record.numRows}"{/if} class='imageColumnOverdrive'>
							<img src="{$record.imageUrl}" alt="Cover Image" />
						</td>
						<td>
							{if $record.recordId != -1}<a href="{$path}/EcontentRecord/{$record.recordId}/Home">{/if}{$record.title}{if $record.recordId == -1}OverDrive Record {$record.overDriveId}{/if}{if $record.recordId != -1}</a>{/if}
							{if $record.subTitle}<br/>{$record.subTitle}{/if}
							{if strlen($record.record->author) > 0}<br/>by: {$record.record->author}{/if}
						</td>
						<td>{$record.expiresOn|replace:' ':'&nbsp;'}</td>
						<td>{* Ratings cell*}
							{if $record.recordId != -1}
							<div class="resultActions">
								{include file="EcontentRecord/title-rating.tpl" ratingClass="" recordId=$record.recordId shortId=$record.recordId ratingData=$record.ratingData}
								{assign var=id value=$record.recordId}
								{include file="EcontentRecord/title-review.tpl"}
 							</div>

							{/if}
						</td>
						<td>
							{if $record.formatSelected}
								You downloaded the <strong>{$record.selectedFormat.name}</strong> format of this title.
								<br/>
								<a href="#" class="button" onclick="followOverDriveDownloadLink('{$record.overDriveId}', '{$record.selectedFormat.format}')">Download&nbsp;Again</a>
							{else}
								<label for="downloadFormat_{$record.overDriveId}">Select one format to download.</label>
								<select name="downloadFormat_{$record.overDriveId}" id="downloadFormat_{$record.overDriveId}">
									<option value="-1">Select a Format</option>
									{foreach from=$record.formats item=format}
										<option value="{$format.id}">{$format.name}</option>
									{/foreach}
								</select>
								<a href="#" onclick="selectOverDriveDownloadFormat('{$record.overDriveId}')" class="button">Download</a>
							{/if}

							{if $record.earlyReturn}
								<br/>
								<br/>
								<a href="#" onclick="returnOverDriveTitle('{$record.overDriveId}', '{$record.transactionId}');" class="button">Return&nbsp;Now</a>
							{/if}
							{if $record.overdriveRead}
								<br/>
								<br/>
								<a href="#" onclick="followOverDriveDownloadLink('{$record.overDriveId}', 'ebook-overdrive')" class="button">Read&nbsp;Online with OverDrive&nbsp;Read</a>
							{/if}
						</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		{else}
			<div class='noItems'>You do not have any titles from OverDrive checked out</div>
		{/if}

	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
{/strip}