<script type="text/javascript" src="{$url}/services/MyResearch/ajax.js"></script>
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div data-role="page" id="MyResearch-checkedout-overdrive">
	{include file="header.tpl"}
	<div data-role="content">
		{if $user->cat_username}
			
			{if count($holds.available) > 0}
				<h3>Available Holds</h3>
				<ul class="results checkedout-list" data-role="listview">
				{foreach from=$holds.available item=record}
					<li>
	        	<div class="result">
	        	<h3>{$record.title}</h3>
	        	<p><strong>Source: </strong>{$record.source}</p>
	        	<p><strong>Available: </strong>{$record.create|date_format}</p>
	        	<p><strong>Expires: </strong>{$record.expire|date_format}</p>
	        	</div>
	        	
	        	<div data-role="controlgroup">
	        		{* Options for the user to view online or download *}
							{foreach from=$record.links item=link}
								<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} data-role="button" rel="external">{$link.text}</a>
							{/foreach}
	        	</div>
	        </li>
		    {/foreach}
		    </ul>
	    {else}
	    	<div class='noItems'>You do not have any available holds on eContent.</div>
	    {/if}
	    
	    <h3>Unavailable Holds</h3>
	    {if count($holds.unavailable) > 0}
	    	<ul class="results checkedout-list" data-role="listview">
		    {foreach from=$holds.unavailable item=record}
		    	<li>
						{if !empty($record.recordId)}<a rel="external" href="{$path}/EcontentRecord/{$record.recordId|escape}">{/if}
						<div class="result">
	        	<h3>{$record.title}</h3>
	        	<p><strong>Source: </strong>{$record.source}</p>
	        	<p><strong>Created: </strong>{$record.createTime|date_format}</p>
	        	<p><strong>Position: </strong>{$record.position}</p>
	        	<p>
	        		{if $record.frozen}<span class='frozenHold'>{/if}{$record.status} {if $record.frozen}until {$record.reactivateDate|date_format}</span>{/if}
              {if strlen($record.freezeMessage) > 0}
                <div class='{if $record.freezeResult == true}freezePassed{else}freezeFailed{/if}'>
                  {$record.freezeMessage|escape}
                </div>
              {/if}
	        	</p>
	        	</div>
	        	
	        	<div data-role="controlgroup">
	        		{* Options for the user to view online or download *}
							{foreach from=$record.links item=link}
								<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} data-role="button" rel="external">{$link.text}</a>
							{/foreach}
	        	</div>
	        </li>
		    {/foreach}
		    </ul>
	    {else}
	    	<div class='noItems'>You do not have any eContent on hold</div>
	    {/if}
	    
  {else}
    You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
  {/if}
  </div>
</div>
<script type="text/javascript">
	$(document).ready(function() {literal} { {/literal}
		doGetRatings();
	{literal} }); {/literal}
</script>