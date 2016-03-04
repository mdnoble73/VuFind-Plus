{strip}
<div id="main-content" class="col-xs-12">
  <h1>Pika Administration</h1>
  <h2>Grouped Index (Searcher)</h2>
  <table class="citation">
    <tr>
      <th>Record Count: </th>
      <td>{$data.grouped.index.numDocs._content}</td>
    </tr>
    <tr>
      <th>Start Time: </th>
      <td>{$data.grouped.startTime._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
    </tr>
    <tr>
      <th>Last Modified: </th>
      <td>{$data.grouped.index.lastModified._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
    </tr>
    <tr>
      <th>Uptime: </th>
      <td>{$data.grouped.uptime._content|printms}</td>
    </tr>
  </table>
	<h2>Grouped Index (Master)</h2>
	<table class="citation">
		<tr>
			<th>Record Count: </th>
			<td>{$master_data.grouped.index.numDocs._content}</td>
		</tr>
		<tr>
			<th>Start Time: </th>
			<td>{$master_data.grouped.startTime._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
		</tr>
		<tr>
			<th>Last Modified: </th>
			<td>{$master_data.grouped.index.lastModified._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
		</tr>
		<tr>
			<th>Uptime: </th>
			<td>{$master_data.grouped.uptime._content|printms}</td>
		</tr>
	</table>
	<h2>Genealogy Index</h2>
	<table class="citation">
		<tr>
			<th>Record Count: </th>
			<td>{$data.genealogy.index.numDocs._content}</td>
		</tr>
		<tr>
			<th>Start Time: </th>
			<td>{$data.genealogy.startTime._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
		</tr>
		<tr>
			<th>Last Modified: </th>
			<td>{$data.genealogy.index.lastModified._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
		</tr>
		<tr>
			<th>Uptime: </th>
			<td>{$data.genealogy.uptime._content|printms}</td>
		</tr>
	</table>
</div>
{/strip}