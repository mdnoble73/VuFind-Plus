<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
          <h1>{$pageTitle}</h1>
          
          {include file="Admin/savestatus.tpl"}
          
          <p>
            You are viewing the file at {$configPath}.
          </p>

          <form method="post">
            <textarea name="config_file" rows="20" cols="70" class="configEditor">{$configFile|escape}</textarea><br />
            <input type="submit" name="submit" value="Save">
          </form>
  </div>
</div>