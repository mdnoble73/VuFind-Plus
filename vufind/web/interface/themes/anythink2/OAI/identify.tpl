  <Identify>
    <repositoryName>{$repositoryName|escape}</repositoryName>
    <baseURL>{$baseURL|escape}</baseURL>
    <protocolVersion>{$protocolVersion|escape}</protocolVersion>
    <earliestDatestamp>{$earliestDatestamp|escape}</earliestDatestamp>
    <deletedRecord>{$deletedRecord|escape}</deletedRecord>
    <granularity>{$granularity|escape}</granularity>
    <adminEmail>{$adminEmail|escape}</adminEmail>
    {if !empty($idNamespace)}
      <description>
        <oai-identifier xmlns="http://www.openarchives.org/OAI/2.0/oai-identifier"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai-identifier
                                            http://www.openarchives.org/OAI/2.0/oai-identifier.xsd">
          <scheme>oai</scheme>
          <repositoryIdentifier>{$idNamespace|escape}</repositoryIdentifier>
          <delimiter>:</delimiter>
          <sampleIdentifier>oai:{$idNamespace|escape}:123456</sampleIdentifier>
        </oai-identifier>
      </description>
    {/if}
  </Identify>