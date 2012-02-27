<style type="text/css">
{literal}
#searchbar{
	background-image: url({/literal}{$url}{literal}/interface/themes/{$theme}/images/searchbar-gradient.png);
	background-repeat: repeat-x;
	width: 438px;
	position: relative;
}
#searchbar #type, #searchbar #lookfor{
	vertical-align: top;
	padding-left: 5px;
	margin-top: 7px;
}
#searchbar #lookfor{
	width: 180px;
}
#searchTools{
	text-align: right;
	padding-right: 32px;
}
#searchbarGo{
	position: absolute;
	top: 0px;
	right: 0px;
}
{/literal}
</style>

<div class="searchform">
    <form method="get" action="{$path}/Search/Results" id="searchForm" class="search">
      <div id="searchbar" >
      <img src='{$path}/interface/themes/{$theme}/images/searchbar-left.png' alt='' width="7px" height="34px" />
      <select name="type" id="type">
      {foreach from=$basicSearchTypes item=searchDesc key=searchVal}
        <option value="{$searchVal}"{if $searchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
      {/foreach}
      </select>
      <img src='{$url}/interface/themes/{$theme}/images/searchbar-search-label.png' alt='Search' width="55px" height="34px" />
      <input id="lookfor" type="text" name="lookfor" size="20"  />
      <input id="searchbarGo" type="image" width="32px" height="34px" name="submit" value="Go" src='{$url}/interface/themes/{$theme}/images/searchbar-go.png' alt='{translate text="Go"}' />
      </div>
   </form>
</div>

