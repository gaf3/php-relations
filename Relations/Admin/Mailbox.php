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

require_once('Relations/IMAP.php');
require_once('Relations/Admin.php');
require_once('Relations/Admin/Form.php');

class Relations_Admin_Mailbox extends Relations_Admin_Form{



  /*** Create ***/



  //// Constructor

  function Relations_Admin_Mailbox() {

    /* 
    
      $name - The name of the form in PHP
      $label - The label of the form (pretty format)
      $home_url - The default 'home' url
      $self_url - The page's url
      $imap - The Relations_IMAP object to use
      $abstract - The Relations_Abstract object to use
      $database - The database to use with this form
      $table - The table to use with this form
      $id_field - The primary key field
      $id_input - The primary key input
      $filters - Filters of folder to avoid
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
      $this->imap,
      $this->abstract,
      $this->database,
      $this->table,
      $this->id_field,
      $this->id_input,
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
      'IMAP',
      'ABSTRACT',
      'DATABASE',
      'TABLE',
      'ID_FIELD',
      'ID_INPUT',
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

    // Parent

    parent::initialize();

    // Make sure the imap

    if (!$this->imap)
      die('IMAP must be set for ' . $this->label . '!');

    // Make sure we have a table name

    if (!strlen($this->table))
      $this->table = "_temporary_$this->name";

    // Make sure we have a id name

    if (!strlen($this->id_field))
      $this->id_field = $this->id_input;

    // Create the array of filters if it
    // wasn't set

    if (!is_array($this->filters))
      $this->filters = array();

    // Go through the inputs and set their descendants,

    foreach (array_keys($this->inputs) as $name) {

      $this->inputs[$name]->descendants = 'prefix';

    }

  }



  /*** Validate ***/



  // Check to see if a mailbox exists

  
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

      if (($this->originals[$record] != $this->ID($record)) &&
          $this->imap->folded($this->ID($record)))
        $errors[$record][] = "Mailbox already exists";

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

        if (strtolower($this->ID($record)) == strtolower($this->ID($other)))
          $match = true;

      }

      // If there was a match, then there'll be errors

      if ($match)
        $errors[$record][] = "Mailbox name must be unique";

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
          
          if (!$this->imap->folded($value) && strlen($value)) {

            $errors[$input->prefix . $input->name . '_' . $record][] = "Mailbox does not exist";
            break;

          }

        }

      } else {

        // Check to see if value exists

        if (!$this->imap->folded($input->values[$record]) && strlen($input->values[$record]))
          $errors[$input->prefix . $input->name . '_' . $record][] = "Mailbox does not exist";

      }

    }

  }



  /*** Database ***/



  //// Calculates an value for children

  function childSQL($descendants,$field,$value) {

    $delimit = preg_quote($this->imap->delimit,'"');

    if ($descendants != 'prefix')
      return "'" . mysql_escape_string($value) . "'";
    else
      return "^" . mysql_escape_string(strtolower($value)) . "$delimit" . "[^$delimit]+\\$";

  }



  //// Calculates a where clause for children

  function childWhere($descendants,$field,$values) {

    // If there's no prefix, standard
    // values, if there is make the 
    // regex complete

    if ($descendants != 'prefix')
      return "$field in (" . join(',',$values) . ")";
    else
      return "lower($field) regexp '" . join('|',$values) . "'";

  }



  //// Determines if a child exists for a given value

  function childExists($descendants,&$parents,$value) {

    // First lowercase everything

    $lowereds = array();
    foreach (array_keys($parents) as $parent)
      $lowereds[strtolower($parent)] = 1;

    // A regular form has no prefix

    if ($descendants != 'prefix') {

      if ($lowereds[strtolower($value)])
        return true;

    } else {

      foreach (array_keys($lowereds) as $parent)
        if (strtolower($value) == substr($parent,0,strlen($value)))
          return true;

    }

    // Nothing, we're fine

    return false;

  }



  //// Gets the values for a specific record

  function childValues($descendants,$display,&$local_records,&$local_originals) {

    // Initialize the values

    $values = array();

    // If there's no local originals, 
    // nothing to do

    if (!is_array($local_records) || !is_array($local_originals))
      return $values;

    // Lowercase everything

    $lower_records = array();
    foreach ($local_records as $value=>$record)
      $lower_records[strtolower($value)] = $record;

    $lower_originals = array();
    foreach ($local_originals as $record=>$value)
      $lower_originals[$record] = strtolower($value);

    // Go through all the rows and attach this value to the right record

    for ($find = 0; $find < count($this->local_originals); $find++) {

      if ($descendants != 'prefix') {

        if ($display != 'count')
          $values[$lower_records[strtolower($this->local_originals[$find])]][] = $this->ids[$find];
        else
          $values[$lower_records[strtolower($this->local_originals[$find])]] += $this->counts[$find];

      } else {

        for ($local = 0; $local < count($lower_records); $local++) {

          if ($lower_originals[$local] == strtolower(substr($this->local_originals[$find],0,strlen($lower_originals[$local])))) {

            if ($display != 'count')
              $values[$lower_records[strtolower(substr($this->local_originals[$find],0,strlen($lower_originals[$local])))]][] = $this->ids[$find];
            else
              $values[$lower_records[strtolower(substr($this->local_originals[$find],0,strlen($lower_originals[$local])))]] += $this->counts[$find];

          }

        }

      }

    }

    return $values;

  }



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

    // Get a listing of all the folders

    $folders = $this->imap->branch();

    // Select the database

    $this->abstract->runQuery("use $this->database");

    // Create a temporay table to hold everything

    $this->abstract->runQuery("drop table if exists $this->table");

    $this->abstract->runQuery("
      create temporary table $this->table (
        $this->id_field char(255) not null
      )
    ");

    // Escape all the data and filter

    $values = array();
    foreach ($folders as $folder)
      if (!Relations_Admin_filtered($this->filters,$folder))
        $values[] = array(
          $this->id_field => "'" . mysql_escape_string($folder) ."'"
        );

    // Insert the data into the table

    if (count($values))
      $this->abstract->insertRows(array(
        _table  => $this->table,
        _values => $values
      ));

  }



  //// Sends data to the database
  
  function toDB(&$errors,&$totals) {

    // First check toValidate

    if (!$this->toValidate($errors))
      return false;

    // Go through all the records 

    for ($record = 0; $record < $this->records; $record++) {

      // See what we intend to do with this one

      switch ($this->intentions[$record]) {

        case 'insert':

          if ($this->imap->create($this->ID($record)))
            $totals[$this->label][$this->intentions[$record]][] = $this->ID($record);

          break;

        case 'copy':

          if ($this->imap->create($this->ID($record))) {

            $totals[$this->label]['insert'][] = $this->ID($record);
            $totals[$this->label][$this->intentions[$record]][] = $this->originals[$record];

          } 
          break;

        case 'update':
        case 'replace':

          if (($this->originals[$record] != $this->ID($record)) && $this->imap->create($this->ID($record))) {

            $totals[$this->label][$this->intentions[$record]][] = $this->ID($record);
            $totals[$this->label]['replace'][] = $this->originals[$record];

          } 
          break;

        case 'delete':

          if ($this->imap->remove($this->originals[$record]))
            $totals[$this->label][$this->intentions[$record]][] = $this->originals[$record];
          break;


        case 'ignore':

          $totals[$this->label][$this->intentions[$record]]++;
          break;

      }

      $imap_errors = $this->imap->errors();
      if (is_array($imap_errors) && count($imap_errors))
        for ($record = 0; $record < $this->records; $record++)
          foreach ($imap_errors as $imap_error)
            $errors[$record][] = $imap_error;


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

    // Create a query to get at the data

    $this->query = new Relations_Query(array(
      _select => array(
        id    => "$this->table.$this->id_field",
        label => "$this->table.$this->id_field",
      ),
      _from => $this->table,
      _options => 'distinct'
    ));

    // Configure extras

    if ($purpose == 'select')
      $extras['values'] = 'id';
      
    // Call parent

    parent::fromDB($errors,$totals,$purpose,$intention,$ambition,$extras,$add,$set);

  }



  /*** Process ***/



  //// Process the update's children if the id changed

  function updateChildrenProcess() {

    // Shift out an Info

    if (is_array($this->child_infos) && !count($this->child_infos)) {

      // Go through all the records 

      for ($record = 0; $record < $this->records; $record++) {

        // Create the temporary, value and original filename

        if (($this->intentions[$record] == 'update') && ($this->ID($record) != $this->originals[$record]) && 
            $this->imap->folded($this->ID($record)))
          $this->imap->remove($this->originals[$record]);

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

        if (($this->intentions[$record] == 'replace') && ($this->ID($record) != $this->originals[$record]) && 
            $this->imap->folded($this->ID($record)))
          $this->imap->remove($this->originals[$record]);

      }

    }

    parent::replaceChildrenProcess();

  }

}

?>