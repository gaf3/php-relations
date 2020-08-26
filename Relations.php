<?php

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Relations version 0.96                                               |
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

  This package contains some generalized functions for 
  dealing with databases and queries. It serves as the
  base module for all other Relations packages.

  HOW IT WORKS

  Relations has functions for creating SQL clauses (like where, 
  from etc.) from hashes, arrays and strings. It also has functions
  for converting strings to arrays or hashes, if they're not hashes
  or arrays already. It even has an argument parser, which is 
  used quite heavily by the other Relations modules.

*/



//// Relations_rearranges arguments from either the straight ordered format, or named format, 
//// into their respective variables.

// This code was modified from the Perl CGI module by Lincoln D. Stein

function Relations_rearrange($order,$param) {

  // Return unless there's something to parse and there's only 
  // one thing to parse, and that one thing is an array.

  if (gettype($param[0]) != 'array')
    return $param;

  // Create an array that hold the args position by its name

  $pos = array();

  // Initialize count

  $i = 0;

  // Go through each value in the order array

  foreach ($order as $ord) {

    // The order of this argument name is the current location of the counter.

    $pos[strtoupper($ord)] = $i;

    // Increase the counter to the next position.

    $i++;

  }

  // Create the results array

  $result = array();

  // Go through the param array

  foreach ($param[0] as $name=>$value) {

    // If this key isn't preceeded with a '_',
    // return the param list as is. 

    if (substr($name,0,1) != '_') 
      return $param[0];

    $name = substr($name,1);

    // Add this parameter to the results array, using
    // the position lookup from order

    $result[$pos[strtoupper($name)]] = $value;

  }

  // Return the array of arguments' values.

  return $result;

}



//// This function determines whether an array
//// has all integers for its keys.

function Relations_isKeyed($array) {

  // Go through all the key value pairs.

  foreach ($array as $key=>$value) {

    // If this key isn't an int,
    // return false.

    if (is_int($key)) 
      return 0;

  }

  // Return true since we've got this far.

  return 1;

}



//// This routine is used for concatenting arrays and (leaving alone) 
//// strings to be used in different clauses within an SQL statement. 
//// If sent an associative array, the key-value pairs will be concatentated 
//// with the minor string and those pairs will be concatenated with the 
//// major string, and that string returned. If an numeric array is sent, 
//// the members of the array with will be concatenated with the major 
//// string, and that string returned. If a string is sent, that string will 
//// be returned. 

function Relations_delimitClause($minor,$major,$reverse,$clause) {

  // If the clause is an array and is keyed

  if (is_array($clause) && Relations_isKeyed($clause)) {

    // Create a new array to hold everything.

    $new_clause = array();

    // Go through the keys and values, adding to the array to make it seem
    // we were really passed a numeric array.

    foreach ($clause as $key=>$value) {

      // Unless we're supposed to reverse the order, like in a select or
      // from clause

      if ($reverse) {

        // Concantenate the values in reverse order.

        $new_clause[] = "$value$minor$key";

      }

      else {

        // Concantenate the values.

        $new_clause[] = "$key$minor$value";

      }

    }

    // Overwrite the old clause with the new one.

    $clause = $new_clause;

  }

  // If the clause array is isset, meaning we were passed an numeric 
  // array of clause info, (or made to think that we were).

  if (is_array($clause)) {

    // Concatenate the array into a string, as if we were really
    // passed a string.

    $clause = join($major, $clause);

  }

  // Return the string since that's all we were passed, (or made to think
  // we were).

  return $clause;

}



//// Builds the meat for a select or from clause of an SQL 
//// statement. 

function Relations_asClause($as) {

  // If there's something to delimit

  if (isset($as)) {

    // Return the proper clause manimpulation
 
    return Relations_delimitClause(' as ',',',1,$as);

  }

  // If where here, than there's nothing to 
  // delimit. So return that.

  return '';

}



//// Builds the meat for a where or having clause of an
//// SQL statment. Concats with and, not or.

function Relations_equalsClause($equals) {

  // If there's something to delimit

  if (isset($equals)) {

    // Return the proper clause manimpulation
 
    return Relations_delimitClause('=',' and ',0,$equals);

  }

  // If where here, than there's nothing to 
  // delimit.

  return '';

}



//// Builds the meat for a group by, order by, or limit 
//// clause of an SQL statement. You can send it an 
//// associative array, but the order could be changed. 
//// That's why you should only send it a numeric array 
//// or string.

function Relations_commaClause($comma) {

  // If there's something to delimit

  if (isset($comma)) {

    // Return the proper clause manimpulation
 
    return Relations_delimitClause(',',',',0,$comma);

  }

  // If where here, than there's nothing to 
  // delimit.

  return '';

}



//// Builds the meat for a set clause of an SQL
//// statement.

function Relations_assignClause($assign) {

  // If there's something to delimit

  if (isset($assign)) {

    // Return the proper clause manimpulation
 
    return Relations_delimitClause('=',',',0,$assign);

  }

  // If where here, than there's nothing to 
  // delimit.

  return '';

}



//// Builds the meat for the options area of an SQL
//// statement.

function Relations_optionsClause($options) {

  // If there's something to delimit

  if (isset($options)) {

    // Return the proper clause manimpulation
 
    return Relations_delimitClause(' ',' ',0,$options);

  }

  // If where here, than there's nothing to 
  // delimit.

  return '';

}



//// Builds the meat for the fields and values area of 
//// an insert SQL statement.

function Relations_valuesClause($clause) {

  // If it's a hash, then create an array 
  // of it

  if (is_array($clause) && Relations_isKeyed($clause))
    $clause = array($clause);

  // If we're sent an array, then it's an array
  // of hashes.

  if (is_array($clause)) {
    
    // Create the fields part of the clause

    $fields = array_keys($clause[0]);

    // Initialize the values clause array

    $values = array();

    // Go through all the rows 

    foreach ($clause as $row) {

      // Create an array for these fields 
      // values

      $field_values = array();

      // Go through all the fields 

      foreach ($fields as $field) {

        // Add these values to the field

        $field_values[] = $row[$field];

      }

      // Add these field values to the clause

      $values[] = '(' . join(',',$field_values) . ')';

    }

    // Create the entire values clause

    $clause = '(' . join(',',$fields) . ') values' . join(',',$values);

  }

  // Return the string since that's all we were passed, 
  // (or made to think we were).

  return $clause;

}



//// Add the meat for a select or from clause of 
//// an SQL statement.

function Relations_asClauseAdd($as,$add_as) {

  // If there's something to add

  if (isset($add_as)) {

    // If there's something already there

    if (strlen($as)) {

      // Return what's there, plus a comma,
      // plus what's to be added

      return $as . ',' . Relations_asClause($add_as);

    }

    // If we're here than there's nothing
    // already there so just return what's
    // to be added.

    return Relations_asClause($add_as);

  }
  
  // If we're here than there's nothing
  // to be added so just return what's 
  // already there.

  return $as;

}



//// Add the meat for a where or having clause of 
//// an SQL statement.

function Relations_equalsClauseAdd($equals,$add_equals) {

  // If there's something to add

  if (isset($add_equals)) {

    // If there's something already there

    if (strlen($equals)) {

      // Return what's there, plus a comma,
      // plus what's to be added

      return $equals . ' and ' . Relations_equalsClause($add_equals);

    }

    // If we're here than there's nothing
    // already there so just return what's
    // to be added.

    return Relations_equalsClause($add_equals);

  }
  
  // If we're here than there's nothing
  // to be added so just return what's 
  // already there.

  return $equals;

}



//// Add the meat for a order by, group by, or 
//// limit clause of an SQL statement.

function Relations_commaClauseAdd($comma,$add_comma) {

  // If there's something to add

  if (isset($add_comma)) {

    // If there's something already there

    if (strlen($comma)) {

      // Return what's there, plus a comma,
      // plus what's to be added

      return $comma . ',' . Relations_commaClause($add_comma);

    }

    // If we're here than there's nothing
    // already there so just return what's
    // to be added.

    return Relations_commaClause($add_comma);

  }
  
  // If we're here than there's nothing
  // to be added so just return what's 
  // already there.

  return $comma;

}



//// Add the meat for a set caluse of an SQL statement.

function Relations_assignClauseAdd($assign,$add_assign) {

  // If there's something to add

  if (isset($add_assign)) {

    // If there's something already there

    if (strlen($assign)) {

      // Return what's there, plus a comma,
      // plus what's to be added

      return $assign . ',' . Relations_assignClause($add_assign);

    }

    // If we're here than there's nothing
    // already there so just return what's
    // to be added.

    return Relations_assignClause($add_assign);

  }
  
  // If we're here than there's nothing
  // to be added so just return what's 
  // already there.

  return $assign;

}



//// Add the meat for the options area of an SQL statement.

function Relations_optionsClauseAdd($options,$add_options) {

  // If there's something to add

  if (isset($add_options)) {

    // If there's something already there

    if (strlen($options)) {

      // Return what's there, plus a comma,
      // plus what's to be added

      return $options . ' ' . Relations_optionsClause($add_options);

    }

    // If we're here than there's nothing
    // already there so just return what's
    // to be added.

    return Relations_optionsClause($add_options);

  }
  
  // If we're here than there's nothing
  // to be added so just return what's 
  // already there.

  return $options;

}



//// Sets the meat for a set clause of an SQL statement.
//// If there's something to be set, it overrides what's
//// already there. If there's nothing to set, it'll 
//// leave what's there alone.

function Relations_asClauseSet($as,$set_as) {

  // If there's something to set

  if (isset($set_as)) {

    // Just return what's to be set.

    return Relations_asClause($set_as);

  }
  
  // If we're here than there's nothing
  // to be set so just return what's 
  // already there.

  return $as;

}



//// Sets the meat for a where or having clause of 
//// an SQL statement. If there's something to be 
//// set, it overrides what's already there. If 
//// there's nothing to set, it'll leave what's there 
//// alone.

function Relations_equalsClauseSet($equals,$set_equals) {

  // If there's something to set

  if (isset($set_equals)) {

    // Just return what's to be set.

    return Relations_equalsClause($set_equals);

  }
  
  // If we're here than there's nothing
  // to be set so just return what's 
  // already there.

  return $equals;

}



//// Sets the meat for a order by, group by, or 
//// limit clause of an SQL statement. If there's 
//// something to be set, it overrides what's
//// already there. If there's nothing to set, it'll 
//// leave what's there alone.

function Relations_commaClauseSet($comma,$set_comma) {

  // If there's something to set

  if (isset($set_comma)) {

    // Return what's to be set.

    return Relations_commaClause($set_comma);

  }
  
  // If we're here than there's nothing
  // to be set so just return what's 
  // already there.

  return $comma;

}



//// Sets the meat for a set clause of an SQL statement. 
//// If there's something to be set, it overrides what's
//// already there. If there's nothing to set, it'll 
//// leave what's there alone.

function Relations_assignClauseSet($assign,$set_assign) {

  // If there's something to set

  if (isset($set_assign)) {

    // Return what's to be set.

    return Relations_assignClause($set_assign);

  }
  
  // If we're here than there's nothing
  // to be set so just return what's 
  // already there.

  return $assign;

}



//// Sets the meat for a set clause of an SQL statement. 
//// If there's something to be set, it overrides what's
//// already there. If there's nothing to set, it'll 
//// leave what's there alone.

function Relations_optionsClauseSet($options,$set_options) {

  // If there's something to set

  if (isset($set_options)) {

    // Return what's to be set.

    return Relations_optionsClause($set_options);

  }
  
  // If we're here than there's nothing
  // to be set so just return what's 
  // already there.

  return $options;

}



//// Takes a comma delimitted string or array ref 
//// and returns an array ref.

function Relations_toArray($value,$explode=',') {

  // Unless it's a reference to something

  if (!(is_array($value))) {

    // If there's a string length, break it
    // up by $explode. Else return an empty 
    // array

    if (strlen($value))
      $value = explode($explode,$value);
    else
      $value = array();

  }

  // Return the array refence
  
  return $value;

}



//// Takes a comma delimitted string, array or 
//// associative array and returns an associative 
//// array. The array will have the individual 
//// values as keys with their values set to true.

function Relations_toHash($value,$explode=',') {

  // If it's not an array or not a keyed array

  if (!(is_array($value)) || !Relations_isKeyed($value)) {

    // Assume its a comma delimitted string or an
    // array and send it to Relations_toArray

    $value = Relations_toArray($value,$explode);

  }

  // If it's not a keyed array

  if (!Relations_isKeyed($value)) {

    // Declare the associative array to send back.

    $new_value = array();

    // Go through each one, settting the 
    // key's value to true.

    foreach ($value as $key) {

      $new_value[$key] = 1;

    }

    // Store the new associative array in $value

    $value = $new_value;

  }

  // Return the associative array
  
  return $value;

}



//// Creates a default database settings module.
//// Takes in the defaults, prompts the user for
//// info. If the user sends info, that's used. 
//// Once the settings are determined, it creates
//// a Settings.php file in the current direfctory.

function Relations_configureSettings() {

  // Get the defaults sent. These we be used if
  // the user just hits return for each one. 

  $args = func_get_args();

  list($def_database,
       $def_username,
       $def_password,
       $def_host,
       $def_port) = Relations_rearrange(array('DATABASE',
                                    'USERNAME',
                                    'PASSWORD',
                                    'HOST',
                                    'PORT'),$args);

  global $HTTP_GET_VARS;
  global $REQUEST_URI;

  if (isset($REQUEST_URI))
    $uri = $REQUEST_URI;
  elseif($_SERVER['REQUEST_URI'])
    $uri = $_SERVER['REQUEST_URI'];

  if (isset($HTTP_GET_VARS))
    $gets = $HTTP_GET_VARS;
  elseif($_GET)
    $gets = $_GET;

  // If configured_settings is true, then we've
  // already gone through once and we're ready
  // to create the Settings.php file.

  if ($gets['Relations_configureSettings']) {

    // Create a Settings.php file

    $fp = fopen("Settings.php", "w");

    fputs($fp,"<?php\n");
    fputs($fp,"global \$database;\n");
    fputs($fp,"global \$username;\n");
    fputs($fp,"global \$password;\n");
    fputs($fp,"global \$host;\n");
    fputs($fp,"global \$port;\n");
    fputs($fp,"\n");
    fputs($fp,"\$database = '$gets[database]';\n");
    fputs($fp,"\$username = '$gets[username]';\n");
    fputs($fp,"\$password = '$gets[password]';\n");
    fputs($fp,"\$host = '$gets[host]';\n");
    fputs($fp,"\$port = '$gets[port]';\n");
    fputs($fp,"?" . ">\n");

    fclose($fp);

    if (isset($gets['original_location'])) {

      header("Location: $gets[original_location]"); 
      exit;
      
    }

  } else {

    ?>

<html>

  <head>

    <title>Configure Settings</title>

  </head>

  <body>

    <center>

      <h1>Configure Settings</h1>

      <form action='<?php print $uri; ?>' method='get'>

        <p>Before we can get started, I need to know some
        info about your MySQL settings. Please fill in
        the fields below. To accept the default values, 
        leave the fields as is.</p>

        <h3>MySQL Database Name</h3>

        <p>Make sure the database isn't the same as the name
        as an existing database of yours, since the this 
        script will delete that database when run.</p>

        <p>Database: <input type='text' name='database' value='<?php print $def_database; ?>'></p>

        <h3>MySQL Username and Password</h3>

        <p>Make sure the this username password account can
        create and destroy databases.</p>

        <p>Username: <input type='text' name='username' value='<?php print $def_username; ?>'></p>
        <p>Password: <input type='text' name='password' value='<?php print $def_password; ?>'></p>

        <h3>MySQL Host and Port</h3>

        <p>Make sure the computer running the demo can connect to
        this host and port, or this script will not function
        properly.</p>

        <p>Host: <input type='text' name='host' value='<?php print $def_host; ?>'></p>
        <p>Port: <input type='text' name='port' value='<?php print $def_port; ?>'></p>

        <input type='hidden' name='original_location' value='<?php print $uri; ?>'></p>
        <input type='submit' name='Relations_configureSettings' value='Configure Settings'></p>

      </form>

    </body>

  </center>

</html>

    <?php

    exit();

  }

}

?>