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

class Relations_Admin_ChildForm extends Relations_Admin_Form {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_ChildForm() {

    /* 
    
      $name - The name of the form in PHP
      $label - The label of the form (pretty format)
      $self_url - This form's actual admin page
      $abstract - The Relations_Abstract object to use
      $database - The database to use with this form
      $table - The table to use with this form
      $id_field - The primary key field
      $parent_field - The parent field in the parent table
      $child_field - The child field in this table (connect to parent)
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
      $styles - The styles to use in HTML
      $classes - The classes to use in HTML
      $elements - The extra element to use in HTML
      $form - The form to grab info from

    */

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the arguments passed

    list(
      $this->name,
      $this->label,
      $this->self_url,
      $this->abstract,
      $this->database,
      $this->table,
      $this->id_field,
      $this->parent_field,
      $this->child_field,
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
      $this->styles,
      $this->classes,
      $this->elements,
      $form
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL',
      'SELF_URL',
      'ABSTRACT',
      'DATABASE',
      'TABLE',
      'ID_FIELD',
      'PARENT_FIELD',
      'CHILD_FIELD',
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
      'STYLES',
      'CLASSES',
      'ELEMENTS',
      'FORM'
    ),$arg_list);

    // Create an associative array of inputs for 
    // looking up input objects by name, and for
    // the automatic inputs (not entered by user)

    $this->inputs = array();

    // Now we have to go through and replace any 
    // blank values with the form's values

    if (isset($form)) {

      if (!isset($this->name))
        $this->name = $form->name;

      if (!isset($this->label))
        $this->label = $form->label;

      if (!isset($this->self_url))
        $this->self_url = $form->self_url;

      if (!isset($this->abstract))
        $this->abstract = $form->abstract;

      if (!isset($this->database))
        $this->database = $form->database;

      if (!isset($this->table))
        $this->table = $form->table;

      if (!isset($this->id_field))
        $this->id_field = $form->id_field;

      if (!isset($this->id_input))
        $this->id_input = $form->id_input;

      if (!isset($this->query))
        $this->query = $form->query;

      if (!isset($this->forbids))
        $this->forbids = $form->forbids;

      if (!isset($this->uniques))
        $this->uniques = $form->uniques;

      if (!isset($this->valids))
        $this->valids = $form->valids;

      if (!isset($this->identity))
        $this->identity = $form->identity;

      if (!isset($this->security))
        $this->security = $form->security;

      if (!isset($this->logging))
        $this->logging = $form->logging;

      if (!isset($this->layout))
        $this->layout = $form->layout;

      if (!isset($this->format))
        $this->format = $form->format;

      if (!isset($this->labeling))
        $this->labeling = $form->labeling;

      if (!isset($this->helps))
        $this->helps = $form->helps;

      if (!isset($this->styles))
        $this->styles = $form->styles;

      if (!isset($this->classes))
        $this->classes = $form->classes;

      if (!isset($this->elements))
        $this->elements = $form->elements;

    }

    // Since we're a ChildForm, we need a prefix of 
    // our name

    $this->prefix = $this->name . '_';

  }



  //// Initializes the form, grabs the record number, depth, etc.

  function initialize() {

    // Assume if format's a scalar, it's 
    // meant just for records

    if (is_string($this->format))
      $this->format = array('records' => $this->format);

    // If format's not set for record, 
    // assume columnar

    if (empty($this->format['records']))
      $this->format['records'] = 'tabular';

    // Create the layout array if it
    // wasn't set

    if (!isset($this->layout))
      $this->layout = array();

    // Create the main layout array
    // if wasn't set

    if (!isset($this->layout['main']))
      $this->layout['main'] = 'help,checkbox,records,create';

    // Call parent

    parent::initialize();

  }

  
  
  /*** Input ***/



  //// Creates a reverse lookup from the children array

  function lineage() {

    // If the children aren't set, skip

    if (!isset($this->children))
      return;

    // Initialize the parents array

    $this->parents = array();

    // Go through the parents in the children array

    foreach ($this->children as $parent=>$children) {

      // Go through the children array

      foreach ($children as $child) {

        // Assign this parent record to the child position

        $this->parents[$child] = $parent;

      }

    }

  }



  //// Changes the intention of a record to be 
  //// different from its ambition

  function kill($parent) {

    // Take off if there's no children

    if (!is_array($this->children[$parent]))
      return;

    foreach ($this->children[$parent] as $record) {

      if ($this->ambitions[$record] == 'update')
        $this->intentions[$record] = 'delete';
      else
        $this->intentions[$record] = 'ignore';

      // Now go through inputs and have 'em do the 
      // same

      foreach (array_keys($this->inputs) as $name)
        $this->inputs[$name]->kill($record);  

    }

  }



  //// Changes the intention of a record to be 
  //// the same as its ambition

  function raise($parent) {

    // Take off if there's no children

    if (!is_array($this->children[$parent]))
      return;

    foreach ($this->children[$parent] as $record) {

      $this->intentions[$record] = $this->ambitions[$record];

      foreach (array_keys($this->inputs) as $name)
        $this->inputs[$name]->raise($record);

    }

  }



  //// Steals settings from html/sessions

  function settings($depth) {

  }



  //// Grabs defaults from html/sessions

  function defaults($parents,$depth) {

    // Grab the records and children if they're there

    if (!$this->records)
      $this->records = Relations_Admin_default($depth,$this->prefix . 'records',$this->records);

    if (!$this->children)
      $this->children = Relations_Admin_default($depth,$this->prefix . 'children',$this->children);

    // Make sure records are set

    if (!$this->records)
      $this->records = 0;

    // Go through all the different parents and split
    // up children

    for ($parent = 0; $parent < $parents; $parent++) 
      if (!is_array($this->children[$parent]))
        $this->children[$parent] = Relations_toArray($this->children[$parent]);

    // Record our lineage

    $this->lineage();

    // Go through the inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->defaults($this->records,$depth);

  }



  //// Grabs input from html

  function entered($parents) {

    // Go through all the different parents

    for ($parent = 0; $parent < $parents; $parent++) {

      // Split up the children

      $this->children[$parent] = Relations_toArray($this->children[$parent]);

      // If we're to create records

      if (Relations_Admin_grab($this->prefix . $parent . '_create',0,'VPG')) {

        if (($created = Relations_Admin_grab($this->prefix . $parent . '_created',0,'VPG')) > 0) {

          // Add each new one to the children array

          for ($record = $this->records; $record < ($this->records + $created); $record++) {

            $this->children[$parent][] = $record;
            $this->intentions[$record] = 'insert';
            $this->ambitions[$record] = 'insert';

            // If we have no focus, set it

            if (!count($this->focus))
              $this->focus[$record] = true;

          }

          $this->records += $created;

        }

      }

    }

    // Record our lineage

    $this->lineage();

    // If we're to change a record's intention

    if (($kill = Relations_Admin_grab($this->prefix . 'kill',-1,'VPG')) > -1) {

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

    if (($raise = Relations_Admin_grab($this->prefix . 'raise',-1,'VPG')) > -1) {

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

  }



  //// Grabs input from html for mass set

  function massed($parents) {

    // Check to see if we're going to use this
    // mass value. If so, go through the records
    // and check to see if each record is to use
    // the mass set value. If so, set accordingly

    if (Relations_Admin_grab($this->prefix . 'mass',false,'VPG')) {

      // Go throught he inputs and tell 
      // them to do the same, 

      foreach (array_keys($this->inputs) as $name)
        $this->inputs[$name]->massed($this->records);

    }

  }



  //// Grabs input from html for searching

  function sought() {

    // Check to see if you're searching

    $this->sought = Relations_Admin_grab($this->prefix . 'search',false,'VPG');

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->sought();

  }



  /*** Storage ***/



  //// Stores info not entered by the user

  function set($depth) {

    // Save everything

    Relations_Admin_store($depth . '_' . $this->prefix . 'records',$this->records);
    Relations_Admin_store($depth . '_' . $this->prefix . 'children',$this->children);
    Relations_Admin_store($depth . '_' . $this->prefix . 'originals',$this->originals);
    Relations_Admin_store($depth . '_' . $this->prefix . 'ambitions',$this->ambitions);
    Relations_Admin_store($depth . '_' . $this->prefix . 'intentions',$this->intentions);
    Relations_Admin_store($depth . '_' . $this->prefix . 'redirected',$this->redirected);
    Relations_Admin_store($depth . '_' . $this->prefix . 'parent_values',$this->parent_values);
    Relations_Admin_store($depth . '_' . $this->prefix . 'parent_originals',$this->parent_originals);

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->set($depth);

  }



  //// Retrieves info not entered by the user

  function get($depth) {

    // Load everything

    $this->records = Relations_Admin_retrieve($depth . '_' . $this->prefix . 'records');
    $this->children = Relations_Admin_retrieve($depth . '_' . $this->prefix . 'children');
    $this->originals = Relations_Admin_retrieve($depth . '_' . $this->prefix . 'originals');
    $this->ambitions = Relations_Admin_retrieve($depth . '_' . $this->prefix . 'ambitions');
    $this->intentions = Relations_Admin_retrieve($depth . '_' . $this->prefix . 'intentions');
    $this->redirected = Relations_Admin_retrieve($depth . '_' . $this->prefix . 'redirected');
    $this->parent_values = Relations_Admin_retrieve($depth . '_' . $this->prefix . 'parent_values');
    $this->parent_originals = Relations_Admin_retrieve($depth . '_' . $this->prefix . 'parent_originals');

    // Record our lineage

    $this->lineage();

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->get($depth);

  }



  //// Stores info entered by the user

  function save($depth) {

    // Just records

    Relations_Admin_store($depth . '_' . $this->prefix . 'sought',$this->sought);

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->save($depth);

  }



  //// Retrieves info entered by the user

  function load($depth) {

    // Just records

    $this->sought = Relations_Admin_retrieve($depth . '_' . $this->prefix . 'sought');

    // Go through the inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->load($depth);

  }



  /*** Validate ***/



  //// Sets the intention for all records

  function oblige($intention,$parents) {

    // If we have no records, skip

    if (!$this->records)
      return;

    // If ambition is replace, we're update
    // else use default

    if ($intention == 'merge') {

      $this->intentions = array_fill(0,$this->records,'update');

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



  //// Takes the error messages and creates an
  //// array for adding onto the labels.

  function heed($numbers,$parents) {

    // Go through all the records. Use the value
    // in the HTML input fields. 

    for ($record = 0; $record < $parents; $record++) {
    
      if (is_array($numbers[$this->prefix . $record]) &&
         (count($numbers[$this->prefix . $record]) > 0))
        $this->errors[$record] = $numbers[$this->prefix . $record];

    }      

    // Go throught he inputs and tell 
    // them to do the same

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->heed($numbers,$this->records);

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
        // add their value to the select clause. Grab the
        // parent value if this is the child field and
        // it's set

        foreach ($inputs as $input) {

          if ($this->child_field != $input) {

            $this->inputs[$input]->toSQL($us,'data',$record);
            $this->inputs[$input]->fromSQL($query,'select',$this->intentions[$this->parents[$record]],"$this->database.$this->table",$record);

          } elseif (isset($this->parent_values[$this->parents[$record]]) &&
                   ($this->intentions[$record] != 'copy')) {

            $us[$input] = $this->parent_values[$this->parents[$record]];
            $query->add(array(_where =>
              "$this->table.$this->child_field='" . mysql_escape_string($this->parent_values[$this->parents[$record]]) . "'"
            ));

          } else {

            $us[$input] = $this->parents[$record];
            $query->add(array(_where => "1=0"));

          }

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
        // values are identical. Only check those with the same
        // parent though

        $match = false;

        foreach ($this->children[$this->parents[$record]] as $other) {

          // Don't compare against ourselves

          if ($other == $record)
            continue;

          // Skip this one if it's scheduled for deletion or ignoring

          if (in_array($this->intentions[$other],array('ignore','delete')))
            continue;

          // Go through all the unique inputs and have them
          // add their value to the select clause. Grab the
          // parent value if this is the child field if
          // it's set

          $them = array();

          // Go through all the unique inputs and have them
          // add their value to the select clause. Grab the
          // parent value if this is the child field and
          // it's set

          foreach ($inputs as $input) {

            if ($this->child_field != $input) {

              $this->inputs[$input]->toSQL($them,'data',$other);

            } elseif (isset($this->parent_values[$this->parents[$other]])) {

              $them[$input] = $this->parent_values[$this->parents[$other]];

            } else {

              $them[$input] = $this->parents[$other];

            }

          }

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
            if ($this->child_field != $input)
              $errors[$this->prefix . $input . '_' . $record][] = "$unique must be unique";

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

          // Add the parent field value

          $set[$this->child_field] = $this->parent_values[$this->parents[$record]];

          // Check permissions

          if (!$this->allow('insert',$set))
            $errors[$this->prefix . $record][] = "Cannot insert";

          break;

        case 'copy':

          // Create a set clause to send to all the inputs

          $set = array();

          foreach (array_keys($this->inputs) as $name)
            $this->inputs[$name]->toSQL($set,'data',$record);

          // Add the parent field value

          $set[$this->child_field] = $this->parent_values[$this->parents[$record]];

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

  }



  //// Makes sure everything is valid for fromDB
  
  function fromValidate(&$errors) {

    // Now go through all the inputs and have them do 
    // what they have to do. 

    foreach (array_keys($this->inputs) as $name) {

      $this->inputs[$name]->fromValidate($errors,$this->intentions,$this->records);

    }

  }



  /*** Database ***/



  //// Sets from SQL data from the database

  function setSQL($sql,$parent) {

    // $sql - The row of data returned from the DB 
    // $record - The record at which to set this data
    
    // Get the value returned 

    $this->parent_values[$parent] = Relations_Admin_cleanSQL($sql[$this->parent_field]);

  }



  //// Builds SQL data to send to the database

  function toSQL(&$sql,$intention,$record) {

    // Check the reason we're getting data

    switch ($intention) {

      case 'data':
      case 'insert':
      case 'copy':
      case 'update':
      case 'replace':
      case 'delete':
      case 'ignore':
        break;

    }

  }



  //// Builds SQL data to receive from the database

  function fromSQL(&$sql,$intention,$ambition,$table,$parent) {

    // Check the reason we're getting data

    switch ($intention) {

      case 'select':

        break;

      case 'search':

        if ($this->sought) {

          $mine = $this->parent_prefix . $this->name;

          $sql->add(array(
            _from  => array($mine => "$this->database.$this->table"),
            _where => array(
              "$table.$this->parent_field=$mine.$this->child_field",
            )
          ));

          // Go through all the children

          foreach (array_keys($this->inputs) as $name)
            $this->inputs[$name]->fromSQL($sql,$intention,$ambition,$mine,0);

        }

        break;

    }

  }



  //// Merges the relations from all to first

  function merge(&$totals,$parents) {

    // Wipe children

    $this->children = array();

    // Go through all the records and attach
    // to main

    for ($record = 0; $record < $this->records; $record++)
      $this->children[0][] = $record;

    // Record our lineage

    $this->lineage();

  }


  
  //// Sends data to the database
  
  function toDB(&$errors,&$totals,$intentions,$parents) {

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

      // Add the parent field value

      $set = Relations_assignClauseAdd($set,array($this->child_field => "'" . mysql_escape_string($this->parent_values[$this->parents[$record]]) . "'"));

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

  }



  //// Receives data from the database
  
  function fromDB(&$errors,&$totals,$intention,$ambition,$parents) {

    // Set originals from current

    $this->parent_originals = $this->parent_values;
    $this->orginals = array();

    // Go through all the parent records to
    // reverse lookup parent values to 
    // parent records

    $parent_records = array();
    for ($parent = 0; $parent < $parents; $parent++) {

      $parent_records[$this->parent_values[$parent]] = $parent;
      $this->children[$parent] = array();

    }

    // Copy our query to lookup

    $query = clone $this->query;

    // Create the ins array

    $ins = array();

    // Escape all parent values

    foreach ($this->parent_values as $parent_value)
      $ins[] = "'" . mysql_escape_string($parent_value) . "'";

    // Add all fields and select by child field

    $query->add(array(
      _select => "$this->table.*",
      _where  => "$this->table.$this->child_field in (" . join(',',$ins) . ")"
    ));

    // Get the rows

    $finds = $this->abstract->selectMatrix(array(
      _query => $query
    ));

    // Initialize the record count

    $this->records = 0;
  
    // Go through all the rows

    for ($record = 0; $record < count($finds); $record++) {

      // Attach this row to the right record

      $this->children[$parent_records[$finds[$record][$this->child_field]]][] = $this->records;

      // Now get all the info from the db using the id value

      foreach (array_keys($this->inputs) as $name)
        if (!$this->deny($ambition,$name,$finds[$record][$this->id_field]))
          $this->inputs[$name]->setSQL($finds[$record],$this->records);

      // Set originals

      $this->originals[$this->records] = $this->ID($this->records);

      // Increase our values in the totals array

      $totals[$this->label]['select'][] = $finds[$record][$this->id_field];

      // Increase records

      $this->records++;

    }

    // Now go through all the inputs and have them do 
    // what they have to do if we have records

    if ($this->records > 0)
      foreach (array_keys($this->inputs) as $name)
        $this->inputs[$name]->fromDB($errors,$totals,$intention,$ambition,$this->records);

    // Record our lineage

    $this->lineage();

  }



  /*** Process ***/



  //// Process the input's redirecting

  function redirectProcess($task,$record) {

    // Get who redirected, etc

    $this->redirected = Relations_Admin_grab($this->prefix . 'redirect_name');

    // Return the url to get there

    return $this->inputs[$this->redirected]->redirectProcess($task,$record);

  }



  //// Gets the redirect info for children

  function updateInfosProcess(&$infos,$intentions,$records) {

    // Now go through all the inputs and have them do 
    // what they have to do. 

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->updateInfosProcess($infos,$this->intentions,$this->records);

  }



  //// Gets the redirect info for children

  function copyInfosProcess(&$infos,$intentions,$records) {

    // Now go through all the inputs and have them do 
    // what they have to do. 

    foreach (array_keys($this->inputs) as $name)
      $this->inputs[$name]->copyInfosProcess($infos,$this->intentions,$this->records);

  }



  /*** HTML ***/



  //// Gets the records for this state

  function getRecords($state,$parent) {

    $records = array();

    if (($state == 'mass') || ($state == 'search'))
      array_push($records, 0);
    elseif (is_array($this->children[$parent]))
      $records = $this->children[$parent];

    return $records;
  
  }



  //// Prepares for display

  function prepare(&$errors,&$totals,$task,$state,$parents) {

     // Create the html structure

    $this->data = array();

    // Create the inverse lookup of layout

    $this->outlay = array();
    foreach ($this->layout as $section=>$items)
      foreach($items as $item)
        $this->outlay[$item] = $section;

    // If we're searching, prepare
    // just for one record, else
    // do for all

    if (state == 'search')
      $records = 1;
    else
      $records = $this->records;

    // Call inputs

    foreach (array_keys($this->inputs) as $name) 
      $this->inputs[$name]->prepare($errors,$totals,$task,$state,$records);

  }



  //// Returns the start of a form html

  function formStartHTML($parents) {

    // Add nothing

    $html = '';

    // Call inputs

    foreach (array_keys($this->inputs) as $name) 
      $html .= $this->inputs[$name]->formStartHTML($this->records);

    // Send back the html

    return $html;

  }



  //// Returns the end of a form html

  function formEndHTML($parents) {

    // Get prefix

    $prefix = $this->prefix;

    // Get the html

    $html .= "<input type='hidden' name='${prefix}redirect_name' value=''>\n";
    $html .= "<input type='hidden' name='${prefix}kill' value='-1'>\n";
    $html .= "<input type='hidden' name='${prefix}raise' value='-1'>\n";

    // Call inputs

    foreach (array_keys($this->inputs) as $name) 
      $html .= $this->inputs[$name]->formEndHTML($this->records);

    // Send back the html

    return $html;

  }



  //// Returns the script code

  function scriptJS(&$functions) {

    // Get the prefixes

    $prefix = $this->prefix;
    $parent = $this->parent_prefix;

    // Set Redirect

    $functions["set_${prefix}redirect"] = "function set_${prefix}redirect(task,name,record) {\n";
    $functions["set_${prefix}redirect"] .= "document.relations_admin_form.${prefix}redirect_name.value = name;\n";
    $functions["set_${prefix}redirect"] .= "set_${parent}redirect(task,'$this->name',record);\n";
    $functions["set_${prefix}redirect"] .= "}\n";

    // Set Mass

    $functions["set_${prefix}mass"] = "function set_${prefix}mass(input) {\n";
    $functions["set_${prefix}mass"] .= "input.checked = true;\n";
    $functions["set_${prefix}mass"] .= "set_${parent}mass(document.relations_admin_form.${prefix}mass);\n";
    $functions["set_${prefix}mass"] .= "}\n";

    // Set Search

    $functions["set_${prefix}search"] = "function set_${prefix}search(input) {\n";
    $functions["set_${prefix}search"] .= "input.checked = true;\n";
    $functions["set_${prefix}search"] .= "set_${parent}search(document.relations_admin_form.${prefix}search);\n";
    $functions["set_${prefix}search"] .= "}\n";

    // Call inputs

    foreach (array_keys($this->inputs) as $name) 
      $this->inputs[$name]->scriptJS($functions);

  }



  //// Returns HTML for all inputs

  function inputHTML($state,$parent,$extra) {

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

    $this->data[$parent][$fill] = array();

    // Get the records

    $records = $this->getRecords($state,$parent);

    // Save the current extra as alive,
    // in case we need it

    $alive = $extra;

    // If we're entering, and we're not 
    // dead, we need the insert new ones

    if (($state == 'enter') && $alive)
      $this->data[$parent][$fill]['create'] = array('prefix' => $this->prefix . $parent . "_");

    // If we're mass, we need to add a 
    // checkbox for mass set

    if ($state == 'mass')
      $this->data[$parent][$fill]['checkbox'] = array('name' => $this->prefix . "mass",'label' => "Use for Mass");

    // If we're search, we need to add a 
    // checkbox for sought

    if ($state == 'search')
      $this->data[$parent][$fill]['checkbox'] = array('name' => $this->prefix . "search",'label' => "Use in Search",'checked' => $this->sought);

    // Get the help

    $this->data[$parent]['help'] = 1;

    // Initialize records

    $this->data[$parent][$fill]['record'] = array();

    // If there's records

    if (count($records)) {

      // Push anything in records down
      // to record if we're supposed to

      $push = array();

      foreach ($this->layout['record'] as $record_layout)
        if ($this->isData($this->data[$parent][$fill][$record_layout]))
          $push[$record_layout] = $this->data[$parent][$fill][$record_layout];

      // Go through all the records

      foreach ($records as $record) {

        // Push on the extra

        foreach ($push as $push_layout => $push_data)
          $this->data[$parent][$fill]['record'][$record][$push_layout] = $push_data;

        // If we're entering, we have to figure out
        // if we're doing what we intended for inputs
        // like child forms

        if ($state == 'enter')
          $extra = $alive && ($this->intentions[$record] == $this->ambitions[$record]);

        // Get the error message

        $this->data[$parent][$fill]['record'][$record]['error'] = $this->errors[$record];

        // If we're previewing, we need the intended action

        if ($state == 'preview') {

          $this->data[$parent][$fill]['record'][$record]['intended']['type'] = 'view';
          $this->data[$parent][$fill]['record'][$record]['intended']['record'] = $record;

        // If we're entering and we're not single,
        // we need the intention controls  

        } elseif ($state == 'enter') {

          $this->data[$parent][$fill]['record'][$record]['intended']['type'] = 'control';
          $this->data[$parent][$fill]['record'][$record]['intended']['record'] = $record;
          $this->data[$parent][$fill]['record'][$record]['intended']['alive'] = $alive;

        }

        // Now get everything from the inputs
        // if they're not fobidden, etc.  

        $this->data[$parent][$fill]['record'][$record]['inputs'] = array();

        foreach (array_keys($this->inputs) as $name)
          $this->data[$parent][$fill]['record'][$record]['inputs'][] = array(
            'name'  => 'input',
            'label' => $this->inputs[$name]->label,
            'data'  => $this->inputs[$name]->inputHTML($state,$record,$extra)
          );
          
      }

    }

    // Start the html

    $html = '';

    // Go through all the layouts  and
    // add

    foreach ($this->layout['main'] as $layout)
      if ($layout == 'records')
        $html .= $this->toHTML($fill,$this->data[$parent][$fill]);
      else
        $html .= $this->toHTML($layout,$this->data[$parent][$fill][$layout]);
      
    // Send back the html

    return $html;

  }

  //// Returns HTML for all inputs

  function inputXML($state,$parents,$extra) {

    // Figure our what to set

    $this->data = array();
    $this->data['name'] = $this->name;
    $this->data['type'] = 'ChildForm';
    $this->data['settings'] = array();
    $this->data['lineage'] = $this->children;
    $this->data['help'] = $this->help;
    $this->data['errors'] = $this->errors;
    $this->data['message'] = $this->message;
    $this->data['intended'] = array();

    $this->data['settings']['label'] = $this->label;
    $this->data['settings']['prefix'] = $this->prefix;

    // Get the records

    // If we're searching, prepare
    // just for one record, else
    // do for all

    $records = array();
    if ($state == 'search')
      $records[] = 0;
    else
      for ($record = 0; $record < $this->records; $record++)
        $records[] = $record;

    $this->data['settings']['records'] = count($records);

    // If we're search, we need to add a 
    // checkbox for sought

    if ($state == 'search')
      $this->data['sought'] = $this->sought;

    // Initialize inputs

    $this->data['inputs'] = array();

    // If there's records

    $nextra = array();
    if (count($records)) {

      foreach ($records as $record) {

        // If we're entering, we have to figure out
        // if we're doing what we intended for inputs
        // like child forms

        $alive = $extra[$this->parents[$record]];

        if ($state == 'enter')
          $nextra[$record] = $alive && ($this->intentions[$record] == $this->ambitions[$record]);

        // If we're previewing, we need the intended action

        if ($state == 'preview') {

          $this->data['intended'][$record] = array(
            'type' => 'view',
            'alive' => 0
          );

        // If we're entering and we're not single,
        // we need the intention controls  

        } elseif ($state == 'enter') {

          $this->data['intended'][$record] = array(
            'type' => 'control',
            'alive' => $alive
          );

        }

      }

    }

    // Now get all the input info

    foreach (array_keys($this->inputs) as $name)
      $this->data['inputs'][] = $this->inputs[$name]->inputXML($state,$records,$nextra);

    // Send back the data

    return $this->data;

  }

}

?>