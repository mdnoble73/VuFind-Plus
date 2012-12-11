<div id="formattedMarcRecord">
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