<table class="table-striped table table-condensed">
	{foreach from=$details key='field' item='values'}
		<tr>
			<th>{$field|escape}</th>
			<td>
				<div style="width: 500px; overflow: auto;">
					{implode subject=$values glue=', ' sort=true}
				</div>
			</td>
		</tr>
	{/foreach}
</table>