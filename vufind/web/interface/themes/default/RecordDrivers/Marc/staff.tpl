<div class="row">
	<div class="result-label col-xs-2">Grouped Work ID: </div>
	<div class="col-xs-10 result-value">
		{$recordDriver->getPermanentId()}
	</div>
</div>

<div class="row">
	<div class="col-xs-12">
		<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">Go To Grouped Work</a>
		{if $classicUrl && $classicId}
			<a href="{$classicUrl}/record={$classicId|escape:"url"}&amp;searchscope={$millenniumScope}" class="btn btn-sm btn-info">View in Classic</a>
		{/if}
	</div>
</div>


{if $marcRecord}
	<div id="formattedMarcRecord">
		<h3>MARC Record</h3>
		<table class="citation" border="0">
			<tbody>
				{*Output leader*}
				<tr><th>LEADER</th><td colspan="3">{$marcRecord->getLeader()}</td></tr>
				{foreach from=$marcRecord->getFields() item=field}
					{if get_class($field) == "File_MARC_Control_Field"}
						<tr><th>{$field->getTag()}</th><td colspan="3">{$field->getData()|escape|replace:' ':'&nbsp;'}</td></tr>
					{else}
						<tr><th>{$field->getTag()}</th><th>{$field->getIndicator(1)}</th><th>{$field->getIndicator(2)}</th><td>
						{foreach from=$field->getSubfields() item=subfield}
						<strong>|{$subfield->getCode()}</strong>&nbsp;{$subfield->getData()|escape}
						{/foreach}
						</td></tr>
					{/if}

				{/foreach}
			</tbody>
		</table>
	</div>
{/if}

{if $solrRecord}
	<div id="formattedSolrRecord">
		<h3>Solr Record</h3>
		<dl>
			{foreach from=$solrRecord key='field' item='values'}
				<dt>{$field|escape}</dt>
				<dd>{implode subject=$values glue=", "}</dd>
			{/foreach}
		</dl>
	</div>
{/if}

{if $overDriveProductRaw}
	<div id="formattedSolrRecord">
		<h3>OverDrive Product Record</h3>
		{formatJSON subject=$overDriveProductRaw}
		<h3>OverDrive MetaData</h3>
		{formatJSON subject=$overDriveMetaDataRaw}
	</div>
{/if}