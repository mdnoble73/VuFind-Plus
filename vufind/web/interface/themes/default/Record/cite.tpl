{strip}
{if $lightbox}
<div onmouseup="this.style.cursor='default';" id="popupboxHeader" class="header">
	<a onclick="hideLightbox(); return false;" href="">close</a>
	{translate text='Title Citation'}
</div>
<div id="popupboxContent" class="content">
{/if}
{if $citationCount < 1}
	{translate text="No citations are available for this record"}.
{else}
	<div style="text-align: left;">
		{if false && $ama}
			<b>{translate text="AMA Citation"}</b>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$ama}
			</p>
		{/if}
		
		{if $apa}
			<b>{translate text="APA Citation"}</b>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$apa}
			</p>
		{/if}
		
		{if $chicagoauthdate}
			<b>{translate text="Chicago / Turabian - Author Date Citation"}</b>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$chicagoauthdate}
			</p>
		{/if}
		
		{if $chicagohumanities}
			<b>{translate text="Chicago / Turabian - Humanities Citation"}</b>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$chicagohumanities}
			</p>
		{/if}

		{if $mla}
			<b>{translate text="MLA Citation"}</b>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$mla}
			</p>
		{/if}

	</div>
	<div class="note">{translate text="Citation formats are based on standards as of July 2010.  Citations contain only title, author, edition, publisher, year published."}</div>
{/if}
{if $lightbox}
</div>
{/if}
{/strip}