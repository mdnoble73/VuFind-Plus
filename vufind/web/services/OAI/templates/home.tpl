    <div align="center">
      <div style="width: 760px;" align="left">
      
      <h1>OAI Server</h1>
      <p>
        This OAI server is OAI 2.0 compliant.
        It currently does not use Resumption Tokens.<br>
        The OAI Server URL is: {$url}/OAI/Server
      </p>

      <h2>Available Functionality:</h2>
      <dl>
        <dt>Identify</dt>
        <dd>Returns the Identification information of this OAI Server.</dd>
        <form method="GET" action="{$path}/OAI/Server">
        <input type="hidden" name="verb" value="Identify">
        <table>
          <tr><td colspan="2">Accepts no additional parameters.</td></tr>
          <tr><td width="150" align="right"></td><td><input type="submit" name="submit" value="Go"></td></tr>
        </table>
        </form>

        <dt>ListIdentifiers</dt>
        <dd>Returns a listing of available identifiers</dd>
        <form method="GET" action="{$path}/OAI/Server">
        <input type="hidden" name="verb" value="ListIdentifiers">
        <table>
          <tr><td width="150" align="right">From: </td><td><input type="text" name="from"></td></tr>
          <tr><td width="150" align="right">Until: </td><td><input type="text" name="until"></td></tr>
          <tr><td width="150" align="right">Set: </td><td><input type="text" name="set"></td></tr>
          <tr><td width="150" align="right">Resumption Token: </td><td><input type="text" name="resumptionToken" disabled="true"></td></tr>
          <tr><td width="150" align="right"></td><td><input type="submit" name="submit" value="Go"></td></tr>
        </table>
        </form>

        <dt>ListMetadataFormats</dt>
        <dd>Returns a listing of available metdata formats.</dd>
        <form method="GET" action="{$path}/OAI/Server">
        <input type="hidden" name="verb" value="ListMetadataFormats">
        <table>
          <tr><td width="150" align="right">Identifier: </td><td><input type="text" name="identifier"></td></tr>
          <tr><td width="150" align="right"></td><td><input type="submit" name="submit" value="Go"></td></tr>
        </table>
        </form>

        <dt>ListSets</dt>
        <dd>Returns a listing of available metdata formats.</dd>
        <form method="GET" action="{$path}/OAI/Server">
        <input type="hidden" name="verb" value="ListSets">
        <table>
          <tr><td width="150" align="right">Metadata Prefix: </td><td><input type="text" name="metadataPrefix"></td></tr>
          <tr><td width="150" align="right">Resumption Token: </td><td><input type="text" name="resumptionToken" disabled="true"></td></tr>
          <tr><td width="150" align="right"></td><td><input type="submit" name="submit" value="Go"></td></tr>
        </table>
        </form>

        <dt>ListRecords</dt>
        <dd>Returns a listing of available metdata formats.</dd>
        <form method="GET" action="{$path}/OAI/Server">
        <input type="hidden" name="verb" value="ListRecords">
        <table>
          <tr><td width="150" align="right">From: </td><td><input type="text" name="from"></td></tr>
          <tr><td width="150" align="right">Until: </td><td><input type="text" name="until"></td></tr>
          <tr><td width="150" align="right">Set: </td><td><input type="text" name="set"></td></tr>
          <tr><td width="150" align="right">Metadata Prefix: </td><td><input type="text" name="metadataPrefix"></td></tr>
          <tr><td width="150" align="right">Resumption Token: </td><td><input type="text" name="resumptionToken" disabled="true"></td></tr>
          <tr><td width="150" align="right"></td><td><input type="submit" name="submit" value="Go"></td></tr>
        </table>
        </form>

        <dt>GetRecord</dt>
        <dd>Returns a listing of available metdata formats.</dd>
        <form method="GET" action="{$path}/OAI/Server">
        <input type="hidden" name="verb" value="GetRecord">
        <table>
          <tr><td width="150" align="right">Identifier: </td><td><input type="text" name="identifier"></td></tr>
          <tr><td width="150" align="right">Metadata Prefix: </td><td><input type="text" name="metadataPrefix"></td></tr>
          <tr><td width="150" align="right"></td><td><input type="submit" name="submit" value="Go"></td></tr>
        </table>
        </form>
      </dl>
      
      
      </div>
    </div>