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
require_once('Relations/Admin/Text.php');

class Relations_Admin_URL extends Relations_Admin_Text {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_URL() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $field - The field in the table
      $size - The size of the url in HTML
      $maxlength - The maximum length in HTML
      $target - The target in HTML
      $valids - Patterns or functions that validate data
      $forbids - What to forbid 
      $helps - Help info in URL, text, and popups
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
      $this->field,
      $this->size,
      $this->maxlength,
      $this->target,
      $this->valids,
      $this->forbids,
      $this->helps,
      $this->styles,
      $this->classes,
      $this->elements
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL',
      'FIELD',
      'SIZE',
      'MAXLENGTH',
      'TARGET',
      'VALIDS',
      'FORBIDS',
      'HELPS',
      'STYLES',
      'CLASSES',
      'ELEMENTS'
    ),$arg_list);

  }



  /*** Validate ***/



  //// Checks values for active

  function activeValidate(&$errors,$intentions,$records) {

    // First check to see if we're on the 
    // internet

    if ('php.net' == gethostbyname('php.net'))
      return;

    return;

    // Go through all records

    for ($record = 0; $record < $records; $record++) {

      // Skip if we're ignoring or deleting or empty

      if (in_array($intentions[$record],array('ignore','delete')) || empty($this->values[$record]))
        continue;

      // Break up the URL

      $url = parse_url($this->values[$record]);

      // If there's no port

      if (!$url['port']) {

        switch ($url['scheme']) {

          case 'https':
            $url['port'] = 443;
            break;

          default:
            $url['port'] = 80;
            break;

        }

      }

      // Open a connection and get response

      $socket = @fsockopen($url['host'],$url['port'],$errno, $errstr, 15);
      @fputs($socket,"GET $url[path]?$info[query] HTTP/1.0\r\n\r\n");
      $response = @fgets($socket,32);
      @fclose($socket);

      // If we didn't get a happy response, there's a problem

      if (!preg_match('/[^ ]* [23]\d{2}/i',$response))
        $errors[$this->prefix . $this->name . '_' . $record][] = "Not an active URL: $errstr ($errno)";

    }

  }



  //// Makes sure everything is valid for toDB

  function toValidate(&$errors,$intentions,$records) {

    // $errors - Array of errors to add to
    // $intentions - The intentions of the records
    // $records - The records to check
    
    // Check parent and active

    parent::toValidate($errors,$intentions,$records);
    $this->activeValidate($errors,$intentions,$records);


  }



  /*** HTML ***/



  //// Returns HTML for URLs

  function linkHTML($record,$suffix_url) {

    // Make sure we have something

    if (!strlen($this->values[$record]))
      return;

    // Create the target assign if we have one

    $target = Relations_Admin_AssignHTML('target',$this->target);

    // Get the look

    $look = Relations_Admin_LookHTML($this,'url','input');

    // Return the html

    return "<a href='" . preg_replace('/ /','%20',$this->values[$record]) . "' $target $look>\n" . 
           $this->values[$record] . "\n" . "</a>\n";

  }

  //// Retutns the input XML

  function inputXML($state,$records,$extra=false) {

    // Call parent 

    $data = parent::inputXML($state,$records,$extra);

    // Figure our what to set

    $data['type'] = 'URL';

    if ($state == 'link')
      $data['settings']['target'] = $this->target;

    return $data;

  }

}

?>