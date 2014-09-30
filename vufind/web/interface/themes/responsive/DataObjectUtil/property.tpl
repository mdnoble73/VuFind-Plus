{assign var=propName value=$property.property}
{assign var=propValue value=$object->$propName}
{if !isset($propValue) && isset($property.default)}
	{assign var=propValue value=$property.default}
{/if}
{if ((!isset($property.storeDb) || $property.storeDb == true) && !($property.type == 'label' || $property.type == 'oneToManyAssociation' || $property.type == 'hidden' || $property.type == 'method'))}
	<div class='form-group' id="propertyRow{$propName}">
		{* Output the label *}
		{if $property.type == 'enum'}
			<label for='{$propName}Select'>{$property.label}</label>
		{elseif $property.type != 'section' && $property.type != 'checkbox'}
			<label for='{$propName}'>{$property.label}</label>
		{/if}
		{* Output the editing control*}
		{if $property.type == 'section'}
			<div class='panel-group' id="accordion_{$property.label|escapeCSS}">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title">
							<a data-toggle="collapse" data-parent="#accordion_{$property.label|escapeCSS}" href="#accordion_body_{$property.label|escapeCSS}">
								{$property.label}
							</a>
						</h4>
					</div>

					<div id="accordion_body_{$property.label|escapeCSS}" class="panel-collapse collapse">
						<div class="panel-body">
							{foreach from=$property.properties item=property}
								{include file="DataObjectUtil/property.tpl"}
							{/foreach}
						</div>
					</div>
				</div>
			</div>
		{elseif $property.type == 'text' || $property.type == 'folder'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} title='{$property.description}' class='form-control {if $property.required}required{/if}'/>
		{elseif $property.type == 'integer'}
			<input type='number' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} title='{$property.description}' class='form-control {if $property.required}required{/if}'/>
		{elseif $property.type == 'url'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} title='{$property.description}' class='form-control url {if $property.required}required{/if}' />
		{elseif $property.type == 'email'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} title='{$property.description}' class='form-control email {if $property.required}required{/if}' />
		{elseif $property.type == 'multiemail'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} title='{$property.description}' class='form-control multiemail {if $property.required}required{/if}' />
		{elseif $property.type == 'date'}
			<input type='{$property.type}' name='{$propName}' id='{$propName}' value='{$propValue}' {if $property.maxLength}maxLength='10'{/if}	class='form-control {if $property.required}required{/if} date'/>
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
			<div class="checkbox">
				<label for='{$propName}'>
					<input type='checkbox' name='{$propName}' id='{$propName}' {if ($propValue == 1)}checked='checked'{/if}/> {$property.label}
				</label>
			</div>

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