    <record>
      {include file="OAI/header.tpl"}
      {if !empty($recordMetadata)}
        <metadata>
          {* DO NOT ESCAPE -- RAW XML: *}{$recordMetadata}
        </metadata>
      {/if}
    </record>