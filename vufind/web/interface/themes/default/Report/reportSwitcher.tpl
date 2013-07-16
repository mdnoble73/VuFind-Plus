<div id="reportSwitcher">
	Active Report:
	<select onchange="changeActiveReport()" id="reportSwitcherSelect">
		<option value="dashboard" data-destination="{$path}/Report/Dashboard" {if $action == "Dashboard"}selected="selected"{/if}>Dashboard</option>
		<option value="pageviews" data-destination="{$path}/Report/PageViews" {if $action == "PageViews"}selected="selected"{/if}>Page Views</option>
		<option value="searches" data-destination="{$path}/Report/Searches" {if $action == "Searches"}selected="selected"{/if}>Searches</option>
		<option value="ilsIntegration" data-destination="{$path}/Report/ILSIntegration" {if $action == "ILSIntegration"}selected="selected"{/if}>ILSIntegration</option>
	</select>
</div>
