			</div> <!-- content -->
			<div data-role="footer" data-position="fixed">
				{if $Logout}
					<a href="/MyResearch/Logout" data-theme="b" data-icon='idclreader-logout'>Logout</a>
				{/if}			
			</div>
		</div>
		
		{literal}
			<script type="text/javascript">
				$(document).ready(function()
				{
					$('.linkDetail').click(function(){
						$.mobile.changePage("/EcontentRecord/" + $(this).attr('titleId').replace('econtentRecord',''));
					});
				});
			</script>
		{/literal}
	</body>
</html>