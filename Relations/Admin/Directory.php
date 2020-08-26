<?php

// +----------------------------------------------------------------------+
// | Relations-Admin v0.93                                                |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002, GAF-3 Industries, Inc. All rights reserved.      |
// +----------------------------------------------------------------------+
// | This program is free software, you can redistribute it and/or modify |
// | it under the same terms as PHP istelf                                |
// +----------------------------------------------------------------------+
// | Authors: George A. Fitch III (aka Gaffer) <gaf3@gaf3.com>            |
// +----------------------------------------------------------------------+

require_once('Relations/Admin.php');
require_once('Relations/Admin/Form.php');

class Relations_Admin_Directory extends Relations_Admin_Form{



  /*** Create ***/



  //// Constructor

  function Relations_Admin_Directory() {

    /* 
    
      $name - The name of the form in PHP
      $label - The label of the form (pretty format)
      $home_url - The default 'home' url
      $self_url - The page's url
      $abstract - The Relations_Abstract object to use
      $database - The database to use with this form
      $table - The table to use with this form
      $id_field - The primary key field
      $id_input - The primary key input
      $location - Main location to store the files
      $filters - Filters of directories to avoid
      $valids - List of functions to validate input
      $forbids - What to forbid 
      $security - Security object to verify actions
      $logging - Logging object to log actions
      $identity - ID to use with security and logging
      $helps - Help info in URL, text, and popups
      $layout - The layout of the form in arrays
      $format - The format of the form layouts
      $labeling - The labels to use with layouts
      $title - The prefix to use for the title and headers
      $css - The style sheet or (link) to use with the form
      $styles - The styles to use in HTML
      $classes - The classes to use in HTML
      $elements - The extra element to use in HTML

    */

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the arguments passed

    list(
      $this->name,
      $this->label,
      $this->home_url,
      $this->self_url,
      $this->abstract,
      $this->database,
      $this->table,
      $this->id_field,
      $this->id_input,
      $this->location,
      $this->filters,
      $this->valids,
      $this->forbids,
      $this->security,
      $this->logging,
      $this->identity,
      $this->helps,
      $this->layout,
      $this->format,
      $this->labeling,
      $this->title,
      $this->css,
      $this->styles,
      $this->classes,
      $this->elements
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL',
      'HOME_URL',
      'SELF_URL',
      'ABSTRACT',
      'DATABASE',
      'TABLE',
      'ID_FIELD',
      'ID_INPUT',
      'LOCATION',
      'FILTERS',
      'VALIDS',
      'FORBIDS',
      'SECURITY',
      'LOGGING',
      'IDENTITY',
      'HELPS',
      'LAYOUT',
      'FORMAT',
      'LABELING',
      'TITLE',
      'CSS',
      'STYLES',
      'CLASSES',
      'ELEMENTS'
    ),$arg_list);

    // We're not going to prefix anything since we're
    // the main form. ChildForms will however.

    $this->prefix = '';

    // Create an associative array of inputs for 
    // looking up input objects by name, and for
    // the automatic inputs (not entered by user)

    $this->inputs = array();

  }



  //// Initialize the Directory

  function initialize() {

     // Assume if format's a scalar, it's 
    // meant just for records

    if (is_string($this->format))
      $this->format = array('records' => $this->format);

    // If format's not set for record, 
    // assume columnar

    if (empty($this->format['records']))
      $this->format['records'] = 'columnar';

    // Create the layout array if it
    // wasn't set

    if (!isset($this->layout))
      $this->layout = array();

    // Create the main layout array
    // if wasn't set

    if (!isset($this->layout['main']))
      $this->layout['main'] = 'start,heading,errors,message,help,form_start,' .
                              'control,all,list,records,admins,new,mass,all,control,finish,' . 
                              'form_end,script,end';

    // If there format is tabular, assume
    // errors are part of the table, else
    // part of records

    if ($this->format['records'] == 'tabular') {

      // Create the mass layout array
      // if wasn't set

      if (!isset($this->layout['records']))
        $this->layout['records'] = 'record,set';

      // Create the search layout array
      // if wasn't set

      if (!isset($this->layout['record']))
        $this->layout['record'] = 'caption,error,above,select,initial,filter,rename,inputs,url,intended,admin,single,choose,view';

    } else {

      // Create the mass layout array
      // if wasn't set

      if (!isset($this->layout['records']))
        $this->layout['records'] = 'error,record,set';

      // Create the search layout array
      // if wasn't set

      if (!isset($this->layout['record']))
        $this->layout['record'] = 'caption,above,select,initial,filter,rename,inputs,url,intended,admin,single,choose,view';

    }

   // Parent

    parent::initialize();

    // Make sure the directory is set

    if (!strlen($this->location))
      die('Location must be set for ' . $this->label . '!');

    // Make sure we have a table name

    if (!strlen($this->table))
      $this->table = "_temporary_$this->name";

    // Make sure we have a id name

    if (!strlen($this->id_field))
      $this->id_field = $this->id_input;

    // Replace a \ with /

    $this->location = str_replace('\\','/',$this->location);

    // Create the array of filters if it
    // wasn't set

    if (!is_array($this->filters))
      $this->filters = array();

    // Add . and .. to filters

    $this->filters[] = '/^\.\.?$/';

    // Go through the inputs and set their descendants,

    foreach (array_keys($this->inputs) as $name) {

      $this->inputs[$name]->descendants = 'prefix';

    }

  }



  //// Grabs input from html for searching

  function sought() {

    // Call parent

    parent::sought();

    // Check to see if they typed a limit

    $this->above = Relations_Admin_grab('above','','VPG');

  }



  //// Stores info entered by the user

  function save() {

    // Call parent

    parent::save();

    Relations_Admin_store($this->depth . '_above',$this->above);

  }



  //// Retrieves info entered by the user

  function load() {

     // Call parent

    parent::load();

    $this->above = Relations_Admin_retrieve($this->depth . '_above');

  }



/*** Validate ***/



  //// Checks all values for uniqueness

  function uniquesValidate(&$errors) {

    // Go through all records

    for ($record = 0; $record < $this->records; $record++) {
        
      // Skip if we're ignoring or deleting

      if (in_array($this->intentions[$record],array('ignore','delete')))
        continue;

      // Skip if there's no value 

      if (!strlen($this->ID($record)))
        continue;

      // Try to see if the file already exists and is
      // different from the original

      if (is_dir($this->location . '/' . $this->ID($record)) &&
         ($this->originals[$record] != $this->ID($record)))
        $errors[$record][] = "Directory already exists";

      // Check the other values to see if they're identical

      $match = false;

      for ($other = 0; $other < $this->records; $other++) {

        // Don't compare against ourselves

        if ($other == $record)
          continue;

        // Skip if they're ignoring or deleting

        if (in_array($this->intentions[$other],array('ignore','delete')))
          continue;

        // If there's a match

        if ($this->ID($record) == $this->ID($other))
          $match = true;

      }

      // If there was a match, then there'll be errors

      if ($match)
        $errors[$record][] = "Directory name must be unique";

    }

  }



  //// Checks all values against their location and name

  function invalidValidate(&$errors) {

    // Go through all records

    for ($record = 0; $record < $this->records; $record++) {
        
      // Skip if we're ignoring or deleting

      if (in_array($this->intentions[$record],array('ignore','delete')))
        continue;

      // Skip if there's no value 

      if (!strlen($this->ID($record)))
        continue;

      // Try to see if the file isn't within
      // where it should be

      if (!Relations_Admin_within($this->location,$this->ID($record)))
        $errors[$record][] = "Invalid location";

      // Make sure this isn't a filter filename

      if (Relations_Admin_filtered($this->filters,$this->ID($record)))
        $errors[$record][] = "Invalid directory name";

    }

  }



  //// Checks values for existance

  function existsValidate(&$errors,$intentions,$records,$input) {

    // Go through all records to tally all directories

    $directories = array();

    for ($record = 0; $record < $records; $record++) {

      // Skip if we're ignoring or deleting

      if (!in_array($intentions[$record],array('ignore','delete'))) {

        // If this is an array, add the values one 
        // by one, else, just add this value

        if (is_array($input->values[$record])) {

          foreach ($input->values[$record] as $value)
            $directories[] = $value;

        } else {

          $directories[] = $input->values[$record];

        }

      }

    }

    // Get only uniques

    $directories = array_unique($directories);

    // Return if there's nothing to check

    if (!count($directories))
      return;

    // Now get all files from the database and hash them

    $exists = array();

    foreach ($directories as $directory)
      $exists[$directory] = is_dir($this->location . '/' . $directory);

    // Go through all the record to check

    for ($record = 0; $record < $records; $record++) {

      // Skip if we're ignoring or deleting

      if (in_array($intentions[$record],array('ignore','delete')))
        continue;

      // If this is an array, add the values one 
      // by one, else, just add this value

      if (is_array($input->values[$record])) {

        // Check to see if values exist

        foreach ($input->values[$record] as $value) {

          if (!$exists[$value] && strlen($value)) {

            $errors[$input->prefix . $input->name . '_' . $record][] = "Directory does not exist";
            break;

          }

        }

      } else {

        // Check to see if value exists

        if (!$exists[$input->values[$record]] && strlen($input->values[$record]))
          $errors[$input->prefix . $input->name . '_' . $record][] = "Directory does not exist";

      }

    }

  }



  //// Makes sure everything is valid for toDB
  
  function toValidate(&$errors) {

    // Check invalid

    $this->invalidValidate($errors);

    // Call parent

    return parent::toValidate($errors);

    // Return if there's any errors

    if (count($errors))
      return false;

  }



  /*** Database ***/



  //// Creates a regex to use for replacements

  function ChildReplace($descendants,$value) {

    // There's no prefix stuff here

    if ($descendants != 'prefix')
      return '/^' . preg_quote($value,'/') . '$/';
    else
      return '/^' . preg_quote($value,'/') . '/';

  }



  //// Creates the database, not necessary for
  //// regular forms, but the special ones

  function makeDB() {

  }



  //// Sends data to the database
  
  function toDB(&$errors,&$totals) {

    // First check toValidate

    if (!$this->toValidate($errors))
      return false;

    // Go through all the records 

    for ($record = 0; $record < $this->records; $record++) {

      // Create the temporary, value and original filename

      $value = $this->location . '/' . $this->ID($record);
      $original = $this->location . '/' . $this->originals[$record];
    
      // See what we intend to do with this one

      switch ($this->intentions[$record]) {

        case 'insert':

          if (!is_dir($value))
            mkdir($value,0770);

          $totals[$this->label][$this->intentions[$record]][] = $this->ID($record);

          break;

        case 'copy':

          if (!is_dir($value))
            mkdir($value,0770);
          $totals[$this->label]['insert'][] = $this->ID($record);
          $totals[$this->label][$this->intentions[$record]][] = $this->originals[$record];

          break;

        case 'update':
        case 'replace':

          if (($original != $value) && !is_dir($value))
            rename($original,$value);

          $totals[$this->label][$this->intentions[$record]][] = $this->ID($record);

          // If the id value changed, indicate 
          // the new one was replaced too

          if ($value != $original)
            $totals[$this->label]['replace'][] = $this->originals[$record];

          break;

        case 'delete':

          @rmdir($original);
          $totals[$this->label][$this->intentions[$record]][] = $original;
          break;

        case 'ignore':

          $totals[$this->label][$this->intentions[$record]]++;
          break;

      }

      // If we didn't delete or ignore

      if (!in_array($this->intentions[$record],array('ignore','delete'))) {

        // Get the row 

        $row = array($this->id_field => $this->ID($record));

        // Now go through all the inputs and have them
        // load in the data from the row

        foreach (array_keys($this->inputs) as $name)
          $this->inputs[$name]->setSQL($row,$record);

      }

    }

    // Now go through all the inputs and have them do 
    // what they have to do. 

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->toDB($errors,$totals,$this->intentions,$this->records);

    // Log everything

    $this->event($totals);

    // Return whether there's any errors

    return count($errors) == 0 ? true : false;

  }


  
  //// Receives data from the database
  
  function fromDB(&$errors,&$totals,$purpose,$intention,$ambition,$extras=array(),$add=array(),$set=array()) {

    // Make the database

    $this->makeDB();

    // if we want counts all a count fields, and
    // initialize counts. If we want listing, 
    // initialize ids and labe. If we want records, 
    // add the all the fields, and initialize 
    // originals

    switch ($purpose) {

      case 'count':

        $this->counts = array();
        break;

      case 'list':

        $this->ids = array();
        $this->labels = array();
        break;

      case 'select':

        $this->orginals = array();
        break;

    }

    // Initialize records

    $this->records = 0;

    // Get a listing of all the files and directories

    $finds = array();

    if ($intention == 'choose') {

      // Add chosen

      if (is_array($this->chosen) && count($this->chosen)) {

        // Get only uniques

        $this->chosen = array_unique($this->chosen);

        foreach ($this->chosen as $chosen)
          if (is_file($this->location . '/' . $chosen))
            $finds[] = $chosen;

      }

    } else {

      $location = $this->location;

      if ($this->above)
        $location .= '/' . $this->above;

      $tree = array();

      Relations_Admin_climb($tree,$location);

      $finds = array();

      foreach ($tree as $branch)
        if (($branch['type'] == 'directory') && !Relations_Admin_filtered($this->filters,$branch['path'] . $branch['name']))
          $finds[] = $branch['path'] . $branch['name'];

      // If there's nothing, return

      if (!count($finds))
        return false;

      // See what we intend to do with this one,
      // If we're searching, do everything, selecting
      // check chosen and inputs, custom just add the
      // custom info

      switch ($intention) {

        case 'search':

          // Add initial and filter

          if (!empty($this->initial))
            $finds = array_values(preg_grep("/^" . preg_quote($this->initial) . "/i",$finds));

          if (!empty($this->filter))
            $finds = array_values(preg_grep("/" . preg_quote($this->filter) . "/i",$finds));

          if (!empty($this->limit)) {

            list($offset,$length) = split(',',$this->limit);

            if (!strlen($length)) {

              $length = $offset;
              $offset = 0;

            }

            $finds = array_splice($finds,$offset,$length);

          }
           
        case 'select':

        case 'choose':

        case 'custom':

        case 'all':

      }

      if ($this->above) {

        $aboves = array();

        foreach ($finds as $find)
          $aboves[] = $this->above . '/' . $find;

        $finds = $aboves;

      }

    }

    // If there's nothing, return

    if (!count($finds))
      return false;

    // See what the purpose is 

    switch ($purpose) {

      case 'count':

        if ($this->allow('count')) {


          $this->counts[0] = count($finds);
          $totals[$this->label]['count'] += count($finds);
          $this->records += count($finds);

        }

        break;

      case 'list':

        foreach ($finds as $find) {

          // Check permissions

          if (!$this->allow($ambition,$find))
            continue;

          $this->ids[] = $find;
          $this->labels[$find] = $find;
          $totals[$this->label]['list'][] = $find;
          $this->records++;

        }

        // Update totals appropriately

        $this->records = count($this->ids);

        break;

      case 'select':

        // Go through all the rows

        for ($record = 0; $record < count($finds); $record++) {

          // Check permissions

          if (!$this->allow($ambition,$finds[$record]))
            continue;

          // Now get all the info from the db using the id value

          foreach (array_keys($this->inputs) as $name) {

            if (!$this->deny($ambition,$name,array($this->id_field => $finds[$record])))
              $this->inputs[$name]->setSQL(array($this->id_field => $finds[$record]),$this->records);

          }

          // Set originals

          $this->originals[$this->records] = $this->ID($this->records);

          // Increase our values in the totals array

          $totals[$this->label]['select'][] = $finds[$record];

          // Increase records

          $this->records++;

        }

        // If there's no records, break
        // (none were allowed)

        if (!$this->records)
          break;

        // Now go through all the inputs and have them do 
        // what they have to do. 

        foreach (array_keys($this->inputs) as $name)
          $this->inputs[$name]->fromDB($errors,$totals,$intention,$ambition,$this->records);

        break;

    }

    // Set the intentions, ambitions

    $this->oblige($ambition);

    // Log everything

    $this->event($totals);

    // Check for validate here

    return $this->fromValidate($errors);
 
  }



  /*** Process ***/



  //// Process the update's children if the id changed

  function updateChildrenProcess() {

    // Shift out an Info

    if (is_array($this->child_infos) && !count($this->child_infos)) {

      // Go through all the records 

      for ($record = 0; $record < $this->records; $record++) {

        // Create the temporary, value and original filename

        $value = $this->location . '/' . $this->ID($record);
        $original = $this->location . '/' . $this->originals[$record];
      
        // See what we intend to do with this one

        if (($this->intentions[$record] == 'update') && ($original != $value) && is_dir($value))
          @rmdir($original);

      }

    }

    parent::updateChildrenProcess();

  }



  //// Process the repalced children

  function replaceChildrenProcess() {

    // Shift out an Info

    if (is_array($this->child_infos) && !count($this->child_infos)) {

      // Go through all the records 

      for ($record = 0; $record < $this->records; $record++) {

        // Create the temporary, value and original filename

        $value = $this->location . '/' . $this->ID($record);
        $original = $this->location . '/' . $this->originals[$record];
      
        if (($this->intentions[$record] == 'replace') && ($original != $value) && is_dir($value))
          @rmdir($original);

      }

    }

    parent::replaceChildrenProcess();

  }

  /*** HTML ***/



  //// Sets data for all records

  function recordsData($state,$records=array(0),$admins=array(),$extra=array()) {

    // Take off if we're to display mass
    // and there's no need.

    if (($state == 'mass') && !$this->needMass())
      return;

    // Call parent

    parent::recordsData($state,$records,$admins,$extra);

    // If we're mass, use mass, search, 
    // else use records

    if ($state == 'mass')
      $fill = 'mass';
    else
      $fill = 'records';

    // If we're searching, we need to add
    // inputs.

    if ($state == 'search') {

      // Call fromDB 

      $errors = array();
      $totals = array();
      $this->fromDB($errors,$totals,'list','all',$this->ambition);

      // Add a select list and button

      $choose_url = "$this->self_url?depth=$this->depth&task=$this->task&state=choose&chosen=";
      $this->data[$fill]['record'][0]['select']['ids'] = $this->ids;
      $this->data[$fill]['record'][0]['select']['labels'] = $this->labels;
      $this->data[$fill]['record'][0]['select']['url'] = $choose_url;

      $url = "$this->self_url?depth=$this->depth&task=$this->task&above=";

      $location = $this->location;

      if ($this->above) {

        $location .= '/' . $this->above;
        $this->data[$fill]['record'][0]['above']['below'] = preg_replace("/\\/?[^\\/]+$/",'',$this->above);

      }

      $finds = Relations_Admin_delve($location);
      $this->data[$fill]['record'][0]['above']['aboves'] = array(); 

      foreach ($finds as $find)
        if ($this->above)
          $this->data[$fill]['record'][0]['above']['aboves'][] = $this->above . '/' . $find;
        else 
          $this->data[$fill]['record'][0]['above']['aboves'][] = $find;

      if (count($finds) || $this->above)
        $this->data[$fill]['record'][0]['above']['url'] = $url;
      
    }

    // If we're entering, go through all the records 
    // and add the extra stuff

    if ($state == 'link') {

      // Go through all the records

      foreach ($records as $record) {

        $file_url = $this->prefix_url . str_replace('%2F','/',rawurlencode($this->ID($record)));

        $this->data[$fill]['record'][$record]['url'] = Relations_Admin_URLHTML($this,$file_url,'URL','');

      }

    }

  }

  //// Converts the data into HTML

  function toHTML($name,$data) {

    // If there's no data take off

    if (!$this->isData($data)) 
      return '';

    // If we're at mass or search, then
    // set the name to records

    if ($name == 'mass')
      $name = 'records';

    // Figure out the format. If there's
    // something special, use that. Else
    // just use the name.

    if (strlen($this->format[$name]))
      $format = $this->format[$name];
    else
      $format = $name;

    // Figure out what to format

    switch ($format) {

      case 'select':
        if (is_array($data['ids']))
          foreach ($data['ids'] as $id)
            $html .= Relations_Admin_URLHTML($this,"$data[url]$id",$id,'','initial_') . "<br/>";

        return $html;

      case 'above':
        
        $html = '';
        if ($this->above)
          $html .= Relations_Admin_URLHTML($this,"$data[url]$data[below]",$data['below'] . ' (Go up)','','initial_') . "<br/>";

        if (is_array($data['aboves']))
          foreach ($data['aboves'] as $above)
            $html .= Relations_Admin_URLHTML($this,"$data[url]$above",$above,'','initial_') . "<br/>";

        return $html;

      default:
        return parent::toHTML($name,$data);;

    }

  }

}

?>