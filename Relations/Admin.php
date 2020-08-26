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

/*

  PURPOSE

  This package classes for administering relational MySQL databases. 
  It has functionality for tables and all their diffent field types. 
  This inlcludes set and enum fields, fields that stored ids from other 
  tables. Even Many to many relationships between tables. If also look 
  up records that are3 related between tables, such a parent-child 
  relationship, or even a more complicated relationship between multiple 
  tables.

  HOW IT WORKS

  On average, each table should have a php page with a Form object and 
  several Input objects. Usually, there's an Input for each field in 
  the table. But more can be added, like those for many to many 
  relationships, or some deeper relationship, which is specified by a 
  query.

*/



//// Cleans GPC escaped data

function Relations_Admin_cleanGPC($value) {

  // $value - The value(s) to clean

  // If GPC's not set, take off

  if (!ini_get('magic_quotes_gpc'))
    return $value;

  // If it's an array 
  
  if (is_array($value)) {

    // Create a new array

    $clean = array();

    foreach ($value as $dirty)
      $clean[] = stripslashes($dirty);

    return $clean;

  // If it's not an array

  } else {

    // Return the cleaned string

    return stripslashes($value);

  }

}



//// Cleans runtime escaped data

function Relations_Admin_cleanSQL($value) {

  // $value - The value to clean

  // If runtime's not set, take off

  if (!ini_get('magic_quotes_runtime'))
    return $value;

  // Return the cleaned string

  return stripslashes($value);

}



//// Saves a parameter value by name into session vars

function Relations_Admin_store($param,$value=null,$prefix="_RELATIONS_ADMIN_") {

  // $param - The name of the param
  // $value - The value to save
  // $prefix - The prefix to use with the sessions

  // Make sure we have a session

  session_start();

  // If the value's not set, clear it 
  // so we don't have tons of nulls in
  // the sessions file

  if (!isset($value)) {

    Relations_Admin_clear($param,$prefix);
    return;

  }

  // If register globals is on, funky stuff can happen
  // to the arrays. Best to set the globals.

  if (ini_get('register_globals')) {

    // Make sure the value's registered

    session_register("$prefix$param");

    global ${"$prefix$param"};
    ${"$prefix$param"} = $value;

  // Else use the regular arrays

  } else {

    // Globalize the old array

    global $HTTP_SESSION_VARS;

    // Save the value using both old and new arrays

    $HTTP_SESSION_VARS["$prefix$param"] = $value;
    $_SESSION["$prefix$param"] = $value;

  }

}



//// Loads a parameter value by name into session vars

function Relations_Admin_retrieve($param,$prefix="_RELATIONS_ADMIN_") {

  // $param - The name of the param
  // $prefix - The prefix to use with the sessions

  // Make sure we have a session

  session_start();

  // Initialize the return value

  $return = null;
  
  // If register globals is on, funky stuff can happen
  // to the arrays. Best to get the globals.

  if (ini_get('register_globals')) {

    // Make sure the value's registered

    session_register("$prefix$param");

    global ${"$prefix$param"};

    if (isset(${"$prefix$param"}))
      $return = ${"$prefix$param"};

  // Else use the regular arrays

  } else {

    // Globalize the old array

    global $HTTP_SESSION_VARS;

    // Load up the session value from both

    if (isset($HTTP_SESSION_VARS["$prefix$param"])) 
      $return = $HTTP_SESSION_VARS["$prefix$param"];

    if (isset($_SESSION["$prefix$param"])) 
      $return = $_SESSION["$prefix$param"];

  }

  // Send it back

  return $return;

}



//// Clears a parameter value by name from session vars

function Relations_Admin_clear($param,$prefix="_RELATIONS_ADMIN_") {

  // $param - The name of the param
  // $prefix - The prefix to use with the sessions

  // Make sure we have a session

  session_start();

  // If register globals is on, funky stuff can happen
  // to the arrays. Best to get the globals.

  if (ini_get('register_globals') && session_is_registered("$prefix$param")) {

    global ${"$prefix$param"};

    unset(${"$prefix$param"});

    // Make sure the value's unregistered

    session_unregister("$prefix$param");

  // Else use the regular arrays

  } else {

    // Globalize the old array

    global $HTTP_SESSION_VARS;

    // Load up the session value from both

    unset($HTTP_SESSION_VARS["$prefix$param"]);

    unset($_SESSION["$prefix$param"]);

  }

}



//// Clears all parameter value by name from session vars

function Relations_Admin_clean($depth=0,$prefix="_RELATIONS_ADMIN_") {

  // $depth - The starting depth to clear things
  // $prefix - The prefix to use with the sessions

  // Make sure we have a session

  session_start();

  // Globalize the old array

  global $HTTP_SESSION_VARS;

  // Figure out which array of values to search

  $search = array();

  // If register globals is on, funky stuff can happen
  // to the arrays. Best to get the globals. I don't 
  // know if this works. 

  if (ini_get('register_globals')) {

    // Add all that match this prefix to search

    $search = array_merge($search,array_values(preg_grep("/^$prefix/",get_defined_vars())));
      
  // Else use the regular arrays

  } else {

    // If there's values at this depth
    // keep searching

    $search = array_merge($search,array_values(preg_grep("/^$prefix/",array_keys($HTTP_SESSION_VARS))));
    $search = array_merge($search,array_values(preg_grep("/^$prefix/",array_keys($_SESSION))));
      
  }

  // Search for only unqiues

  $search = array_unique($search);

  // If we have a depth

  if ($depth) {

    // Now go through all the depths
    // from this one on

    $current = $depth;
    $deeper = true;
    $params = array();

    // While we still need to go
    // deeper

    while ($deeper)
      if (count(preg_grep("/^$prefix${current}_/",$search)))
        $params = array_merge($params,preg_replace("/^$prefix/",'',array_values(preg_grep("/^$prefix" . ($current++) . "/",$search))));
      else
        $deeper = false;

  } else {

    // Clear everything Relations related

    $params = preg_replace("/^$prefix/",'',$search);

  }

  // Go through them all and clear each one

  foreach ($params as $param)
    Relations_Admin_clear($param,$prefix);

}



//// Saves info to send to who we're going

function Relations_Admin_redirect($info,$prefix="_RELATIONS_ADMIN_") {

  // Figure out the depth

  if (!strlen($info['values']['depth']))
    return $info['url'];
  else
    $depth = $info['values']['depth'];

  // Set the URL

  $url = "$info[url]?depth=$depth";

  // If we have values

  if (is_array($info['values'])) {

    // Go through the values and send 
    // them, except for depth, which
    // is set in url. Make sure the
    // settings are absorbed.

    foreach ($info['values'] as $name=>$value)
      if ($name != 'depth')
        Relations_Admin_store($name,$value,$prefix . $depth . '_DEFAULTS_');

  }

  // Return the URL

  return $url;

}



//// Grabs a parameter value by name. Checks GET, POST, and SESSION
//// variables and return the value in that order.

function Relations_Admin_grab($param,$value=null,$order='VGPFS',$prefix="_RELATIONS_ADMIN_") {

  // $param - The name of the param
  // $value - The value to use if nothing's set
  // $order - The order of the sources
  // $prefix - The prefix to use with the sessions

  // Initialize the return value

  $return = null;
  
  // Globalize the old arrays

  global $HTTP_GET_VARS;
  global $HTTP_POST_VARS;
  global $HTTP_POST_FILES;

  // Go through the order of values

  for ($place = 0; $place < strlen($order); $place++) {
    
    switch (substr($order,$place,1)) {

      // If sent value

      case 'V':

        // Grab the value sent.

        if (isset($value)) 
          $return = $value;

        break;

      // If Get 

      case 'G':

        // Grab the GET value. Try the old
        // style first. Then try the new.

        if (isset($HTTP_GET_VARS[$param])) 
          $return = Relations_Admin_cleanGPC($HTTP_GET_VARS[$param]);

        if (isset($_GET[$param])) 
          $return = Relations_Admin_cleanGPC($_GET[$param]);

        break;

      // If Post 

      case 'P':

        // Grab the POST value. Try the old
        // style first. Then try the new.

        if (isset($HTTP_POST_VARS[$param])) 
          $return = Relations_Admin_cleanGPC($HTTP_POST_VARS[$param]);

        if (isset($_POST[$param])) 
          $return = Relations_Admin_cleanGPC($_POST[$param]);

        break;

      // If Post Files

      case 'F':

        // Grab the POST value. Try the old
        // style first. Then try the new.

        if (isset($HTTP_POST_FILES[$param])) 
          $return = $HTTP_POST_FILES[$param];

        if (isset($_FILES[$param])) 
          $return = $_FILES[$param];

        break;

      // If Session

      case 'S':

        // Use the load routine 

        $load = Relations_Admin_retrieve($param,$prefix);
        
        if (isset($load)) 
          $return = $load;

        break;

    }

  }

  // Send it back

  return $return;

}



//// Grabs a parameter value by name for default

function Relations_Admin_default($depth,$param,$value=null,$order='VSFPG',$prefix="_RELATIONS_ADMIN_") {

  // $param - The name of the param
  // $depth - The depth to look at 
  // $value - The value to use if nothing's set
  // $order - The order of the sources
  // $prefix - The prefix to use with the sessions

  // Just use grab

  return Relations_Admin_grab($param,$value,$order,$prefix . $depth . '_DEFAULTS_');

}



//// Grabs a parameter value, and then clears it

function Relations_Admin_steal($param,$value=null,$order='VGPFS',$prefix="_RELATIONS_ADMIN_") {

  // $param - The name of the param
  // $value - The value to use if nothing's set
  // $order - The order of the sources
  // $prefix - The prefix to use with the sessions

  // Get the return value

  $return = Relations_Admin_grab($param,$value,$order,$prefix);
  Relations_Admin_clear($param,$prefix);
  
  // Send it back

  return $return;

}



//// Checks to see if value is within main

function Relations_Admin_within($directory,$value) {

  // $directory - The directory
  // $value - The value to check

  // Clean 'em

  $directory = preg_replace('/\\/+|\\\\+/','/',$directory);
  $directory = preg_replace('/\\/$/','',$directory);
  $realpath = preg_replace('/\\/+|\\\\+/','/',realpath($directory . '/' . dirname($value)));

  if ($directory == substr($realpath,0,strlen($directory)))
    return true;

  return false;

}



//// Returns value's relative location

function Relations_Admin_relative($directory,$value) {

  // $directory - The directory
  // $value - The value to convert

  // Clean 'em

  $directory = preg_replace('/\\/+|\\\\+/','/',$directory);
  $directory = preg_replace('/\\/$/','',$directory);
  $realpath = preg_replace('/\\/+|\\\\+/','/',realpath($directory . '/' . dirname($value)));

  return substr($realpath . '/' . basename($value),strlen($directory)+1);

}



//// Checks if a value is within local

function Relations_Admin_localed($local,$value) {

  // $local - The local directory
  // $value - The value to convert

  // Clean 'em

  $local = preg_replace('/\\/+|\\\\+/','/',$local);
  $local = preg_replace('/\\/$/','',$local);
  $realpath = preg_replace('/\\/+|\\\\+/','/',realpath($local . '/' . $value));

  if (($local == dirname($realpath)) && is_file($local . '/' . $value))
    return true;

  return false;

}



///// Checks whether a value is should be filtered

function Relations_Admin_filtered($filters,$value) {

  // $filters - The filters array
  // $value - The value to convert

  foreach ($filters as $filter) 
    if (preg_match($filter,$value))
      return true;

  return false;

}



//// Takes a search pattern and makes it regular 
//// expression friendly for files

function Relations_Admin_regulate($filter) {

  // $filter - The filter expression

  return str_replace(array("%","\\","/"),array(".*","/","\\/"),$filter);

}



//// Copies a directory in its entirety

function Relations_Admin_branch($from,$to) {

  // $from - The directory to copy from
  // $to - The directory to copy to

  // Return if it already exists

  if (file_exists($to))
    return;

  // Create the destination

  mkdir($to,0770);

  // Open copy from

  $dh = opendir($from);

  // Go through all the entries

  while ($entry = readdir($dh)) {

    // Skip the . and ..

    if (preg_match('/^\.\.?$/',$entry))
      continue;

    // If it's a directory, call us again,
    // else just copy the file

    if (is_dir("$from/$entry"))
      Relations_Admin_branch("$from/$entry","$to/$entry");
    else
      copy("$from/$entry","$to/$entry");

  }

  closedir($dh);

}



//// Gets all the files in a directory

function Relations_Admin_scale($absolute,$relative='') {

  // $tree - The tree info when found
  // $location - The directory to climb
  // $relative - The directory we're in

  // Make sure relative is just that

  $relative = Relations_Admin_relative($absolute,$relative);

  // Return if it doesn't exist

  if (!is_dir("$absolute/$relative"))
    return;

  // Initialize the current files and 
  // directories

  $files = array();

  // Open climb

  $dh = opendir("$absolute/$relative");

  // Go through all the entries

  while ($entry = readdir($dh)) {

    // Skip the . and ..

    if (preg_match('/^\.\.?$/',$entry))
      continue;

    // If it's a directory add the info and 
    // save to call us again. If it's a file, 
    // add the info. 

    if (is_file("$absolute/$relative/$entry"))
      $files[] = $entry;

  }

  closedir($dh);

  // Sort all the files

  sort($files);

  // Return the files

  return $files;

}



//// Gets all the directories in a directory

function Relations_Admin_delve($absolute,$relative='') {

  // $tree - The tree info when found
  // $absolute - The directory to climb
  // $relative - The directory we're in

  // Make sure relative is just that

  $relative = Relations_Admin_relative($absolute,$relative);

  // Return if it doesn't exist

  if (!is_dir("$absolute/$relative"))
    return;

  // Initialize the current files and 
  // directories

  $directories = array();

  // Open climb

  $dh = opendir("$absolute/$relative");

  // Go through all the entries

  while ($entry = readdir($dh)) {

    // Skip the . and ..

    if (preg_match('/^\.\.?$/',$entry))
      continue;

    // If it's a directory add the info and 
    // save to call us again. If it's a file, 
    // add the info. 

    if (is_dir("$absolute/$relative/$entry"))
      $directories[] = $entry;

  }

  closedir($dh);

  // Sort all the directories 

  sort($directories);

  // Return the files

  return $directories;

}



function Relations_Admin_climb(&$tree,$absolute,$relative='') {

  // $tree - The tree info when found
  // $absolute - The directory to climb
  // $relative - The directory we're in

  // Make sure relative is just that

  $relative = Relations_Admin_relative($absolute,$relative);

  // Return if it doesn't exist

  if (!is_dir("$absolute/$relative"))
    return;

  // Initialize the current files and 
  // directories

  $files = array();
  $directories = array();

  // Open climb

  $dh = opendir("$absolute/$relative");

  // Go through all the entries

  while ($entry = readdir($dh)) {

    // Skip the . and ..

    if (preg_match('/^\.\.?$/',$entry))
      continue;

    // If it's a directory add the info and 
    // save to call us again. If it's a file, 
    // add the info. 

    if (is_dir("$absolute/$relative/$entry"))
      $directories[] = $entry;
    else
      $files[] = $entry;

  }

  closedir($dh);

  // Sort all the directories and files

  sort($directories);
  sort($files);

  // Add a / to relative for path if needed

  if (strlen($relative))
    $path = $relative . '/';
  else
    $path ='';

  // Go through all the directories and add them

  foreach ($directories as $directory)
    $tree[] = array('type' => 'directory', 'path' => $path, 'name' => $directory);

  // Go through all the files and add them

  foreach ($files as $file)
    $tree[] = array('type' => 'file', 'path' => $path, 'name' => $file);

  // Go through all the directories and call

  foreach ($directories as $directory)
    Relations_Admin_climb($tree,$absolute,"$relative/$directory");

}



//// Removes a directory in its entirety

function Relations_Admin_prune($branch) {

  // $branch - The directory to remove

  // Return if it doesn't exist

  if (!is_dir($branch))
    return;

  // Open branch from

  $dh = opendir($branch);

  // Go through all the entries

  while ($entry = readdir($dh)) {

    // Skip the . and ..

    if (preg_match('/^\.\.?$/',$entry))
      continue;

    // If it's a directory, call us again,
    // else just copy the file

    if (is_dir("$branch/$entry"))
      Relations_Admin_prune("$branch/$entry");
    else
      unlink("$branch/$entry");

  }

  closedir($dh);

  // Remove the destination

  rmdir($branch);

}



//// Takes an array of Relations-Admin erros and 
//// translates them to a single string to inform the 
//// user of what happened along with an array
//// for each input with a problem.

function Relations_Admin_advise($errors) {

  // Declare some arrays. The advice array
  // is what we're sending back, and it's
  // numbers array stores the the message 
  // numbers associated with that input's 
  // record. The uniques stores all the 
  // uniques messages.
  
  $advice = array();
  $advice['uniques'] = array();
  $advice['numbers'] = array();

  // Go through all the errors store all
  // the unique messages

  foreach ($errors as $brand=>$messages) {

    foreach ($messages as $message) {

      $advice['uniques'][$message] = 0;

    }

  }

  // Sort the array based off the messages

  ksort($advice['uniques']);

  // Go back through the array, setting 
  // the proper number

  $number = 0; 
  foreach ($advice['uniques'] as $message=>$zero) {

    $number++;
    $advice['uniques'][$message] = $number;

  }

  // Go through the errors array again pushing
  // the message numbers onto the input name's
  // array.

  foreach ($errors as $brand=>$messages) {

    foreach ($messages as $message) {

      $advice['numbers'][$brand][] = $advice['uniques'][$message];

    }

    sort($advice['numbers'][$brand]);

  }

  // Create a message advising people what's 
  // wrong. 

  $advice['message'] = "The following errors were encounted:<br>";

  foreach ($advice['uniques'] as $message=>$number)
    $advice['message'] .= "$number. $message<br>";

  // Return the advice

  return $advice;

}

//// Takes an array of Relations-Admin events and 
//// translates them to a single string to inform the 
//// user of what happened.

function Relations_Admin_inform($totals) {

  // $totals - Array to translate
  // $totals[label][task][] = ids (blank for ignore)

  // If its not an array, take off

  if (!is_array($totals))
    return '';

  $info = array();
  Relations_Admin_info($info,$totals);
  return Relations_Admin_infoHTML($info);

}



//// Takes an array of Relations-Admin events and 
//// translates them to a data structure

function Relations_Admin_info(&$info,$totals) {

  // $inform - The current inform
  // $totals - Array to translate
  // $totals[label][task][] = ids (blank for ignore)

  // If its not an array, take off

  if (!is_array($totals))
    return;

  // If inform isn't an array, make it so

  if (!is_array($inform))
    $v = array();

  // Initialize the message and tallys

  // Go through all labels and tasks, and
  // save the ids count

  foreach ($totals as $label=>$actions)
    foreach ($actions as $task=>$ids)
      if (is_array($ids))
        $info[$task][$label] += count($ids);
      else
        $info[$task][$label] += $ids;

}



//// Takes a structure of Relations-Admin events and 
//// translates them to a single string to inform the 
//// user of what happened.

function Relations_Admin_infoHTML($info) {

  // If its not an array, take off

  if (!is_array($info))
    return '';

  // Initialize the message and tallys

  $message = '';

  // Sort tallys alphabetically

  ksort($info);

  // Go through all the sorted tallys

  foreach ($info as $task=>$tally) {

    // Sort the tally

    ksort($tally);

    // Keep track of what's to be added

    $tallied = array();

    // Loop the tally

    foreach ($tally as $label=>$count)
      $tallied[] = "$label($count)";

    // Add to message

    $message .= '<i>' . ucfirst("$task:") . '</i> ' . join (' ',$tallied) . "<br>";

  }

  // Send back the message

  return $message;

}



//// Returns an element tag if needed

function Relations_Admin_ElementHTML($element,$value) {

  if ($value)
    return "$element ";
  else
    return '';

}



//// Returns an assign tag if if needed

function Relations_Admin_AssignHTML($assign,$value) {

  if (strlen($value))
    return "$assign='" . htmlentities($value, ENT_QUOTES) . "' ";
  else
    return '';

}



//// Checks style and classes arrays and sees if the 
//// style or class is set. Returns the class 
//// relations_admin_$name class, overridden by the 
//// relations_admin_$default class, overridden by 
//// the array class, overridden by the style, 
//// overridden by elements

function Relations_Admin_LookHTML(&$object,$name,$default='') {

  // $object - The object to use
  // $name - The name of the info to look for
  // $default - The default class to use if not found

  // Return what you got

  if (in_array($name,array_keys($object->elements)))
    return $object->elements[$name];
  elseif (in_array($name,array_keys($object->styles)))
    return "style='" . $object->styles[$name] . "'";
  elseif (in_array($name,array_keys($object->classes)))
    return "class='" . $object->classes[$name] . "'";
  elseif (strlen($default))
    return "class='ra_$default'";
  else
    return "class='ra_$name'";

}



//// Returns message html if needed

function Relations_Admin_MessageHTML(&$object,$value,$kind='message') {

  // Get this input's look for messages 

  $look = Relations_Admin_LookHTML($object,$kind);

  // Return the message if set

  if (strlen($value))
    return "<span $look>" . nl2br($value) . "</span><br>\n";
  else
    return '';

}



//// Returns the help HTML if needed

function Relations_Admin_HelpHTML(&$object,$name='') {

  // Set the values (for future enhancement)

  if ($name) {

    $text = $object->helps[$name]['text'];
    $url = $object->helps[$name]['url'];

  } else {

    $text = $object->helps['text'];
    $url = $object->helps['url'];

  }

  // Get the help message 

  $html = Relations_Admin_MessageHTML($object,$text,'help');

  // See if we need a URL

  if (strlen($url)) {

    // Get the look

    $look = Relations_Admin_LookHTML($object,'help');

    // Add the URL

    $html .= "<a href='$url' $look>Help</a><br>";

  }

  // Return the full help

  return $html;

}



//// Get the tip info for the HTML function

function Relations_Admin_TipData(&$object,$name) {

  // $object - The object to use
  // $name - The name of the info to look for

  // Return what you got

  if ((in_array('tip',array_keys($object->helps))) &&
      (in_array($name,array_keys($object->helps['tip']))))
    return $object->helps['tip'][$name];
  else
    return '';

}



//// Checks the title array in an objects helps array.
//// If info is found, creates a title elements for
//// HTML tags and returns it. Else returns nothing.

function Relations_Admin_TipHTML(&$object,$name) {

  // $object - The object to use
  // $name - The name of the info to look for

  // Return what you got

  $tip_data = Relations_Admin_TipData($object,$name);

  if ($tip_data)
    return "title='" . htmlentities($tip_data) . "' ";
  else
    return '';

}



//// Returns the value HTML if needed

function Relations_Admin_ValueHTML(&$object,$value,$prefix='',$pure=false) {

  // Get the look

  $look = Relations_Admin_LookHTML($object,$prefix . 'value','input');

  // Return the value if set

  if (strlen($value))
    return "<span $look>" . nl2br($value) . "</span>\n";
  else
    return '';

}



//// Returns the values HTML if needed

function Relations_Admin_ValuesHTML(&$object,$values,$labels,$prefix='') {

  // Get the look

  $look = Relations_Admin_LookHTML($object,$prefix . 'value','input');

  // If there's no values, return nothing

  if (!is_array($values) || !count($values))
    return '';

  // Start the HTML

  $html = "<span $look>";

  // Add each

  foreach ($values as $value)
    $html .= nl2br($labels[$value]) . "<br>";

  // End the HTML

  $html .= "</span>\n";

  // Send back the html

  return $html;

}



//// Returns a url

function Relations_Admin_URLHTML(&$object,$url,$label,$target='',$prefix='') {

  // Get the target

  $target = Relations_Admin_AssignHTML('target',$target);

  // Get the look

  $look = Relations_Admin_LookHTML($object,$prefix . 'url','input');

  // Send back the html

  return "<a href='$url' $target$look>" . nl2br($label) . "</a>\n";

}



//// Returns an image

function Relations_Admin_ImageHTML(&$object,$src,$width='',$height='',$alt='',$prefix='') {

  // Get the target

  $width = Relations_Admin_AssignHTML('width',$width);
  $height = Relations_Admin_AssignHTML('height',$height);
  $alt = Relations_Admin_AssignHTML('alt',$alt);

  // Get the look

  $look = Relations_Admin_LookHTML($object,$prefix . 'img','input');

  // Send back the html

  return "<img src='$src' $width$height$alt$look>\n";

}



//// Returns a JS clickable button

function Relations_Admin_ButtonHTML(&$object,$name,$value,$clicked,$prefix='') {

  // Get the action, tip, and look

  $clicked = Relations_Admin_AssignHTML('OnClick',$clicked);
  $tip = Relations_Admin_TipHTML($object,$prefix . 'button');
  $look = Relations_Admin_LookHTML($object,$prefix . 'button','button');

  // Clean up the value

  $value = htmlentities($value, ENT_QUOTES);

  // Send back the html

  return "<input type='button' name='$name' value='$value' $clicked$tip$look>\n";

}



//// Returns XML control based on a button

function Relations_Admin_ButtonXML(&$object,$name,$value,$script,$arguments=array(),$prefix='',$help='') {

  // Get the action, tip, and look

  if (!$help)
    $help = Relations_Admin_TipData($object,$prefix . 'button');
  $value = ucfirst($value);

  $xml = '';

  $xml .= "<control name='$name' label='$value' script='$script'>\n";
  if ($help)
    $xml .= "<help><![CDATA[$help]]></help>\n";
  foreach ($arguments as $argument)
    $xml .= "<argument><![CDATA[$argument]]></argument>\n";
  $xml .= "</control>\n";

  // Send back the html

  return $xml;

}



//// Returns a submit button

function Relations_Admin_SubmitHTML(&$object,$name,$value,$prefix='') {

  // Get the tip and look

  $tip = Relations_Admin_TipHTML($object,$prefix . 'submit');
  $look = Relations_Admin_LookHTML($object,$prefix . 'submit','submit');

  // Clean up the value

  $value = htmlentities($value, ENT_QUOTES);

  // Send back the html

  return "<input type='submit' name='$name' value='$value' $tip$look>\n";

}



//// Returns a hidden field

function Relations_Admin_HiddenHTML(&$object,$name,$value,$prefix='') {

  // Get the look

  $look = Relations_Admin_LookHTML($object,$prefix . 'hidden','hidden');

  // Clean up the value

  $value = htmlentities($value, ENT_QUOTES);

  // Send back the html

  return "<input type='hidden' name='$name' value='$value' $look>\n";

}



//// Returns a radio field

function Relations_Admin_RadioHTML(&$object,$name,$value,$label,$checked,$prefix='',$changed='') {

  // Get the whether it's selected, change JS,
  // it's tip, and look

  $checked = Relations_Admin_ElementHTML('checked',$checked);
  $changed = Relations_Admin_AssignHTML('OnClick',$changed);
  $tip = Relations_Admin_TipHTML($object,$prefix . 'radio');
  $look = Relations_Admin_LookHTML($object,$prefix . 'radio','radio');

  // Clean up the value and label

  $value = htmlentities($value, ENT_QUOTES);
  $label = nl2br($label);

  // Send back the html

  return "<input type='radio' name='$name' value='$value' $checked$changed$tip$look> $label\n";

}



//// Returns a checkbox field

function Relations_Admin_CheckboxHTML(&$object,$name,$value,$label,$checked,$prefix='',$changed='') {

  // Get the whether it's selected, change JS,
  // it's tip, and look

  $checked = Relations_Admin_ElementHTML('checked',$checked);
  $changed = Relations_Admin_AssignHTML('OnClick',$changed);
  $tip = Relations_Admin_TipHTML($object,$prefix . 'checkbox');
  $look = Relations_Admin_LookHTML($object,$prefix . 'checkbox','checkbox');

  // Clean up the value and label

  $value = htmlentities($value, ENT_QUOTES);
  $label = nl2br($label);

  // Send back the html

  return "<input type='checkbox' name='$name' value='$value' $checked$changed$tip$look> $label\n";

}



//// Returns a text field

function Relations_Admin_TextHTML(&$object,$name,$value,$size='',$maxlength='',$prefix='',$changed='') {

  // Get its sizes, change JS, tip, and look

  $size = Relations_Admin_AssignHTML('size',$size);
  $maxlength = Relations_Admin_AssignHTML('maxlength',$maxlength);
  $changed = Relations_Admin_AssignHTML('OnKeyPress',$changed);
  $tip = Relations_Admin_TipHTML($object,$prefix . 'text');
  $look = Relations_Admin_LookHTML($object,$prefix . 'text','text');

  // Clean up the value

  $value = htmlentities($value, ENT_QUOTES);

  // Send back the html

  return "<input type='text' name='$name' value='$value' $size$maxlength$changed$tip$look>\n";

}



//// Returns a textarea field

function Relations_Admin_TextAreaHTML(&$object,$name,$value,$rows,$cols,$wrap,$prefix='',$changed='') {

  // Get its sizes, wrap, change JS, tip, and look

  $rows = Relations_Admin_AssignHTML('rows',$rows);
  $cols = Relations_Admin_AssignHTML('cols',$cols);
  $wrap = Relations_Admin_AssignHTML('wrap',$wrap);
  $changed = Relations_Admin_AssignHTML('OnKeyPress',$changed);
  $tip = Relations_Admin_TipHTML($object,$prefix . 'textarea');
  $look = Relations_Admin_LookHTML($object,$prefix . 'textarea','textarea');

  // Clean up the value

  $value = htmlentities($value, ENT_QUOTES);

  // Send back the html

  return "<textarea name='$name' $rows$cols$wrap$changed$tip$look>$value</textarea>\n";

}



//// Returns a textarea field

function Relations_Admin_HTMLAreaHTML(&$object,$name,$value,$wrap,$prefix='',$changed='') {

  // Get its sizes, wrap, change JS, tip, and look

  $wrap = Relations_Admin_AssignHTML('wrap',$wrap);
  $changed = Relations_Admin_AssignHTML('OnKeyPress',$changed);
  $tip = Relations_Admin_TipHTML($object,$prefix . 'textarea');
  $look = Relations_Admin_LookHTML($object,$prefix . 'textarea','textarea');

  // Clean up the value

  $value = htmlentities($value, ENT_QUOTES);

  // Send back the html

  return "<textarea name='$name' id='$name' $wrap$changed$tip$look>$value</textarea>\n";

}



//// Returns a date field

function Relations_Admin_DateHTML(&$object,$name,$value,$search=false,$prefix='',$changed='') {

  // Split up the value 

  list($year,$month,$day) = explode('-',$value);

  // Create months

  $months = array();

  // Figure out if we're searching

  if ($search)
    $months['%'] = 'Any';
  else
    $months['00'] = '   ';


  // Create a list of months

  $months['01'] = 'Jan';
  $months['02'] = 'Feb';
  $months['03'] = 'Mar';
  $months['04'] = 'Apr';
  $months['05'] = 'May';
  $months['06'] = 'Jun';
  $months['07'] = 'Jul';
  $months['08'] = 'Aug';
  $months['09'] = 'Sep';
  $months['10'] = 'Oct';
  $months['11'] = 'Nov';
  $months['12'] = 'Dec';

  // Get its sizes, wrap, change JS, tip, and look

  $look = Relations_Admin_LookHTML($object,$prefix . 'span');

  // Start the html

  $html = "<span $look>\n";

  // Add the pieces

  $html .= "Day " . Relations_Admin_TextHTML($object,$name . '_day',$day,2,2,$prefix . 'day_',$changed);
  $html .= "Month " . Relations_Admin_SelectHTML($object,$name . '_month',array_keys($months),$months,$month,1,$prefix . 'month_',$changed);
  $html .= "Year " . Relations_Admin_TextHTML($object,$name . '_year',$year,4,4,$prefix . 'year_',$changed);

  // End the html

  $html .= "</span>\n";

  // Send back the html

  return $html;

}



//// Returns a time field

function Relations_Admin_TimeHTML(&$object,$name,$value,$search=false,$prefix='',$changed='') {

  // Split up the value 

  list($hour,$minute) = explode(':',$value);

  // Create hours

  $hours = array();

  // Figure out if we're searching

  if ($search)
    $hours['%'] = 'Any';

  // Create a list of hours

  $hours['00'] = '12AM';
  $hours['01'] = '1AM';
  $hours['02'] = '2AM';
  $hours['03'] = '3AM';
  $hours['04'] = '4AM';
  $hours['05'] = '5AM';
  $hours['06'] = '6AM';
  $hours['07'] = '7AM';
  $hours['08'] = '8AM';
  $hours['09'] = '9AM';
  $hours["10"] = '10AM';
  $hours['11'] = '11AM';
  $hours['12'] = '12PM';
  $hours['13'] = '1PM';
  $hours['14'] = '2PM';
  $hours['15'] = '3PM';
  $hours['16'] = '4PM';
  $hours['17'] = '5PM';
  $hours['18'] = '6PM';
  $hours['19'] = '7PM';
  $hours['20'] = '8PM';
  $hours['21'] = '9PM';
  $hours['22'] = '10PM';
  $hours['23'] = '11PM';

  // Get its sizes, wrap, change JS, tip, and look

  $look = Relations_Admin_LookHTML($object,$prefix . 'span');

  // Start the html

  $html = "<span $look>\n";

  // Add the pieces

  $html .= "Hour " . Relations_Admin_SelectHTML($object,$name . '_hour',array_keys($hours),$hours,$hour,1,$prefix . 'hour_',$changed);
  $html .= "Min " . Relations_Admin_TextHTML($object,$name . '_minute',$minute,2,2,$prefix . 'minute_',$changed);

  // End the html

  $html .= "</span>\n";

  // Send back the html

  return $html;

}



//// Returns a datetime field

function Relations_Admin_DateTimeHTML(&$object,$name,$value,$search=false,$prefix='',$changed='') {

  // Split up the value 

  list($date,$time) = explode(' ',$value);

  // Send back the html

  return Relations_Admin_DateHTML($object,$name,$date,$search,$prefix,$changed) . 
         Relations_Admin_TimeHTML($object,$name,$time,$search,$prefix,$changed);

}



//// Returns a file upload field

function Relations_Admin_FileHTML(&$object,$name,$prefix='',$changed='') {

  // Get its change JS, tip, and look

  $changed = Relations_Admin_AssignHTML('OnClick',$changed);
  $tip = Relations_Admin_TipHTML($object,$prefix . 'file');
  $look = Relations_Admin_LookHTML($object,$prefix . 'file','file');

  // Send back the html

  return "<input type='file' name='$name' $changed$tip$look>\n";

}



//// Returns an option for selects

function Relations_Admin_OptionHTML(&$object,$value,$label,$selected,$prefix='') {

  // Get the whether it's selected, tip and look

  $selected = Relations_Admin_ElementHTML('selected',$selected);
  $tip = Relations_Admin_TipHTML($object,$prefix . 'option');
  $look = Relations_Admin_LookHTML($object,$prefix . 'option','option');

  // Clean up the value and label

  $value = htmlentities($value, ENT_QUOTES);
  $label = nl2br(htmlentities($label, ENT_QUOTES));

  // Send back the html

  return "<option value='$value' $selected$tip$look>$label</option>\n"; 

}



//// Returns a select field

function Relations_Admin_SelectHTML(&$object,$name,$ids,$labels,$value,$size='',$prefix='',$changed='') {

  // Get its size, change JS, tip, and look

  $size = Relations_Admin_AssignHTML('size',$size);
  $changed = Relations_Admin_AssignHTML('OnChange',$changed);
  $tip = Relations_Admin_TipHTML($object,$prefix . 'select');
  $look = Relations_Admin_LookHTML($object,$prefix . 'select','select');

  // Start the select

  $html = "<select name='$name' $size$changed$tip$look>\n";

  // Add all the options

  foreach ($ids as $id) 
    $html .= Relations_Admin_OptionHTML($object,$id,$labels[$id],($id == $value),$prefix);

  // Finish it off

  $html .= "</select>\n";

  // Send back the html

  return $html;

}



//// Returns a table of radios

function Relations_Admin_RadiosHTML(&$object,$name,$ids,$labels,$value='',$cols='3',$prefix='',$changed='') {

  // If there's no value, set it to the
  // first ids

  if (!strlen($value))
    $value = $ids[0];

  // Get looks

  $table_look = Relations_Admin_LookHTML($object,$prefix . 'table','input');
  $tr_look = Relations_Admin_LookHTML($object,$prefix . 'tr','input');
  $td_look = Relations_Admin_LookHTML($object,$prefix . 'td','input');

  // Start the table

  $html = "<table $table_look>\n";

  // Figure out the number of rows

  $rows = ceil(count($ids)/$cols);

  // Go through all the rows

  for ($row = 0; $row < $rows; $row++) {

    // Start the row

    $html .= "<tr $tr_look>\n";

    // Go through all the columns

    for ($col = 0; $col < $cols; $col++) {

      // Grab the value (easier)

      $id = $ids[$row + $rows * $col];

      // Data start

      $html .= "<td $td_look>\n";

      // Add the radio button or blank

      if (($row + $rows * $col) < count($ids))
        $html .= Relations_Admin_RadioHTML($object,$name,$id,$labels[$id],($id == $value),$prefix,$changed);
      else
        $html .= "&nbsp;\n";

      // Data end

      $html .= "</td>\n";

    }

    // End the row

    $html .= "</tr>\n";

  }

  // End the table

  $html .= "</table>\n";

  // Send back the html

  return $html;

}



//// Returns a choose input

function Relations_Admin_ChooseHTML(&$object,$name,$labels,$value,$choose,$prefix='',$changed='') {

  // If there's no label, its
  // not set

  if ($labels[$value])
    $label = $labels[$value];
  else
    $label = '(not set)';

  // If there's change code, add to choose

  if ($changed)
    $choose = "$changed;$choose";

  // Get the html

  $html = Relations_Admin_HiddenHTML($object,$name,$value,$prefix);
  $html .= Relations_Admin_ValueHTML($object,$label,$prefix) . "<br>\n";
  $html .= Relations_Admin_ButtonHTML($object,$name . '_choose','Choose',$choose,$prefix . 'choose_');

  // Send back the html

  return $html;

}



//// Returns a table of checkboxes

function Relations_Admin_CheckboxesHTML(&$object,$name,$ids,$labels,$values,$cols='3',$prefix='',$changed='') {

  // Get looks

  $table_look = Relations_Admin_LookHTML($object,$prefix . 'table','input');
  $tr_look = Relations_Admin_LookHTML($object,$prefix . 'tr','input');
  $td_look = Relations_Admin_LookHTML($object,$prefix . 'td','input');

  // Start the table

  $html = "<table $table_look>\n";

  // Figure out the number of rows

  $rows = ceil(count($ids)/$cols);

  // Go through all the rows

  for ($row = 0; $row < $rows; $row++) {

    // Start the row

    $html .= "<tr $tr_look>\n";

    // Go through all the columns

    for ($col = 0; $col < $cols; $col++) {

      // Grab the value (easier)

      $id = $ids[$row + $rows * $col];

      // Data start

      $html .= "<td $td_look>\n";

      // Add the checkbox or blank

      if (($row + $rows * $col) < count($ids))
        $html .= Relations_Admin_CheckboxHTML($object,$name,$id,$labels[$id],in_array($id,$values),$prefix,$changed);
      else
        $html .= "&nbsp;\n";

      // Data end

      $html .= "</td>\n";

    }

    // End the row

    $html .= "</tr>\n";

  }

  // End the table

  $html .= "</table>\n";

  // Send back the html

  return $html;

}



//// Returns a multiple select field

function Relations_Admin_MultiSelectHTML(&$object,$name,$ids,$labels,$values,$size='3',$prefix='',$changed='') {

  // Get its size, change JS, tip, and look

  $size = Relations_Admin_AssignHTML('size',$size);
  $changed = Relations_Admin_AssignHTML('OnClick',$changed);
  $tip = Relations_Admin_TipHTML($object,$prefix . 'select');
  $look = Relations_Admin_LookHTML($object,$prefix . 'select','select');

  // Start the select

  $html = "<select multiple name='${name}' $size$changed$tip$look>\n";

  // Add all the options

  foreach ($ids as $id) 
    $html .= Relations_Admin_OptionHTML($object,$id,$labels[$id],in_array($id,$values),$prefix);

  // Finish it off

  $html .= "</select>\n";

  // Send back the html

  return $html;

}



//// Returns dual select html

function Relations_Admin_DualSelectHTML(&$object,$name,$ids,$labels,$values,$size='3',$prefix='',$changed='') {

  // Create the select and deselect names and JS

  $available = $name . '_available';
  $selected = $name . '_selected';
  $select = 'select_group(' .
    "document.relations_admin_form.$available," .
    "document.relations_admin_form.$selected," .
    "document.relations_admin_form.$name)";
  $deselect = 'deselect_group(' .
    "document.relations_admin_form.$selected," .
    "document.relations_admin_form.$name)";

  // Get the html

  $html = Relations_Admin_HiddenHTML($object,$name,implode(',',$values),$prefix);
  $html .= Relations_Admin_MultiSelectHTML($object,$selected,$values,$labels,array(),$size,$prefix . 'selected_');
  $html .= Relations_Admin_ButtonHTML($object,$name . '_deselect','Deselect',"$deselect;$changed",$prefix . 'deselect_') . "<br>\n";
  $html .= Relations_Admin_SelectHTML($object,$available,$ids,$labels,'',1,$prefix . 'available_');
  $html .= Relations_Admin_ButtonHTML($object,$name . '_select','Select',"$select;$changed",$prefix . 'select_');

  // Send back the html

  return $html;

}



//// Returns chooses html

function Relations_Admin_ChoosesHTML(&$object,$name,$labels,$values,$choose,$size='3',$prefix='',$changed='') {

  // Create the chosen names and JS

  $chosen = $name . '_chosen';
  $deselect = 'deselect_group(' .
    "document.relations_admin_form.$chosen," .
    "document.relations_admin_form.$name)";

  // If there's change code, add to choose

  if ($changed)
    $choose = "$changed;$choose";

  // If there's change code, add to deselect

  if ($changed)
    $deselect = "$changed;$deselect";

  // Get the html

  $html = Relations_Admin_HiddenHTML($object,$name,implode(',',$values),$prefix);
  $html .= Relations_Admin_ButtonHTML($object,$name . '_choose','Choose',$choose,$prefix . 'choose_');

  // If we have values

  if (count($values)) {

  // Give the deselect info

    $html .= "<br>\n" . Relations_Admin_MultiSelectHTML($object,$chosen,$values,$labels,array(),$size,$prefix . 'chosen_');
    $html .= "<br>\n" . Relations_Admin_ButtonHTML($object,$name . '_deselect','Deselect',$deselect,$prefix . 'deselect_');

  }

  // Send back the html

  return $html;

}



//// Returns db clear script

function Relations_Admin_ClearChooseJS(&$functions) {

  // If it's already there, skip

  if ($functions["clear_choose"])
    return;

  // Set the script

  $functions["clear_choose"] = "function clear_choose(clearing) {\n";
  $functions["clear_choose"] .= "clearing.value = '';\n";
  $functions["clear_choose"] .= "document.relations_admin_form.submit();\n";
  $functions["clear_choose"] .= "}\n";

}

//// Returns group selection script

function Relations_Admin_SelectGroupJS(&$functions) {

  // If it's already there, skip

  if ($functions["select_group"])
    return;

  // Set the script

  $functions["select_group"] = "function select_group(available,selected,values) {\n";
  $functions["select_group"] .= "not_opt = available.selectedIndex;\n";
  $functions["select_group"] .= "for (sel_opt = 0; sel_opt < selected.length; sel_opt++) {\n";
  $functions["select_group"] .= "if (selected.options[sel_opt].value == available.options[not_opt].value) {\n";
  $functions["select_group"] .= "alert('This item is already selected');\n";
  $functions["select_group"] .= "return;\n";
  $functions["select_group"] .= "}\n";
  $functions["select_group"] .= "}\n";
  $functions["select_group"] .= "new_opt = new Option();\n";
  $functions["select_group"] .= "new_opt.value = available.options[not_opt].value;\n";
  $functions["select_group"] .= "new_opt.text = available.options[not_opt].text;\n";
  $functions["select_group"] .= "new_opt.selected = true;\n";
  $functions["select_group"] .= "selected.options[selected.length] = new_opt;\n";
  $functions["select_group"] .= "implode_group(selected,values);\n";
  $functions["select_group"] .= "}\n";

}



//// Returns group deselection script

function Relations_Admin_DeselectGroupJS(&$functions) {

  // If it's already there, skip

  if ($functions["deselect_group"])
    return;

  // Set the script

  $functions["deselect_group"] = "function deselect_group(selected,values) {\n";
  $functions["deselect_group"] .= "for (sel_opt = 0; sel_opt < selected.length; sel_opt++) {\n";
  $functions["deselect_group"] .= "if (selected.options[sel_opt].selected == true) {\n";
  $functions["deselect_group"] .= "selected.options[sel_opt] = null;\n";
  $functions["deselect_group"] .= "sel_opt--;\n";
  $functions["deselect_group"] .= "}\n";
  $functions["deselect_group"] .= "}\n";
  $functions["deselect_group"] .= "implode_group(selected,values);\n";
  $functions["deselect_group"] .= "}\n";

}



//// Returns group implosion script

function Relations_Admin_ImplodeGroupJS(&$functions) {

  // If it's already there, skip

  if ($functions["implode_group"])
    return;

  // Set the script

  $functions["implode_group"] = "function implode_group(selected,values) {\n";
  $functions["implode_group"] .= "values.value = '';\n";
  $functions["implode_group"] .= "first_comma = '';\n";
  $functions["implode_group"] .= "for (opt = 0; opt < selected.length; opt++) {\n";
  $functions["implode_group"] .= "\n";
  $functions["implode_group"] .= "values.value += first_comma + selected.options[opt].value;\n";
  $functions["implode_group"] .= "first_comma = ',';\n";
  $functions["implode_group"] .= "}\n";
  $functions["implode_group"] .= "}\n";

}

//// Creates a reverse hash for defined inputs.

function Relations_Admin_DefinedInputsJS(&$functions) {

  // If it's already there, skip

  if ($functions["defined_inputs"])
    return;

  // Set the script

  $functions["defined_inputs"] = "var defined_inputs = new Array();\n";
  $functions["defined_inputs"] .= "for (inp = 0; inp < document.relations_admin_form.elements.length; inp++) {\n";
  $functions["defined_inputs"] .= "defined_inputs[document.relations_admin_form.elements[inp].name] = true;\n";
  $functions["defined_inputs"] .= "}\n";

}

//// Returns the HTMLArea config script

function Relations_Admin_SetupHTMLAreaJS(&$functions,$path,$lang) {

  // If it's already there, skip

  if ($functions["setup_htmlarea"])
    return;

  // Set the script

  $functions["setup_htmlarea"] = "_editor_url = '$path';\n";
  $functions["setup_htmlarea"] .= "_editor_lang = '$lang';\n";
  $functions["setup_htmlarea"] .= "</script>\n";
  $functions["setup_htmlarea"] .= "<script type='text/javascript' src='${path}htmlarea.js'></script>\n";
  $functions["setup_htmlarea"] .= "<script>\n";

}

//// Returns the HTMLArea load script

function Relations_Admin_InitHTMLAreaJS(&$functions,$name,$width,$height,$toolbar) {

  // If it's already there, skip

  if ($functions["init_htmlarea_$name"])
    return;

  // Set the script

  $functions["init_htmlarea_$name"] = "function init_htmlarea_$name(name) {\n";
  $functions["init_htmlarea_$name"] .= "${name}_editor = new HTMLArea(name);\n";

  // If there's a toolbar use it.

  if (is_array($toolbar) && count($toolbar)) {

    $bars = array();

    foreach ($toolbar as $bar) {

      $tools = array();

      foreach ($bar as $tool) {

        $tools[] = "'$tool'";

      }

      $bars[] = "[" . join(',',$tools) . "]";

    }

    $functions["init_htmlarea_$name"] .= "${name}_editor.config.toolbar = [" . join(',',$bars) . "];\n";

  }

  $functions["init_htmlarea_$name"] .= "${name}_editor.config.width = '${width}px';\n";
  $functions["init_htmlarea_$name"] .= "${name}_editor.config.height = '${height}px';\n";
  $functions["init_htmlarea_$name"] .= "${name}_editor.config.pageStyle = '</style><link rel=\"stylesheet\" href=\"/htmlarea.css\" type=\"text/css\" /><style>';\n";
  $functions["init_htmlarea_$name"] .= "${name}_editor.generate();\n";
  $functions["init_htmlarea_$name"] .= "}\n";

  $functions["init_htmlarea_$name"] .= "if (defined_inputs['$name']) init_htmlarea_$name('$name');\n";
  $functions["init_htmlarea_$name"] .= "if (defined_inputs['${name}_mass']) init_htmlarea_$name('${name}_mass');\n";
  $functions["init_htmlarea_$name"] .= "for (record = 0; defined_inputs['${name}_' + record]; record++) {\n";
  $functions["init_htmlarea_$name"] .= "init_htmlarea_$name('${name}_' + record);\n";
  $functions["init_htmlarea_$name"] .= "}\n";

}

?>