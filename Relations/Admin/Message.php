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

class Relations_Admin_Message extends Relations_Admin_Form {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_Message() {

    /* 
    
      $name - The name of the form in PHP
      $label - The label of the form (pretty format)
      $home_url - The default 'home' url
      $self_url - The page's url
      $prefix_url - URL to prefix attachments for display
      $abstract - The Relations_Abstract object to use
      $database - The database to use with this form
      $table - The table to use with this form
      $id_field - The primary key field
      $id_input - The primary key input
      $mailbox - The mailbox form object
      $filters - Filters of attachments types to avoid
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
      $this->prefix_url,
      $this->abstract,
      $this->database,
      $this->table,
      $this->id_field,
      $this->id_input,
      $this->mailbox,
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
      'PREFIX_URL',
      'ABSTRACT',
      'DATABASE',
      'TABLE',
      'ID_FIELD',
      'ID_INPUT',
      'MAILBOX',
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



  //// Initialize the File

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
        $this->layout['record'] = 'caption,error,select,initial,filter,mailbox,inputs,attachment,intended,admin,single,choose,view';

    } else {

      // Create the mass layout array
      // if wasn't set

      if (!isset($this->layout['records']))
        $this->layout['records'] = 'error,record,set';

      // Create the search layout array
      // if wasn't set

      if (!isset($this->layout['record']))
        $this->layout['record'] = 'caption,select,initial,filter,mailbox,inputs,attachment,intended,admin,single,choose,view';

    }

    // Parent

    parent::initialize();

    // Directory

    $this->mailbox->initialize();

    // Make sure the directory is set

    if (!is_object($this->mailbox))
      die('Mailbox must be set for ' . $this->label . '!');

    // Set our resource and reference

    $this->imap = &$this->mailbox->imap;

    // Make sure we have a table name

    if (!strlen($this->table))
      $this->table = "_temporary_$this->name";

    // Make sure we have a id name

    if (!strlen($this->id_field))
      $this->id_field = $this->id_input;

  }



  /*** Input ***/



  //// Grabs settings from html/sessions

  function settings() {

    // Call parent

    parent::settings();

    $this->detaching = Relations_Admin_default($this->depth,'detaching',$this->detaching);

  }



  //// Grabs input from html

  function entered() {

    // Call parent

    parent::entered();

    // Get detached

    $this->detached = Relations_Admin_grab('detached','','VPG');

    // Go through all the records. Use the value
    // in the HTML input fields make sure they're 
    // within the main directory

    for ($record = 0; $record < $this->records; $record++) {

      $none = Relations_Admin_grab('none_' . $record,$default,'VPG');

      if ($none)
        $this->inputs[$this->id_input]->values[$record] = $this->imap->split($this->ID($record),'uid');

    }

  }



  /*** Storage ***/



  //// Stores info not entered by the user

  function set() {

    // Call parent

    parent::set();

    // Save everything

    Relations_Admin_store($this->depth . '_detaching',$this->detaching);

  }



  //// Retrieves info not entered by the user

  function get() {

    // Call parent

    parent::get();

    // Get everything

    $this->detaching = Relations_Admin_retrieve($this->depth . '_detaching');

  }



  /*** Validate ***/



  //// Checks all values for uniqueness

  function uniquesValidate(&$errors) {

    // De nada

  }



  //// Checks values for existance

  function existsValidate(&$errors,$intentions,$records,$input) {

    // Go through all records to tally all messages

    $messages = array();

    for ($record = 0; $record < $records; $record++) {

      // Skip if there's no value 

      if (!strlen($input->values[$record]))
        continue;

      // Skip if we're ignoring or deleting

      if (!in_array($intentions[$record],array('ignore','delete'))) {

        // If this is an array, add the values one 
        // by one, else, just add this value

        if (is_array($input->values[$record])) {

          foreach ($input->values[$record] as $value)
            $messages[] = $value;

        } else {

          $messages[] = $input->values[$record];

        }

      }

    }

    // Get only uniques

    $messages = array_unique($messages);

    // Return if there's nothing to check

    if (!count($messages))
      return;

    // Now get all messages from the database and hash them

    $exists = array();

    foreach ($messages as $message) {

      $folder = $this->imap->folder($message);
      $uid = $this->imap->uid($message);
      $exists[$message] = $this->imap->exists($folder,$uid);

    }

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

            $errors[$input->prefix . $input->name . '_' . $record][] = "Message does not exist";
            break;

          }

        }

      } else {

        // Check to see if value exists

        if (!$exists[$input->values[$record]] && strlen($input->values[$record]))
          $errors[$input->prefix . $input->name . '_' . $record][] = "Message does not exist";

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

    // Select the database

    $this->abstract->runQuery("use $this->database");

    // Create a temporay table to hold everything

    $this->abstract->runQuery("drop table if exists $this->table");

    $this->abstract->runQuery("
      create temporary table $this->table (
        $this->id_field char(255) not null,
        uid int(10) unsigned NOT NULL default '0',
        folder char(255) not null,
        header char(255) not null,
        mess_dt datetime,
        subject text,
        from_addr text,
        sender_addr text,
        reply_to_addr text,
        to_addr text,
        cc_addr text,
        bcc_addr text,
        in_reply_to text,
        message_id text
      )
    ");

    // Get a listing of all the messages

    $routes = $this->imap->climb();

    // Escape all the data and filter

    $values = array();
    foreach ($routes as $route)
      $values[] = array(
        $this->id_field => "'" . mysql_escape_string("$route[folder].$route[uid]") . "'",
        'uid' => "'" . mysql_escape_string($route['uid']) . "'",
        'folder' => "'" . mysql_escape_string($route['folder']) . "'",
        'header' => "'" . mysql_escape_string($route['header']) . "'",
        'mess_dt' => "'" . mysql_escape_string(date('Y-m-d H:i:s',strtotime($route['date']))) . "'",
        'subject' => "'" . mysql_escape_string($route['subject']) . "'",
        'from_addr' => "'" . mysql_escape_string($route['from']) . "'",
        'sender_addr' => "'" . mysql_escape_string($route['sender']) . "'",
        'reply_to_addr' => "'" . mysql_escape_string($route['reply_to']) . "'",
        'to_addr' => "'" . mysql_escape_string($route['to']) . "'",
        'cc_addr' => "'" . mysql_escape_string($route['cc']) . "'",
        'bcc_addr' => "'" . mysql_escape_string($route['bcc']) . "'",
        'in_reply_to' => "'" . mysql_escape_string($route['in_reply_to']) . "'",
        'message_id' => "'" . mysql_escape_string($route['message_id']) . "'"
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

    // Go through all the records 

    for ($record = 0; $record < $this->records; $record++) {

      // See what we intend to do with this one

      switch ($this->intentions[$record]) {

        case 'insert':

          $totals[$this->label][$this->intentions[$record]][] = $this->ID($record);
          break; 

        case 'copy':

          // First get the folder and next uid 

          $uid = $this->imap->split($this->originals[$record],'uid');
          $from = $this->imap->split($this->originals[$record],'folder');
          $to = $this->imap->split($this->ID($record),'folder');

          if ($to_id = $this->imap->copy($from,$to,$uid)) {

            $this->imap->flag($to,$to_id,$this->inputs['flags']->values[$record]);
            $this->inputs[$this->id_input]->values[$record] = $this->imap->join($to,$to_id);
            $totals[$this->label]['insert'][] = $this->ID($record);
            $totals[$this->label][$this->intentions[$record]][] = $this->originals[$record];

          }



          break;

        case 'update':
        case 'replace':

          // First get the folder and next uid 

          $uid = $this->imap->split($this->originals[$record],'uid');
          $from = $this->imap->split($this->originals[$record],'folder');
          $to = $this->imap->split($this->ID($record),'folder');

          $this->imap->flag($from,$uid,$this->inputs['flags']->values[$record]);

          if ($this->ID($record) != $this->originals[$record]) {

            if ($to_id = $this->imap->move($from,$to,$uid)) {

            $this->inputs[$this->id_input]->values[$record] = $this->imap->join($to,$to_id);
              $totals[$this->label][$this->intentions[$record]][] = $this->ID($record);
              $totals[$this->label]['replace'][] = $this->originals[$record];

            }

          }

          break;

        case 'delete':

          // First get the folder and next uid 

          $uid = $this->imap->split($this->originals[$record],'uid');
          $folder = $this->imap->split($this->originals[$record],'folder');

          if ($this->imap->delete($folder,$uid)) 
            $totals[$this->label][$this->intentions[$record]][] = $this->originals[$record];

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

    // Create a query to get at the data

    $this->query = new Relations_Query(array(
      _select => array(
        id    => "$this->table.$this->id_field",
        label => "concat($this->table.folder,':',$this->table.header)",
      ),
      _from => $this->table,
      _options => 'distinct'
    ));

    // Make the database

    $this->makeDB();

    // Copy our query to send to all the inputs

    $query = $this->query;

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
          $totals[$this->label]['count'][] += $find['count'];
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

          // Populate finds with the rest of the record

          $finds[$record]['body'] = $this->imap->deliver($finds[$record]['folder'],$finds[$record]['uid']);

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



  //// Grabs an attachment and either 
  //// returns the data, or writes it
  //// to a filename and returns the 
  //// name.

  function &detach($chosen) {

    // Get the info

    $letter = $this->imap->split($chosen,'letter');
    $folder = $this->imap->split($chosen,'folder');
    $uid = $this->imap->split($chosen,'uid');
    $section = $this->imap->split($chosen,'section');

    // Check security

    if (!$this->allow('select',$letter))
      return '';

    // Get the attachment info

    $attachments = array();
    $this->imap->select($folder);
    $structure = $this->imap->structure($folder,$uid);
    $this->imap->section($structure);
    $this->imap->attach($structure,$attachments);
    $attachment = $attachments[$section];

    // Get the data

    $attachment['data'] = &$this->imap->detach($folder,$uid,$section,$attachment['encoding']);

    return $attachment;

  }

  /*** Process ***/



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

      // Detaching a file

      case 'detach':

        $totals = array();
        $this->entered();

        if ($this->detached) {

          $totals[$this->label]['detach'] = array($this->detached);
          $this->finish($totals);
          $this->finishProcess();

        } else {

          $this->state = 'link';

        }

        break;

    }

  }



  //// Processes the input's (or directory) redirecting

  function redirectProcess() {

    // Get who redirected, etc

    $task = Relations_Admin_grab('redirect_task');
    $this->redirected = Relations_Admin_grab('redirect_name');
    $record = Relations_Admin_grab('redirect_record');

    // Get the info to get there, check to 
    // see if it's the mailbox

    if ($this->redirected == 'mailbox') {

      // Create an array of redirect info

      $redirect_info = array(
        'values' => array()
      );

      // Create the redirect url

      $redirect_info['url'] = $this->mailbox->self_url; 
      $redirect_info['values']['task'] = $task;
      $redirect_info['values']['single'] = 1;

    } else {

      $redirect_info = $this->inputs[$this->redirected]->redirectProcess($task,$record);

    }

    // Set the depth

    $redirect_info['values']['depth'] = $this->depth + 1;

    // Create the url to return to us

    $redirect_info['values']['return_info'] = array();
    $redirect_info['values']['return_info']['url'] = $this->self_url;
    $redirect_info['values']['return_info']['values'] = array();
    $redirect_info['values']['return_info']['values']['task'] = $this->task;
    $redirect_info['values']['return_info']['values']['state'] ='return';
    $redirect_info['values']['return_info']['values']['depth'] = $this->depth;

    // If it was mailbox, throw on the record

    if ($this->redirected == 'mailbox')
      $redirect_info['values']['return_info']['values']['record'] = $record;

    // Send and get url

    $this->redirect_url = Relations_Admin_redirect($redirect_info);

    // Set the state to redirect

    $this->state = 'redirect';

  }



  //// Processes the input's (or directory) returning

  function returnProcess($totals) {

    // Call the inputs function, check to 
    // see if it's the mailbox

    if ($this->redirected == 'mailbox') {

      // Get the record that was stored

      $record = Relations_Admin_default($this->depth,'record');

      // Get the redirected record's value to the 
      // form's first chosen or inserted id value.

      if ($totals[$this->mailbox->label]['choose'][0])
        $location = $totals[$this->mailbox->label]['choose'][0];
      elseif ($totals[$this->mailbox->label]['insert'][0])
        $location = $totals[$this->mailbox->label]['insert'][0];

      // Replace the current file's location with
      // the new one

      $this->inputs[$this->id_input]->values[$record] = $this->imap->join($location,$this->imap->split($this->ID($record),'uid'));

    } else {

      $this->inputs[$this->redirected]->returnProcess($totals);

    }

  }



  /*** HTML ***/



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

        if ($this->imap->total() < 100) {

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

      foreach (array_keys($this->inputs) as $name) {

        if (!$this->deny($this->ambitions[$record],$name,$this->ID($record))) {

          $this->data[$fill]['record'][$record]['inputs'][] = array(
            'name'  => 'input',
            'label' => $this->inputs[$name]->label,
            'data'  => $this->inputs[$name]->inputHTML($state,$record,$extra)
          );

        }

      }
        
    } 

    // If we're mass, use mass, search, 
    // else use records

    if ($state == 'mass')
      $fill = 'mass';
    else
      $fill = 'records';

    // If we're entering, go through all the records 
    // and add the extra stuff

    if ($state == 'enter') {

      // Go through all the records

      foreach ($records as $record) {

        // Mailbox  

        $insert = 'set_redirect("insert","mailbox",' . $record . ')';
        $choose = 'set_redirect("choose","mailbox",' . $record . ')';

        $this->data[$fill]['record'][$record]['mailbox'] = Relations_Admin_ButtonHTML($this,'choose_' . $record,'Choose',$choose,'choose_') .
                                                           Relations_Admin_ButtonHTML($this,'insert_' . $record,'Insert',$insert,'insert_') .
                                                           Relations_Admin_SubmitHTML($this,'none_' . $record,'None','none_');

      }

    } 

    // Go through all the records

    foreach ($records as $record) {

      $this->data[$fill]['record'][$record]['attach'] = array();

      $uid = $this->imap->split($this->originals[$record],'uid');
      $folder = $this->imap->split($this->originals[$record],'folder');

      $attachments = array();
      $structure = $this->imap->structure($folder,$uid);
      $this->imap->section($structure);
      $this->imap->attach($structure,$attachments);

      foreach ($attachments as $section=>$attachment) {

        $type = $attachment['type'];

        if ($type == 'multipart')
          continue;

        if ($attachment['subtype'])
          $type .= "/$attachment[subtype]";

        if ($attachment['parameters']['name'])
          $name = $attachment['parameters']['name'] . " ($type)";
        else
          $name = $type;

        $this->data[$fill]['record'][$record]['attachment'][] = array(
          'id' => $this->imap->join($folder,$uid,$section),
          'label' => $name
        );

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

      case 'attachment':
        foreach ($data as $attachment) {

          $url = $attachment['label'] . ' ' . 
                  Relations_Admin_URLHTML($this,"$this->prefix_url$attachment[id]",'View','_blank','attachment_') . ' ' .
                  Relations_Admin_URLHTML($this,"$this->prefix_url$attachment[id]&task=download",'Download','','attachment_');

          $detach = 'set_detach("' . $attachment[id] . '")';
          $button = 'detach_' . $record . '_' . str_replace('.','_',$attachment['section']);

          if ($this->detaching)
            $url .=  ' ' . Relations_Admin_ButtonHTML($this,$button,'Detach',$detach,'detach_');

          $urls[] = $url;

        }
        return Relations_Admin_HelpHTML($this,'attachment') . join('<br>',$urls);

      default:
        return parent::toHTML($name,$data);

    }

  }



  //// Returns the start of a form html

  function formEndHTML() {

    // Get the html 

    $html = "<input type='hidden' name='detaching' value='$this->detaching'>\n";
    $html .= "<input type='hidden' name='detached' value=''>\n";

    // Add parent stuff

    $html .= parent::formEndHtml();

    // Send back the html

    return $html;

  }



  //// Returns the script code

  function scriptHTML() {

    // Create an array of scripts so
    // the various inputs won't send the
    // same funciton twice

    $functions = array();

    // Set Task

    $functions["set_task"] = "function set_task(task,state) {\n";
    $functions["set_task"] .= "document.relations_admin_form.task.value = task;\n";
    $functions["set_task"] .= "document.relations_admin_form.state.value = state;\n";
    $functions["set_task"] .= "document.relations_admin_form.submit();\n";
    $functions["set_task"] .= "}\n";

    // Set State

    $functions["set_state"] = "function set_state(state) {\n";
    $functions["set_state"] .= "document.relations_admin_form.state.value = state;\n";
    $functions["set_state"] .= "document.relations_admin_form.submit();\n";
    $functions["set_state"] .= "}\n";

    // Set Limit

    $functions["set_limit"] = "function set_limit(start,total) {\n";
    $functions["set_limit"] .= "document.relations_admin_form.limit.value = start + ',' + total;\n";
    $functions["set_limit"] .= "document.relations_admin_form.state.value = 'limit';\n";
    $functions["set_limit"] .= "document.relations_admin_form.submit();\n";
    $functions["set_limit"] .= "}\n";

    // Set Life

    $functions["set_life"] = "function set_life(life,record) {\n";
    $functions["set_life"] .= "life.value = record;\n";
    $functions["set_life"] .= "document.relations_admin_form.submit();\n";
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
    $functions["set_admin"] .= "document.relations_admin_form.submit();\n";
    $functions["set_admin"] .= "}\n";

    // Set Detached

    $functions["set_detach"] = "function set_detach(chosen) {\n";
    $functions["set_detach"] .= "document.relations_admin_form.state.value = 'detach';\n";
    $functions["set_detach"] .= "document.relations_admin_form.detached.value = chosen;\n";
    $functions["set_detach"] .= "document.relations_admin_form.submit();\n";
    $functions["set_detach"] .= "}\n";

    // Call inputs

    foreach (array_keys($this->inputs) as $name) 
      $this->inputs[$name]->scriptJS($functions);

    // Start the script 

    $html = "<script language='JavaScript'>\n";

    // Get all the functions

    foreach ($functions as $name=>$function)
      $html .= $function;

    // End the script 

    $html .= "</script>\n";

    // Send back the html

    return $html;

  }


}

?>