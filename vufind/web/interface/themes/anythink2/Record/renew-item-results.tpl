<div id='renew_results'>
	<div class='hold_result_title header'>
		Renewal Results
		<a href="#" onclick='hideLightbox();return false;' class="closeIcon">Close <img src="{$path}/images/silk/cancel.png" alt="close" /></a>
	</div>
	<div class = "content">
		<ol>
		{foreach from=$renew_results item=renewalResult}
			<li class='{if $renewalResult.result == true}renewPassed{else}renewFailed{/if}'>{$renewalResult.title} - {$renewalResult.message}</li>
		{/foreach}
		</ol>
	</div>
</div>
