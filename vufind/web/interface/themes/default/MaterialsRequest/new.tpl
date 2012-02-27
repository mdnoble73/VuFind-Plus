<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js" ></script>
<div id="page-content" class="content">
	<div id="main-content">
		<h2>{translate text='Materials Request'}</h2>
		<div id="materialsRequest">
			<div class="materialsRequestExplanation">
				If you cannot find a title in our catalog, you can request the title via this form.  
			</div>
			<form id="materialsRequestForm" action="{$path}/MaterialsRequest/Submit" method="post">
				{if !$user}
					<div>
						<label for="username">{translate text='Username'}: </label>
						<input type="text" name="username" id="username" value="{$username|escape}" size="15" class="required"/>
					</div>
					<div>
						<label for="password">{translate text='Password'}: </label>
						<input type="password" name="password" id="password" size="15" class="required"/>
					</div>
				{/if}
				<div>
					<label for="format">Format:</label>
					<select name="format" class="required">
						<option value="book">Book</option>
						<option value="cd">CD</option>
						<option value="dvd">DVD</option>
						<option value="ebook">e-Book</option>
						<option value="eaudio">e-Audio</option>
						<option value="article">Article</option>
						<option value="cassette">Cassette</option>
						<option value="other">Other</option>
					</select>
				</div>
				
				<div>
					<label for="title">Title:</label>
					<input name="title" id="title" size="80" maxlength="255" class="required"/>
				</div>
				<div>
					<label for="author">Author:</label>
					<input name="author" id="author" size="80" maxlength="255" class="required"/>
				</div>
				<div>
					<label for="ageLevel">Age Level:</label>
					<select name="ageLevel">
						<option value="adult">Adult</option>
						<option value="teen">Teen</option>
						<option value="children">Children</option>
					</select>
				</div>
				<div>
					<label for="isbn_upc">ISBN/UPC:</label>
					<input name="isbn_upc" id="isbn_upc" size="15" maxlength="15"/>
				</div>
				<div>
					<label for="oclcNumber">OCLC Number</label>
					<input name="oclcNumber" id="oclcNumber" size="15" maxlength="30"/>
				</div>
				<div>
					<label for="publisher">Publisher:</label>
					<input name="publisher" id="publisher" size="80" maxlength="255"/>
				</div>
				<div>
					<label for="publicationYear">Publication Year:</label>
					<input name="publicationYear" id="publicationYear" size="4" maxlength="4"/>
				</div>
				<div>
					<label for="articleInfo">Article Info:</label>
					<input name="articleInfo" id="articleInfo" size="80" maxlength="255"/>
					<div class="fieldNotes">
					If you are requesting an article, please enter the Journal title, Article title, Author, Date, Volume, and Page number(s) of the article.
					</div> 
				</div>
				<div>
					<input type="radio" name="abridged" value="unabridged" id="unabridged" checked="checked"/><label for="unabridged">Unabridged</label> <input type="radio" name="abridged" value="abridged" id="abridged"/><label for="abridged">Abridged</label>
				</div>
				<div>
					<label for="about">How/where did you hear about this title:</label>
					<textarea name="about" id="about" rows="3" cols="80" class="required"></textarea>
				</div>
				<div>
					<label for="comments">Comments:</label>
					<textarea name="comments" id="comments" rows="3" cols="80"></textarea>
				</div>
				
				<div id="copyright">
					WARNING CONCERNING COPYRIGHT RESTRICTIONS The copyright law of the United States (Title 17, United States Code) governs the making of photocopies or other reproductions of copyrighted material. Under certain conditions specified in the law, libraries and archives are authorized to furnish a photocopy or other reproduction. One of these specified conditions is that the photocopy or reproduction is not to be used for any purpose other than private study, scholarship, or research. If a user makes a request for, or later uses, a photocopy or reproduction for purposes in excess of fair use, that user may be liable for copyright infringement. This institution reserves the right to refuse to accept a copying order if, in its judgment, fulfillment of the order would involve violation of copyright law.
				</div>
				<div>
					<input type="submit" value="Submit Materials Request" />
				</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	$("#materialsRequestForm").validate();
</script>