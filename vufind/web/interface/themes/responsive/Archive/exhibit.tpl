{strip}
<div class="col-xs-12">
	<div class="main-project-image">
		<img src="{$main_image}" class="img-responsive" usemap="#map"/>

		<map name="map">
			<!-- #$-:Image map file created by GIMP Image Map plug-in -->
			<!-- #$-:GIMP Image Map plug-in by Maurits Rijk -->
			<!-- #$-:Please do not edit lines starting with "#$" -->
			<!-- #$VERSION:2.3 -->
			<!-- #$AUTHOR:jfields -->
			<area shape="poly" coords="362,244,386,239,394,242,403,244,413,254,425,272,419,299,400,312,374,312,357,307,341,291,339,277,342,257,353,248" href="/Archive/ssb:34/Image" />
			<area shape="poly" coords="348,203,362,235,399,235,416,206,381,188" href="/Archive/ssb:40/Image" />
			<area shape="poly" coords="404,237,418,207,457,218,469,246,431,256" href="/Archive/ssb:39/Image" />
			<area shape="poly" coords="433,262,433,292,474,307,492,275,469,248" href="/Archive/ssb:38/Image" />
			<area shape="poly" coords="407,319,432,296,474,311,471,338,451,354,424,354" href="/Archive/ssb:37/Image" />
			<area shape="poly" coords="364,318,402,319,421,357,400,372,371,372,347,355" href="/Archive/ssb:36/Image" />
			<area shape="poly" coords="336,294,361,318,343,354,319,351,296,331,295,307" href="/Archive/ssb:35/Image" />
			<area shape="poly" coords="295,246,335,259,335,292,292,306,277,287,277,260" href="/Archive/ssb:42/Image" />
			<area shape="poly" coords="323,206,345,206,360,237,334,256,297,243,299,226,311,212" href="/Archive/ssb:41/Image" />
			<area shape="poly" coords="97,261,114,215,132,194,150,203,156,215,105,264" href="/Archive/ssb:50/Image" />
			<area shape="poly" coords="515,142,520,105,578,123,582,154,546,162" href="/Archive/ssb:53/Image" />
			<area shape="poly" coords="627,362,673,360,669,407,620,411,613,362" href="/Archive/ssb:54/Image" />
			<area shape="poly" coords="199,357,254,357,253,387,364,386,363,403,344,403,342,438,191,436" href="/Archive/ssb:51/Image" />
			<area shape="poly" coords="534,544,566,528,598,503,612,490,644,513,615,542,582,563" href="/Archive/ssb:55/Image" />
			<area shape="poly" coords="553,234,563,326,636,330,627,275,624,233" href="/Archive/ssb:48/Image" />
			<area shape="poly" coords="630,243,641,317,656,314,676,281,654,247" href="/Archive/ssb:49/Image" />
			<area shape="poly" coords="546,165,609,164,633,203,612,223,546,227" href="/Archive/ssb:52/Image" />
		</map>
	</div>

	<h2>
		{$title|escape}
	</h2>
	{$description}

	{* TODO: Figure out why the heck lightbox doesn't work *}
	<div class="related-exhibit-images">
		{foreach from=$relatedImages item=image}
			<a href="{$repositoryUrl}/{$image.image}" data-lightbox="related_images" {if $image.title}data-title="{$image.title}"{/if}>
				<img src="{$repositoryUrl}/{$image.thumbnail}" {if $image.shortTitle}alt="{$image.shortTitle}"{/if}/>
			</a>
		{/foreach}
	</div>
</div>
{/strip}
