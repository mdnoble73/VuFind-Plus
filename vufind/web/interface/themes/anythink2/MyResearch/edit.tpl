<div id="page-content" class="content">
	<div id="main-content">

		<div class="record">
		
			<h3 id='resourceTitle'>{$resource->title|escape:"html"}</h3>

			<form method="post" id="listResourceEditForm" action="{$path}/MyResearch/Edit">
				<input type="hidden" name="resourceId" value="{$resource->id}" />
				<div>
					{if empty($savedData)}
						<p>
						{if isset($listFilter)}
							{translate text='The record you selected is not part of the selected list.'}
						{else}
							{translate text='The record you selected is not part of any of your lists.'}
						{/if}
						</p>
					{else}
						<table>
							{foreach from=$savedData item=current}
								<tr>
									<td>{translate text='List'}: </td>
									<td>{$current.listTitle|escape:"html"}<input type="hidden" name="lists[]" value="{$current.listId}" /></td>
								</tr>
								<tr>
									<td>{translate text='Tags'}: </td>
									<td>
										<input type="text" name="tags{$current.listId}" value="{$current.tags|escape:"html"}" size="65" />
									</td>
								</tr>
								<tr>
									<td>{translate text='Notes'}: </td>
									<td>
										<textarea name="notes{$current.listId}" rows="3" cols="50">{$current.notes|escape:"html"}</textarea>
									</td>
								</tr>
								<tr><td></td><td><br /></td></tr>
							{/foreach}
							<tr><td></td><td><input type="submit" name="submit" value="{translate text='Save'}" /></td></tr>
						</table>
					{/if}
				</div>
			</form>

		</div>
	</div>
</div>