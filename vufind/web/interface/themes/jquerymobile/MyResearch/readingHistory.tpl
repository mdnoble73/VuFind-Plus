{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div data-role="page" id="MyResearch-reading-history">
	{include file="header.tpl"}

	<div data-role="content">
		{if $user->cat_username}
			{if $profile.web_note}
				<div id="web_note">{$profile.web_note}</div>
			{/if}
			<h3>{translate text='My Reading History'} {if $historyActive == true}<span id='readingListWhatsThis' onclick="$('#readingListDisclaimer').toggle();">(What's This?)</span>{/if}</h3>
			<div class="resulthead">
				{if $userNoticeFile}
					{include file=$userNoticeFile}
				{/if}

				<div id='readingListDisclaimer' {if $historyActive == true}style='display: none'{/if}>
					The library takes seriously the privacy of your library records. Therefore, we do not keep track of what you borrow after you return it.
					However, our automated system has a feature called "My Reading History" that allows you to track items you check out.
					Participation in the feature is entirely voluntary. You may start or stop using it, as well as delete any or all entries in "My Reading History" at any time.
					If you choose to start recording "My Reading History", you agree to allow our automated system to store this data.
					The library staff does not have access to your "My Reading History", however, it is subject to all applicable local, state, and federal laws, and under those laws, could be examined by law enforcement authorities without your permission.
					If this is of concern to you, you should not use the "My Reading History" feature.
				</div>
			</div>

				<div class="page">
					<form id='readingListForm' action ="{$fullPath}">
						<div>
							<input name='readingHistoryAction' id='readingHistoryAction' value='' type='hidden' />


							{if $transList}
								<ul class="results checkedout-list" data-role="listview">

									{foreach from=$transList item=record name="recordLoop" key=recordKey}
										<li>
											{if !empty($record.recordId)}<a rel="external" href="{$path}/EcontentRecord/{$record.recordId|escape}">{/if}
												<div class="result">
													<h3>
													{if !$record.title|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$record.title|regex_replace:"/(\/|:)$/":""}{/if}
													{if $record.title2}
														<br/>{$record.title2|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
													{/if}
													</h3>
													{if $record.author}
														<p>{translate text='by'}
														{if is_array($record.author)}
															{foreach from=$summAuthor item=author}
																{$author|highlight:$lookfor}
															{/foreach}
														{else}
															{$record.author|highlight:$lookfor}
														{/if}
														</p>
													{/if}
													{if $record.publicationDate}<p><strong>{translate text='Published'} </strong>{$record.publicationDate|escape}</p>{/if}
													<p><strong>Format: </strong>
														{if is_array($record.format)}
															{foreach from=$record.format item=format}
																{translate text=$format}
															{/foreach}
														{else}
															{translate text=$record.format}
														{/if}
													</p>

													<p><strong>Checked Out: </strong>
														{$record.checkout|escape}{if $record.lastCheckout} to {$record.lastCheckout|escape}{/if}
													</p>
												</div>
											{if !empty($record.recordId)}</a>{/if}

										</li>
									{/foreach}
							</ul>

						{else if $historyActive == true}
							{* No Items in the history, but the history is active *}
							You do not have any items in your reading list.	It may take up to 3 hours for your reading history to be updated after you start recording your history.
						{/if}
						{if $transList} {* Don't double the actions if we don't have any items *}
							<div data-role="controlgroup">
								{if $historyActive == true}
									{if $transList}
										<a data-role="button" rel="external" data-ajax="false" onclick='return deletedMarkedAction()' href="#">Delete Marked</a>
										<a data-role="button" rel="external" data-ajax="false" onclick='return deleteAllAction()' href="#">Delete All</a>
									{/if}
									<a data-role="button" rel="external" data-ajax="false" onclick="return exportListAction();">Export To Excel</a>
									<a data-role="button" rel="external" data-ajax="false" onclick="return optOutAction({if $transList}true{else}false{/if})" href="#">Stop Recording My Reading History</a>
								{else}
									<a data-role="button" rel="external" data-ajax="false" onclick='return optInAction()' href="#">Start Recording My Reading History</a>
								{/if}
							</div>

							<div data-role="controlgroup" data-type="horizontal" style="textalign:center">
								{if $pageLinks.back} {$pageLinks.back|replace:' href=':' data-role="button" data-rel="back" href='} {/if}
								{if $pageLinks.next} {$pageLinks.next|replace:' href=':' rel="external" data-role="button" href='} {/if}
							</div>
						{/if}
					</div>
				</form>
			</div>
		{else}
			<div class="page">
				You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
			</div>
		{/if}
	</div>
	{include file="footer.tpl"}
</div>