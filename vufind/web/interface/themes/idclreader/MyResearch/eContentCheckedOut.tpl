<script type="text/javascript" src="/services/MyResearch/ajax.js"></script>
{if $user}
	{if count($checkedOut) > 0}
		<div data-role="collapsible-set" data-theme="a" data-content-theme="c">
			{foreach from=$checkedOut item=record}
			
				
				{if $record.overdue}
					{assign var="warningDueDate" value="OVERDUE"} 
				{/if}
				{if $record.daysUntilDue == 0}
		           {assign var="warningDueDate" value="Due today"}
		        {/if}
		        {if $record.daysUntilDue == 1}
		         	{assign var="warningDueDate" value="Due tomorrow"}
		        {/if}
			
				<div data-role="collapsible">
					<h3>{$record.title}{if $warningDueDate neq ""}&nbsp;<span class='warningDueDate'>({$warningDueDate})</span>{/if}</h3>
					<p>
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
							<ul data-role="listview" data-theme="a" data-inset="true">
            					<li><a href="/EcontentRecord/{$record.recordId|substr:14:10}">View Item Record</a></li>
            					<li data-role="list-divider" data-divider-theme="b">Access to the eContent</li>
            					{foreach from=$record.links item=link}
            					 	{assign var="returnLink" value=$link.text|contains:"Return"} 
									{if !$returnLink}
										<li>
											<a href="{if $link.url}{$link.url}{else}#{/if}" target='_blank' rel="external">
												<img src="/interface/themes/{$theme}/images/{$link.item_type}.png" alt="France" class="ui-li-icon">{$link.text}
											</a>
										</li>
									{elseif $link.typeReturn == 1}
										<li>
											<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} target='_blank' rel="external">
												{$link.text}
											</a>
										</li>
									{else}
										<li><a href="#" rel='{$record.recordId|substr:14:10}' class='returnItemCheckedOut'>Return Now</a></li>
									{/if}
								{/foreach}
            				</ul>
					</p>
				</div>
			{/foreach}
			{literal}
				<script type="text/javascript">
					$('.returnItemCheckedOut').bind('click', function () {
						returnEpubIdclReader($(this).attr('rel'));
					});
			    </script>
			{/literal}
		</div>
	{else}
	    	<p>You do not have any eContent checked out</p>
	{/if}
{else}
	<p>You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.</p>
{/if}