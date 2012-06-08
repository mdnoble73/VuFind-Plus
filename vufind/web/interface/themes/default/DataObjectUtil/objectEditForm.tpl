<script type="text/javascript" src="{$path}/js/validate/jquery.validate.min.js" ></script>
{* Errors *}
{if isset($errors) && count($errors) > 0}
  <div id='errors'>
  {foreach from=$errors item=error}
    <div id='error'>{$error}</div>
  {/foreach}
  </div>
{/if}

{* Create the base form *}
<form id='objectEditor' method="post" {if $contentType}enctype="{$contentType}"{/if} action="{$submitUrl}">
  {literal}
  <script type="text/javascript">
  $(document).ready(function(){
    $("#objectEditor").validate();
  });
  </script>
  {/literal}
  
  <div class='editor'>
    <input type='hidden' name='objectAction' value='save' />
    <input type='hidden' name='id' value='{$id}' />
    
    {foreach from=$structure item=property}
      {assign var=propName value=$property.property}
      {assign var=propValue value=$object->$propName}
      {if ((!isset($property.storeDb) || $property.storeDb == true) && !($property.type == 'label' || $property.type == 'oneToManyAssociation' || $property.type == 'hidden' || $property.type == 'method'))}
        <div class='propertyInput' id="propertyRow{$propName}">
	        {* Output the label *}
	        <label for='{$propName}' class='objectLabel'>{$property.label}</label>
          
	        {* Output the editing control*}
	        {if $property.type == 'text' || $property.type == 'folder' || $property.type == 'integer'}
	          <br/>
	          <input type='{$property.type}' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} title='{$property.description}' class='{if $property.required}required{/if}'/>
	        
	        {elseif $property.type == 'url'}
	          <br/>
	          <input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} title='{$property.description}' class='url {if $property.required}required{/if}' />
      
	        {elseif $property.type == 'date'}
	          <input type='{$property.type}' name='{$propName}' id='{$propName}' value='{$propValue}' {if $property.maxLength}maxLength='10'{/if}  class='{if $property.required}required{/if} date'/>
	        
	        {elseif $property.type == 'partialDate'}
	          {assign var=propNameMonth value=$property.propNameMonth}
	          {assign var=propNameDay value=$property.propNameDay}
	          {assign var=propNameYear value=$property.propNameYear}
	          Month: <select name='{$propNameMonth}' id='{$propNameMonth}' >
	            <option value=""></option>
	            <option value="1" {if $object->$propNameMonth == '1'}selected='selected'{/if}>January</option>
	            <option value="2" {if $object->$propNameMonth == '2'}selected='selected'{/if}>February</option>
	            <option value="3" {if $object->$propNameMonth == '3'}selected='selected'{/if}>March</option>
	            <option value="4" {if $object->$propNameMonth == '4'}selected='selected'{/if}>April</option>
	            <option value="5" {if $object->$propNameMonth == '5'}selected='selected'{/if}>May</option>
	            <option value="6" {if $object->$propNameMonth == '6'}selected='selected'{/if}>June</option>
	            <option value="7" {if $object->$propNameMonth == '7'}selected='selected'{/if}>July</option>
	            <option value="8" {if $object->$propNameMonth == '8'}selected='selected'{/if}>August</option>
	            <option value="9" {if $object->$propNameMonth == '9'}selected='selected'{/if}>September</option>
	            <option value="10" {if $object->$propNameMonth == '10'}selected='selected'{/if}>October</option>
	            <option value="11" {if $object->$propNameMonth == '11'}selected='selected'{/if}>November</option>
	            <option value="12" {if $object->$propNameMonth == '12'}selected='selected'{/if}>December</option>
	            </select>
	          Day: <input type='text' name='{$propNameDay}' id='{$propNameDay}' value='{$object->$propNameDay}' maxLength='2' size='2'/>
	          Year: <input type='text' name='{$propNameYear}' id='{$propNameYear}' value='{$object->$propNameYear}' maxLength='4' size='4'/>
	        
	        {elseif $property.type == 'textarea' || $property.type == 'html' || $property.type == 'crSeparated'}
	          <br/><textarea name='{$propName}' id='{$propName}' rows='{$property.rows}' cols='{$property.cols}' title='{$property.description}' class='{if $property.required}required{/if}'>{$propValue|escape}</textarea>
	          {if $property.type == 'html'}
              <script type="text/javascript">
							{literal}
							CKEDITOR.replace( '{/literal}{$propName}{literal}',
							    {
							          toolbar : 'Full'
							    });
							{/literal}
							</script>
            {/if}
            
	        {elseif $property.type == 'password'}
	          <input type='password' name='{$propName}' id='{$propName}'/>
	          Repeat the Password:
	          <input type='password' name='{$propName}Repeat' />
	        
	        {elseif $property.type == 'currency'}
	          {assign var=propDisplayFormat value=$property.displayFormat}
	          <input type='text' name='{$propName}' id='{$propName}' value='{$propValue|string_format:$propDisplayFormat}'></input>
	        
	        {elseif $property.type == 'label'}
	          <div id='{$propName}'>{$propValue}</div>
	          
	        {elseif $property.type == 'html'}
            {include file="DataObjectUtil/htmlField.tpl"}
	        
	        {elseif $property.type == 'enum'}
	          <select name='{$propName}' id='{$propName}Select'>
	          {foreach from=$property.values item=propertyName key=propertyValue}
	            <option value='{$propertyValue}' {if $propValue == $propertyValue}selected='selected'{/if}>{$propertyName}</option>
	          {/foreach}
	          </select>
	        
	        {elseif $property.type == 'multiSelect'}
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
    {/foreach}
    <input type="submit" name="submit" value="Save Changes"/>
  </div>          
</form>