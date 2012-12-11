<select name='{$propName}' id='{$propName}Select'>
{foreach from=$property.values item=propertyName key=propertyValue}
	<option value='{$propertyValue}' {if $propValue == $propertyValue}selected='selected'{/if}>{$propertyName}</option>
{/foreach}
</select>