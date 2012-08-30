<script type="text/javascript" src="{$path}/services/MyResearch/ajax.js"></script>
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div data-role="page" id="MyResearch-checkedout-overdrive">
	{include file="header.tpl"}
	<div data-role="content">
		{if $user}
			{if count($checkedOut) > 0}
				<ul class="results checkedout-list" data-role="listview">
				{foreach from=$checkedOut item=record}
					<li>
						{if !empty($record.recordId)}<a rel="external" href="{$path}/EcontentRecord/{$record.recordId|escape}">{/if}
						<div class="result">
	        	<h3>{$record.title}</h3>
	        	<p><strong>Source: </strong>{$record.source}</p>
	        	<p><strong>Checked Out: </strong>{$record.checkoutdate|date_format}</p>
	        	<p><strong>Due: </strong>
	        		{$record.duedate|date_format}
	        		{if $record.overdue}
                <span class='overdueLabel'>OVERDUE</span>
              {elseif $record.daysUntilDue == 0}
                <span class='dueSoonLabel'>(Due today)</span>
              {elseif $record.daysUntilDue == 1}
                <span class='dueSoonLabel'>(Due tomorrow)</span>
              {elseif $record.daysUntilDue <= 7}
                <span class='dueSoonLabel'>(Due in {$record.daysUntilDue} days)</span>
              {/if}
	        	</p>
	        	<p><strong>Hold Queue: </strong>{$record.holdQueueLength}</p>
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
	    	<div class='noItems'>You do not have any eContent checked out</div>
	    {/if}
  {else}
    You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
  {/if}
  </div>
</div>
