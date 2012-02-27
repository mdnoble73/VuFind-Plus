<?php
require_once 'Action.php';
require_once 'sys/Genealogy/Person.php';
require_once 'sys/Genealogy/Obituary.php';
require_once 'sys/Genealogy/Marriage.php';

class JSON extends Action {

    function launch()
    {
        //header('Content-type: application/json');
        header('Content-type: text/html');
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        
        if (is_callable(array($this, $_GET['method']))) {
            $output = json_encode(array('result'=>$this->$_GET['method']()));
        } else {
            $output = json_encode(array('error'=>'invalid_method'));
        }
        
        echo $output;
    }
    
    function importPeople(){
        //open the file
        $file=fopen($_SESSION['genealogyImport']['filename'],"r");
        $row = 0;
        $headers;
        $currentRecord = 0;
        $recordsPerBatch = 25;
        $startRecord = isset($_SESSION['genealogyImport']['currentRecord']) ? $_SESSION['genealogyImport']['currentRecord'] : 0;
        $numRecords = $_SESSION['genealogyImport']['numRecords'];
        while (($data = fgetcsv($file, 0, ",", '"')) !== FALSE){
            if ($row == 0){
                $headers = $data;
            }else{
                //Process the data to create people, marriages, obits, etc.
                $currentRecord++;
                if ($currentRecord >= $startRecord){
                  $this->importPerson($headers, $data);
                }
            }
            $row++;
            if ($currentRecord >= ($startRecord + $recordsPerBatch)){
                break;
            }
        }
        $_SESSION['genealogyImport']['currentRecord'] = $currentRecord;
        return array(
          'percentComplete' => floor(($currentRecord / $numRecords) * 100),
          'moreData' => ($currentRecord < $numRecords),
          'currentRecord' => $currentRecord,
        );
    }
    
    function importPerson($headers, $data){
        $person = new Person();
        $obit1 = null;
        $obit2 = null;
        $obit3 = null;
        $marriage1 = null;
        $marriage2 = null;
        foreach ($data as $index => $fieldData){
            if (is_null($fieldData)) continue;
            $fieldData = trim($fieldData);
            if ($fieldData == '') continue;
            switch (strtolower($headers[$index])){
                case 'first name':
                    $person->firstName = $fieldData;
                    break;
                case 'last name':
                    $person->lastName = $fieldData;
                    break;
                case 'middle name':
                    $person->middleName = $fieldData;
                    break;
                case 'maiden name':
                    $person->maidenName = $fieldData;
                    break;
                case 'other name':
                    $person->otherName = $fieldData;
                    break;
                case 'nick name':
                    $person->nickName = $fieldData;
                    break;
                case 'birth date':
                case 'born':
                    $this->dateFromField($person, 'birthDate', $fieldData);
                    break;
                case 'death date':
                case 'services held':
                    $this->dateFromField($person, 'deathDate', $fieldData);
                    break;
                case 'age':
                    $person->ageAtDeath = $fieldData;
                    break;
                case 'marriage date':
                    if ($marriage1 == null){
                        $marriage1 = new Marriage();
                    }
                    $this->dateFromField($marriage1, 'marriageDate', $fieldData);
                    break;
                case 'spouse 1':
                    if ($marriage1 == null){
                        $marriage1 = new Marriage();
                    }
                    $marriage1->spouseName = $fieldData;
                    break;
                case 'spouse 2':
                    if ($marriage2 == null){
                        $marriage2 = new Marriage();
                    }
                    $marriage2->spouseName = $fieldData;
                    break;
                case 'cemetery':
                    $person->cemeteryName = $fieldData;
                    break;
                case 'location':
                    $person->cemeteryLocation = $fieldData;
                    break;
                case 'mortuary':
                    $person->mortuaryName = $fieldData;
                    break;
                case 'nick name':
                    $person->nickName = $fieldData;
                    break;
                case 'comments':
                    $person->comments = $fieldData;
                    break;
                case 'obituary citation':
                    if ($obit1 == null) $obit1 = new Obituary();
                    $obit1->source = $this->mapObitSource($fieldData);
                    break;
                case 'obit 1 paper':
                    if ($obit1 == null) $obit1 = new Obituary();
                    $obit1->source = $this->mapObitSource($fieldData);
                    break;
                case 'obit 1 date':
                    if ($obit1 == null) $obit1 = new Obituary();
                    $this->dateFromField($obit1, 'date', $fieldData);
                    break;
                case 'obit 1 page':
                    if ($obit1 == null) $obit1 = new Obituary();
                    $obit1->sourcePage = $fieldData;
                    break;
                case 'obit 2 paper':
                    if ($obit2 == null) $obit2 = new Obituary();
                    $obit2->source = $this->mapObitSource($fieldData);
                    break;
                case 'obit 2 date':
                    if ($obit2 == null) $obit2 = new Obituary();
                    $this->dateFromField($obit2, 'date', $fieldData);
                    break;
                case 'obit 2 page':
                    if ($obit2 == null) $obit2 = new Obituary();
                    $obit2->sourcePage = $fieldData;
                    break;
                case 'obit 3 paper':
                    if ($obit3 == null) $obit3 = new Obituary();
                    $obit3->source = $this->mapObitSource($fieldData);
                    break;
                case 'obit 3 date':
                    if ($obit3 == null) $obit3 = new Obituary();
                    $this->dateFromField($obit3, 'date', $fieldData);
                    break;
                case 'obit 3 page':
                    if ($obit3 == null) $obit3 = new Obituary();
                    $obit3->sourcePage = $fieldData;
                    break;
            }
        }
    
        //Check to see if the person already exists
        $person2 = new Person();
        $person2->firstName = $person->firstName;
        $person2->lastName = $person->lastName;
        $person2->birthDateDay = $person->birthDateDay;
        $person2->birthDateMonth = $person->birthDateMonth;
        $person2->birthDateYear = $person->birthDateYear;
        $person2->deathDateDay = $person->deathDateDay;
        $person2->deathDateMonth = $person->deathDateMonth;
        $person2->deathDateYear = $person->deathDateYear;
        $person2->find(true);
        if ($person2->N > 0){
            //this person already exists, update the existing record
            $person->personId = $person2->personId;
            $person->update();
        }else{
          $person->insert();
        }

        //Must enter obits and marriages after the initial save. 
        $obituaries = array();
        if (!is_null($obit1)){
            $obituaries[] = $obit1;
        }
        if (!is_null($obit2)){
            $obituaries[] = $obit2;
        }
        if (!is_null($obit3)){
            $obituaries[] = $obit3;
        }
        $person->obituaries = $obituaries;
        $marriages = array();
        if (!is_null($marriage1)){
            $marriages[] = $marriage1;
        }
        if (!is_null($marriage2)){
            $marriages[] = $marriage2;
        }
        $person->marriages = $marriages;
    }    
    
    function mapObitSource($source){
        if (preg_match('/[`\d]?DS.*/i', $source )){
            return 'Grand Junction Daily Sentinel';
        }else if (preg_match('/EVE.*/i', $source )){
            return 'Eagle Valley Enterprise';
        }elseif(preg_match('/(BETHRAN|).*/i', $source, $matches)){
            //Sources with no translation
            return $matches[1];
        }else{
            return 'Other';
        }
    }
    
    function dateFromField($object, $fieldName, $fieldData){
        $dateParts = date_parse($fieldData);
        if ($dateParts['day']){
            $dayField = $fieldName . 'Day';
            $object->$dayField = $dateParts['day'];
        }
        if ($dateParts['month']){
            $dayField = $fieldName . 'Month';
            $object->$dayField = $dateParts['month'];
        }
        if ($dateParts['year']){
            $dayField = $fieldName . 'Year';
            $object->$dayField = $dateParts['year'];
        }
    }
    
    function reindexGenealogy(){
        //Make a connection to the database
        $recordsPerBatch = 5;
        $startRecord = isset($_SESSION['genealogyReindex']['currentRecord']) ? $_SESSION['genealogyReindex']['currentRecord'] : 0;
        $currentRecord = $startRecord;
        $numRecords = $_SESSION['genealogyReindex']['numRecords'];
        //Get the next set of people to reindex 
        $person = new Person();
        $person->limit($startRecord, $recordsPerBatch);
        $person->find();
        if ($person->N){
            while ($person->fetch() == true){
                $person->saveToSolr();
                $currentRecord += 1;
            }
        }
        
        $_SESSION['genealogyReindex']['currentRecord'] = $currentRecord;
        $moreData = $currentRecord < $numRecords;
        
        if (!$moreData){
            //Optimize the solr core 
            $person->optimize();
        }
        return array(
          'percentComplete' => floor(($currentRecord / $numRecords) * 100),
          'moreData' => $moreData,
          'currentRecord' => $currentRecord,
        );
    }
    
    function fixGenealogyDates(){
        //Make a connection to the database
        $recordsPerBatch = 10;
        $startRecord = isset($_SESSION['genealogyDateFix']['currentRecord']) ? $_SESSION['genealogyDateFix']['currentRecord'] : 0;
        $currentRecord = $startRecord;
        $numRecords = $_SESSION['genealogyDateFix']['numRecords'];
        //Get the next set of people to reindex 
        $person = new Person();
        $person->limit($startRecord, $recordsPerBatch);
        $person->find();
        if ($person->N){
            while ($person->fetch() == true){
                //Check the dates to see if they need to be fixed
                $dateUpdated = false;
                if (isset($person->birthDate) && strlen($person->birthDate) > 0 && $person->birthDate != '0000-00-00'){
                    $dateParts = date_parse($person->birthDate);
                    $person->birthDateDay = $dateParts['day'];
                    $person->birthDateMonth = $dateParts['month'];
                    $person->birthDateYear = $dateParts['year'];
                    $person->birthDate = '';
                    $dateUpdated = true;
                }
                if (isset($person->deathDate) && strlen($person->deathDate) > 0 && $person->deathDate != '0000-00-00'){
                    $dateParts = date_parse($person->deathDate);
                    $person->deathDateDay = $dateParts['day'];
                    $person->deathDateMonth = $dateParts['month'];
                    $person->deathDateYear = $dateParts['year'];
                    $person->deathDate = '';
                    $dateUpdated = true;
                }
                //Update marriages
                $marriages = $person->marriages;
                $marriagesUpdated = false;
                foreach ($marriages as $key=>$marriage){
                    $marriageUpdated = false;
                    if (isset($marriage->marriageDate) && strlen($marriage->marriageDate) > 0 && $marriage->marriageDate != '0000-00-00'){
                        $dateParts = date_parse($person->marriageDate);
                        $marriage->marriageDateDay = $dateParts['day'];
                        $marriage->marriageDateMonth = $dateParts['month'];
                        $marriage->marriageDateYear = $dateParts['year'];
                        $marriage->marriageDate = '';
                        $marriageUpdated = true;
                    }
                    if ($marriageUpdated){
                      $marriages[$key] = $marriage;
                      $marriagesUpdated = true;
                    }
                }
                if ($marriagesUpdated){
                  $person->marriages = $marriages;
                  $dateUpdated = true;
                }
                //Update obituaries
                $obituaries = $person->obituaries;
                $obituariesUpdated = false;
                foreach ($obituaries as $key=>$obit){
                    $obitUpdated = false;
                    if (isset($obit->date) && strlen($obit->date) > 0 && $obit->date != '0000-00-00'){
                        $dateParts = date_parse($obit->date);
                        $obit->dateDay = $dateParts['day'];
                        $obit->dateMonth = $dateParts['month'];
                        $obit->dateYear = $dateParts['year'];
                        $obit->date = '';
                        $obitUpdated = true;
                    }
                    if ($obitUpdated){
                      $obituaries[$key] = $obit;
                      $obituariesUpdated = true;
                    }
                }
                if ($obituariesUpdated){
                  $person->obituaries = $obituaries;
                  $dateUpdated = true;
                }
                
                if ($dateUpdated){
                  $person->update();
                }
                $currentRecord += 1;
            }
        }
        
        $_SESSION['genealogyDateFix']['currentRecord'] = $currentRecord;
        $moreData = $currentRecord < $numRecords;
        
        if (!$moreData){
            //Optimize the solr core 
            $person->optimize();
        }
        return array(
          'percentComplete' => floor(($currentRecord / $numRecords) * 100),
          'moreData' => $moreData,
          'currentRecord' => $currentRecord,
        );
    }
}