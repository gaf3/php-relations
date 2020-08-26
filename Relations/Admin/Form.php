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

class Relations_Admin_Form {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_Form() {

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
      $query - Query object to use for selecting
      $uniques - List of hashes of unqiue input combos
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
      $member - Member to grab info from

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
      $this->query,
      $this->uniques,
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
      $this->elements,
      $member
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
      'QUERY',
      'UNIQUES',
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
      'ELEMENTS',
      'MEMBER'
    ),$arg_list);

    // We're not going to prefix anything since we're
    // the main form. ChildForms will however.

    $this->prefix = '';

    // Create an associative array of inputs for 
    // looking up input objects by name, and for
    // the automatic inputs (not entered by user)

    $this->inputs = array();

    // Now we have to go through and replace any 
    // blank values with the member's values

    if (isset($member)) {

      if (!isset($this->name))
        $this->name = $member->name;

      if (!isset($this->label))
        $this->label = $member->label;

      if (!isset($this->database))
        $this->database = $member->database;

      if (!isset($this->table))
        $this->table = $member->table;

      if (!isset($this->id_field))
        $this->id_field = $member->id_field;

      if (!isset($this->query))
        $this->query = $member->query;

    }

    if (!isset($this->id_input))
      $this->id_input = $this->id_field;

  }



  //// Adds an input to the form

  function add(&$input) {

    // If we already have this name, die
    // Else add to inputs array 
    
    if ($this->inputs[$input->name]) 
      die("Input name '$input->name' already exists");

    $this->inputs[$input->name] = &$input;

  }



  //// Initializes the form, grabs the record number, depth, etc.

  function initialize() {

    // Convert forbids to a hash

    $this->forbids = array_change_key_case(Relations_toHash($this->forbids));

    // Create the identity if it
    // wasn't set

    if (!isset($this->identity))
      $this->identity = $this->name;

    // Create the array of uniques if it
    // wasn't set

    if (!is_array($this->uniques))
      $this->uniques = array();

    // Create the array of valids if it
    // wasn't set

    if (!is_array($this->valids))
      $this->valids = array();

    // Create the array of helps if it
    // wasn't set

    if (!is_array($this->helps))
      $this->helps = array();

    // Create the array of styles if it
    // wasn't set

    if (!is_array($this->styles))
      $this->styles = array();

    // Create the array of classes if it
    // wasn't set

    if (!is_array($this->classes))
      $this->classes = array();

    // Create the array of elements if it
    // wasn't set

    if (!is_array($this->elements))
      $this->elements = array();

    // Initialize focus

    $this->focus = array();

    // Assume if format's a scalar, it's 
    // meant just for records

    if (is_string($this->format))
      $this->format = array('records' => $this->format);

    // If format's not set for record, 
    // assume columnar

    if (empty($this->format['records']))
      $this->format['records'] = 'columnar';

    // If format's not set for list, 
    // assume columnar

    if (empty($this->format['list']))
      $this->format['list'] = 'tabular';

    // Create the layout array if it
    // wasn't set

    if (!isset($this->layout))
      $this->layout = array();

    // Create the main layout array
    // if wasn't set

    if (!isset($this->layout['main']))
      $this->layout['main'] = 'header,start,heading,errors,info,message,help,form_start,' .
                              'admins,control,limit,all,list,records,admins,create,mass,all,control,finish,' . 
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
        $this->layout['record'] = 'caption,error,select,initial,filter,inputs,intended,admin,single,narrow,choose,view';

    } else {

      // Create the mass layout array
      // if wasn't set

      if (!isset($this->layout['records']))
        $this->layout['records'] = 'error,record,set';

      // Create the search layout array
      // if wasn't set

      if (!isset($this->layout['record']))
        $this->layout['record'] = 'caption,select,initial,filter,inputs,intended,admin,single,narrow,choose,view';

    }

    // Make sure all layouts are arrays

    $this->layout['main'] = Relations_toArray($this->layout['main']);
    $this->layout['records'] = Relations_toArray($this->layout['records']);
    $this->layout['record'] = Relations_toArray($this->layout['record']);

    // Set intended and admin to action

    if (!isset($form->labeling['intended']))
      $this->labeling['intended'] = 'Action';

    if (!isset($form->labeling['admin']))
      $this->labeling['admin'] = 'Action';

    if (!isset($form->labeling['admins']))
      $this->labeling['admins'] = 'Action';

    if (!isset($form->labeling['single']))
      $this->labeling['single'] = ' ';

    if (!isset($form->labeling['narrow']))
      $this->labeling['narrow'] = 'Choose';

    // Go through the inputs and set their prefix,
    // security, logging and identity

    foreach (array_keys($this->inputs) as $name) {

      $this->inputs[$name]->prefix = $this->prefix . $this->inputs[$name]->prefix;
      $this->inputs[$name]->parent_prefix = $this->prefix;
      $this->inputs[$name]->descendants = 'exact';

      // Check if the id input is auto and 
      // kids are counting

      if (!$this->inputs[$this->id_input]->auto && ($this->inputs[$name]->display == 'count'))
        die("Non auto IDs can't have counts for children!");

    }

    // Create a list of default tips if
    // not set

    $defaults = array(
      'create_submit'           => "Creates a completely new $this->label in addition to the current $this->label(s)",
      'control_cancel_button'   => "Cancels the current action and returns to the previous one",
      'control_home_button'     => "Cancels all actions and returns Home",
      'control_preview_button'  => "Previews current record(s) before commiting them to the database",
      'control_insert_button'   => "Inserts current record(s) into the database",
      'control_list_button'     => "Searches the database using the given criteria and displays a list of results",
      'control_choose_button'   => "Chooses the selected record(s)",
      'control_browse_button'   => "Chooses the selected record(s)",
      'control_research_button' => "Goes back to the search back to search on different criteria",
      'search_choose_button'    => "Selects this record only",
      'select_all_button'       => "Selects all of the displayed record(s)",
      'select_none_button'      => "Selects none of the displayed record(s)",
    );

    // Set them if not already set

    foreach ($defaults as $control=>$tip)
      if (!isset($this->helps['tip'][$control]))
        $this->helps['tip'][$control] = $tip;

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->initialize();

  }



  /*** Input ***/



  //// Grabs settings from html/sessions

  function settings() {

    // Grab the records if they're there

    $this->ambition = Relations_Admin_default($this->depth,'ambition','choose');
    $this->title = Relations_Admin_default($this->depth,'title',$this->title);
    $this->single = Relations_Admin_default($this->depth,'single',$this->single);
    $this->return_info = Relations_Admin_default($this->depth,'return_info',$this->return_info);

    if (!$this->return_info['url'] && ($return_url = Relations_Admin_default($this->depth,'return_url')))
      $this->return_info['url'] = $return_url;

    // Make sure title is set

    if (!strlen($this->title))
      $this->title = 'Relations-Admin';

    // Make sure chosen is an array

    if (!is_array($this->chosen))
      $this->chosen = array();

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->settings($this->depth);

  }



  //// Grabs default values from html/sessions

  function defaults() {

    // Grab the message and records if they're there

    $this->message = Relations_Admin_default($this->depth,'message',$this->message);

    if (!$this->records)
      $this->records = Relations_Admin_default($this->depth,'records',$this->records);

    // Make sure records are set

    if ($this->records < 1)
      $this->records = 1;

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->defaults($this->records,$this->depth);

  }



  //// Grabs input from html

  function entered() {

    // If we're to create records, add them
    // to the count

    if (Relations_Admin_grab('create',0,'VPG')) {

      $created = Relations_Admin_grab('created',0,'VPG');

      if ($created > 0) {

        for ($record = $this->records; $record < ($this->records + $created); $record++) {

          $this->intentions[$record] = 'insert';
          $this->ambitions[$record] = 'insert';

          // If we have no focus, set it

          if (!count($this->focus))
            $this->focus[$record] = true;

        }

        $this->records += $created;

      }

    }

    // If we're to change a record's intention

    if (($kill = Relations_Admin_grab('kill',-1,'VPG')) > -1) {

      if ($this->ambitions[$kill] == 'update')
        $this->intentions[$kill] = 'delete';
      else
        $this->intentions[$kill] = 'ignore';

      // Now go through inputs and have 'em do the 
      // same

      foreach (array_keys($this->inputs) as $name)
        $this->inputs[$name]->kill($kill);

      // If we have no focus, set it

      if (!count($this->focus))
        $this->focus[$kill] = true;

    }

    if (($raise = Relations_Admin_grab('raise',-1,'VPG')) > -1) {

      $this->intentions[$raise] = $this->ambitions[$raise];

      foreach (array_keys($this->inputs) as $name)
        $this->inputs[$name]->raise($raise);

      // If we have no focus, set it

      if (!count($this->focus))
        $this->focus[$raise] = true;

    }

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->entered($this->records);

    // Mass set everything
    
    $this->massed();

  }



  //// Grabs input from html for mass set

  function massed() {

    // Check to see if we're going to use this
    // mass value. If so, go through the records
    // and check to see if each record is to use
    // the mass set value. If so, set accordingly

    if (Relations_Admin_grab('mass',false,'VPG')) {

      // Go throught he inputs and tell 
      // them to do the same

      foreach (array_keys($this->inputs) as $name)
        $this->inputs[$name]->massed($this->records);

    }

  }



  //// Grabs input from html for searching

  function sought() {

    // Grab the message and records if they're there

    $this->message = Relations_Admin_default($this->depth,'message',$this->message);

    // Check to see if they clicked an initial

    $this->initial = Relations_Admin_grab('initial','','VPG');

    // Check to see if they typed a filter

    $this->filter = Relations_Admin_grab('filter','','VPG');

    // Check to see if they typed a limit

    $this->limit = Relations_Admin_grab('limit','','VPG');

    // If no limit, assume 0,30 so we 
    // don't return hundreds of rows

    if (!strlen($this->limit))
      $this->limit = '0,15';

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->sought();

  }



  //// Grabs chosen from html/sessions

  function chose(&$totals) {

    // Check to see if we already have values
    // chosen in the totals array

    if ($totals[$this->label]['choose']) {

      $this->chosen = Relations_toArray($totals[$this->label]['choose']);

    } else {

      // Get from session,post,get

      $this->chosen = Relations_toArray(Relations_Admin_default($this->depth,'chosen',array(),'VSPG'));

      // Increase our values in the totals array

      $totals[$this->label]['choose'] = $this->chosen;

    }

  }



  /*** Storage ***/



  //// Stores info not entered by the user

  function set() {

    // Save everything

    Relations_Admin_store($this->depth . '_title',$this->title);
    Relations_Admin_store($this->depth . '_single',$this->single);
    Relations_Admin_store($this->depth . '_records',$this->records);
    Relations_Admin_store($this->depth . '_originals',$this->originals);
    Relations_Admin_store($this->depth . '_replacees',$this->replacees);
    Relations_Admin_store($this->depth . '_ambition',$this->ambition);
    Relations_Admin_store($this->depth . '_ambitions',$this->ambitions);
    Relations_Admin_store($this->depth . '_intentions',$this->intentions);
    Relations_Admin_store($this->depth . '_redirected',$this->redirected);
    Relations_Admin_store($this->depth . '_return_info',$this->return_info);
    Relations_Admin_store($this->depth . '_child_infos',$this->child_infos);

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->set($this->depth);

  }



  //// Retrieves info not entered by the user

  function get() {

    // Get everything

    $this->title = Relations_Admin_retrieve($this->depth . '_title');
    $this->single = Relations_Admin_retrieve($this->depth . '_single');
    $this->records = Relations_Admin_retrieve($this->depth . '_records');
    $this->originals = Relations_Admin_retrieve($this->depth . '_originals');
    $this->replacees = Relations_Admin_retrieve($this->depth . '_replacees');
    $this->ambition = Relations_Admin_retrieve($this->depth . '_ambition');
    $this->ambitions = Relations_Admin_retrieve($this->depth . '_ambitions');
    $this->intentions = Relations_Admin_retrieve($this->depth . '_intentions');
    $this->redirected = Relations_Admin_retrieve($this->depth . '_redirected');
    $this->return_info = Relations_Admin_retrieve($this->depth . '_return_info');
    $this->child_infos = Relations_Admin_retrieve($this->depth . '_child_infos');

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->get($this->depth);

  }



  //// Stores info entered by the user

  function save() {

    // Save everything, including the important stuff

    $this->set();

    Relations_Admin_store($this->depth . '_filter',$this->filter);
    Relations_Admin_store($this->depth . '_initial',$this->initial);

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->save($this->depth);

  }



  //// Retrieves info entered by the user

  function load() {

    // Load everything, including the important stuff

    $this->get();

    $this->filter = Relations_Admin_retrieve($this->depth . '_filter');
    $this->initial = Relations_Admin_retrieve($this->depth . '_initial');

    // Go through the inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->load($this->depth);

  }



  /*** Identity ***/



  //// Check for allowance of record

  function allow($task,$id=null) {

    // If it's forbidden, F it

    if ($this->forbids[$task])
      return false;

    // If don't we have a secuirty object, 
    // allow it

    if (!is_object($this->security))
      return true;

    // If it passes permissions, allow it

    if ($this->security->allow($this->identity,$task,$id))
      return true;

    // Else deny it

    return false;

  }



  //// Check for denial of input

  function deny($task,$name,$id=null) {

    // If it's forbidden, deny it

    if ($this->inputs[$name]->forbids[$task])
      return true;

    // If don't we have a secuirty object, 
    // allow it

    if (!is_object($this->security))
      return false;

    // If it fails permissions, deny it

    if ($this->security->deny($this->identity,$name,$task,$id))
      return true;

    // Else allow it

    return false;

  }



  //// Logs everything that happened

  function event(&$totals) {

    // If we have a log object, use it

    if (is_object($this->logging))
      $this->logging->event($this->identity,$totals[$this->label]);

  }



  /*** Validate ***/



  //// Sets the intention for all records

  function oblige($intention) {

    // If we have no records, return

    if (!($this->records > 0))
      return;

    // If ambition is merge, we're replacing
    // everything but the first, else default

    if ($intention == 'merge') {

      $this->intentions = array_fill(0,$this->records,'replace');
      $this->intentions[0] = 'update';

    } else {

      $this->intentions = array_fill(0,$this->records,$intention);

    }

    // Set ambitions to intentions

    $this->ambitions = $this->intentions;

    // Now go through inputs and have 'em do the 
    // same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->oblige($intention,$this->records);

  }



  //// Takes the error messages and creates a
  //// message and an array for heed.

  function advise($errors) {

    // Get the advice  
  
    $this->error = Relations_Admin_advise($errors);

    // Set everyone else's

    $this->heed($this->error['numbers']);

  }



  //// Takes the error messages and creates an
  //// array for adding onto the labels.

  function heed($numbers) {

    // $numbers - Array of numbered errors 
    
    // Go through all the records. Use the value
    // in the HTML input fields. 

    for ($record = 0; $record < $this->records; $record++) {
    
      if (is_array($numbers[$this->prefix . $record]) &&
         (count($numbers[$this->prefix . $record]) > 0))
        $this->errors[$record] = $numbers[$this->prefix . $record];

    }      

    // Go through the inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->heed($numbers,$this->records);

  }



  //// Checks records against valids

  function validsValidate(&$errors) {

    // Go through all the valids

    foreach ($this->valids as $message=>$valid) {

      // If this valid exists as a function,
      // call function with this form, the 
      // errors array, and the message

      if (function_exists($valid)) {

        $valid($this,$errors,$message);

      }

    }

  }



  //// Checks records against uniques

  function uniquesValidate(&$errors) {

    // Go through all the records

    for ($record = 0; $record < $this->records; $record++) {

      // Skip if we're ignoring or deleting

      if (in_array($this->intentions[$record],array('ignore','delete')))
        continue;

      // Now let's check our uniques to make sure 
      // there's no duplicates in any of the records

      foreach (array_keys($this->uniques) as $unique) {

        // Get the inputs in array form

        $inputs = Relations_toArray($this->uniques[$unique]);

        // Create a data array to send to all the inputs
        // and the basics of a query

        $us = array();
        $query = new Relations_Query('count(*)',"$this->database.$this->table");

        // Go through all the unique inputs and have them
        // add their value to the us array

        foreach ($inputs as $input) {

          $this->inputs[$input]->toSQL($us,'data',$record);
          $this->inputs[$input]->fromSQL($query,'select',$this->intentions[$record],$this->table,$record);

        }

        // Now add our id input if we're not inserting
        // or copying so when don't query against us

        if (!in_array($this->intentions[$record],array('insert','copy')))
          $query->add(array(_where =>
            $this->inputs[$this->id_input]->field . "!='" . mysql_escape_string($this->originals[$record]) . "'"
          ));

        // Try to get a count from the query

        $total = $this->abstract->selectField(array(
          _field => 'count(*)',
          _query => $query
        ));

        // Check the inputs to see if any of the unqiue members 
        // values are identical

        $match = false;

        for ($other = 0; $other < $this->records; $other++) {

          // Don't compare against ourselves

          if ($other == $record)
            continue;

          // Skip this one if it's scheduled for deletion or ignoring

          if (in_array($this->intentions[$other],array('ignore','delete')))
            continue;

          // Go through all the unique inputs and have them
          // add their value to the select clause

          $them = array();

          foreach ($inputs as $input)
            $this->inputs[$input]->toSQL($them,'data',$other);

          // If they're the same, we have a match

          if ($us == $them)
            $match = true;

        }

        // If the numbers greater than 0 or there's 
        // a match, these ain't unique. Go through 
        // all the unique inputs, and add their info 
        // to errors

        if (($total > 0) || $match)
          foreach ($inputs as $input)
            $errors[$this->prefix . $input . '_' . $record][] = "$unique must be unique";

      }

    }

  }



  //// Checks values for existance

  function existsValidate(&$errors,$intentions,$records,$input) {

    // Go through all records to tally all ids

    $ins = array();

    for ($record = 0; $record < $records; $record++) {

      // Skip if we're ignoring or deleting

      if (!in_array($intentions[$record],array('ignore','delete'))) {

        // If this is an array, add the values one 
        // by one, else, just add this value

        if (is_array($input->values[$record])) {

          foreach ($input->values[$record] as $value)
            $ins[] = "'" . mysql_escape_string($value) . "'";

        } else {

          $ins[] = "'" . mysql_escape_string($input->values[$record]) . "'";

        }

      }

    }

    // Get only uniques

    $ins = array_unique($ins);

    // Return if there's nothing to check

    if (!count($ins))
      return;

    // Now get all ids from the database and hash them

    $ids = Relations_toHash($this->abstract->selectColumn(
      $this->id_field,
      $this->database . '.' . $this->table,
      $this->id_field . " in (" . join(',',$ins) . ")"
    ));

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

          if (!$ids[$value]) {

            $errors[$input->prefix . $input->name . '_' . $record][] = "Value does not exist";
            break;

          }

        }

      } else {

        // Check to see if value exists

        if (!$ids[$input->values[$record]])
          $errors[$input->prefix . $input->name . '_' . $record][] = "Value does not exist";

      }

    }

  }



  //// Checks values for relatability

  function relateValidate(&$errors,$intentions,$records,$input) {

    // Go through all records to tally all ids

    $relating = array();

    for ($record = 0; $record < $records; $record++) {

      // Skip if we're ignoring

      if ($intentions[$record] == 'ignore')
        continue;

      // If this is an array, add the values one 
      // by one, else, just add this value

      if (is_array($input->values[$record])) {

        foreach ($input->values[$record] as $value)
          $relating[] = $value;

      } else {

        $relating[] = $input->values[$record];

      }

    }

    // Get only uniques

    $relating = array_unique($relating);

    // Return if there's nothing to check

    if (!count($relating))
      return;

    // Now check all the ids

    $relatable = array();

    foreach ($relating as $id)
      $relatable[$id] = $this->allow('relate',$id);

    // Go through all the record to check

    for ($record = 0; $record < $records; $record++) {

      // Skip if we're ignoring

      if ($intentions[$record] == 'ignore')
        continue;

      // If this is an array, add the values one 
      // by one, else, just add this value

      if (is_array($input->values[$record])) {

        // Check to see if values exist

        foreach ($input->values[$record] as $value) {

          if (!$relatable[$value]) {

            $errors[$input->prefix . $input->name . '_' . $record][] = "Cannot relate value";
            break;

          }

        }

      } else {

        // Check to see if value exists

        if (!$relatable[$input->values[$record]])
          $errors[$input->prefix . $input->name . '_' . $record][] = "Cannot relate value";

      }

    }

  }



  //// Makes sure everything is allowed for toDB
  
  function allowValidate(&$errors) {

    // Go through all the records 

    for ($record = 0; $record < $this->records; $record++) {

      // Figure out the original id value and the 
      // new one

      $value = $this->ID($record);
      $original = $this->originals[$record];

      // See what we intend to do with this one

      switch ($this->intentions[$record]) {

        case 'insert':

          // Create a set clause to send to all the inputs

          $set = array();

          foreach (array_keys($this->inputs) as $name)
            $this->inputs[$name]->toSQL($set,'data',$record);

          // Check permissions

          if (!$this->allow('insert',$set))
            $errors[$this->prefix . $record][] = "Cannot insert";

          break;

        case 'copy':

          // Create a set clause to send to all the inputs

          $set = array();

          foreach (array_keys($this->inputs) as $name)
            $this->inputs[$name]->toSQL($set,'data',$record);

          // Check permissions

          if (!$this->allow('insert',$set))
            $errors[$this->prefix . $record][] = "Cannot insert";

          if (!$this->allow('copy',$original))
            $errors[$this->prefix . $record][] = "Cannot copy";

          break;

        case 'update':
        case 'replace':

          // Check permissions

          if (!$this->allow($this->intentions[$record],$value))
            $errors[$this->prefix . $record][] = "Cannot " . $this->intentions[$record];

          // If the id value changed, indicate 
          // the new one was replaced too

          if ($value != $original)
            if (!$this->allow('replace',$original))
              $errors[$this->prefix . $record][] = "Cannot replace";

          break;

        case 'delete':

          // Check permissions

          if (!$this->allow('delete',$original))
            $errors[$this->prefix . $record][] = "Cannot delete";

          break;

        case 'ignore':

          $totals[$this->label][$this->intentions[$record]]++;
          break;

      }

    }

  }



  //// Makes sure everything is valid for toDB
  
  function toValidate(&$errors) {

    // Check valids and uniques

    $this->allowValidate($errors);
    $this->validsValidate($errors);
    $this->uniquesValidate($errors);

    // Now go through all the inputs and have them do 
    // what they have to do. 

    foreach (array_keys($this->inputs) as $name) {
  
      $this->inputs[$name]->toValidate($errors,$this->intentions,$this->records);

    }

    // Return whether there's any errors

    return count($errors) == 0 ? true : false;

  }



  //// Makes sure everything is valid for fromDB
  
  function fromValidate(&$errors) {

    // Now go through all the inputs and have them do 
    // what they have to do. 

    foreach (array_keys($this->inputs) as $name) {

      $this->inputs[$name]->fromValidate($errors,$this->intentions,$this->records);

    }

    // Return whether there's any errors

    return count($errors) == 0 ? true : false;

  }



  /*** Database ***/



  //// Gets the record's ID value

  function ID($record) {

    // Return the ID input's value

    return $this->inputs[$this->id_input]->values[$record];

  }



  //// Calculates an value for children

  function childSQL($descendants,$field,$value) {

    // If there's no prefix, standard
    // values, if there is create a 
    // regex pattern to match the front

    if ($descendants != 'prefix')
      return "'" . mysql_escape_string($value) . "'";
    else
      return "^" . mysql_escape_string($value) . "/[^/]+\\$";

  }



  //// Calculates a where clause for children

  function childWhere($descendants,$field,$values) {

    // If there's no prefix, standard
    // values, if there is make the 
    // regex complete

    if ($descendants != 'prefix')
      return "$field in (" . join(',',$values) . ")";
    else
      return "$field regexp '" . join('|',$values) . "'";

  }



  //// Determines if a child exists for a given value

  function childExists($descendants,&$parents,$value) {

    // A regular form has no prefix

    if ($descendants != 'prefix') {

      if ($parents[$value])
        return true;

    } else {

      foreach (array_keys($parents) as $parent) {

        if ($value_check == substr($parent_check,0,strlen($value_check)))
          return true;

      }

    }

    // Nothing, we're fine

    return false;

  }



  //// Creates a regex to use for replacements

  function ChildReplace($descendants,$value) {

    // There's no prefix stuff here

    if ($descendants != 'prefix')
      return '/^' . preg_quote($value,'/') . '$/';
    else
      return '/^' . preg_quote($value,'/') . '/';

  }



  //// Gets the values for a specific record

  function childValues($descendants,$display,&$local_records,&$local_originals) {

    // Initialize the values

    $values = array();

    // Go through all the rows and attach this value to the right record

    for ($find = 0; $find < count($this->local_originals); $find++) {

      if ($descendants != 'prefix') {

        if ($display != 'count')
          $values[$local_records[$this->local_originals[$find]]][] = $this->ids[$find];
        else
          $values[$local_records[$this->local_originals[$find]]] += $this->counts[$find];

      } else {

        for ($local = 0; $local < count($local_records); $local++) {

          if ($local_originals[$local] == substr($this->local_originals[$find],0,strlen($local_originals[$local]))) {

            if ($display != 'count')
              $values[$local_records[substr($this->local_originals[$find],0,strlen($local_originals[$local]))]][] = $this->ids[$find];
            else
              $values[$local_records[substr($this->local_originals[$find],0,strlen($local_originals[$local]))]] += $this->counts[$find];

          }

        }

      }

    }

    return $values;

  }



  //// Merges the relations from all to first

  function merge(&$totals) {

    // First grab the replacer's row

    // Set the select value to zero for now

    $totals[$this->label]['merge'] = array();

    // Go through all the rows

    for ($record = 0; $record < $this->records; $record++)
      $totals[$this->label]['merge'][] = $this->ID($record);

    // Now merge everyone else

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->merge($totals,$this->records);

  }



  //// Creates the database, not necessary for
  //// regular forms, but the special ones

  function makeDB() {

    // De nada

  }



  //// Sends data to the database
  
  function toDB(&$errors,&$totals) {

    // First check toValidate

    if (!$this->toValidate($errors))
      return false;

    // Go through all the records 

    for ($record = 0; $record < $this->records; $record++) {

      // Figure out the original id value and the 
      // new one

      $value = $this->ID($record);
      $original = $this->originals[$record];

      // Create a set clause to send to all the inputs

      $set = '';

      foreach (array_keys($this->inputs) as $name)
        if (!$this->deny($this->intentions[$record],$name,$original))
          $this->inputs[$name]->toSQL($set,$this->intentions[$record],$record);

      // See what we intend to do with this one

      switch ($this->intentions[$record]) {

        case 'insert':

          // Do an insert ID to get the ID
          // if the id field is an auto field,
          // else do an insert row

          if ($this->inputs[$this->id_input]->auto) {

            $value = $this->abstract->insertID("$this->database.$this->table",$set);
            $totals[$this->label][$this->intentions[$record]][] = $value;

          } else {

            $this->abstract->insertRow("$this->database.$this->table",$set);
            $totals[$this->label][$this->intentions[$record]][] = $value;

          }

          break;

        case 'copy':

          // Do an insert ID to get the ID
          // if the id field is an auto field,
          // else do an insert row

          if ($this->inputs[$this->id_input]->auto) {

            $value = $this->abstract->insertID("$this->database.$this->table",$set);
            $totals[$this->label][$this->intentions[$record]][] = $original;
            $totals[$this->label]['insert'][] = $value;

          } else {

            $this->abstract->insertRow("$this->database.$this->table",$set);
            $totals[$this->label][$this->intentions[$record]][] = $original;
            $totals[$this->label]['insert'][] = $value;

          }

          break;

        case 'update':
        case 'replace':

          // Update with the original

          $this->abstract->updateRows("$this->database.$this->table","$this->id_field='$original'",$set);
          $totals[$this->label][$this->intentions[$record]][] = $value;

          // If the id value changed, indicate 
          // the new one was replaced too

          if ($value != $original)
            $totals[$this->label]['replace'][] = $original;

          break;

        case 'delete':

          // Get the ID and delete

          $this->abstract->deleteRows("$this->database.$this->table","$this->id_field='$original'");
          $totals[$this->label][$this->intentions[$record]][] = $original;
          break;

        case 'ignore':

          $totals[$this->label][$this->intentions[$record]]++;
          break;

      }

      // If there was any errors, log and return

      if (mysql_error())
        $errors[$this->prefix . $record][] = mysql_error();

      // If we didn't delete or ignore

      if (!in_array($this->intentions[$record],array('ignore','delete'))) {

        // Get the row 

        $row = $this->abstract->selectRow("$this->database.$this->table","$this->id_field='" . mysql_escape_string($value) . "'");

        if (mysql_error())
          $errors[$this->prefix . $record][] = mysql_error();

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

    // Copy our query to send to all the inputs

    $query = clone $this->query;

    // if we want counts all a count fields, and
    // initialize counts. If we want listing, 
    // initialize ids and labe. If we want records, 
    // add the all the fields, and initialize 
    // originals

    switch ($purpose) {

      case 'count':

        $query->set("count(*) as 'count'");
        $this->counts = array();
        break;

      case 'list':

        $this->ids = array();
        $this->labels = array();
        break;

      case 'select':

        $query->add("$this->table.*");
        $this->orginals = array();
        break;

    }

    // See what we intend to do with this one,
    // If we're searching, do everything, selecting
    // check chosen and inputs, custom just add the
    // custom info

    switch ($intention) {

      case 'search':

        // Add initial and filter

        if (!empty($this->initial))
          $query->add(array(_having => "lcase(label) like lcase('" . mysql_escape_string($this->initial) . "%')"));

        if (!empty($this->filter))
          $query->add(array(_having => "lcase(label) like lcase('%" . mysql_escape_string($this->filter) . "%')"));
         
        if (!empty($this->limit))
          $query->add(array(_limit => mysql_escape_string($this->limit)));
         
      case 'select':

         // Have all inputs do the same

        foreach (array_keys($this->inputs) as $name)
          $this->inputs[$name]->fromSQL($query,$intention,$ambition,$this->table,0);

      case 'choose':

       // Add chosen

        if (is_array($this->chosen) && count($this->chosen)) {

          // Get only uniques

          $this->chosen = array_unique($this->chosen);

          // Create the ins array

          $ins = array();

          // Escape all chosen ids and add

          foreach ($this->chosen as $id)
            $ins[] = "'" . mysql_escape_string($id) . "'";

          // If we're merging, we need to all a little
          // extra to the query so that the first id 
          // chosen is the first row returned

          if ($ambition == 'merge') {

            $query->add("if($this->table.$this->id_field=$ins[0],0,1) as _merging");
            $query->set(array(_order_by => Relations_commaClauseAdd("_merging",$query->order_by)));

            // Escape all replacee ids and add

            foreach ($this->replacees as $id)
              $ins[] = "'" . mysql_escape_string($id) . "'";

          }

          $query->add(array(_where => "$this->table.$this->id_field in (" . join(',',$ins) . ")"));

        }

      case 'custom':

        // Add the custom info if sent

        if (is_array($set) && count($set))
          $query->set($set);
         
        if (is_array($add) && count($add))
          $query->add($add);
         
      case 'all':

    }

    // Initialize records

    $this->records = 0;

    // Get all the rows found

    $finds = $this->abstract->selectMatrix(array(_query => $query));

    // If there was any errors, log

    if (mysql_error())
      $errors[$this->prefix . $record][] = mysql_error();

    // If there's nothing, return

    if (!count($finds))
      return false;

    // See what the purpose is 

    switch ($purpose) {

      case 'count':

        // Go through extras too

        foreach ($extras as $name=>$field)
          $this->$name = array();

        foreach ($finds as $find) {

          // Check permissions

          if (!$this->allow('count'))
            continue;

          $this->counts[] = $find['count'];
          $totals[$this->label]['count'] += $find['count'];
          $this->records += $find['count'];

          // Go through extras too

          foreach ($extras as $name=>$field)
            array_push($this->$name,$find[$field]);

        }

        break;

      case 'list':

        // Go through extras too

        foreach ($extras as $name=>$field)
          $this->$name = array();

        foreach ($finds as $find) {

          // Check permissions

          if (!$this->allow($ambition,$find['id']))
            continue;

          $this->ids[] = $find['id'];
          $this->labels[$find['id']] = $find['label'];
          $totals[$this->label]['list'][] = $find['id'];
          $this->records++;

          // Go through extras too

          foreach ($extras as $name=>$field)
            array_push($this->$name,$find[$field]);

        }

        // Update totals appropriately

        $this->records = count($this->ids);

        break;

      case 'select':

        // Go through extras too

        foreach ($extras as $name=>$field)
          $this->$name = array();

        // Go through all the rows

        for ($record = 0; $record < count($finds); $record++) {

          // Check permissions

          if (!$this->allow($ambition,$finds[$record][$this->id_field]))
            continue;

          // Now get all the info from the db using the id value

          foreach (array_keys($this->inputs) as $name)
            if (!$this->deny($ambition,$name,$finds[$record][$this->id_field]))
              $this->inputs[$name]->setSQL($finds[$record],$this->records);

          // Go through extras too

          foreach ($extras as $name=>$field)
            array_push($this->$name,$finds[$record][$field]);

          // Set originals

          $this->originals[$this->records] = $this->ID($this->records);

          // Increase our values in the totals array

          $totals[$this->label]['select'][] = $finds[$record][$this->id_field];

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

        // If our ambition is to merge, do so

        if ($ambition == 'merge')
          $this->merge($totals);

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



  //// Takes the totals and tallies what happened

  function inform($totals) {

    Relations_Admin_info($this->info,$totals);

  }



  //// Stores what everyone did

  function finish($totals) {

    // Save the totals if we're not
    // the last

    if ($this->depth)
      Relations_Admin_store(($this->depth-1) . '_results',$totals);

  }



  //// Grabs what everyone did

  function results() {

    // Grab the last totals

    //print "Depth " . $this->depth . "<br>";

    return Relations_Admin_retrieve($this->depth . '_results');

  }



  //// Creates the choose message 

  function messageChoose($ambition) {

    // Figure out what we're doing

    switch ($ambition) {

      case 'replacer':

        $this->message .= 'Please choose the record to replace the other(s)';
        break;

      case 'replacee':

        $this->message .= 'Please choose the record(s) to be replaced';
        break;

      default:

        $this->message .= 'Please choose record(s) for ' . ucfirst($ambition);

    }

  }



  //// Creates the proper ambition 

  function ambitionChoose($ambition) {

    // Figure out what we're doing

    switch ($ambition) {

      case 'replacer':

        return 'update';
        break;

      case 'replacee':

        return 'replace';
        break;

      default:

        return $ambition;

    }

  }



  //// Redirects to choose function 

  function redirectChoose($ambition='select',$state='choose',$single=0) {

    // Set the info to get there

    $choose_info = array(
      'url'    => $this->self_url,
      'values' => array()
    );

    // Set the values

    $choose_info['values']['task'] = 'choose';
    $choose_info['values']['ambition'] = $ambition;
    $choose_info['values']['single'] = $single;
    $choose_info['values']['depth'] = $this->depth + 1;
    $choose_info['values']['mode'] = $this->mode;

    // Create the url to return to us

    $choose_info['values']['return_info'] = array();
    $choose_info['values']['return_info']['url'] = $this->self_url;
    $choose_info['values']['return_info']['values'] = array();
    $choose_info['values']['return_info']['values']['task'] = $this->task;
    $choose_info['values']['return_info']['values']['state'] = $state;
    $choose_info['values']['return_info']['values']['depth'] = $this->depth;
    $choose_info['values']['return_info']['values']['mode'] = $this->mode;

    // Send and get URL

    $this->redirect_url = Relations_Admin_redirect($choose_info);

    // Set the state to redirect

    $this->state = 'redirect';

  }



  //// Redirects to admin function 

  function redirectAdmin($chosen,$task='select',$state='changed',$current=array()) {

    // Set the depth

    $admin_info = array();
    $admin_info['values'] = array();
    $admin_info['url'] = $this->self_url;
    $admin_info['values']['depth'] = $this->depth + 1;
    $admin_info['values']['task'] = Relations_Admin_grab('redirect_task');
    $admin_info['values']['chosen'] = $chosen;
    $admin_info['values']['mode'] = $this->mode;

    // Create the url to return to us

    $admin_info['values']['return_info'] = array();
    $admin_info['values']['return_info']['url'] = $this->self_url;
    $admin_info['values']['return_info']['values'] = array();
    $admin_info['values']['return_info']['values']['task'] = $task;
    $admin_info['values']['return_info']['values']['state'] = $state;
    $admin_info['values']['return_info']['values']['depth'] = $this->depth;
    $admin_info['values']['return_info']['values']['chosen'] = $current;
    $admin_info['values']['return_info']['values']['mode'] = $this->mode;

    // Send and get url

    $this->redirect_url = Relations_Admin_redirect($admin_info);

    // Set the state to redirect

    $this->state = 'redirect';

  }



  //// Redirects to insert function 

  function redirectInsert() {

    // Set the depth

    $insert_info = array();
    $insert_info['values'] = array();
    $insert_info['url'] = $this->self_url;
    $insert_info['values']['depth'] = $this->depth + 1;
    $insert_info['values']['task'] = 'insert';
    $insert_info['values']['single'] = $this->single;
    $insert_info['values']['mode'] = $this->mode;

    // Create the url to return to us

    $insert_info['values']['return_info'] = array();
    $insert_info['values']['return_info']['url'] = $this->self_url;
    $insert_info['values']['return_info']['values'] = array();
    $insert_info['values']['return_info']['values']['task'] = 'choose';
    $insert_info['values']['return_info']['values']['state'] = 'inserted';
    $insert_info['values']['return_info']['values']['depth'] = $this->depth;
    $insert_info['values']['return_info']['values']['mode'] = $this->mode;

    // Send and get url

    $this->redirect_url = Relations_Admin_redirect($insert_info);

    // Set the state to redirect

    $this->state = 'redirect';

  }



  //// Processes the input's redirecting

  function redirectProcess() {

    //print "Wah<br>";

    // Get who redirected, etc

    $task = Relations_Admin_grab('redirect_task');
    $this->redirected = Relations_Admin_grab('redirect_name');
    $record = Relations_Admin_grab('redirect_record');

    // Get the info to get there

    $redirect_info = $this->inputs[$this->redirected]->redirectProcess($task,$record);

    // Set the depth

    $redirect_info['values']['depth'] = $this->depth + 1;
    $redirect_info['values']['mode'] = $this->mode;

    // Create the url to return to us

    $redirect_info['values']['return_info'] = array();
    $redirect_info['values']['return_info']['url'] = $this->self_url;
    $redirect_info['values']['return_info']['values'] = array();
    $redirect_info['values']['return_info']['values']['task'] = $this->task;
    $redirect_info['values']['return_info']['values']['state'] ='return';
    $redirect_info['values']['return_info']['values']['depth'] = $this->depth;
    $redirect_info['values']['return_info']['values']['mode'] = $this->mode;

    // Send and get url

    $this->redirect_url = Relations_Admin_redirect($redirect_info);

  }



  //// Processes the input's returning

  function returnProcess($totals) {

    // Call the inputs function

    $this->inputs[$this->redirected]->returnProcess($totals);

  }



  //// Processes user clicking Home

  function homeProcess() {

    // Set the redirect to home

    $this->redirect_url = $this->home_url;

    // Set state to finish

    $this->state = 'home';

  }



  //// Processes user clicking Cancel

  function cancelProcess() {

    // If don't have a return URL return,
    // use home, else use the return one

    if ($this->return_info['url']) {

      $this->redirect_url = Relations_Admin_redirect($this->return_info);
      $this->state = 'finish';

    } else { 
      
      $this->redirect_url = $this->home_url;
      $this->state = 'home';

    }

  }



  //// Processes when everything's finished

  function finishProcess() {

    // If don't have a return URL return,
    // use home, else use the return one

    if ($this->return_info['url']) {

      $this->redirect_url = Relations_Admin_redirect($this->return_info);
      $this->state = 'finish';

    } else { 
      
      $this->state = 'home';

    }

  }



  //// Processes admins

  function adminProcess() {

    // Go though the states

    switch ($this->state) {

      // If we're cancelling, do so

      case 'cancel':

        $this->cancelProcess();
        break;

      // If we're going home

      case 'home':

        $this->homeProcess();
        break;

    }

  }



  //// Processes inserts

  function insertProcess() {

    //print "Insert";

    // Go though the states

    switch ($this->state) {

      // Just starting out, set the 
      // state and format to enter

      case 'start':

        $this->state = 'enter';
        $this->defaults();
        $this->oblige('insert');
        break;

      // If we're redirecting, get what was entered
      // and redirect.

      case 'redirect':

        //print "redirect";

        $this->entered();
        $this->redirectProcess();
        break;

      // If we're coming back, get what we saved,
      // figure out which input is be returned to,
      // and have it do its thing.

      case 'return':

        $this->load($this->depth);
        $totals = $this->results();
        $this->returnProcess($totals);
        $this->inform($totals);
        $this->state = 'enter';
        break;

      // If we're at enter, something else
      // was pressed

      case 'enter':

        $this->entered();
        break;

      // If the user wants to preview,
      // check for errors, go back to
      // entering if there is any

      case 'preview':

        $this->entered();
        $errors = array();

        if ($this->toValidate($errors)) {

          $this->save($this->depth);

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
        
      // If they want to enter again,
      // load what was saved and go
      // back to entering

      case 'reenter':

        $this->load($this->depth);
        $this->state = 'enter';
        break;
        
      // If user confirmed, load the
      // info, insert the records, but
      // go back to entering if there's
      // a problem

      case 'confirm':

        $this->load($this->depth);
        $errors = array();
        $totals = array();

        if ($this->toDB($errors,$totals)) {

          $this->inform($totals);
          $this->finish($totals);
          $this->finishProcess();

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
                          
      // If the user just wants to
      // go ahead, get what they
      // entered and try to send it
      // to the database. Let them
      // know if something's wrong

      case 'execute':

        $this->entered();
        $errors = array();
        $totals = array();

        if ($this->toDB($errors,$totals)) {

          $this->inform($totals);
          $this->finish($totals);
          $this->finishProcess();

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
        
    }

  }



  //// Processes chooses

  function chooseProcess() {

    // Go though the states

    switch ($this->state) {

      // Just starting out, set the 
      // state to search

      case 'start':

        $this->messageChoose($this->ambition);
        $this->sought();
        $this->state = 'search';

        break;

      // This really shouldn't be necessary,
      // but is here for future use

      case 'search':

        $this->messageChoose($this->ambition);
        $this->sought();
        break;

      // Go back to searching

      case 'research':

        $this->sought();
        $this->load($this->depth);
        $this->messageChoose($this->ambition);
        $this->state = 'search';
        break;

      // If we're redirecting, get what was entered
      // and redirect.

      case 'redirect':

        $this->sought();
        $this->redirectProcess();
        break;

      // If we're coming back, get what we saved,
      // figure out which input is be returned to,
      // and have it do its thing.

      case 'return':

        $this->load($this->depth);
        $this->messageChoose($this->ambition);
        $totals = $this->results();
        $this->returnProcess($totals);
        $this->inform($totals);
        $this->state = 'search';
        break;

      // If the user wants to list the records,
      // get what was sought, se what matches.
      // If there isn't any records, go back to 
      // searching. Also save everything that's
      // being searched on.

      case 'list':

        $this->load($this->depth);
        $this->sought();
        $this->save($this->depth);
        $errors = array();
        $totals = array();
        $this->fromDB($errors,$totals,'list','search',$this->ambitionChoose($this->ambition));
        $this->inform($totals);

        if (!$this->records) {

          $this->message = 'No Records Found';
          $this->state = 'search';
          $this->records = 1;

        }

        break;

      // If the user wants to limit the records,
      // get what was saved, see what matches.
      // If there isn't any records, go back to 
      // searching. Also save everything that's
      // being searched on.

      case 'limit':

        // Check to see if they typed a limit

        $this->load($this->depth);
        $this->limit = Relations_Admin_grab('limit',$this->limit,'VPG');
        $errors = array();
        $totals = array();
        $this->fromDB($errors,$totals,'list','search',$this->ambitionChoose($this->ambition));
        $this->inform($totals);
        $this->state = 'list';

        if (!$this->records) {

          $this->message = 'No Records Found';
          $this->state = 'search';
          $this->records = 1;

        }

        break;

      // If we're at browse, we need to
      // get what was selected, or get
      // what they're searching on

      case 'browse':

        $errors = array();
        $totals = array();
        $this->chose($totals);
        $this->save($this->depth);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose',$this->ambitionChoose($this->ambition));

        } else {
        
          $this->load($this->depth);
          $totals = array();
          $this->fromDB($errors,$totals,'select','search',$this->ambitionChoose($this->ambition));

          if (!$this->records)
            $this->state = 'search';
          else
            $this->state = 'list';

        }

        $this->inform($totals);
        break;

      // If we're at view, we need to
      // get what was selected

      case 'view':

        $errors = array();
        $totals = array();
        $this->chose($totals);

        if (count($this->chosen) > 0)
          $this->fromDB($errors,$totals,'select','choose',$this->ambitionChoose($this->ambition));
        else
          $this->state = 'search';

        if (!($this->records > 0))
          $this->state = 'search';

        $this->inform($totals);
        break;

      // If they click insert

      case 'insert':

        $this->sought();
        $this->save($this->depth);
        $this->redirectInsert();
        break;

      // If they click insert

      case 'inserted':

        $inserts = $this->results();

        if (count($inserts[$this->label]['insert'])) {

          $totals = array();
          $totals[$this->label]['choose'] = $inserts[$this->label]['insert'];
          $this->finish($totals);
          $this->finishProcess();

        } else {

          $this->load($this->depth);
          $this->state = 'search';

        }

        $this->inform($totals);
        break;

      // If they picked a record,
      // get it, and save it for
      // the next form

      case 'choose':

        $totals = array();
        $this->sought();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->finish($totals);
          $this->finishProcess();

        } else {

          $this->state = 'search';

        }

        $this->inform($totals);
        break;

    }

  }



  //// Processes selects

  function selectProcess() {

    // Go though the states

    switch ($this->state) {

      // Just starting out, see if anything's
      // chosen. If not, go to Choose

      case 'start':

        $errors = array();
        $totals = array();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose','select');
          $this->inform($totals);

          if ($this->records > 0)
            $this->state = 'link';

        }

        if ($this->state != 'link')
          $this->redirectChoose('select');

        break;

      // If we're returning from Choose,
      // get what's chosen. If we don't 
      // find anything, Cancel

      case 'choose':

        $errors = array();
        $totals = $this->results();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose','select');
          $this->inform($totals);

          if ($this->records > 0)
            $this->state = 'link';

        } 

        if ($this->state != 'link')
          $this->cancelProcess();

        break;

      // If we're returning, rechoose, and 
      // let the user know what changed. If
      // There's nothing chosen now, store
      // the results, and finish

      case 'return':

        $errors = array();
        $totals = $this->results();
        $originals = $totals;
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose','select');
          $this->inform($totals);

          if ($this->records > 0)
            $this->state = 'link';

        }

        if ($this->state != 'link') {

          $this->finish($originals);
          $this->finishProcess();

        }

        break;

      // Want to perform an Admin, get the redirect task
      // the chosen, and set for the return

      case 'admin':

        $totals = array();
        $this->chose($totals);

        if (count($this->chosen) > 0) 
          $this->redirectAdmin($this->chosen,'select','changed',$this->inputs[$this->id_input]->values);
        else 
          $this->state = 'link';

        break;

      // Coming back from a change

      case 'changed':

        $totals = array();
        $originals = $totals;
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose','select');
          $this->inform($totals);

          if ($this->records > 0)
            $this->state = 'link';

        }

        if ($this->state != 'link') {

          $this->finish($originals);
          $this->finishProcess();

        }

        break;

    }

  }



  //// Gets the redirect info for children

  function updateInfosProcess() {

    // Create the array

    $this->child_infos = array();

    // Now go through all the inputs and have them do 
    // what they have to do. 

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->updateInfosProcess($this->child_infos,$this->intentions,$this->records);

  }



  //// Process the update's children if the id changed

  function updateChildrenProcess() {

    // Shift out an Info

    if ($redirect_info = array_shift($this->child_infos)) {

      // Set the depth

      $redirect_info['values']['depth'] = $this->depth + 1;
      $redirect_info['values']['mode'] = $this->mode;

      // Create the url to return to us

      $redirect_info['values']['return_info'] = array();
      $redirect_info['values']['return_info']['url'] = $this->self_url;
      $redirect_info['values']['return_info']['values'] = array();
      $redirect_info['values']['return_info']['values']['task'] = 'update';
      $redirect_info['values']['return_info']['values']['state'] ='children';
      $redirect_info['values']['return_info']['values']['depth'] = $this->depth;
      $redirect_info['values']['return_info']['values']['mode'] = $this->mode;

      // Send and get url

      $this->redirect_url = Relations_Admin_redirect($redirect_info);

      // Set the state to redirect

      $this->state = 'redirect';

    } else {

      $this->finishProcess();

    }

  }



  //// Processes updates

  function updateProcess() {

    // Go though the states

    switch ($this->state) {

      // Just starting out, see if anything's
      // chosen and if so, get it and override
      // it's values with the defaultes. If 
      // not, go to Choose

      case 'start':

        $errors = array();
        $totals = array();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose','update');
          $this->inform($totals);

          if ($this->records > 0) {

            $this->state = 'enter';
            $this->defaults();

          }

        }

        if ($this->state != 'enter')
          $this->redirectChoose('update');

        break;

      // If we're returning from Choose,
      // get what's chosen. If we don't 
      // find anything, Cancel

      case 'choose':

        $errors = array();
        $totals = $this->results();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose','update');

          if ($this->records > 0) {

            $this->state = 'enter';

          }

        } 

        if ($this->state != 'enter')
          $this->cancelProcess();

        $this->inform($totals);
        break;

      // If we're at enter, something else
      // was pressed

      case 'enter':

        $this->entered();
        break;

      // If we're redirecting, get what was entered
      // and redirect.

      case 'redirect':

        $this->entered();
        $this->redirectProcess();
        break;

      // If we're coming back, get what we saved,
      // figure out which input is be returned to,
      // and have it do its thing.

      case 'return':

        $this->load($this->depth);
        $totals = $this->results();
        $this->returnProcess($totals);
        $this->inform($totals);
        $this->state = 'enter';
        break;

      // If the user wants to preview,
      // check for errors, go back to
      // entering if there is any

      case 'preview':

        $this->entered();
        $errors = array();

        if ($this->toValidate($errors)) {

          $this->save($this->depth);

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
        
      // If they want to enter again,
      // load what was saved and go
      // back to entering

      case 'reenter':

        $this->load($this->depth);
        $this->state = 'enter';
        break;
        
      // If user confirmed, load the
      // info, update the records, but
      // go back to entering if there's
      // a problem

      case 'confirm':

        $this->load($this->depth);
        $errors = array();

        if ($this->toDB($errors,$totals)) {

          $this->inform($totals);
          $this->finish($totals);
          $this->updateInfosProcess();
          $this->updateChildrenProcess();

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
                          
      // If the user just wants to
      // go ahead, get what they
      // entered and try to send it
      // to the database. Let them
      // know if something's wrong

      case 'execute':

        $this->entered();
        $errors = array();

        if ($this->toDB($errors,$totals)) {

          $this->inform($totals);
          $this->finish($totals);
          $this->updateInfosProcess();
          $this->updateChildrenProcess();

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
        
      // Get what was done previously,
      // let the user know, and then
      // try the next child info

      case 'children':

        $totals = $this->results();
        $this->inform($totals);
        $this->finish($totals);
        $this->updateChildrenProcess();

        break;

    }

  }



  //// Gets the redirect info for children

  function copyInfosProcess() {

    // Create the array

    $this->child_infos = array();

    // Now go through all the inputs and have them do 
    // what they have to do. 

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->copyInfosProcess($this->child_infos,$this->intentions,$this->records);

  }



  //// Process the copy's children

  function copyChildrenProcess() {

    // Shift out an Info

    if ($redirect_info = array_shift($this->child_infos)) {

      // Set the depth

      $redirect_info['values']['depth'] = $this->depth + 1;
      $redirect_info['values']['mode'] = $this->mode;

      // Create the url to return to us

      $redirect_info['values']['return_info'] = array();
      $redirect_info['values']['return_info']['url'] = $this->self_url;
      $redirect_info['values']['return_info']['values'] = array();
      $redirect_info['values']['return_info']['values']['task'] = 'copy';
      $redirect_info['values']['return_info']['values']['state'] ='children';
      $redirect_info['values']['return_info']['values']['depth'] = $this->depth;
      $redirect_info['values']['return_info']['values']['mode'] = $this->mode;

      // Send and get url

      $this->redirect_url = Relations_Admin_redirect($redirect_info);

      // Set the state to redirect

      $this->state = 'redirect';

    } else {

      $this->finishProcess();

    }

  }



  //// Processes copies

  function copyProcess() {

    // Go though the states

    switch ($this->state) {

      // Just starting out, see if anything's
      // chosen and if so, get it and override
      // it's values with the defaultes. If 
      // not, go to Choose

      case 'start':

        $errors = array();
        $totals = array();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose','copy');
          $this->inform($totals);

          if ($this->records > 0) {

            $this->state = 'enter';
            $this->defaults();

          }

        } 

        if ($this->state != 'enter')
          $this->redirectChoose('copy');

        break;

      // If we're returning from Choose,
      // get what's chosen. If we don't 
      // find anything, Cancel

      case 'choose':

        $errors = array();
        $totals = $this->results();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose','copy');

          if ($this->records > 0) {

            $this->state = 'enter';

          }

        }

        if ($this->state != 'enter')
          $this->cancelProcess();

        $this->inform($totals);
        break;

      // If we're at enter, something else
      // was pressed

      case 'enter':

        $this->entered();
        break;

      // If we're redirecting, get what was entered
      // and redirect.

      case 'redirect':

        $this->entered();
        $this->redirectProcess();
        break;

      // If we're coming back, get what we saved,
      // figure out which input is be returned to,
      // and have it do its thing.

      case 'return':

        $this->load($this->depth);
        $totals = $this->results();
        $this->returnProcess($totals);
        $this->inform($totals);
        $this->state = 'enter';
        break;

      // If the user wants to preview,
      // check for errors, go back to
      // entering if there is any

      case 'preview':

        $this->entered();
        $errors = array();

        if ($this->toValidate($errors)) {

          $this->save($this->depth);

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
        
      // If they want to enter again,
      // load what was saved and go
      // back to entering

      case 'reenter':

        $this->load($this->depth);
        $this->state = 'enter';
        break;
        
      // If user confirmed, load the
      // info, update the records, but
      // go back to entering if there's
      // a problem

      case 'confirm':

        $this->load($this->depth);
        $errors = array();

        if ($this->toDB($errors,$totals)) {

          $this->inform($totals);
          $this->finish($totals);
          $this->copyInfosProcess();
          $this->copyChildrenProcess();

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
                          
      // If the user just wants to
      // go ahead, get what they
      // entered and try to send it
      // to the database. Let them
      // know if something's wrong

      case 'execute':

        $this->entered();
        $errors = array();

        if ($this->toDB($errors,$totals)) {

          $this->inform($totals);
          $this->finish($totals);
          $this->copyInfosProcess();
          $this->copyChildrenProcess();

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
        
      // Get what was done previously,
      // let the user know, and then
      // try the next child info

      case 'children':

        $totals = $this->results();
        $this->inform($totals);
        $this->finish($totals);
        $this->copyChildrenProcess();

        break;
        
    }

  }



  //// Gets the redirect info for children

  function replaceInfosProcess() {

    // Create the array

    $this->child_infos = array();

    // Now go through all the inputs and have them do 
    // what they have to do. 

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->replaceInfosProcess($this->child_infos,$this->intentions,$this->records);

  }



  //// Process the repalced children

  function replaceChildrenProcess() {

    // Shift out an Info

    if ($redirect_info = array_shift($this->child_infos)) {

      // Set the depth

      $redirect_info['values']['depth'] = $this->depth + 1;
      $redirect_info['values']['mode'] = $this->mode;

      // Create the url to return to us

      $redirect_info['values']['return_info'] = array();
      $redirect_info['values']['return_info']['url'] = $this->self_url;
      $redirect_info['values']['return_info']['values'] = array();
      $redirect_info['values']['return_info']['values']['task'] = 'replace';
      $redirect_info['values']['return_info']['values']['state'] ='children';
      $redirect_info['values']['return_info']['values']['depth'] = $this->depth;
      $redirect_info['values']['return_info']['values']['mode'] = $this->mode;

    } else {

      // Set the info to get there

      $redirect_info = array(
        'url'    => $this->self_url,
        'values' => array()
      );

      // Set the values

      $redirect_info['values']['task'] = 'delete';
      $redirect_info['values']['depth'] = $this->depth + 1;
      $redirect_info['values']['mode'] = $this->mode;
      $redirect_info['values']['chosen'] = $this->replacees;

      // Create the url to return to us

      $redirect_info['values']['return_info'] = array();
      $redirect_info['values']['return_info']['url'] = $this->self_url;
      $redirect_info['values']['return_info']['values'] = array();
      $redirect_info['values']['return_info']['values']['task'] = 'replace';
      $redirect_info['values']['return_info']['values']['state'] ='finish';
      $redirect_info['values']['return_info']['values']['depth'] = $this->depth;
      $redirect_info['values']['return_info']['values']['mode'] = $this->mode;

    }

    // Send and get url

    $this->redirect_url = Relations_Admin_redirect($redirect_info);

    // Set the state to redirect

    $this->state = 'redirect';

  }



  //// Processes replaces

  function replaceProcess() {

    // Go though the states

    switch ($this->state) {

      // Just starting out, see if anything's
      // chosen and if so, get it and override
      // it's values with the defaultes. If 
      // not, go to Choose

      case 'start':

        $errors = array();
        $totals = array();
        $this->chose($totals);

        if (count($this->chosen) > 1) {

          $this->replacees = array_splice($this->chosen,1);

          $this->fromDB($errors,$totals,'select','choose','merge');
          $this->inform($totals);
          $this->defaults();

          if ($this->records > 1)
            $this->state = 'enter';

        } 

        if ($this->state != 'enter')
          $this->redirectChoose('replacee','replacee');

        break;

      // If they've chosen the replacees, get them
      // and send the user to choose the replacer.
      // If nothing was selected, Cancel

      case 'replacee':

        $totals = $this->results();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->replacees = $this->chosen;
          $this->redirectChoose('replacer','replacer',1);

        } else {

          $this->cancelProcess();

        }

        $this->inform($totals);
        break;

      // If they've chosen the replacer, get it
      // and load all from the database

      case 'replacer':

        $errors = array();
        $totals = $this->results();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose','merge');

          if ($this->records > 1)
            $this->state = 'enter';

        }

        if ($this->state != 'enter')
          $this->cancelProcess();

        $this->inform($totals);
        break;

      // If we're at enter, something else
      // was pressed

      case 'enter':

        $this->entered();
        break;

      // If we're redirecting, get what was entered
      // and redirect.

      case 'redirect':

        $this->entered();
        $this->redirectProcess();
        break;

      // If we're coming back, get what we saved,
      // figure out which input is be returned to,
      // and have it do its thing.

      case 'return':

        $this->load($this->depth);
        $totals = $this->results();
        $this->returnProcess($totals);
        $this->inform($totals);
        $this->state = 'enter';
        break;

      // If the user wants to preview,
      // check for errors, go back to
      // entering if there is any

      case 'preview':

        $this->entered();
        $errors = array();

        if ($this->toValidate($errors)) {

          $this->save($this->depth);

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
        
      // If they want to enter again,
      // load what was saved and go
      // back to entering

      case 'reenter':

        $this->load($this->depth);
        $this->state = 'enter';
        break;
        
      // If user confirmed, load the
      // info, update the records, but
      // go back to entering if there's
      // a problem

      case 'confirm':

        $this->load($this->depth);
        $errors = array();

        if ($this->toDB($errors,$totals)) {

          $this->inform($totals);
          $this->finish($totals);
          $this->replaceInfosProcess();
          $this->replaceChildrenProcess();

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
                          
      // If the user just wants to
      // go ahead, get what they
      // entered and try to send it
      // to the database. Let them
      // know if something's wrong

      case 'execute':

        $this->entered();
        $errors = array();

        if ($this->toDB($errors,$totals)) {

          $this->inform($totals);
          $this->finish($totals);
          $this->replaceInfosProcess();
          $this->replaceChildrenProcess();

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
        
      // Get what was done previously,
      // let the user know, and then
      // try the next child info

      case 'children':

        $totals = $this->results();
        $this->inform($totals);
        $this->finish($totals);
        $this->replaceChildrenProcess();

        break;
        
      // Get what was done previously,
      // let the user know, and then
      // finish up

      case 'finish':

        $totals = $this->results();
        $this->inform($totals);
        $this->finish($totals);
        $this->finishProcess();
        break;
                  
    }

  }



  //// Processes deletes

  function deleteProcess() {

    // Go though the states

    switch ($this->state) {

      // Just starting out, see if anything's
      // chosen and if so, get it and override
      // it's values with the defaultes. If 
      // not, go to Choose

      case 'start':

        $errors = array();
        $totals = array();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

          $this->fromDB($errors,$totals,'select','choose','delete');
          $this->inform($totals);

          if ($this->records > 0)
            $this->state = 'enter';

        } 

        if ($this->state != 'enter')
          $this->redirectChoose('delete');

        break;

      // If we're returning from Choose,
      // get what's chosen. If we don't 
      // find anything, Cancel

      case 'choose':

        $errors = array();
        $totals = $this->results();
        $this->chose($totals);

        if (count($this->chosen) > 0) {

            $this->fromDB($errors,$totals,'select','choose','delete');

          if ($this->records > 0)
            $this->state = 'enter';

        }

        if ($this->state != 'enter')
          $this->cancelProcess();

        $this->inform($totals);
        break;

      // If we're at enter, something else
      // was pressed

      case 'enter':

        $this->entered();
        break;

      // If we're redirecting, get what was entered
      // and redirect.

      case 'redirect':

        $this->entered();
        $this->redirectProcess();
        break;

      // If we're coming back, get what we saved,
      // figure out which input is be returned to,
      // and have it do its thing.

      case 'return':

        $this->load($this->depth);
        $totals = $this->results();
        $this->returnProcess($totals);
        $this->inform($totals);
        $this->state = 'enter';
        break;

      // If the user wants to preview,
      // check for errors, go back to
      // entering if there is any

      case 'preview':

        $this->entered();
        $errors = array();

        if ($this->toValidate($errors)) {

          $this->save($this->depth);

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
        
      // If they want to enter again,
      // load what was saved and go
      // back to entering

      case 'reenter':

        $this->load($this->depth);
        $this->state = 'enter';
        break;
        
      // If user confirmed, load the
      // info, insert the records, but
      // go back to entering if there's
      // a problem

      case 'confirm':

        $this->load($this->depth);
        $errors = array();

        if ($this->toDB($errors,$totals)) {

          $this->inform($totals);
          $this->finish($totals);
          $this->finishProcess();

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
                          
      // If the user just wants to
      // go ahead, get what they
      // entered and try to send it
      // to the database. Let them
      // know if something's wrong

      case 'execute':

        $this->entered();
        $errors = array();

        if ($this->toDB($errors,$totals)) {

          $this->inform($totals);
          $this->finish($totals);
          $this->finishProcess();

        } else {

          $this->advise($errors);
          $this->state = 'enter';

        }

        break;
        
    }

  }



  //// Process the form's task and state

  function process() {

    // First grab the depth. This will drive 
    // everything else.

    $this->depth = Relations_Admin_grab('depth',0);

    //print "depth: " . $this->depth . "<br>";

    // Clean anything that came before

    Relations_Admin_clean($this->depth+1);

    // Grab the task, state, and mode.

    $this->task = Relations_Admin_default($this->depth,'task','select','VSPG');
    $this->state = Relations_Admin_default($this->depth,'state','start','VSPG');
    $this->mode = Relations_Admin_default($this->depth,'mode','html','VSPG');

    //print "task: " . $this->task . "<br>";
    //print "state: " . $this->state . "<br>";

    // Set up info

    Relations_Admin_redirect($force_info);

    // If we're starting, grab the defaults
    // else get the our important data

    if ($this->state == 'start')
      $this->settings();
    else
      $this->get($this->depth);

    //print "task: " . $this->task . "<br>";
    //print "state: " . $this->state . "<br>";

    // Initialize the error and redirect url

    $this->error = '';
    $this->redirect_url = '';

    // Figure our what to process

    switch ($this->task) {

      case 'admin':

        $this->adminProcess();
        break;

      case 'insert':

        $this->insertProcess();
        break;

      case 'choose':

        $this->chooseProcess();
        break;

      case 'select':

        $this->selectProcess();
        break;

      case 'update':

        $this->updateProcess();
        break;

      case 'copy':

        $this->copyProcess();
        break;

      case 'replace':

        $this->replaceProcess();
        break;

      case 'delete':

        $this->deleteProcess();
        break;

      default:

        die("Unknown Process Task: '$task'");

    }

    // If we're going home, clean,
    // If we're finished, clear,
    // if we're redirecting, save
    // else just set 

    if ($this->state == 'home')
      Relations_Admin_clean();
    elseif ($this->state == 'finish')
      Relations_Admin_clean($this->depth);
    elseif ($this->state == 'redirect')
      $this->save();
    else
      $this->set();

  }



  /*** HTML ***/



  //// Returns a current value and toggles 

  function alternate() {

    // If even, set to odd, and vice versa

    if ($this->parity == 'even')
      $this->parity = 'odd';
    else
      $this->parity = 'even';

    // Send it back

    return $this->parity;

  }



  //// Outputs a redirect header

  function redirect() {

    // Do it

    header("Location: $this->redirect_url");
    exit();

  }



  //// Get the proper label

  function labeled($name,$default='') {

    // Use the right label

    if (strlen($this->labeling[$name]))
      return $this->labeling[$name];
    elseif (strlen($default))
      return $default;
    else
      return ucfirst($name);

  }



  //// Create the full title

  function getTitle() {

    return $this->title . ' - ' . $this->label . ' - ' .  ucfirst($this->task) . ' - ' . ucfirst($this->state);
    
  }



  //// Gets the records for this state

  function getRecords($state) {

    $records = array();

    if (($state == 'mass') || ($state == 'search'))
      array_push($records, 0);
    else
      for ($record = 0; $record < $this->records; $record++)
        array_push($records, $record);

    return $records;
  
  }



  //// Gets the admins allowed

  function getAdmins($records) {

    // Define the tasks

    $tasks = array('update','copy','replace','delete');

    // Initialize the alls

    foreach ($tasks as $task)
      $admins['all'][$task] = array();

    // Go through all records

    foreach ($records as $record) {
      $admins[$record] = array();
      foreach ($tasks as $task) {
        if ($this->allow($task,$this->ID($record))) {
          $admins[$record][$task] = $this->ID($record);
          $admins['all'][$task][] = $this->ID($record);
        }
      }
    }

    // Send them back    

    return $admins;
  
  }



  //// Gets whether we need a mass area

  function needMass() {

    $need = ($this->records > 1);

    foreach (array_keys($this->inputs) as $name)
      $need = $need || $this->inputs[$name]->needMass();

    return $need;

  }



  //// Return true if there's data

  function isData($data) {

    if (is_array($data))
      return count($data);
    else
      return strlen($data);

  }



  //// Sets data for a list

  function listData($records=array()) {

    // If no records, return

    if (!count($records))
      return;

    // Create the html structure and
    // links

    $this->data['list'] = array();

    // Create the labels, choose and 
    // view links and records array

    $choose_url = "$this->self_url?depth=$this->depth&task=$this->task&state=choose&chosen=";
    $view_url = "$this->self_url?depth=$this->depth&task=$this->task&state=view&chosen=";
    $this->data['list']['record'] = array();

    // Figure out type

    if ($this->single)
      $type = 'radio';
    else
      $type = 'checkbox';

    // Go through all the records

    foreach ($records as $record) { 

      // Figure out selected

      if ($this->single)
        $selected = ($record == 0);
      else
        $selected = true;

      // List the label with a link to view and 
      // a choose link as well.

      $this->data['list']['record'][$record]['single']['type'] = $type;
      $this->data['list']['record'][$record]['single']['selected'] = $selected;
      $this->data['list']['record'][$record]['single']['id'] = $this->ids[$record];

      $this->data['list']['record'][$record]['choose']['id'] = $this->ids[$record];
      $this->data['list']['record'][$record]['choose']['label'] = $this->labels[$this->ids[$record]];
      $this->data['list']['record'][$record]['choose']['url'] = $choose_url;

      $this->data['list']['record'][$record]['view']['id'] = $this->ids[$record];
      $this->data['list']['record'][$record]['view']['url'] = $view_url;

    }

  }



  //// Sets data for a list

  function listXML($records=array()) {

    // If no records, return

    if (!count($records))
      return;

    // Create the html structure and
    // links

    $this->data['list'] = array();
    $this->data['list']['records'] = count($records);

    // Figure out type

    if ($this->single)
      $this->data['list']['type'] = 'radio';
    else
      $this->data['list']['type'] = 'checkbox';

    // Create the labels, choose and 
    // view links and records array

    $this->data['list']['choices'] = array();

    // Go through all the records

    foreach ($records as $record)
      $this->data['list']['choices'][$this->ids[$record]] = $this->labels[$this->ids[$record]];

  }



  //// Sets data for all records

  function recordsData($state,$records=array(0),$admins=array(),$extra=array()) {

    // Take off if we're to display mass
    // and there's no need.

    if (($state == 'mass') && !$this->needMass())
      return;

    // If we're mass, use mass, search, 
    // else use records

    if ($state == 'mass')
      $fill = 'mass';
    else
      $fill = 'records';

    // Create the html structure

    $this->data[$fill] = array();

    // Set the Title

    if ($state == 'mass')
      $this->data[$fill]['caption'] = "Mass Set";
    elseif ($state == 'search')
      $this->data[$fill]['caption'] = "Search On";

    // Initialize records

    $this->data[$fill]['record'] = array();

    // If we're mass, we need to add a control
    // for mass set

    if (($state == 'mass') && !$this->single)
      $this->data[$fill]['record'][0]['set'] = 1;

    // If we're searching, we need to add
    // inputs.

    if ($state == 'search') {

      // If there's less than 100 records, do a select list
      // only if it's needed (can be huge).

      if (in_array('select',$this->layout['main']) ||
          in_array('select',$this->layout['records']) ||
          in_array('select',$this->layout['record'])) {

        $this->fromDB($errors,$totals,'count','all',$this->ambition);

        if ($this->records < 100) {

          // Call fromDB 

          $errors = array();
          $totals = array();
          $this->fromDB($errors,$totals,'list','all',$this->ambition);

          // Add a select list and button

          $this->data[$fill]['select']['ids'] = $this->ids;
          $this->data[$fill]['select']['labels'] = $this->labels;

        }

      }

      // Create the initial link

      $this->data[$fill]['initial']['url'] = "$this->self_url?depth=$this->depth&task=$this->task&state=list&initial=";

      // Create all the initials

      $this->data[$fill]['initial']['initials'] = array();

      for ($initial = 0; $initial < 26; $initial++)
        $this->data[$fill]['initial']['initials'][] = chr(65 + $initial);

      for ($initial = 0; $initial < 10; $initial++) 
        $this->data[$fill]['initial']['initials'][] = chr(48 + $initial);

      // Add the filter html

      $this->data[$fill]['filter'] = 1;
                                            
    }

    // If we're browsing, we need a radio or 
    // checkbox for choosing

    if ($state == 'browse') {

      $this->data['list'] = array();

      // Figure out type

      if ($this->single)
        $this->data['list']['type'] = 'radio';
      else
        $this->data['list']['type'] = 'checkbox';

      // Create the URL

      $choose_url = "$this->self_url?depth=$this->depth&task=$this->task&state=choose&chosen=";

    }

    // Push anything in records down
    // to record if we're supposed to

    $push = array();

    foreach ($this->layout['record'] as $record_layout)
      if ($this->isData($this->data[$fill][$record_layout]))
        $push[$record_layout] = $this->data[$fill][$record_layout];

    // Go through all the records

    foreach ($records as $record) {

      // Push on the extra

      foreach ($push as $push_layout => $push_data)
        $this->data[$fill]['record'][$record][$push_layout] = $push_data;

      // If we're entering, we have to figure out
      // if we're doing what we intended for inputs
      // like child forms

      if ($state == 'enter')
        $extra = ($this->intentions[$record] == $this->ambitions[$record]);

      // Get the error message

      $this->data[$fill]['record'][$record]['error'] = $this->errors[$record];

      // If we're browsing, we need a radio or 
      // checkbox for choosing

      if ($state == 'browse') {

        // Figure out selected

        if ($this->single)
          $selected = ($record == 0);
        else
          $selected = true;

        // List a checkbox to check

        $this->data[$fill]['record'][$record]['narrow']['type'] = $type;
        $this->data[$fill]['record'][$record]['narrow']['selected'] = $selected;
        $this->data[$fill]['record'][$record]['narrow']['id'] = $this->ID($record);

      // If we're previewing, we need the intended action

      } elseif ($state == 'preview') {

        $this->data[$fill]['record'][$record]['intended']['label'] = 'Action';
        $this->data[$fill]['record'][$record]['intended']['type'] = 'view';
        $this->data[$fill]['record'][$record]['intended']['record'] = $record;

      // If we're linking, and there's admins for this
      // record, add them

      } elseif (($state == 'link') && count($admins[$record])) {

        // Go through and add them all

        $this->data[$fill]['record'][$record]['admin']['tasks'] = $admins[$record];

      // If we're entering and we're not single,
      // we need the intention controls  

      } elseif (($state == 'enter') && !$this->single) {

        $this->data[$fill]['record'][$record]['intended']['label'] = 'Action';
        $this->data[$fill]['record'][$record]['intended']['type'] = 'control';
        $this->data[$fill]['record'][$record]['intended']['record'] = $record;
        $this->data[$fill]['record'][$record]['intended']['alive'] = 1;

      }

      // Now get everything from the inputs
      // if they're not fobidden, etc.  

      $this->data[$fill]['record'][$record]['inputs'] = array();

      foreach (array_keys($this->inputs) as $name)
        if (!$this->deny($this->ambitions[$record],$name,$this->ID($record))) 
          $this->data[$fill]['record'][$record]['inputs'][] = array(
            'name'  => 'input',
            'label' => $this->inputs[$name]->label,
            'data'  => $this->inputs[$name]->inputHTML($state,$record,$extra)
          );
        
    } 

  }


  
  //// Sets data for all records

  function recordsXML($state,$records=array(0),$admins=array(),$extra=array()) {

    // Set the Title

    if ($state == 'search')
      $this->data['caption'] = "Search On";

    // Initialize inputs

    $this->data['inputs'] = array();

    // If we're searching, we need to add
    // inputs.

    if ($state == 'search') {

      // If there's less than 100 records, do a select list
      // only if it's needed (can be huge).

      if (in_array('select',$this->layout['main']) ||
          in_array('select',$this->layout['records']) ||
          in_array('select',$this->layout['record'])) {

        $this->fromDB($errors,$totals,'count','all',$this->ambition);

        if ($this->records < 100) {

          // Call fromDB 

          $errors = array();
          $totals = array();
          $this->fromDB($errors,$totals,'list','all',$this->ambition);

          // Add a select list and button

          $this->data['select']['ids'] = $this->ids;
          $this->data['select']['labels'] = $this->labels;

        }

      }

      // Create all the initials

      $this->data['initial']['initials'] = array();

      for ($initial = 0; $initial < 26; $initial++)
        $this->data['initial']['initials'][] = chr(65 + $initial);

      for ($initial = 0; $initial < 10; $initial++) 
        $this->data['initial']['initials'][] = chr(48 + $initial);

      // Add the filter html

      $this->data['filter'] = 1;
                                            
    }

    // If we're browsing, we need a radio or 
    // checkbox for choosing

    if ($state == 'browse') {

      $this->data['narrow'] = array();

      // Figure out type

      if ($this->single)
        $this->data['narrow']['type'] = 'radio';
      else
        $this->data['narrow']['type'] = 'checkbox';

      $this->data['narrow']['narrows'] = array();

    // Preview, need to just view intentions

    } elseif ($state == 'preview') {

      $this->data['intended'] = array();

    // If we're linking, and there's admins

    } elseif (($state == 'link') && count($admins['all'])) {

      // Go through and add them all

      $this->data['admins'] = array();

    // If we're entering and we're not single,
    // we need the intention controls  

    } elseif (($state == 'enter') && !$this->single) {

      $this->data['intended'] = array();

    }

    // Go through all the records
    $extra = array();
    foreach ($records as $record) {

      // If we're entering, we have to figure out
      // if we're doing what we intended for inputs
      // like child forms

      if ($state == 'enter')
        $extra[$record] = ($this->intentions[$record] == $this->ambitions[$record]);

      // If we're browsing, we need a radio or 
      // checkbox for choosing

      if ($state == 'browse') {

        // Add

        $this->data['narrow']['narrows'][] = $this->ID($record);

      // If we're previewing, we need the intended action

      } elseif ($state == 'preview') {

        $this->data['intended'][$record] = array(
          'type' => 'view',
          'alive' => 1
        );

      // If we're linking, and there's admins for this
      // record, add them

      } elseif ($state == 'link') {

        // Go through and add them all

        $this->data['admins'][$record] = $admins[$record];

      // If we're entering and we're not single,
      // we need the intention controls  

      } elseif (($state == 'enter') && !$this->single) {

        $this->data['intended'][$record] = array(
          'type' => 'control',
          'alive' => 1
        );

      }
        
    } 

    // Now get all the input info

    foreach (array_keys($this->inputs) as $name)
      $this->data['inputs'][] = $this->inputs[$name]->inputXML($state,$records,$extra);
      
  }


  
  //// Prepares for html display

  function mainData() {

    // Get all the pieces

    $this->data['start']['title'] = $this->getTitle();
    $this->data['start']['css'] = $this->css;
    $this->data['heading'] = $this->getTitle();
    $this->data['errors'] = $this->error;
    $this->data['message'] = $this->message;
    $this->data['info'] = $this->info;
    $this->data['form_start'] = 1;
    $this->data['form_end'] = 1;
    $this->data['script'] = 1;
    $this->data['end'] = 1;

    // Figure our what to control

    switch ($this->state) {

      case 'search':

        if ($this->allow('insert'))
          $buttons = array('list' => 'Search','insert' => 'Insert');
        else
          $buttons = array('list' => 'Search');
        break;

      case 'list':

        if ($this->allow('insert'))
          $buttons = array('choose' => 'Choose', 'browse' => 'Browse', 'research' => 'Search Again','insert' => 'Insert');
        else
          $buttons = array('choose' => 'Choose', 'browse' => 'Browse', 'research' => 'Search Again');

        break;

      case 'browse':

        if ($this->allow('insert'))
          $buttons = array('choose' => 'Choose','research' => 'Search Again','insert' => 'Insert');
        else
          $buttons = array('choose' => 'Choose','research' => 'Search Again');

        break;

      case 'enter':

        $buttons = array('preview' => 'Preview','execute' => ucfirst($this->task));
        break;

      case 'preview':

        $buttons = array( 'confirm'  => ucfirst($this->task), 'reenter' => 'Re-Enter');
        break;

      case 'view':
      case 'link':
      case 'finish':
      case 'home':

        $buttons = array();
        break;

      default:

        die("Unknown Dislay State: '$this->state'");

    }

    // Get the buttons if we're not done

    if (!in_array($this->state,array('view','finish','home')))
      $this->data['control'] = array('state' => $this->state,'states' => $buttons);

    // Get the records and admins

    $records = $this->getRecords($this->state);
    $admins = $this->getAdmins($records);

    // If we're linking, we need to figure out
    // the prefix link with return url

    if ($this->state == 'link') {

      // Prepare every form for a redirect

      $link_info = array();
      $link_info['url'] = '';
      $link_info['values'] = array();

      // Set the values

      $link_info['values']['task'] = 'select';
      $link_info['values']['depth'] = $this->depth + 1;

      // Create the url to return to us

      $link_info['values']['return_info'] = array();
      $link_info['values']['return_info']['url'] = $this->self_url;
      $link_info['values']['return_info']['values'] = array();
      $link_info['values']['return_info']['values']['task'] = $this->task;
      $link_info['values']['return_info']['values']['state'] = 'return';
      $link_info['values']['return_info']['values']['chosen'] = $this->chosen;
      $link_info['values']['return_info']['values']['depth'] = $this->depth;

      // Set up info

      Relations_Admin_redirect($link_info);

      // Set extra

      $extra = "&depth=" . ($this->depth+1);

    }

    // If we're linking, and more than one record,
    // and we can admin some of them, we need the 
    // admin links.

    if (($this->state == 'link') && ($this->records > 1) && count($admins['all']))
      $this->data['admins']['tasks'] = $admins['all'];

    // If we're browsing or listing, then we
    // need limit stuff

    if (in_array($this->state,array('browse','list')))
      $this->data['limit'] = $this->limit;

    // If we're browsing or listing, and 
    // we not just getting a single record,
    // and there's not than one, we need the 
    // select all and deselect controls

    if (in_array($this->state,array('browse','list')) && 
       (!$this->single) && ($this->records > 1))
      $this->data['all'] = 1;

    // If we're entering, and we're not at
    // single, we need the insert new ones

    if (($this->state == 'enter') && !$this->single)
      $this->data['create'] = array('prefix' => '');

    // Get mass if we're entering

    if ($this->state == 'enter')
      $this->recordsData('mass');

    // If we're not home or finishing, get records

    if ($this->state == 'list')
      $this->listData($records);
    elseif (!in_array($this->state,array('finish','home')))
      $this->recordsData($this->state,$records,$admins,$extra);
    else 
      $this->data['finish'] = 1;

  }



  //// Prepares for html display

  function xmlData() {

    // Get all the pieces

    $this->data = array();
    $this->data['settings'] = array();
    $this->data['control'] = array();
    $this->data['advice'] = $this->error;
    $this->data['errors'] = $this->errors;
    $this->data['info'] = $this->info;
    $this->data['message'] = $this->message;
    $this->data['intended'] = array();
    $this->data['settings']['label'] = $this->label;
    $this->data['settings']['title'] = $this->getTitle();
    $this->data['settings']['self_url'] = $this->self_url;
    $this->data['settings']['home_url'] = $this->home_url;
    $this->data['settings']['single'] = $this->single;

    // Figure our what to control

    switch ($this->state) {

      case 'search':

        if ($this->allow('insert'))
          $buttons = array('list' => 'Search','insert' => 'Insert');
        else
          $buttons = array('list' => 'Search');
        break;

      case 'list':

        if ($this->allow('insert'))
          $buttons = array('choose' => 'Choose', 'browse' => 'Browse', 'research' => 'Search Again','insert' => 'Insert');
        else
          $buttons = array('choose' => 'Choose', 'browse' => 'Browse', 'research' => 'Search Again');

        break;

      case 'browse':

        if ($this->allow('insert'))
          $buttons = array('choose' => 'Choose','research' => 'Search Again','insert' => 'Insert');
        else
          $buttons = array('choose' => 'Choose','research' => 'Search Again');

        break;

      case 'enter':

        $buttons = array('preview' => 'Preview','execute' => ucfirst($this->task));
        break;

      case 'preview':

        $buttons = array( 'confirm'  => ucfirst($this->task), 'reenter' => 'Re-Enter');
        break;

      case 'view':
      case 'link':
      case 'finish':
      case 'home':

        $buttons = array();
        break;

      default:

        die("Unknown Dislay State: '$this->state'");

    }

    // Get the buttons if we're not done

    if (!in_array($this->state,array('view','finish','home'))) {

      $this->data['control']['state'] = $this->state;
      $this->data['control']['states'] = $buttons;
      $this->data['control']['admins'] = array();

    }

    // Get the records and admins

    $records = $this->getRecords($this->state);
    $admins = $this->getAdmins($records);
    $this->data['settings']['records'] = count($records);

    // If we're linking, we need to figure out
    // the prefix link with return url

    if ($this->state == 'link') {

      // Prepare every form for a redirect

      $link_info = array();
      $link_info['url'] = '';
      $link_info['values'] = array();

      // Set the values

      $link_info['values']['task'] = 'select';
      $link_info['values']['depth'] = $this->depth + 1;

      // Create the url to return to us

      $link_info['values']['return_info'] = array();
      $link_info['values']['return_info']['url'] = $this->self_url;
      $link_info['values']['return_info']['values'] = array();
      $link_info['values']['return_info']['values']['task'] = $this->task;
      $link_info['values']['return_info']['values']['state'] = 'return';
      $link_info['values']['return_info']['values']['chosen'] = $this->chosen;
      $link_info['values']['return_info']['values']['depth'] = $this->depth;

      // Set up info

      Relations_Admin_redirect($link_info);

    }

    // If we're linking, and more than one record,
    // and we can admin some of them, we need the 
    // admin links.

    if (($this->state == 'link') && (count($records) > 1) && count($admins['all']))
      $this->data['control']['admins'] = $admins['all'];

    // If we're browsing or listing, then we
    // need limit stuff

    if (in_array($this->state,array('browse','list')))
      $this->data['limit'] = $this->limit;

    // If we're not home or finishing, get records

    if ($this->state == 'list')
      $this->listXML($records);
    elseif (!in_array($this->state,array('finish','home')))
      $this->recordsXML($this->state,$records,$admins,$extra);
    else 
      $this->data['finish'] = 1;

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

      case 'start':
        return $this->startHTML($data['title'],$data['css']);

      case 'heading':
        return $this->headingHTML($data);

      case 'errors':

        $errors = "The following errors were encounted:<br>";
        foreach ($data['uniques'] as $message=>$number)
          $errors .= "$number. $message<br>";
        return Relations_Admin_MessageHTML($this,$errors,'error');

      case 'error':
        $error = "Errors: " . implode(', ',$data);
        return Relations_Admin_MessageHTML($this,$error,'error');

      case 'message':
        return Relations_Admin_MessageHTML($this,$data);

      case 'info':
        return Relations_Admin_MessageHTML($this,Relations_Admin_infoHTML($data),'info');

      case 'help':
        return Relations_Admin_HelpHTML($this);

      case 'form_start':
        return $this->formStartHTML();

      case 'control':
        return $this->controlHTML($data['state'],$data['states']) . '<br>';

      case 'select':
        $label = $this->labeled("search_choose","Select");
        return Relations_Admin_HelpHTML($this,'select') . 
              Relations_Admin_SelectHTML($this,'chosen',$data['ids'],$data['labels'],'',1,'search_choose_') .
              Relations_Admin_ButtonHTML($this,"search_choose","Select","set_state('choose')",'search_choose_');

      case 'initial':
        $urls = array();
        foreach ($data['initials'] as $initial)
          $urls[] = Relations_Admin_URLHTML($this,"$data[url]$initial",$initial,'','initial_');
        return Relations_Admin_HelpHTML($this,'initial') . join(' ',$urls);

      case 'filter':
        return Relations_Admin_HelpHTML($this,'filter') . Relations_Admin_TextHTML($this,'filter','',24,'','filter_');

      case 'checkbox':
        return Relations_Admin_CheckboxHTML($this,$data['name'],1,$data['label'],$data['checked']);

      case 'columnar':
        return $this->columnarHTML($data);

      case 'columnar_data':
        if ($input = $this->toHTML($data['name'],$data['data'])) {
          $parity = $this->alternate();
          $label = $this->labeled($data['name'],$data['label']);
          $tr_look = Relations_Admin_LookHTML($this,'columnar_tr_' . $parity);
          $th_look = Relations_Admin_LookHTML($this,'columnar_th_' . $parity);
          $td_look = Relations_Admin_LookHTML($this,'columnar_td_' . $parity);
          return "<tr $tr_look>\n<th $th_look>$label</th>\n<td $td_look>$input</td>\n</tr>\n";
        } else
          return '';

      case 'tabular':
        return $this->tabularHTML($data);

      case 'tabular_th':
        $parity = $this->parity;
        $label = $this->labeled($data['name'],$data['label']);
        $th_look = Relations_Admin_LookHTML($this,'tabular_th_' . $parity);
        return "<th $th_look>$label</th>\n";

      case 'tabular_td':
        $parity = $this->parity;
        $td_look = Relations_Admin_LookHTML($this,'tabular_td_' . $parity);
        if ($input = $this->toHTML($data['name'],$data['data']))
          return "<td $td_look>$input</td>\n";
        else
          return "<td $td_look>&nbsp;</td>\n";

      case 'plain':
        return $this->plainHTML($data);

      case 'plain_data':
        if ($input = $this->toHTML($data['name'],$data['data'])) {
          $parity = $this->alternate();
          $label = $this->labeled($data['name'],$data['label']);
          $label_look = Relations_Admin_LookHTML($this,'plain_label_' . $parity);
          $input_look = Relations_Admin_LookHTML($this,'plain_input_' . $parity);
          return "<b $label_look>$label</b><br><span $input_look>$input</span><br><br>\n";
        } else
          return '';

      case 'single':
      case 'narrow':
        if ($data['type'] == 'radio')
          return Relations_Admin_RadioHTML($this,'chosen',$data['id'],'',$data['selected'],'list_');
        else
          return Relations_Admin_CheckboxHTML($this,'chosen[]',$data['id'],'',$data['selected'],'list_');

      case 'choose':
        return Relations_Admin_URLHTML($this,"$data[url]$data[id]",$data['label'],'','list_');

      case 'view':
        return Relations_Admin_URLHTML($this,"$data[url]$data[id]",'View','_blank','list_');

      case 'intended':
        return $this->intendedHTML($data['record'],$data['type'],$data['alive']);

      case 'admins':
        $buttons = array();
        foreach ($data['tasks'] as $task=>$ids)
          $buttons[] = Relations_Admin_ButtonHTML($this,"${task}_all", ucfirst($task),"set_admin('$task','" . join(",",$ids) . "')",'admin_');
        return join(' ',$buttons);

      case 'admin':
        $buttons = array();
        foreach ($data['tasks'] as $task=>$id)
          $buttons[] = Relations_Admin_ButtonHTML($this,"${task}", ucfirst($task),"set_admin('$task','$id')",'admin_');
        return join(' ',$buttons);

      case 'limit':
        return $this->limitHTML($data);
        
      case 'all':
        return Relations_Admin_ButtonHTML($this,"select_all","All","set_all(true)","select_all_") .
               Relations_Admin_ButtonHTML($this,"select_none","None","set_all(false)","select_none_") . '<br>';
        
      case 'create':
        $look = Relations_Admin_LookHTML($this,'create','input');
        return Relations_Admin_SubmitHTML($this,"$data[prefix]create","Create","create_") .
               Relations_Admin_TextHTML($this,"$data[prefix]created","1","3","","create_") . "<span $look>Additional $this->label<br></span>";

      case 'set':
        return Relations_Admin_SubmitHTML($this,"mass","Mass Set","mass_set_");
  
      case 'finish':
        return $this->finishHTML();

      case 'form_end':
        return $this->formEndHTML();

      case 'script':
        return $this->scriptHTML();

      case 'end':
        return $this->endHTML();

      default:
        if (function_exists($format))
          return $format($form,$name,$data);
        else
          return $data;

    }

  }


  
  //// Converts the data into XML

  function toXML($name,$data) {

    // If there's no data take off

    if (!$this->isData($data)) 
      return '';

    // Figure out what to format

    switch ($name) {

      case 'settings':
        $settings = "<settings>\n";
        foreach ($data as $name=>$value)
          $settings .= "<setting name='$name'><![CDATA[$value]]></setting>\n";
        $settings .= "</settings>\n";
        return $settings;

      case 'advice':
        $advice = "<advices>\n";
        foreach ($data['uniques'] as $message=>$number)
          $advice .= "<advice number='$number'><![CDATA[$message]]></advice>\n";
        $advice .= "</advices>\n";
        return $advice;

      case 'errors':

        $errors = array();
        foreach ($data as $record=>$numbers) {

          $error = '';
          if (!is_array($numbers) || !count($numbers))
            continue;
          $error .= "<error record='$record'>\n";
            foreach ($numbers as $number)
            $error .= "<number>$number</number>\n";
          $error .= "</error>\n";

          $errors[] = $error;
        }

        if (count($errors))
          return "<errors>\n" . join('',$errors) . "</errors>\n";
        else
          return '';

      case 'message':
        return "<message><![CDATA[$data]]></message>\n";

      case 'info':
        $info = "<info>\n";
        ksort($data);
        foreach ($data as $task=>$tally) {
          $info .= "<task name='$task'>\n";
          ksort($tally);
          foreach ($tally as $label=>$count)
            $info .= "<tally name='$label'>$count</tally>\n";
          $info .= "</task>\n";
        }
        $info .= "</info>\n";
        return $info;

      case 'help':
        $help = "<help>\n";
        $help .= "<url><![CDATA[$data[url]]]></url>\n";
        $help .= "<text><![CDATA[$data[text]]]></text>\n";
        $help .= "</help>\n";
        return $help;

      case 'control':
        return $this->controlXML($data['state'],$data['states'],$data['admins']);

      case 'select':
        $select = "<select>\n";
        $ids = $data['ids'];
        $labels = $data['labels'];
        foreach ($ids as $id)
          $select .= "<option><id><![CDATA[$id]]></id><label><![CDATA[$labels[$id]]]></label></option>\n";
        $select .= "</select>\n";
        return $select;

      case 'initial':
        $initial = "<initials>\n";
        foreach ($data['initials'] as $each)
          $initial .= "<initial>$each</initial>\n";
        $initial .= "</initials>\n";
        return $initial;

      case 'filter':
        return "<filter>$this->filter</filter>";

      case 'limit':
        return $this->limitXML($data);
        
      case 'list':
        $list = "<choices type='$data[type]'>\n";
        foreach ($data['choices'] as $id=>$label)
          $list .= "<choice><id><![CDATA[$id]]></id><label><![CDATA[$label]]></label></choice>\n";
        $list .= "</choices>\n";
        return $list;

      case 'narrow':
        $list = "<narrows type='$data[type]'>\n";
        foreach ($data['narrows'] as $id)
          $list .= "<narrow><![CDATA[$id]]></narrow>\n";
        $list .= "</narrows>\n";
        return $list;

      case 'inputs':
        $inputs = "<inputs>\n";
        foreach ($data as $input)
          $inputs .= $this->toXML('input',$input);
        $inputs .= "</inputs>\n";
        return $inputs;

      case 'input':
        $input = "<input name='$data[name]' type='$data[type]'>\n";
        foreach ($data as $name=>$datas)
          if (!in_array($name,array('name','label','type')))
            $input .= $this->toXML($name,$datas);
        $input .= "</input>\n";
        return $input;

      case 'values':
        $values = "<values>\n";
        foreach ($data as $value) {

          $values .= '<value>';

          if (is_array($value) && count($value))
            $values .= "\n";

          if (is_array($value)) {

            foreach ($value as $valued)
              $values .= "<valued><![CDATA[$valued]]></valued>\n";

          } else
            $values .= "<![CDATA[$value]]>";

          $values .= "</value>";

        }
          
        $values .= "</values>\n";
        return $values;

      case 'options':
        $options = "<options>\n";
        foreach ($data as $id=>$label)
          $options .= "<option><id><![CDATA[$id]]></id><label><![CDATA[$label]]></label></option>\n";
        $options .= "</options>\n";
        return $options;

      case 'sought':
        return "<sought>$data</sought>\n";

      case 'lineage':
        $lineage = "<lineage>\n";
        foreach ($data as $parent=>$children) {
          $lineage .= "<parent>\n";
          foreach ($children as $child)
            $lineage .= "<child>$child</child>\n";
          $lineage .= "</parent>\n";
        }
        $lineage .= "</lineage>\n";
        return $lineage;

      case 'intended':
        $intended = "<intentions>\n";
        foreach ($data as $record=>$intention)
          $intended .= $this->intendedXML($record,$intention['type'],$intention['alive']);
        $intended .= "</intentions>\n";
        return $intended;

      case 'admins':
        $admins = "<admins>\n";
        foreach ($data as $record=>$tasks) {
          $admins .= "<admin>\n";
          foreach ($tasks as $task=>$id)
            $admins .= Relations_Admin_ButtonXML($this,"$task", ucfirst($task),"set_admin",array($task,$id),'admin_');
          $admins .= "</admin>\n";
        }
        $admins .= "</admins>\n";
        return $admins;

      case 'controls':
        $controls = "<controls>\n";
        foreach ($data as $control)
          $controls .= Relations_Admin_ButtonXML($this,$control['name'],$control['label'],$control['function'],$control['arguments'],'',$control['help']);
        $controls .= "</controls>\n";
        return $controls;

    }

  }


  
  //// Encodes the ids for html

  function encodeHTML($values) {

    // If it's not an array, return 

    if (!is_array($values))
      return '';

    // Create an array of all encoded
    // values

    $encoded = array();

    // Encode them all 

    foreach ($values as $value)
      $encoded[] = rawurlencode($value);

    // Return everything imploded

    return rawurlencode(implode(',',$encoded));

  }



  //// Returns the start html

  function startHTML($title,$css) {

    // Get the look

    $look = Relations_Admin_LookHTML($this,'body');

    // Get the html 

    $html = "<html>\n";
    $html .= "<head>\n";
    $html .= "<title>$title</title>\n";
    $html .= "</head>\n";
    $html .= $css;
    $html .= "<body $look>\n";

    // Send back the html

    return $html;

  }



  //// Returns the start xml

  function startXML() {

    // Get the xml 

    $xml = '<' . '?xml version="1.0" encoding="UTF-8" ?' . '>' . "\n"; 

    // Send back the xml

    return $xml;

  }



  //// Returns the end html

  function endHTML() {

    // Send back the html

    return "</body>\n</html>\n";

  }



  //// Returns the end xml

  function endXML() {

    // Send back the xml

    return "";

  }



  //// Returns the title html

  function headingHTML($title) {

    // Get the look

    $look = Relations_Admin_LookHTML($this,'title');

    // Send back the html

    return "<h3 $look>$title</h3>\n";

  }



  //// Returns the message html

  function messageHTML() {

    // If there's an error, flag it

    if ($this->error)
      $look = Relations_Admin_LookHTML($this,'error');
    else
      $look = Relations_Admin_LookHTML($this,'message');

    // Send back the html

    return "<p $look>$this->message</p>\n";

  }



  //// Returns the message xml

  function messageXML() {

    // Send back the html

    return "<message>$this->xml</message>\n";

  }



  //// Returns the start of a form html

  function formStartHTML() {

    // Get the look

    $look = Relations_Admin_LookHTML($this,'body');

    // Start the form 

    $html = "<form method='post' enctype='multipart/form-data' name='relations_admin_form' action='$this->self_url' $look>\n";

    // Call inputs

    foreach (array_keys($this->inputs) as $name) 
      $html .= $this->inputs[$name]->formStartHTML($this->records);

    // Send back the html

    return $html;

  }

  

  //// Returns the start of a form xml

  function formStartXML() {

    // Start the form 

    return "<form name='$this->name' depth='$this->depth' task='$this->task' state='$this->state'>\n";

  }

  

  //// Returns the start of a form html

  function formEndHTML() {

    // Get the html 

    $html = "<input type='hidden' name='depth' value='$this->depth'>\n";
    $html .= "<input type='hidden' name='task' value='$this->task'>\n";
    $html .= "<input type='hidden' name='state' value='$this->state'>\n";
    $html .= "<input type='hidden' name='mode' value='$this->mode'>\n";
    $html .= "<input type='hidden' name='limit' value=''>\n";
    $html .= "<input type='hidden' name='redirect_task' value=''>\n";
    $html .= "<input type='hidden' name='redirect_name' value=''>\n";
    $html .= "<input type='hidden' name='redirect_record' value=''>\n";
    $html .= "<input type='hidden' name='kill' value='-1'>\n";
    $html .= "<input type='hidden' name='raise' value='-1'>\n";

    // If we're at link, add a chosen field

    if ($this->state == 'link')
      $html .= "<input type='hidden' name='chosen' value=''>\n";

    // Call inputs

    foreach (array_keys($this->inputs) as $name) 
      $html .= $this->inputs[$name]->formEndHTML($this->records);

    // End the form 

    $html .= "</form>\n";

    // Send back the html

    return $html;

  }



  //// Returns the start of a form html

  function formEndXML() {

    // End the form 

    return "</form>\n";

  }



  //// Returns the script code

  function scriptHTML() {

    // Create an array of scripts so
    // the various inputs won't send the
    // same funciton twice

    $functions = array();

    // Set Submit

    $functions["set_submit"] = "function set_submit() {\n";
    $functions["set_submit"] .= "if (document.relations_admin_form.onsubmit)\n";
    $functions["set_submit"] .= "document.relations_admin_form.onsubmit();\n";
    $functions["set_submit"] .= "document.relations_admin_form.submit();\n";
    $functions["set_submit"] .= "}\n";

    // Set Task

    $functions["set_task"] = "function set_task(task,state) {\n";
    $functions["set_task"] .= "document.relations_admin_form.task.value = task;\n";
    $functions["set_task"] .= "document.relations_admin_form.state.value = state;\n";
    $functions["set_task"] .= "set_submit();\n";
    $functions["set_task"] .= "}\n";

    // Set State

    $functions["set_state"] = "function set_state(state) {\n";
    $functions["set_state"] .= "document.relations_admin_form.state.value = state;\n";
    $functions["set_state"] .= "set_submit();\n";
    $functions["set_state"] .= "}\n";

    // Set Limit

    $functions["set_limit"] = "function set_limit(start,total) {\n";
    $functions["set_limit"] .= "document.relations_admin_form.limit.value = start + ',' + total;\n";
    $functions["set_limit"] .= "document.relations_admin_form.state.value = 'limit';\n";
    $functions["set_limit"] .= "set_submit();\n";
    $functions["set_limit"] .= "}\n";

    // Set Life

    $functions["set_life"] = "function set_life(life,record) {\n";
    $functions["set_life"] .= "life.value = record;\n";
    $functions["set_life"] .= "set_submit();\n";
    $functions["set_life"] .= "}\n";

    // Set Redirect

    $functions["set_redirect"] = "function set_redirect(task,name,record) {\n";
    $functions["set_redirect"] .= "document.relations_admin_form.redirect_task.value = task;\n";
    $functions["set_redirect"] .= "document.relations_admin_form.redirect_name.value = name;\n";
    $functions["set_redirect"] .= "document.relations_admin_form.redirect_record.value = record;\n";
    $functions["set_redirect"] .= "set_state('redirect');\n";
    $functions["set_redirect"] .= "}\n";

    // Set All

    $functions["set_all"] = "function set_all(value) {\n";
    $functions["set_all"] .= "with (document.relations_admin_form) {\n";
    $functions["set_all"] .= "choices = elements['chosen[]'].length;\n";
    $functions["set_all"] .= "for (choice = 0; choice < choices; choice++)\n";
    $functions["set_all"] .= "elements['chosen[]'][choice].checked = value;\n";
    $functions["set_all"] .= "}\n";
    $functions["set_all"] .= "}\n";

    // Set Search

    $functions["set_search"] = "function set_search(input) {\n";
    $functions["set_search"] .= "input.checked = true;\n";
    $functions["set_search"] .= "}\n";

    // Set Mass

    $functions["set_mass"] = "function set_mass(input) {\n";
    $functions["set_mass"] .= "input.checked = true;\n";
    $functions["set_mass"] .= "}\n";

    // Set Admin

    $functions["set_admin"] = "function set_admin(task,chosen) {\n";
    $functions["set_admin"] .= "document.relations_admin_form.redirect_task.value = task;\n";
    $functions["set_admin"] .= "document.relations_admin_form.state.value = 'admin';\n";
    $functions["set_admin"] .= "document.relations_admin_form.chosen.value = chosen;\n";
    $functions["set_admin"] .= "set_submit();\n";
    $functions["set_admin"] .= "}\n";

    // Set Admin

    $functions["set_filter"] = "function set_filter(event_object) {\n";
    $functions["set_filter"] .= "if (event_object.keyCode == 13)\n";
    $functions["set_filter"] .= "set_state('list');\n";
    $functions["set_filter"] .= "}\n";

    // Call inputs

    foreach (array_keys($this->inputs) as $name) 
      $this->inputs[$name]->scriptJS($functions);

    // Start the script 

    $html = "<script language='JavaScript'>\n";

    // Get all the functions

    foreach ($functions as $name=>$function)
      $html .= $function;

    // Check to see if there's a filter
   
    if ($this->state == 'search') {

      $html .= "if (document.relations_admin_form.filter) {\n";
      $html .= "document.relations_admin_form.filter.focus();\n";
      $html .= "document.relations_admin_form.filter.onkeypress = set_filter;\n";
      $html .= "}\n";

    }

    // Go through all the inptus

    $focusing = false;
    
    // Call our focusing function

    $this->needFocus($focusing);

    if ($focusing)
      $html .= "document.location = '#focus';\n";

    // End the script 

    $html .= "</script>\n";

    // Send back the html

    return $html;

  }



  // Figures out if foucsing is needed

  function needFocus(&$focusing) {

    // If we're focusing on anything

    if (count($this->focus))
      $focusing = true;
 
    // Call inputs

    foreach (array_keys($this->inputs) as $name) 
      $this->inputs[$name]->needFocus($focusing);

  }


  ///// Returns intention information/control html

  function intendedHTML($record,$type,$alive) {

    // Start the html
    
    $html = ucfirst($this->intentions[$record]) . " ";

    // If we're just viewing, or we're 
    // not alive, return the html

    if (($type == 'view') || !$alive)
      return $html;

    // Get lifes

    $kill = "document.relations_admin_form." . $this->prefix . "kill";
    $raise = "document.relations_admin_form." . $this->prefix . "raise";

    // Figure out the info

    if ($this->intentions[$record] == $this->ambitions[$record]) {

      if ($this->ambitions[$record] == 'update') {
        $value = 'Delete';
        $kind = 'delete';
      } else {
        $value = "Ignore";
        $kind = 'ignore';
      }

      $clicked = "set_life($kill,'$record')";

    } else {

      if ($this->ambitions[$record] == 'update') {
        $value = "Update";
        $kind = 'update';
      } else {
        $value = ucfirst($this->ambitions[$record]);
        $kind = $this->ambitions[$record];
      }

      $clicked = "set_life($raise,'$record')";

    }

    // Add the button

    $html .= Relations_Admin_ButtonHTML($this,"intend_$record",$value,$clicked,$kind . '_intend_');

    // Send back the html

    return $html;

  }



  function intendedXML($record,$type,$alive) {

    // Start the xml
    
    $current = ucfirst($this->intentions[$record]);

    // If we're just viewing, or we're 
    // not alive, return the xml

    if (($type == 'view') || !$alive)
      return "<intention>$current</intention>\n";

    // Figure out the info

    if ($this->intentions[$record] == $this->ambitions[$record]) {

      if ($this->ambitions[$record] == 'update') 
        $change = 'Delete';
      else
        $change = "Ignore";

      $life = "kill";

    } else {

      if ($this->ambitions[$record] == 'update')
        $change = "Update";
      else
        $change = ucfirst($this->ambitions[$record]);

      $life = "raise";

    }

    return "<intention change='$change' life='$life'>$current</intention>\n";

  }



  ///// Returns the limit HTML

  function limitHTML($limit) {

    // Start the html
    
    $html = '';

    // Split up the limit

    list($start,$total) = explode(',',$limit);

    // Set previous and next

    $previous = max(0,$start-$total);
    $next = $start+$total;

    // Form prefix

    $prefix = 'document.relations_admin_form';

    // Add everything previous

    $html .= Relations_Admin_ButtonHTML($this,"limit_previous",'Previous',"set_limit('$previous','$total')","limit_previous_");
    $html .= Relations_Admin_ButtonHTML($this,"limit_limit",'Limit',"set_limit($prefix.limit_start.value,$prefix.limit_total.value)","limit_");
    $html .= Relations_Admin_TextHTML($this,'limit_total',$total,5,'','limit_total_');
    $html .= Relations_Admin_ButtonHTML($this,"limit_starting",'Starting',"set_limit($prefix.limit_start.value,$prefix.limit_total.value)","starting_");
    $html .= Relations_Admin_TextHTML($this,'limit_start',$next,5,'','limit_start_');
    $html .= Relations_Admin_ButtonHTML($this,"limit_next",'Next',"set_limit('$next','$total')","limit_next_");
    $html .= '<br>';

    // Send back the html

    return $html;

  }



  ///// Returns the limit XML

  function limitXML($limit) {

    // Split up the limit

    list($start,$total) = explode(',',$limit);

    // Start the xml
    
    $xml = "<limit>\n";
    $xml .= "<start>$start</start>\n";
    $xml .= "<total>$total</total>\n";
    $xml .= "</limit>\n";

    // Send back the xml

    return $xml;

  }



  ///// Returns the limit XML

  function adviceXML($advice) {

    // Split up the limit

    list($start,$total) = explode(',',$limit);

    // Start the xml
    
    $xml = "<limit>";
    $xml .= "<start>$start</start>";
    $xml .= "<total>$total</total>";
    $xml .= "</limit>";

    // Send back the xml

    return $xml;

  }



  ///// Returns the controls

  function controlHTML($state,$states=array()) {

    // Start the html
    
    $html = '';

    // Add everything sent

    foreach ($states as $set_state=>$value)
      $html .= Relations_Admin_ButtonHTML($this,"control_$set_state",$value,"set_state('$set_state')","control_${set_state}_");

    // Add home if necessary

    if (count($this->return_info))
      $html .= Relations_Admin_ButtonHTML($this,"control_home","Home","set_task('admin','home')","control_home_");

    // Throw on back if link, otherwise cancel

    if ($state == 'link')
      $html .= Relations_Admin_ButtonHTML($this,"control_return","Return","set_task('admin','cancel')","control_return_");
    else
      $html .= Relations_Admin_ButtonHTML($this,"control_cancel","Cancel","set_task('admin','cancel')","control_cancel_");


    // Send back the html

    return $html;

  }



  ///// Returns the controls

  function controlXML($state,$states=array(),$admins=array()) {

    // Start the xml
    
    $xml = "<controls>\n";

    // Add everything sent

    foreach ($admins as $task=>$ids) {

      if (!count($ids))
        continue;

      $chosen = join(',',$ids);

      $xml .= Relations_Admin_ButtonXML($this,"${task}_all",$task,"set_admin",array($task,$chosen),'admin_all_');

    }

    // Add everything sent

    foreach ($states as $set_state=>$value) 
      $xml .= Relations_Admin_ButtonXML($this,"control_$set_state",$value,"set_state",array($set_state),"control_${set_state}_");

    // Add home if necessary

    if (count($this->return_info)) 
      $xml .= Relations_Admin_ButtonXML($this,"control_home",'Home',"set_task",array('admin','home'),"control_home_");

    // Throw on back if link, otherwise cancel

    if ($state == 'link') 
      $xml .= Relations_Admin_ButtonXML($this,"control_return",'Return',"set_task",array('admin','cancel'),"control_return_");
    else
      $xml .= Relations_Admin_ButtonXML($this,"control_cancel",'Cancel',"set_task",array('admin','cancel'),"control_cancel_");

    $xml .= "</controls>\n";

    // Send back the xml

    return $xml;

  }



  //// Returns HTML after everything's done

  function finishHTML() {

    // Send back the html

    return Relations_Admin_ButtonHTML($this,"control_home","Home","location.href='$this->home_url'","control_home_");

  }



  //// Returns all the html for columnar

  function columnarHTML(&$data) {

    // Get the looks

    $table_look = Relations_Admin_LookHTML($this,'columnar_table');

    // Start the html

    $html = "";

    // Go through all the records

    foreach ($data['record'] as $record=>$record_data) {

      // If there's focusing

      if ($this->focus[$record])
        $html .= "<a name='focus'></a>\n";

      // Go through the records layout

      foreach ($this->layout['records'] as $records_layout) {

        // If this isn't the actual record

        if ($records_layout != 'record') {

          // Just add it on

          $html .= $this->toHTML($records_layout,$record_data[$records_layout]);

        // If we're at the actual record

        } else {

          // Start the table and initialize parity

          $html .= "<table $table_look>\n";
          $this->parity = '';

          // Add the caption if we should

          if (($this->outlay['caption'] == 'record') && strlen($data['caption']))
            $html .= "<caption>$data[caption]</caption>\n";

          // Go through all the layouts

          foreach ($this->layout['record'] as $record_layout)
            if ($record_layout == 'caption')
              continue;
            elseif ($record_layout != 'inputs')
              $html .= $this->toHTML('columnar_data',array('name' => $record_layout,'data' => $record_data[$record_layout]));
            else
              if (count($record_data['inputs']))
                foreach ($record_data['inputs'] as $input)
                  $html .= $this->toHTML('columnar_data',$input);

          // End the table and add a break

          $html .= "</table>\n";

        }

      }

    }

    // Send back the html

    return $html;

  }



  //// Returns all the html for tabular

  function tabularHTML(&$data) {

    // Get the looks

    $table_look = Relations_Admin_LookHTML($this,'tabular_table');
    $tr_look = Relations_Admin_LookHTML($this,'tabular_tr');

    // Start the html

    $html = '';

    // Go through all the records

    foreach ($this->layout['records'] as $records_layout) {

      // If this isn't the actual record

      if ($records_layout != 'record') {

        // Just add it on

        $html .= $this->toHTML($records_layout,$data[$records_layout]);

      // If we're at the actual record

      } else {

        // If there's no data, skip

        if (!$this->isData($data['record']))
          continue;

        // Start the table and initialize parity

        $html .= "<table $table_look>\n";
        $this->parity = 'odd';

        // Add the caption if we should

        if (($this->outlay['caption'] == 'record') && strlen($data['caption']))
          $html .= "<caption>$data[caption]</caption>\n";

        // Start the label row

        $html .= "<tr $tr_look>\n";

        // Add the right labels

        $labeled = array();
        $inputted = array();

        foreach ($this->layout['record'] as $record_layout) {

          // Skip caption

          if ($record_layout == 'caption')
            continue;

          // If we're not doing inputs

          if ($record_layout != 'inputs') {

            // Go through all records and if there's any
            // data, display the label

            foreach ($data['record'] as $record=>$record_data) {

              if ($this->isData($record_data[$record_layout])) {

                // Use the right label and break out

                $html .= $this->toHTML("tabular_th",array('name' => $record_layout));
                $labeled[$record_layout] = true;
                break;

              }

            }

          // If we're at inputs

          } else {

            // Go through all the inputs in all records
            // and check to see if they have any input data

            $inputs = false;
            foreach ($data['record'] as $record=>$record_data)
              if (count($record_data['inputs']))
                $inputs = true;

            // If no input data, skip

            if (!$inputs)
              continue;

            // Go through all the inputs in all records
            // and flag them as not needed

            foreach ($data['record'] as $record=>$record_data)
              foreach ($record_data['inputs'] as $input)
                $inputted[$input['label']] = 0;

            // Go through all records  and it's inputs, if 
            // there's any data, flag it

            foreach ($data['record'] as $record=>$record_data)
              foreach ($record_data['inputs'] as $input)
                if ($this->isData($input['data']))
                  $inputted[$input['label']] = true;

            // Go though all that's needed and display the
            // ones that are

            foreach ($inputted as $label=>$needed)
              if ($needed)
                $html .= $this->toHTML('tabular_th',array('name' => 'input','label' => $label));

          }

        }


        // End the label row

        $html .= "</tr>\n";

        // Go through all the records

        foreach ($data['record'] as $record=>$record_data) {
 
          // Start the row and initialize parity

          $html .= "<tr $tr_look>\n";
          $this->alternate();

          // If there's focusing

          if ($this->focus[$record])
            $html .= "<a name='focus'></a>\n";

          // Go through all the layouts

          foreach ($this->layout['record'] as $record_layout) {

            // If we're not doing inputs

            if ($record_layout != 'inputs') {

              // If we we're labeled, add

              if ($labeled[$record_layout])
                $html .= $this->toHTML('tabular_td',array('name' => $record_layout, 'data' => $record_data[$record_layout]));

            } else {

              // If there's no data, skip

              if (!count($record_data['inputs']))
                continue;

              // Go through all the input data and
              // add when we should

              foreach ($record_data['inputs'] as $input) 
                if ($inputted[$input['label']])
                  $html .= $this->toHTML('tabular_td',$input);

            }

          }

          // End the row

          $html .= "</tr>\n";

        }

        // End the table

        $html .= "</table>\n";

      }

    }

    // Send back the html

    return $html;

  }



  //// Returns all the html for plain

  function plainHTML(&$data) {

    // Get the looks

    $caption_look = Relations_Admin_LookHTML($this,'plain_caption');

    // Start the html 

    $html = "<hr>\n";

    // Go through all the records

    foreach ($this->layout['records'] as $records_layout) {

      // If this isn't the actual record

      if ($records_layout != 'record') {

        // Just add it on

        $html .= $this->toHTML($records_layout,$data[$records_layout]);

      // If we're at the actual record

      } else {

        // Initialize parity

        $this->parity = '';

        // Go through all the records

        foreach ($data['record'] as $record=>$record_data) {
  
          // If there's focusing

          if ($this->focus[$record])
            $html .= "<a name='focus'></a>\n";

          // Add the caption if we should

          if (($this->outlay['caption'] == 'record') && strlen($data['caption']))
            $html .= "<br><i $caption_look>$data[caption]</i><br><br>\n";

          // Go through all the layouts

          foreach ($this->layout['record'] as $record_layout)
            if ($record_layout == 'caption')
              $html .= '';
            elseif ($record_layout != 'inputs')
              $html .= $this->toHTML('plain_data',array('name' => $record_layout,'data' => $data[$record_layout]));
            else
              if (count($record_data['inputs']))
                foreach ($record_data['inputs'] as $input)
                  $html .= $this->toHTML('plain_data',$input);

          $html .= "<hr>\n";

        }

      }

    }

    // Send back the html

    return $html;

  }



  //// Returns all the html for main

  function mainHTML() {

    // If there's header in mains layout,
    // get rid of the page expire thingy

    if (in_array('header',$this->layout['main'])) {

      header("Cache-control: private, no-cache");  
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
      header("Pragma: no-cache");

    }

    // Start the html

    $html = '';

    // Go through all the layouts  and
    // add

    foreach ($this->layout['main'] as $layout)
      if ($layout != 'header')
        $html .= $this->toHTML($layout,$this->data[$layout]);
      
    // Send back the html

    return $html;

  }



  //// Returns all the html for main

  function mainXML() {

    header("Cache-control: private, no-cache");  
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Pragma: no-cache");
    header("Content-type: text/xml");

    // Start the xml

    $xml = '';

    $xml .= $this->startXML();
    $xml .= $this->formStartXML();

    // Add pieces

    foreach (array_keys($this->data) as $type)
      $xml .= $this->toXML($type,$this->data[$type]);
      
    // Send back the xml


    $xml .= $this->formEndXML();
    $xml .= $this->endXML();

    return $xml;

  }



  //// Prepares for display

  function prepare() {

    // Create array to make sure
    // everything's cool

    $errors = array();
    $totals = array();

    // If we're searching, prepare
    // just for one record, else
    // do for all

    if ($this->state == 'search')
      $records = 1;
    else
      $records = $this->records;

    // Call inputs

    foreach (array_keys($this->inputs) as $name) 
      $this->inputs[$name]->prepare($errors,$totals,$this->task,$this->state,$records);

    // If there was any errors,
    // inform the user

    if (count($errors))
      $this->advise($errors);

    // Create the inverse lookup of layout

    $this->outlay = array();
    foreach ($this->layout as $section=>$items)
      foreach($items as $item)
        $this->outlay[$item] = $section;

    // Create the data

    $this->data = array();

  }



  //// Display the entire form

  function display() {

    // Redirect first

    if ($this->redirect_url)
      $this->redirect();

    // Initalize

    $this->prepare();

    // Get it all based on mode

    if ($this->mode == 'xml') {

      $this->xmlData();
      print $this->mainXML();

    } else {

      $this->mainData();
      print $this->mainHTML();

    }

  }



  //// Displays the input URL's (for input)

  function linkHTML($record,$suffix_url,&$input) {

    // If it's an array, use it, else make
    // the one value an array

    if (is_array($input->values[$record]))
      $values = $input->values[$record];
    else
      $values = array($input->values[$record]);

    // Go through each, and make sure 
    // You can either link to it or
    // just display the value otherwise

    $urls = array();
    foreach ($values as $value)
      if ($this->allow('select',$value))
        $urls[] = Relations_Admin_URLHTML($input,"$this->self_url?task=select&chosen=$value$suffix_url",$this->labels[$value]);
      else
        $urls[] = $this->labels[$value];

    // Return them all separate with returns

    return join("<br>\n",$urls);

  }

}

?>