#!/bin/bash
## Begin Script

## Install PEAR Packages
pear upgrade pear
pear install --onlyreqdeps DB
pear install --onlyreqdeps DB_DataObject
pear install --onlyreqdeps Structures_DataGrid-beta
pear install --onlyreqdeps Structures_DataGrid_DataSource_DataObject-beta
pear install --onlyreqdeps Structures_DataGrid_DataSource_Array-beta
pear install --onlyreqdeps Structures_DataGrid_Renderer_HTMLTable-beta
pear install --onlyreqdeps HTTP_Client
pear install --onlyreqdeps HTTP_Request
pear install --onlyreqdeps Log
pear install --onlyreqdeps Mail
pear install --onlyreqdeps Mail_Mime
pear install --onlyreqdeps Net_SMTP
pear install --onlyreqdeps Pager
pear install --onlyreqdeps XML_Serializer-beta
pear install --onlyreqdeps Console_ProgressBar-beta
pear install --onlyreqdeps File_Marc-alpha
pear channel-discover pear.horde.org
pear channel-update pear.horde.org
pear install Horde/Horde_Yaml-beta