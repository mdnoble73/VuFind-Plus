{assign var=propName value=$property.property}
{assign var=propValue value=$object->$propName}
{if !isset($propValue) && isset($property.default)}
	{assign var=propValue value=$property.default}
{/if}
{if ((!isset($property.storeDb) || $property.storeDb == true) && !($property.type == 'label' || $property.type == 'oneToManyAssociation' || $property.type == 'hidden' || $property.type == 'method'))}
	<div class='propertyInput' id="propertyRow{$propName}">
		{* Output the label *}
		{if $property.type != 'section'}
			<label for='{$propName}' class='objectLabel'>{$property.label}</label>
		{/if}
		{* Output the editing control*}
		{if $property.type == 'section'}
			<fieldset class='fieldset-collapsible'>
				<legend>{$property.label}</legend>
				<div>
					{foreach from=$property.properties item=property}
						{include file="DataObjectUtil/property.tpl"}
					{/foreach}
				</div>
			</fieldset>
		{elseif $property.type == 'text' || $property.type == 'folder' || $property.type == 'integer'}
			<br/>
			<input type='{$property.type}' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} title='{$property.description}' class='{if $property.required}required{/if}'/>
		
		{elseif $property.type == 'url'}
			<br/>
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} title='{$property.description}' class='url {if $property.required}required{/if}' />

		{elseif $property.type == 'date'}
			<input type='{$property.type}' name='{$propName}' id='{$propName}' value='{$propValue}' {if $property.maxLength}maxLength='10'{/if}	class='{if $property.required}required{/if} date'/>
		
		{elseif $property.type == 'partialDate'}
			{include file="DataObjectUtil/partialDate.tpl"}
		
		{elseif $property.type == 'textarea' || $property.type == 'html' || $property.type == 'crSeparated'}
			{include file="DataObjectUtil/textarea.tpl"}
			
		{elseif $property.type == 'password'}
			{include file="DataObjectUtil/password.tpl"}
		
		{elseif $property.type == 'currency'}
			{include file="DataObjectUtil/currency.tpl"}
		
		{elseif $property.type == 'label'}
			<div id='{$propName}'>{$propValue}</div>
			
		{elseif $property.type == 'html'}
			{include file="DataObjectUtil/htmlField.tpl"}
		
		{elseif $property.type == 'enum'}
			{include file="DataObjectUtil/enum.tpl"}
		
		{elseif $property.type == 'multiSelect'}
			{include file="DataObjectUtil/multiSelect.tpl"}
			
		{elseif $property.type == 'image' || $property.type == 'file'}
			<br />
			{if $propValue}
				{if $property.type == 'image'}
					<img src='{$path}/files/thumbnail/{$propValue}'/>{$propValue}
					<input type='checkbox' name='remove{$propName}' id='remove{$propName}' /> Remove image.
					<br/>
				{else}
					Existing file: {$propValue}
					<input type='hidden' name='{$propName}_existing' id='{$propName}_existing' value='{$propValue|escape}' /> 
					
				{/if}
			{/if}
			{* Display a table of the association with the ability to add and edit new values *}
			<input type="file" name='{$propName}' id='{$propName}' size="80"/>
					
		{elseif $property.type == 'checkbox'}
			<input type='{$property.type}' name='{$propName}' id='{$propName}' {if ($propValue == 1)}checked='checked'{/if}/>
			
		{elseif $property.type == 'oneToMany'}
			{include file="DataObjectUtil/oneToMany.tpl"}
			
		{/if}
		
	</div>
{elseif $property.type == 'hidden'}	
	<input type='hidden' name='{$propName}' value='{$propValue}' />
{/if}
{if $property.showDescription}
	<div class='propertyDescription'>{$property.description}</div>
{/if}