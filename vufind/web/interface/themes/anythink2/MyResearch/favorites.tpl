<script type="text/javascript" src="{$url}/services/MyResearch/ajax.js"></script>
<div id="sidebar-wrapper"><div id="sidebar">
	{include file="MyResearch/menu.tpl"}
	{include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>{translate text='My Favorites'}</h1>
  {if !empty($listList) || !empty($tagList)}
  <p>Keep your favorite items organized using lists and tags. Manage these items below or create new ones straight from your search results.</p>
  {else}
  Keep your favorite items organized using lists and tags. Haven't created any lists yet? Click the "add tag" or "add to list" links right from your search results to get started. 
  {/if}
  {if $userNoticeFile}
    {include file=$userNoticeFile}
  {/if}
  <div class="favorites-container">
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
          recommendedScroller.loadTitlesFrom('{/literal}{$url}{literal}/Search/AJAX?method=GetListTitles&id=strands:HOME-3&scrollerName=Recommended', false);
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
          recentlyViewedScroller.loadTitlesFrom('{/literal}{$url}{literal}/Search/AJAX?method=GetListTitles&id=strands:HOME-4&scrollerName=RecentlyViewed', false);
        });
        {/literal}
      </script>
    {/if}
    <div class="yui-u">
      {if $listList}
        <div>
          {foreach from=$listList item=list}
            <div id="list{$list->id}" class="titleScrollerWrapper">
              <div id="list{$list->id}Header" class="titleScrollerHeader">
                <span class="listTitle"><a href="{$url}/MyResearch/MyList/{$list->id}">{$list->title|escape:"html"}</a></span>
                <span class="list-edit"><a href='{$url}/MyResearch/MyList/{$list->id}'>View and Edit List</a></span>
              </div>
              <div id="titleScrollerList{$list->id}" class="titleScrollerBody">
              <div class="leftScrollerButton enabled" onclick="list{$list->id}Scroller.scrollToLeft();"></div>
              <div class="rightScrollerButton" onclick="list{$list->id}Scroller.scrollToRight();"></div>
              <div class="scrollerBodyContainer">
                <div class="scrollerBody" style="display:none"></div>
                <div class="scrollerLoadingContainer">
                  <img id="scrollerLoadingImageList{$list->id}" class="scrollerLoading" src="{$path}/interface/themes/default/images/loading_large.gif" alt="Loading..." />
                </div>
              </div>
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
				</div>
			{/if}
			{if $tagList}
				<div>
					<h2 class="tag">{translate text='My Tags'}</h2>
					<ul>
						{foreach from=$tagList item=tag}
						<li><a href='{$url}/Search/Results?lookfor={$tag->tag|escape:"url"}&amp;type=tag'>{$tag->tag|escape:"html"}</a> ({$tag->cnt})
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
</div>
<script type="text/javascript">
{literal}
	$(document).ready(function() {
		doGetRatings();
	});
{/literal}
</script>
