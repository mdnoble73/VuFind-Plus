{strip}
<table id="{$propName}" class="{if $property.sortable}sortableProperty{/if}" >
<thead>
	<tr>
		{if $property.sortable}
			<th>Sort</th>
		{/if}
		{foreach from=$property.structure item=subProperty}
			{if in_array($subProperty.type, array('text', 'enum', 'date', 'checkbox', 'integer')) }
				<th>{$subProperty.label}</th>
			{/if}
		{/foreach}
		<th>Actions</th>
	</tr>
</thead>
<tbody>
{foreach from=$propValue item=subObject}
	<tr id="{$propName}{$subObject->id}">
		<input type="hidden" id="{$propName}Id_{$subObject->id}" name="{$propName}Id[{$subObject->id}]" value="{$subObject->id}"/>
		{if $property.sortable}
			<td>
			<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>
			<input type="hidden" id="{$propName}Weight_{$subObject->id}" name="{$propName}Weight[{$subObject->id}]" value="{$subObject->weight}"/>
			</td>
		{/if}
		{foreach from=$property.structure item=subProperty}
			{if in_array($subProperty.type, array('text', 'enum', 'date', 'checkbox', 'integer')) }
				<td>
					{assign var=subPropName value=$subProperty.property}
					{assign var=subPropValue value=$subObject->$subPropName}
					{if $subProperty.type=='text' || $subProperty.type=='date' || $subProperty.type=='integer'}
						<input type="text" name="{$propName}_{$subPropName}[{$subObject->id}]" value="{$subPropValue|escape}" class="{if $subProperty.type=='date'}datepicker{elseif $subProperty.type=="integer"}integer{/if}{if $subProperty.required == true} required{/if}"/>
					{elseif $subProperty.type=='checkbox'}
						<input type='checkbox' name='{$propName}_{$subPropName}[{$subObject->id}]' {if $subPropValue == 1}checked='checked'{/if}/>
					{else}
						<select name='{$propName}_{$subPropName}[{$subObject->id}]' id='{$propName}{$subPropName}_{$subObject->id}' {if $subProperty.required == true}class='required'{/if}>
						{foreach from=$subProperty.values item=propertyName key=propertyValue}
							<option value='{$propertyValue}' {if $subPropValue == $propertyValue}selected='selected'{/if}>{$propertyName}</option>
						{/foreach}
						</select>
					{/if}
				</td>
			{/if}
		{/foreach}
		<td>
		{* link to delete*}
		<input type="hidden" id="{$propName}Deleted_{$subObject->id}" name="{$propName}Deleted[{$subObject->id}]" value="false"/>
		<a href="#" onclick="if (confirm('Are you sure you want to delete this?')){literal}{{/literal}$('#{$propName}Deleted_{$subObject->id}').val('true');$('#{$propName}{$subObject->id}').hide(){literal}}{/literal};return false;"><img src="{$path}/images/silk/delete.png" alt="delete" /></a>{* link to delete *}
		{if $property.editLink neq ''}
			&nbsp;<a href='{$property.editLink}?objectAction=edit&widgetListId={$subObject->id}&widgetId={$widgetid}' alt='Edit SubLinks' title='Edit SubLinks'>
				<img src="{$path}/images/silk/link.png" alt="edit links" />
			</a>
		{elseif $property.canEdit}
			&nbsp;<a href='{$subObject->getEditLink()}' alt='Edit' title='Edit'>
				<img src="{$path}/images/silk/edit.png" alt="edit" />
			</a>
		{/if}
		</td>
	</tr>
{/foreach}
</tbody>
</table>
<div class="{$propName}Actions">
	<a href="#" onclick="addNew{$propName}();return false;"  class="button">Add New</a>
</div>
{/strip}
<script type="text/javascript">
	{literal}$(document).ready(function(){{/literal}
	{if $property.sortable}
		{literal}$('#{/literal}{$propName}{literal} tbody').sortable({
			update: function(event, ui){
				$.each($(this).sortable('toArray'), function(index, value){
					var inputId = '#{/literal}{$propName}Weight_' + value.substr({$propName|@strlen}); {literal}
					$(inputId).val(index +1);
				});
			}
		});
		{/literal}
	{/if}
	{literal}$('.datepicker').datepicker({dateFormat:"yy-mm-dd"});{/literal}
	{literal}});{/literal}
	var numAdditional{$propName} = 0;
	function addNew{$propName}{literal}(){
		numAdditional{/literal}{$propName}{literal} = numAdditional{/literal}{$propName}{literal} -1;
		var newRow = "<tr>";
		{/literal}
		newRow +=	"<input type='hidden' id='{$propName}Id_" + numAdditional{$propName} + "' name='{$propName}Id[" + numAdditional{$propName} + "]' value='" + numAdditional{$propName} + "'/>"
		{if $property.sortable}
			newRow += "<td><span class='ui-icon ui-icon-arrowthick-2-n-s'></span>";
			newRow += "<input type='hidden' id='{$propName}Weight_" + numAdditional{$propName} +"' name='{$propName}Weight[" + numAdditional{$propName} +"]' value='" + (100 - numAdditional{$propName})  +"'/>";
			newRow += "</td>";
		{/if}
		{foreach from=$property.structure item=subProperty}
			{if in_array($subProperty.type, array('text', 'enum', 'date', 'checkbox', 'integer')) }
				newRow += "<td>";
				{assign var=subPropName value=$subProperty.property}
				{assign var=subPropValue value=$subObject->$subPropName}
				{if $subProperty.type=='text' || $subProperty.type=='date' || $subProperty.type=='integer'}
					newRow += "<input type='text' name='{$propName}_{$subPropName}[" + numAdditional{$propName} +"]' value='{if $subProperty.default}{$subProperty.default}{/if}' class='{if $subProperty.type=="date"}datepicker{elseif $subProperty.type=="integer"}integer{/if}{if $subProperty.required == true} required{/if}'/>";
				{elseif $subProperty.type=='checkbox'}
					newRow += "<input type='checkbox' name='{$propName}_{$subPropName}[" + numAdditional{$propName} +"]' {if $subProperty.default == 1}checked='checked'{/if}/>";
				{else}
					newRow += "<select name='{$propName}_{$subPropName}[" + numAdditional{$propName} +"]' id='{$propName}{$subPropName}_" + numAdditional{$propName} +"' {if $subProperty.required == true}class='required'{/if}>";
					{foreach from=$subProperty.values item=propertyName key=propertyValue}
						newRow += "<option value='{$propertyValue}' {if $subProperty.default == $propertyValue}selected='selected'{/if}>{$propertyName}</option>";
					{/foreach}
					newRow += "</select>";
				{/if}
				newRow += "</td>";
			{/if}
		{/foreach}
		newRow += "</tr>";
		{literal}
		$('#{/literal}{$propName}{literal} tr:last').after(newRow);
		$('.datepicker').datepicker({dateFormat:"yy-mm-dd"});
	}
	{/literal}
</script>
