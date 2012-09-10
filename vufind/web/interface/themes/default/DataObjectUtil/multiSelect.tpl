{if isset($property.listStyle) && $property.listStyle == 'checkbox'}
	{foreach from=$property.values item=propertyName key=propertyValue}
		<br /><input name='{$propName}[{$propertyValue}]' type="checkbox" value='{$propertyValue}' {if is_array($propValue) && in_array($propertyValue, array_keys($propValue))}checked='checked'{/if}>{$propertyName}</input>
	{/foreach}
{else}
	<br />
	<select name='{$propName}' id='{$propName}' multiple="multiple">
	{foreach from=$property.values item=propertyName key=propertyValue}
		<option value='{$propertyValue}' {if $propValue == $propertyValue}selected='selected'{/if}>{$propertyName}</option>
	{/foreach}
	</select>
{/if}