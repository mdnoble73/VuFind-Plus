<button onclick="return VuFind.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Reload Cover</button>
<button onclick="return VuFind.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Reload Enrichment</button>
{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('catalogging'))}
<button onclick="return VuFind.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Reindex</button>
{/if}


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
