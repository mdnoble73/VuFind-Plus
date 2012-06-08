<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js" ></script>
<script type="text/javascript" src="{$path}/js/econtent.js" ></script>
<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>{if $isNew}Add an eContent File{else}Edit eContent File{/if}</h1>
  {$editForm}
</div>
