<script  type="text/javascript" src="{$url}/js/ajax_common.js"></script>
<script  type="text/javascript" src="{$url}/services/Search/ajax.js"></script>
<script  type="text/javascript" src="{$url}/services/MyResearch/ajax.js"></script>
<script  type="text/javascript" src="{$path}/js/jcarousel/lib/jquery.jcarousel.min.js"></script>
<div id="page-content" class="content">
	<div id="sidebar">
    {include file="MyResearch/menu.tpl"}
      
    {include file="Admin/menu.tpl"}
  </div>
	
  <div id="main-content">
      {* Internal Grid *}
      <div class="resulthead">
      <h2 class="fav">{if $showRatings == 1}{translate text='Your Lists and Suggestions'}{else}{translate text='Your Lists'}{/if}</h2>
      </div>
      
      {if $showRatings == 1}
      <h3 class='listHeading'>Suggested Titles</h3>
      <div id='suggestionsPlaceholder'><img src='{$path}/interface/themes/default/images/ajax-loader.gif' alt='Loading...'></div>
      <script  type="text/javascript">
      {literal}
      $(document).ready(function (){getSuggestions();});
      {/literal}
      </script>
      {/if}
        
      <div class="yui-u">

      {if $listList}
      <div>
        {if $showRatings == 1}<h2 class="list">{translate text='Your Lists'}</h2>{/if}
         {foreach from=$listList item=list}
           {* Display the list title with a link to the list itself *}
           <h3><a href="{$url}/MyResearch/MyList/{$list->id}">{$list->title|escape:"html"}</a> ({$list->cnt})</h3>
           {* Display the description *}
           {if $list->description}<div class="listDescription alignleft">{$list->description|escape}</div>{/if}
           <div class='clearer'></div>
           {* Display the titles for the list *}
           <div id='listPlaceholder{$list->id}'><img src='{$path}/interface/themes/default/images/ajax-loader.gif' alt='Loading...'></div>
           <script  type="text/javascript">
			       {literal}
			       $(document).ready(function (){getListTitles('{/literal}{$list->id}{literal}');});
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
          <li><a href='{$url}/Search/Results?lookfor={$tag->tag|escape:"url"}&amp;type=tag'>{$tag->tag|escape:"html"}</a> ({$tag->cnt}) 
	          <a href='{$path}/MyResearch/RemoveTag?tagId={$tag->id}' onclick='return confirm("Are you sure you want to remove the tag \"{$tag->tag|escape:"javascript"}\" from all titles?");'>
	          <img alt="Delete Tag" src="{$path}/images/silk/tag_blue_delete.png">
	          </a>
          </li>
          {/foreach}
        </ul>
      </div>
      {/if}

      </div>
        
      {* End of Internal Grid *}
      <br />
      
    </div>
    
    {* Load Ratings *}
    <script type="text/javascript">
      $(document).ready(function() {literal} { {/literal}
        doGetRatings();
      {literal} }); {/literal}
    </script>


    {* End of first Body *}
    
  </div>
  
  
  

</div>
