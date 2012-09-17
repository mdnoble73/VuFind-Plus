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
		<div class="myAccountTitle">{translate text='Your Checked Out Items In OverDrive'}</div>
		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}

		{if $overDriveCheckedOutItems}
			<div class='sortOptions'>
				Hide Covers <input type="checkbox" onclick="$('.imageColumnOverdrive').toggle();"/>
			</div>
		{/if}

		{if count($overDriveCheckedOutItems) > 0}
			<table class="myAccountTable">
				<thead>
					<tr><th class='imageColumnOverdrive'></th><th>Title</th><th>Checked Out On</th><th>Expires</th><th>Format</th><th>Rating</th><th></th></tr>
				</thead>
				<tbody>
				{foreach from=$overDriveCheckedOutItems item=record}
					<tr>
						<td rowspan="{$record.numRows}" class='imageColumnOverdrive'><img src="{$record.imageUrl}"></td>
						<td>
							{if $record.recordId != -1}<a href="{$path}/EcontentRecord/{$record.recordId}/Home">{/if}{$record.title}{if $record.recordId != -1}</a>{/if}
							{if $record.subTitle}<br/>{$record.subTitle}{/if}
							{if strlen($record.record->author) > 0}<br/>by: {$record.record->author}{/if}
						</td>
						<td>{$record.checkedOutOn}</td>
						<td>{$record.expiresOn}</td>
						<td>{$record.format}</td>
						<td>{* Ratings cell*}
							{if $record.recordId != -1}
							<div id ="searchStars{$record.recordId|escape}" class="resultActions">
								<div class="rate{$record.recordId|escape} stat">
									<div class="statVal">
										<span class="ui-rater">
											<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px"></span></span>
											(<span class="ui-rater-rateCount-{$record.recordId|escape} ui-rater-rateCount">0</span>)
										</span>
									</div>
									<div id="saveLink{$record.recordId|escape}">
										{if $showFavorites == 1} 
											<a href="{$path}/Record/{$record.recordId|escape:"url"}/Save" style="padding-left:8px;" onclick="getLightbox('Record', 'Save', '{$record.recordId|escape}', '', '{translate text='Add to favorites'}', 'Record', 'Save', '{$record.recordId|escape}'); return false;">{translate text='Add to'} <span class='myListLabel'>MyLIST</span></a>
										{/if}
										{if $user}
											<div id="lists{$record.recordId|escape}"></div>
											<script type="text/javascript">
												getSaveStatuses('{$record.recordId|escape:"javascript"}');
											</script>
										{/if}
									</div>
								</div>
								<script type="text/javascript">
									$(function() {literal} { {/literal}
										$('.rate{$record.recordId|escape}').rater({literal}{ {/literal}module: 'EcontentRecord', recordId: {$record.recordId},  rating:0.0, postHref: '{$path}/Record/{$record.recordId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
										{literal} } {/literal}
									);
								</script>
								{assign var=id value=$record.recordId}
								{include file="Record/title-review.tpl"}
 							</div>

							<script type="text/javascript">
								addRatingId('{$record.recordId|escape:"javascript"}');
							</script>
							{/if}
						</td>
						<td>
							<a href="{$record.downloadLink}" class="button">Download</a>
						</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		{else}
			<div class='noItems'>You do not have any titles from OverDrive checked out</div>
		{/if}
		<div id='overdriveMediaConsoleInfo'>
		<img src="{$path}/images/overdrive.png" width="125" height="42" alt="Powered by Overdrive" class="alignleft"/>
		<p>To access OverDrive titles, you will need the <a href="http://www.overdrive.com/software/omc/">OverDrive&reg; Media Console&trade;</a>.  
		If you do not already have the OverDrive Media Console, you may download it <a href="http://www.overdrive.com/software/omc/">here</a>.</p>
		<div class="clearer">&nbsp;</div> 
		<p>Need help transferring a title to your device or want to know whether or not your device is compatible with a particular format?
		Click <a href="http://help.overdrive.com">here</a> for more information. 
		</p>
		 
	</div>
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
