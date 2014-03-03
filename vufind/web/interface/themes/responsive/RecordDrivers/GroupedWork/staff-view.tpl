<h4>Grouping Information</h4>
<table class="table-striped table table-condensed notranslate">
	{foreach from=$groupedWorkDetails key='field' item='value'}
	<tr>
		<th>{$field|escape}</th>
		<td>
			{$value}
		</td>
	</tr>
	{/foreach}
</table>

<h4>Solr Details</h4>
<table class="table-striped table table-condensed notranslate">
	{foreach from=$details key='field' item='values'}
		<tr>
			<th>{$field|escape}</th>
			<td>
				{implode subject=$values glue=', ' sort=true}
			</td>
		</tr>
	{/foreach}
</table>