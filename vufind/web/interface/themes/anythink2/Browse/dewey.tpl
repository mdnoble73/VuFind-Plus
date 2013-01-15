<script type="text/javascript" src="{$path}/services/Browse/ajax.js"></script>

<div id="page-content" class="content">
	<div class="browseNav" style="margin: 0px;">
	{include file="Browse/top_list.tpl" currentAction="Dewey"}
	</div>
    <div class="browseNav" style="margin: 0px;">
      <ul class="browse" id="list2">
        {foreach from=$defaultList item=area}
        <li><a href="" onclick="highlightBrowseLink('list2', this); LoadOptions('dewey-hundreds:%22{$area.0|escape:"url"}%22', 'dewey-tens', 'list3', 'list4', 'dewey-ones'); return false;">{$area.0|escape:"html"} ({$area.1})</a></li>
        {/foreach}
      </ul>
    </div>
    <div class="browseNav" style="margin: 0px;">
    <ul class="browse" id="list3">
    </ul>
    </div>
    <div class="browseNav" style="margin: 0px;">
    <ul class="browse" id="list4">
    </ul>
    </div>
</div>