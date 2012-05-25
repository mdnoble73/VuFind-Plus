<div id="main-content">
  <h1>{translate text='Materials Request Result'}</h1>
  {if $success == 0}
    <div class="error">
    {$error}
    </div>
  {else}
    <div class="result">
    <p>Your request for <em>{$materialsRequest->title}</em> by <strong>{$materialsRequest->author}</strong> has been submitted to the Anythink team for review. Check the status of your request at any time on your <a href='{$path}/MaterialsRequest/MyRequests'>account page</a>. Please keep in mind that it can take two to six weeks for your items to arrive. </p>
    </div>
  {/if}
</div>
