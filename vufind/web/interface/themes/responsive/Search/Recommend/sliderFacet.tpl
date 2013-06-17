<form id='{$title}Filter' action='{$fullPath}'>
	<div>
		{if $title == 'lexile_score'}
			<div id="lexile-range"></div>
		{/if}
		<label for="{$title}from" class='yearboxlabel'>From:</label>
		<input type="text" size="4" maxlength="4" class="yearbox" name="{$title}from" id="{$title}from" value="" />
		<label for="{$title}to" class='yearboxlabel'>To:</label>
		<input type="text" size="4" maxlength="4" class="yearbox" name="{$title}to" id="{$title}to" value="" />
		{* To make sure that applying this filter does not remove existing filters we need to copy the get variables as hidden variables *}
		{foreach from=$smarty.get item=parmValue key=paramName}
			{if is_array($smarty.get.$paramName)}
				{foreach from=$smarty.get.$paramName item=parmValue2}
				{* Do not include the filter that this form is for. *}
					{if strpos($parmValue2, $title) === FALSE}
						<input type="hidden" name="{$paramName}[]" value="{$parmValue2|escape}" />
					{/if}
				{/foreach}
			{else}
				<input type="hidden" name="{$paramName}" value="{$parmValue|escape}" />
			{/if}
		{/foreach}
		<input type="submit" value="Go" id="goButton" />
		{if $title == 'lexile_score'}
			<script type="text/javascript">{literal}
				$(function() {
					$( "#lexile-range" ).slider({
						range: true,
						min: 0,
						max: 2500,
						step: 10,
						values: [ 0, 2500 ],
						slide: function( event, ui ) {
							$( "#lexile_scorefrom" ).val( ui.values[ 0 ] );
							$( "#lexile_scoreto" ).val( ui.values[ 1 ] );
						}
					});
					$( "#lexile_scorefrom" ).change(function (){
						$( "#lexile-range" ).slider( "values", 0, $( "#lexile_scorefrom" ).val());
					});
					$( "#lexile_scoreto" ).change(function (){
						$( "#lexile-range" ).slider( "values", 1, $( "#lexile_scoreto" ).val());
					});
				});{/literal}
			</script>
		{/if}
	</div>
</form>