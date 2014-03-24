<div class="striped">
  {foreach from=$prospectorResults item=prospectorTitle}
	  {if $similar.recordId != -1}
		  <div class="row">
			  <div class="col-md-4">
		      <a href="{$prospectorTitle.link}" rel="external" onclick="window.open (this.href, 'child'); return false"><h5>{$prospectorTitle.title|removeTrailingPunctuation|escape}</h5></a>
			  </div>

		    <div class="col-md-2">
				  {if $prospectorTitle.author}<small>{$prospectorTitle.author|escape}</small>{/if}
		    </div>
			  <div class="col-md-2">
				  {if $prospectorTitle.pubDate}<small>{$prospectorTitle.pubDate|escape}</small>{/if}
			  </div>
			  <div class="col-md-2">
				  {if $prospectorTitle.format}<small>{$prospectorTitle.format|escape}</small>{/if}
			  </div>
			  <div class="col-md-2">
				  <a href="{$prospectorTitle.link}" rel="external" onclick="window.open (this.href, 'child'); return false" class="btn btn-sm">View&nbsp;In&nbsp;Prospector</a>
				</div>
		  </div>
	  {/if}
  {/foreach}
</div>
