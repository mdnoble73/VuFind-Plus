<div id="main-content">
  <h1>VuFind Administration</h1>
  <h2>Grouped Index</h2>
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
	<h2>Grouped 2 Index</h2>
	<table class="citation">
		<tr>
			<th>Record Count: </th>
			<td>{$data.grouped2.index.numDocs._content}</td>
		</tr>
		<tr>
			<th>Start Time: </th>
			<td>{$data.grouped2.startTime._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
		</tr>
		<tr>
			<th>Last Modified: </th>
			<td>{$data.grouped2.index.lastModified._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
		</tr>
		<tr>
			<th>Uptime: </th>
			<td>{$data.grouped2.uptime._content|printms}</td>
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
