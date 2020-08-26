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
require_once('Relations/Admin/File.php');

class Relations_Admin_Image extends Relations_Admin_File {



  /*** Create ***/



  //// Initialize the Image

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
        $this->layout['record'] = 'caption,error,select,initial,filter,image,upload,local,remote,directory,rename,inputs,url,intended,admin,single,thumb,choose,view';

    } else {

      // Create the mass layout array
      // if wasn't set

      if (!isset($this->layout['records']))
        $this->layout['records'] = 'error,record,set';

      // Create the search layout array
      // if wasn't set

      if (!isset($this->layout['record']))
        $this->layout['record'] = 'caption,select,initial,filter,image,upload,local,remote,directory,rename,inputs,url,intended,admin,single,thumb,choose,view';

    }

    // Parent

    parent::initialize();

  }

  
  
  /*** HTML ***/



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

      $this->data['list']['record'][$record]['view']['id'] = $this->ids[$record];
      $this->data['list']['record'][$record]['view']['url'] = $view_url;

      $image_src = $this->prefix_url . str_replace('%2F','/',rawurlencode($this->ids[$record]));
      $this->data['list']['record'][$record]['thumb'] = Relations_Admin_ImageHTML($this,$image_src,'150','',$this->ids[$record],'');

    }

  }



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

    if ($state != 'enter') {

      // Go through all the records and
      // add the image where applicable

      foreach ($records as $record) {

        // Only display something that's set

        if (!$this->ID($record))
          continue;

        $image_src = $this->prefix_url . str_replace('%2F','/',rawurlencode($this->ID($record)));

        $this->data[$fill]['record'][$record]['image'] = Relations_Admin_ImageHTML($this,$image_src,'','',$this->ID($record),'');

      }

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
    foreach ($values as $value) {

      if ($value) {

        $image_src = $this->prefix_url . str_replace('%2F','/',rawurlencode($value));
        $img = Relations_Admin_ImageHTML($input,$image_src,'','60',$value,'') . '<br>';

      } else {

        $img = '';

      }

      if ($this->allow('select',$value))
        $urls[] = $img . Relations_Admin_URLHTML($input,"$this->self_url?task=select&chosen=$value$suffix_url",$this->labels[$value]);
      else
        $urls[] = $img . $this->labels[$value];

    }

    // Return them all separate with returns

    return join("<br>\n",$urls);

  }

}

?>