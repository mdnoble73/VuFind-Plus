<h1>Helpful Searching Tips</h1>


<ul class="HelpMenu">
 <h4>Search Tips</h4>
  <li><a href="#Simple Search">Simple Search</a></li>
  <li><a href="#Narrow">Narrow your search results</a></li>
  <li><a href="#Format">Search by format</a></li>
 
 <p></p>
 
 <h4>Advanced Search Tips</h4>
  
  <li><a href="#Wildcard Searches">Wildcard Searches</a></li>
  <li><a href="#Fuzzy Searches">Fuzzy Searches</a></li>
  <li><a href="#Proximity Searches">Proximity Searches</a></li>
  <li><a href="#Range Searches">Range Searches</a></li>
  <li><a href="#Boosting a Term">Boosting a Term</a></li>
  <li><a href="#Boolean operators">Boolean Operators</a>
    <ul>
      <li><a href="#OR">OR</a></li>
      <li><a href="#AND">AND</a></li>
      <li><a href="#+">+</a></li>
      <li><a href="#NOT">NOT</a></li>
      <li><a href="#-">-</a></li>
    </ul>
  <p></p>
  </li>
</ul>

<dl class="Content">
<h4>Search Tips</h4>
  

  <dt><a name="Simple Search"></a>Simple Search</dt>
  <dd>
  <p>Type in your search terms in the box next to <strong>Catalog Search</strong> and then click on the <strong>Go</strong> button. BRiAN will search for those terms in our catalog records and return a list of items with the most relevant titles at the top.
For example, search for titles on World War II:</p>
<pre class="code">world war II</pre>
<p>A list of over 1600 titles will load. The most relevant titles are the ones you see first.</p> </dd>

<dt><a name="Narrow"></a> Narrow your search results</dt>
  <dd>
    <p>Use the <strong>Title Details</strong> box on the right side on the results screen to narrow your search results. </p>
 <p>For example, if you are interested in adult fiction books set in World War II, type in the words World War II in the search box and click on Go.</p>
 <p>When the result list appears, look in the Title Details box. Under <strong>Collection</strong>, there is an Adult Fiction heading. Click on that heading and you’ll get a list of adult fiction books with World War II as a subject.</p>
<pre class="code"> <strong>Collection</strong><br />
Adult Non Fiction
Books For Children
Downloadable Audio
►Adult Fiction
Adult CD Audio Books... </pre>
  
You can narrow your search by –
<ul>
<li>Audience</li>
<li>Author</li>
<li>Subject</li>
<li>Collection</li> 
<li>Library location</li>
<li>Publication Year</li> 
<li>Literary Form</li>
<li>Language</li>
<li>Genre</li>
<li>Era</li>
<li>Region</li>
<li>Lists</li>
</ul>
</dd>
<br />

<dt><a name="Format"></a>Search by format</dt>

  <pre class="code">
Large Print 	Audio Books
eAudiobooks	eBooks</pre>
    <dd>
    <p>Many people are interested in searching our holdings for items in a particular format. BRiAN offers several easy ways to do this. 
  </p>
 <ol>
 <li>Conduct a simple search and then limit by format.</li>
  <ul>
  <li> Enter your search terms in the box and then click on Go. <br />
<strong>Example:</strong> Search for Harry Bosch (a fictional detective). Immediately above the list of results is a series of icons which represent various formats. </li>
  <li>Click on one of these icons to narrow your results list to that format.<br /> 
    <strong>Example: </strong>Click on the Audio Books icon. The list narrows to audio books with the character Harry Bosch.</li>
  </ul>
  <li>You can see a list of all of our items in a particular format by conducting a blank search and then clicking on the format icon.</li>
  <ul>
  <li>Delete any search terms in the search box. With the search box empty, click on Go.</li>
  <li> Click on one of the format icons. If you click on the icon for Large Print, for example, you will see a list of all of our Large Print books. </li>
  </ul>
  </ol>
</dd>


<p> <h4>Advanced Search Tips</h4></p>

  <dt><a name="Wildcard Searches"></a>Wildcard Searches</dt>
  <dd>
    <p>To perform a single character wildcard search use the <strong>?</strong> symbol.</p>
    <p>For example, to search for "text" or "test" you can use the search:</p>
    <pre class="code">te?t</pre>
    <p>To perform a multiple character, 0 or more, wildcard search use the <strong>*</strong> symbol.</p>
    <p>For example, to search for test, tests or tester, you can use the search: </p>
    <pre class="code">test*</pre>
    <p>You can also use the wildcard searches in the middle of a term.</p>
    <pre class="code">te*t</pre>
    <p>Note: You cannot use a * or ? symbol as the first character of a search.</p>
  </dd><dt><a name="Fuzzy Searches"></a>Fuzzy Searches</dt>
 <dd>
   <p>Use the tilde <strong>~</strong> symbol at the end of a <strong>Single</strong> word Term. For example to search for a term similar in spelling to "roam" use the fuzzy search: </p>
    <pre class="code">roam~</pre>
    <p>This search will find terms like foam and roams.</p>
    <p>An additional parameter can specify the required similarity. The value is between 0 and 1, with a value closer to 1 only terms with a higher similarity will be matched. For example:</p>
    <pre class="code">roam~0.8</pre>
    <p>The default that is used if the parameter is not given is 0.5.</p>
  </dd>
 <dt><a name="Proximity Searches"></a>Proximity Searches</dt>
  <dd>
    <p>
      Use the tilde <strong>~</strong> symbol at the end of a <strong>Multiple</strong> word Term.
      For example, to search for economics and keynes that are within 10 words apart:
    </p>
    <pre class="code">"economics Keynes"~10</pre>
  </dd>
  
  {literal}
  <dt><a name="Range Searches"></a>Range Searches</dt>
  <dd>
    <p>
      To perform a range search you can use the <strong>{ }</strong> characters.
      For example to search for a term that starts with either A, B, or C:
    </p>
    <pre class="code">{A TO C}</pre>
    <p>
      The same can be done with numeric fields such as the Year:
    </p>
    <pre class="code">[2002 TO 2003]</pre>
  </dd>
  {/literal}
  
  <dt><a name="Boosting a Term"></a>Boosting a Term</dt>
  <dd>
    <p>
      To apply more value to a term, you can use the <strong>^</strong> character.
      For example, you can try the following search:
    </p>
    <pre class="code">economics Keynes^5</pre>
    <p>Which will give more value to the term "Keynes"</p>
  </dd>
  
  <dt><a name="Boolean operators"></a>Boolean Operators</dt>
  <dd>
    <p>
      Boolean operators allow terms to be combined with logic operators.
      The following operators are allowed: <strong>AND</strong>, <strong>+</strong>, <strong>OR</strong>, <strong>NOT</strong> and <strong>-</strong>.
    </p>
    <p>Note: Boolean operators must be ALL CAPS</p>
    <dl>
      <dt><a name="OR"></a>OR</dt>
      <dd>
        <p>The <strong>OR</strong> operator is the default conjunction operator. This means that if there is no Boolean operator between two terms, the OR operator is used. The OR operator links two terms and finds a matching record if either of the terms exist in a record.</p>
        <p>To search for documents that contain either "economics Keynes" or just "Keynes" use the query:</p>
        <pre class="code">"economics Keynes" Keynes</pre>
        <p>or</p>
        <pre class="code">"economics Keynes" OR Keynes</pre>
      </dd>
      
      <dt><a name="AND"></a>AND</dt>
      <dd>
        <p>The AND operator matches records where both terms exist anywhere in the field of a record.</p>
        <p>To search for records that contain "economics" and "Keynes" use the query: </p>
        <pre class="code">"economics" AND "Keynes"</pre>
      </dd>
      <dt><a name="+"></a>+</dt>
      <dd>
        <p>The "+" or required operator requires that the term after the "+" symbol exist somewhere in the field of a record.</p>
        <p>To search for records that must contain "economics" and may contain "Keynes" use the query:</p>
        <pre class="code">+economics Keynes</pre>
      </dd>
      <dt><a name="NOT"></a>NOT</dt>
      <dd>
        <p>The NOT operator excludes records that contain the term after NOT.</p>
        <p>To search for documents that contain "economics" but not "Keynes" use the query: </p>
        <pre class="code">"economics" NOT "Keynes"</pre>
        <p>Note: The NOT operator cannot be used with just one term. For example, the following search will return no results:</p>
        <pre class="code">NOT "economics"</pre>
      </dd>
      <dt><a name="-"></a>-</dt>
      <dd>
        <p>The <Strong>-</strong> or prohibit operator excludes documents that contain the term after the "-" symbol.</p>
        <p>To search for documents that contain "economics" but not "Keynes" use the query: </p>
        <pre class="code">"economics" -"Keynes"</pre>
      </dd>
    </dl>
  </dd>
</dl>
