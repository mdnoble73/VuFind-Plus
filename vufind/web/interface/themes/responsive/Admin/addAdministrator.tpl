<div id="page-content" class="content row">
  <div id="sidebar" class="col-md-3">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content" class="col-md-9">
    {if $error}
      <div class='error'>{$error}</div>
    {/if}
    <form name='addAdministrator' method="post" enctype="multipart/form-data" class="form-horizontal">
	    <fieldset>
		    <legend>Setup a new administrator</legend>
	      <input type='hidden' name='objectAction' value='processNewAdministrator' />
		    <div class="form-group">
	        <label for='login' class='col-sm-2 control-label'>Barcode</label>
			    <div class="col-sm-10">
				    <input type='text' name='login' id='login'/>
				    <div class='help-block'>Enter the barcode for the user who should be given administration privileges</div>
			    </div>
		    </div>


		    <div class="form-group">
			    {assign var=property value=$structure.roles}
			    {assign var=propName value=$property.property}
			    <label for='{$propName}' class='control-label'>Roles</label>
			    <div class="controls">
			      {* Display the list of roles to add *}
			      {if isset($property.listStyle) && $property.listStyle == 'checkbox'}
			        {foreach from=$property.values item=propertyName key=propertyValue}
				        <label class="checkbox">
			            <input name='{$propName}[{$propertyValue}]' type="checkbox" value='{$propertyValue}' {if is_array($propValue) && in_array($propertyValue, array_keys($propValue))}checked='checked'{/if} />{$propertyName}
				        </label>
			        {/foreach}
			      {else}
			        <select name='{$propName}' id='{$propName}' multiple="multiple">
			        {foreach from=$property.values item=propertyName key=propertyValue}
			          <option value='{$propertyValue}' {if $propValue == $propertyValue}selected='selected'{/if}>{$propertyName}</option>
			        {/foreach}
			        </select>
			      {/if}
				  </div>
				</div>
		    <div class="form-group">
					<div class="controls">
	          <input type="submit" name="submit" value="Update User" class="btn btn-primary"/>  <a href='{$path}/Admin/{$toolName}?objectAction=list' class="btn">Return to List</a>
					</div>
		    </div>
	    </fieldset>
    </form>
  </div>
</div>
