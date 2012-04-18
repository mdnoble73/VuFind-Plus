<script type="text/javascript" src="{$path}/js/validate/jquery.validate.min.js" ></script>
 {literal}
  <script type="text/javascript">
  	function deleteLink(linkId)
  	{
  		if(confirm('Are you sure you want to delete this link?'))
  		{
  			$('#toDelete_' + linkId).val(1);
  			$('#linkId_' + linkId).fadeOut(500);
  		}
  	}
  	
  	var numNewLink = 0;
  	
  	function addNewLink()
  	{
  		var htmlLink =  "<tr'>";
	  	   	htmlLink += "	<td>";
	  	   	htmlLink += "		<input type='hidden' name='newLink[" + numNewLink + "]' value='" + numNewLink + "'/>";
	  	   	htmlLink += "		<input type='text' size='40' name='nameNewLink[" + numNewLink + "]' value=''/>";
	  	   	htmlLink += "	</td>";
	  	   	htmlLink += "	<td>";
	  	   	htmlLink += "		<input type='text' size='40' name='linkNewLink[" + numNewLink + "]' value=''/>";
	  	   	htmlLink += "	</td>";
	  	   	htmlLink += "	<td>";
			htmlLink += "		&nbsp;";
	  	   	htmlLink += "	</td>";
	  	   	htmlLink += "</tr>";
	  	   	$('#widgetsListLinks').append(htmlLink);
  		numNewLink++;
  	}
  	
  </script>
  {/literal}
{css filename="listWidget.css"}
<div id="page-content" class="content">
  {if $error}<p class="error">{$error}</p>{/if} 
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}

    {include file="Admin/menu.tpl"}
  </div>
  	  
	  <div id="main-content">
	   <h1>Edit Links</h1>
	  <div id="header">
  	  		<h2> <a href='/Admin/ListWidgets?objectAction=edit&id={$widgetId}'>{$widgetName}</a> | {$widgetListName}</h2>
  	  </div>
	  	
	  	 <form id='objectEditor' method="post" enctype="multipart/form-data">  
	  	   <table id='widgetsListLinks'>
	  	   <thead>
	  	   	<tr>
	  	   		<th>Name</th>
	  	   		<th>Link</th>
	  	   		<th>&nbsp;</th>
	  	   	</tr>
	  	   </thead>
	  	   <tbody>
	  	   {foreach from=$availableLinks item=link}
	  	   		<tr id='linkId_{$link->id}'>
	  	   			<td>
	  	   				<input type='hidden' id='toDelete_{$link->id}' name='toDelete_{$link->id}' value='0'/>
	  	   				<input type='hidden' name='id[{$link->id}]' value='{$link->id}'/>
	  	   				<input type='hidden' name='listWidgetListsId[{$link->id}]' value='{$link->listWidgetListsId}'/>
	  	   				<input type='text' size='40' name='name[{$link->id}]' value='{$link->name}'/>
	  	   			</td>
	  	   			<td>
	  	   				<input type='text' size='40' name='link[{$link->id}]' value='{$link->link}'/>
	  	   			</td>
	  	   			<td>
						<a href="#" onclick='deleteLink({$link->id});'>
							<img src="{$path}/images/silk/delete.png" alt="Delete Link" title="Delete Link"/>
						</a>
	  	   			</td>
	  	   		</tr>
	  	   {/foreach}
	  	   </tbody>
	  	   </table>
	  	   <div class="Actions">
				<a href="#" onclick="addNewLink();return false;"  class="button">Add New</a>
			</div>
	  	   <input type='hidden' name='objectAction' value='save' />
	  	   <input type="submit" name="submit" value="Save Changes"/>
	  	   </form>
	  	   
</div>