{strip}
	<div class="archiveComponentContainer nopadding col-sm-12 col-md-6">
		<div class="archiveComponent horizontalComponent">
			<div class="archiveComponentBody">
				<div class="archiveComponentBox">
					<div class="archiveComponentHeader">Random Image</div>
					<div class="archiveComponentRandomImage row">
						<figure class="" title="{$randomObject.label|escape}">
							<a href='{$randomObject.link}'>
								<img src="{$randomObject.image}" alt="{$randomObject.label|escape}">
								<figcaption class="explore-more-category-title">
									<strong>{$randomObject.label|truncate:120}</strong>
								</figcaption>
							</a>
						</figure>
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}