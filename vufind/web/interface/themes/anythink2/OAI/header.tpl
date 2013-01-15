      <header{if !empty($recordStatus)} status="{$recordStatus|escape}"{/if}>
        <identifier>{$recordId|escape}</identifier>
        <datestamp>{$recordDate|escape}</datestamp>
        {foreach from=$recordSets item=set}
          <setSpec>{$set|escape}</setSpec>
        {/foreach}
      </header>
