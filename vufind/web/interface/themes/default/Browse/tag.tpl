<script type="text/javascript" src="{$path}/services/Browse/ajax.js"></script>

<div id="page-content" class="content">
   <div class="browseNav" style="margin: 0px;">
     {include file="Browse/top_list.tpl" currentAction="Tag"}
   </div>
   <div class="browseNav" style="margin: 0px;">
     <ul class="browse" id="list2">
       <li {if $findby == "alphabetical"} class="active"{/if}><a href="{$path}/Browse/Tag?findby=alphabetical">{translate text="By Alphabetical"}</a></li>
       <li {if $findby == "popularity"} class="active"{/if}><a href="{$path}/Browse/Tag?findby=popularity">{translate text="By Popularity"}</a></li>
       <li {if $findby == "recent"} class="active"{/if}><a href="{$path}/Browse/Tag?findby=recent">{translate text="By Recent"}</a></li>
     </ul>
   </div>
   {if !empty($alphabetList)}
     <div class="browseNav" style="margin: 0px;">
       <ul class="browse" id="list3">
       {foreach from=$alphabetList item=letter}
         <li {if $startLetter == $letter}class="active" {/if}style="float: none;">
           <a href="{$path}/Browse/Tag?findby=alphabetical&amp;letter={$letter|escape:"url"}">{$letter|escape:"html"}</a>
         </li>
       {/foreach}
       </ul>
     </div>
   {/if}
   <div class="browseNav" style="margin: 0px;">
   <ul class="browse" id="list4">
   {foreach from=$tagList item=tag}
     <li style="float: none;"><a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"} ({$tag->cnt})</a></li>
   {/foreach}
   </ul>
   </div>
</div>