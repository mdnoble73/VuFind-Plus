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