{strip}
{* Add availability as needed *}
{if $showAvailability && $availability && count($availability) > 0}
	<hr />
	<fieldset class='availabilitySection'>
		<legend>Owned by these libraries</legend>
		<div>
			<table class="holdingsTable">
				<thead>
					<tr><th>Library</th><th>Owned</th><th>Available</th></tr>
				</thead>
				<tbody>
					{foreach from=$availability item=availabilityItem}
						<tr><td>{$availabilityItem->getLibraryName()}</td><td>{$availabilityItem->copiesOwned}</td><td>{$availabilityItem->availableCopies}</td></tr>
					{/foreach}
				</tbody>
			</table>
			<div class="note">
				{if strcasecmp($source, 'OverDrive') == 0}
					Note: Copies owned by the Digital library are available to patrons of any Marmot Library.  Titles owned by a specific library are only available for use by patrons of that library.
				{/if}
			</div>
		</div>
	</fieldset>
{/if}

{if $showOverDriveConsole}
	<fieldset id='overdriveMediaConsoleInfo'>
		<legend>Required Software</legend>
		<div>
			<img src="{$path}/images/overdrive.png" width="125" height="42" alt="Powered by Overdrive" class="alignleft"/>
			<p>This title requires the <a href="http://www.overdrive.com/software/omc/">OverDrive&reg; Media Console&trade;</a> to use the title.
			If you do not already have the OverDrive Media Console, you may download it <a href="http://www.overdrive.com/software/omc/">here</a>.</p>
			<div class="clearer">&nbsp;</div>
			<p>Need help transferring a title to your device or want to know whether or not your device is compatible with a particular format?
			Click <a href="http://help.overdrive.com">here</a> for more information.
			</p>
		</div>
	</fieldset>
{/if}

{if $showAdobeDigitalEditions}
	<fieldset id='digitalEditionsInfo'>
		<legend>Required Software</legend>
		<div>
			<p>Once checked out, ePUB titles may be read on the web without additional software.</p>
			<p>
			<a href="http://www.adobe.com/products/digital-editions/download.html" ><img src="{$path}/images/160x41_Get_Adobe_Digital_Editions.png" alt ="Get Adobe Digital Editions" class="alignleft"/></a>
			To download ePUB and PDF titles to your eReader you must have <a href="http://www.adobe.com/products/digitaleditions/#fp">Adobe Digital Editions</a> installed on your computer.  If you do not already have the Adobe Digital Editions, you may download it <a href="http://www.adobe.com/products/digital-editions/download.html">here</a>.
			</p>
			<p>
			Need help transferring a title to your device or want to know whether or not your device is compatible with a particular format? <a href='http://marmot.org/node/58'>Contact your local library</a>.
			</p>
		</div>
	</fieldset>
{/if}
<script type="text/javascript">
	collapseFieldsets();
</script>
{/strip}