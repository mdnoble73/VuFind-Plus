{strip}
<script type="text/javascript" src="{$path}/services/MyResearch/ajax.js"></script>

<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		
		{include file="Admin/menu.tpl"}
	</div>
	
	<div id="main-content">
			
		{* Internal Grid *}
		<div class="myAccountTitle">{translate text='Your Lists and Suggestions'}</div>
			
		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}
			
		{if $showStrands && $user->disableRecommendations == 0}
			{assign var="scrollerName" value="Recommended"}
			{assign var="wrapperId" value="recommended"}
			{assign var="scrollerVariable" value="recommendedScroller"}
			{assign var="scrollerTitle" value="Recommended for you"}
			{include file=titleScroller.tpl}
		
			<script type="text/javascript">
				{literal}
				var recommendedScroller;
				$(document).ready(function (){
					recommendedScroller = new TitleScroller('titleScrollerRecommended', 'Recommended', 'recommended');
					recommendedScroller.loadTitlesFrom('{/literal}{$path}{literal}/Search/AJAX?method=GetListTitles&id=strands:HOME-3&scrollerName=Recommended', false);
				});
				{/literal}
			</script>

			{assign var="scrollerName" value="RecentlyViewed"}
			{assign var="wrapperId" value="recentlyViewed"}
			{assign var="scrollerVariable" value="recentlyViewedScroller"}
			{assign var="scrollerTitle" value="Recently Browsed"}
			{include file=titleScroller.tpl}
		
			<script type="text/javascript">
			{literal}
			var recentlyViewedScroller;
			$(document).ready(function (){
				recentlyViewedScroller = new TitleScroller('titleScrollerRecentlyViewed', 'RecentlyViewed', 'recentlyViewed');
				recentlyViewedScroller.loadTitlesFrom('{/literal}{$path}{literal}/Search/AJAX?method=GetListTitles&id=strands:HOME-4&scrollerName=RecentlyViewed', false);
			});
			{/literal}
			</script>
		{/if}
				
		<div class="yui-u">
		
			{if $showRatings == 1 && $user->disableRecommendations == 0}
				<div id="titleScrollerSuggestion" class="titleScrollerWrapper">
					<div id="titleScrollerSuggestionHeader" class="titleScrollerHeader">
						<span class="listTitle resultInformationLabel">Suggested Titles</span>
					</div>
					<div id="titleScrollerListSuggestion" class="titleScrollerBody">
						<div class="leftScrollerButton enabled" onclick="suggestionScroller.scrollToLeft();"></div>
						<div class="rightScrollerButton" onclick="suggestionScroller.scrollToRight();"></div>
						<div class="scrollerBodyContainer">
							<div class="scrollerBody" style="display:none">
							</div>
							<div class="scrollerLoadingContainer">
								<img id="scrollerLoadingImageListSuggestion" class="scrollerLoading" src="{$path}/interface/themes/default/images/loading_large.gif" alt="Loading..." />
							</div>
						</div>
						<div class="clearer"></div>
						<div id="titleScrollerSelectedTitleSuggestion" class="titleScrollerSelectedTitle"></div>
						<div id="titleScrollerSelectedAuthorSuggestion" class="titleScrollerSelectedAuthor"></div>
					</div>		
				</div>
				<script	type="text/javascript">
				{literal}
				$(document).ready(function (){getSuggestions();});
				{/literal}
				</script>
			{/if}

			{if $listList}
				<div>
					{foreach from=$listList item=list}
						<div id="list{$list->id}" class="titleScrollerWrapper">
							<div id="list{$list->id}Header" class="titleScrollerHeader">
								<span class="listTitle resultInformationLabel"><a href="{$path}/MyResearch/MyList/{$list->id}">{$list->title|escape:"html"}</a></span>
								<a href='{$path}/MyResearch/MyList/{$list->id}'><span class='seriesLink'>View and Edit List</span></a>
							</div>
							<div id="titleScrollerList{$list->id}" class="titleScrollerBody">
								<div class="leftScrollerButton enabled" onclick="list{$list->id}Scroller.scrollToLeft();"></div>
								<div class="rightScrollerButton" onclick="list{$list->id}Scroller.scrollToRight();"></div>
								<div class="scrollerBodyContainer">
									<div class="scrollerBody" style="display:none">
									</div>
									<div class="scrollerLoadingContainer">
										<img id="scrollerLoadingImageList{$list->id}" class="scrollerLoading" src="{$path}/interface/themes/default/images/loading_large.gif" alt="Loading..." />
									</div>
								</div>
								<div class="clearer"></div>
								<div id="titleScrollerSelectedTitleList{$list->id}" class="titleScrollerSelectedTitle"></div>
								<div id="titleScrollerSelectedAuthorList{$list->id}" class="titleScrollerSelectedAuthor"></div>
							</div>		
						</div>
						<script type="text/javascript">
							{literal}
							$(document).ready(function (){
							list{/literal}{$list->id}{literal}Scroller = new TitleScroller('titleScrollerList{/literal}{$list->id}{literal}', 'List{/literal}{$list->id}{literal}', 'list{/literal}{$list->id}{literal}');
								
							var url = path + "/MyResearch/AJAX";
							var params = "method=GetListTitles&listId=" + {/literal}{$list->id}{literal};;
							var fullUrl = url + "?" + params;
							list{/literal}{$list->id}{literal}Scroller.loadTitlesFrom(fullUrl);
							});
							{/literal}
						</script>
					{/foreach}
					<div class='clearer'></div>
				</div>
			{/if}

			{if $tagList}
			<div>
				<h3 class="tag">{translate text='Your Tags'}</h3>
				
				<ul class="bulleted">
					{foreach from=$tagList item=tag}
					<li><a href='{$path}/Search/Results?lookfor={$tag->tag|escape:"url"}&amp;type=tag'>{$tag->tag|escape:"html"}</a> ({$tag->cnt}) 
						<a href='{$path}/MyResearch/RemoveTag?tagId={$tag->id}' onclick='return confirm("Are you sure you want to remove the tag \"{$tag->tag|escape:"javascript"}\" from all titles?");'>
						<img alt="Delete Tag" src="{$path}/images/silk/tag_blue_delete.png" />
						</a>
					</li>
					{/foreach}
				</ul>
			</div>
			{/if}

		</div>
			
	</div>
		
	{* Load Ratings *}
	<script type="text/javascript">
		$(document).ready(function() {literal} { {/literal}
			doGetRatings();
		{literal} }); {/literal}
	</script>
	{* End of first Body *}
</div>
{/strip}