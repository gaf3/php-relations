<?php

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Relations-Abstract version 0.94                                      |
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

  This PHP class to simplifies using the mysql functions. It takes 
  the most common (in my experience) collection of mysql calls to a 
  MySQL databate, and changes them to one liners. 

  HOW IT WORKS

  All Abstract does is take information about what you want to do
  to a database and does it by creating and executing SQL statements via
  mysql functions. That's it. It's there just to simplify the amount of 
  code one has to write and maintain with respect long and complex 
  database tasks.

*/

require_once('Relations.php');
require_once('Relations/Query.php');

class Relations_Abstract {
	
  //// Creates a new Relations::Abstract object.

  function Relations_Abstract($link = null) {

    // Add the info to the class

    $this->link = $link;

  }



  //// Sets the default database resource to use.

  function setLink($link) {

    // Set the database resource.

    $this->link = $link;

  }



  //// Runs a query

  function runQuery($query) {

    // Run the query and get the result, use the link
    // if it was sent

    if ($this->link)
      return mysql_query($query,$this->link);
    else
      return mysql_query($query); 

  }



  //// Select a field's data.

  function selectField() {

    //// What we're doing here is creating and sending the query 
    //// string to the link, retreiving the requested item and 
    //// reporting an error if the execute failed. We can take
    //// simple info like the table and where clause, or complex
    //// info like a full query.

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the field, table, where clause sent

    list($field,$table,$where,$query) = Relations_rearrange(array('FIELD','TABLE','WHERE','QUERY'),$arg_list);

    // Unless we were sent query info, make some.

    if (!($query))
      $query = "select $field from $table where " . Relations_equalsClause($where);

    // Convert whatever we were sent to a query string.

    $query = Relations_Query_toString($query);

    // Run the query and get the result, let 
    // 'em know if something went wrong

    if (!($result = $this->runQuery($query)))
      return $this->reportError("selectField failed: $query\n");

    // If we got something returned

    if ($row = mysql_fetch_assoc($result)) {

      // Return the value

      return $row[$field];

    }

    return null;

  }



  //// Selects a row of data.

  function selectRow() {

    //// What we're doing here is creating and sending the query 
    //// string to the link, retreiving the requested item and 
    //// reporting an error if the execute failed. We can take
    //// simple info like the table and where clause, or complex
    //// info like a full query.

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the table and where clause sent

    list($table,$where,$query) = Relations_rearrange(array('TABLE','WHERE','QUERY'),$arg_list);

    // Unless we were sent query info, make some.

    if (!($query))
      $query = "select * from $table where " . Relations_equalsClause($where);

    // Convert whatever we were sent to a query string.

    $query = Relations_Query_toString($query);

    // Run the query and get the result, let 
    // 'em know if something went wrong

    if (!($result = $this->runQuery($query)))
      return $this->reportError("selectRow failed: $query\n");

    // If we got something returned

    if ($row = mysql_fetch_assoc($result)) {

      // Return the value

      return $row;

    }

  }



  //// Selects a column of data.

  function selectColumn() {

    //// What we're doing here is creating and sending the query 
    //// string to the link, retreiving the requested items and 
    //// reporting an error if the execute failed. We can take
    //// simple info like the table and where clause, or complex
    //// info like a full query.

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the field table and where clause sent

    list($field,$table,$where,$query) = Relations_rearrange(array('FIELD','TABLE','WHERE','QUERY'),$arg_list);

    // Unless we were sent query info, make some.

    if (!($query))
      $query = "select $field from $table where " . Relations_equalsClause($where);

    // Convert whatever we were sent to a query string.

    $query = Relations_Query_toString($query);

    // Run the query and get the result, let 
    // 'em know if something went wrong

    if (!($result = $this->runQuery($query)))
      return $this->reportError("selectColumn failed: $query\n");

    // Create an array to hold the column

    $column = array();

    // If we got something returned

    while ($row = mysql_fetch_assoc($result)) {

      // Store the value

      $column[] = $row[$field];

    }

    // Return the column

    return $column;

  }



  //// Selects a matrix (rows of columns) of data.

  function selectMatrix() {

    //// What we're doing here is creating the query string 
    //// and sending it to the link, retreiving the rows of hashes, 
    //// returning them unless there's an error. If so we'll report 
    //// the error. We can take simple info like the table and where 
    //// clause, or complex info like a full query.

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the table and where clause sent

    list($table,$where,$query) = Relations_rearrange(array('TABLE','WHERE','QUERY'),$arg_list);

    // Unless we were sent query info, make some.

    if (!($query))
      $query = "select * from $table where " . Relations_equalsClause($where);

    // Convert whatever we were sent to a query string.

    $query = Relations_Query_toString($query);

    // Run the query and get the result, let 
    // 'em know if something went wrong

    if (!($result = $this->runQuery($query)))
      return $this->reportError("selectMatrix failed: $query\n");

    // Create an array to hold the matrix

    $matrix = array();

    // If we got something returned

    while ($row = mysql_fetch_assoc($result)) {

      // Store the value

      $matrix[] = $row;

    }

    // Return the matrix

    return $matrix;

  }



  //// Inserts a row of data into a table.

  function insertRow() {

    //// What we're doing here is sending the query string to the link, and 
    //// returning the number of rows affected, unless there's an error. If
    //// there's an error, we'll send back a 0.

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the table and set clause sent

    list($table,$set) = Relations_rearrange(array('TABLE','SET'),$arg_list);

    // Get the info for the where clause;

    $set = Relations_assignClause($set);

    // Form query

    $query = "insert into $table set $set"; 

    // Run the query and get the result, let 
    // 'em know if something went wrong

    if (!($result = $this->runQuery($query)))
      return $this->reportError("insertRow failed: $query\n");

    // Return the number of rows inserted, use the link
    // if it was sent

    if ($this->link)
      return mysql_affected_rows($this->link);
    else
      return mysql_affected_rows();

  }



  //// Inserts a row data into a table and returns the new id.

  function insertID() {

    //// What we're doing here is sending the query string to the link, retreiving 
    //// the new id, and sending it back, unless there's an error. If there's an 
    //// error, we'll send back a zero.

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the table and set clause sent

    list($table,$set) = Relations_rearrange(array('TABLE','SET'),$arg_list);

    // Get the info for the where clause;

    $set = Relations_assignClause($set);

    // Form query

    $query = "insert into $table set $set"; 

    // Run the query and get the result, let 
    // 'em know if something went wrong

    if (!($result = $this->runQuery($query)))
      return $this->reportError("insertID failed: $query\n");

    // Return the number of rows affected, use the link
    // if it was sent

    if ($this->link)
      return mysql_insert_id($this->link);
    else
      return mysql_insert_id();

  }



  //// Inserts rows of data into a table.

  function insertRows() {

    //// What we're doing here is sending the query string to the dbh, and 
    //// returning the number of rows affected, unless there's an error. If
    //// there's an error, we'll send back a 0.

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the table and set clause sent

    list($table,$values) = Relations_rearrange(array('TABLE','VALUES'),$arg_list);

    // Get the info for the where clause;

    $values = Relations_valuesClause($values);

    // Form query

    $query = "insert into $table $values"; 

    if (!($result = $this->runQuery($query)))
      return $this->reportError("insertRows failed: $query\n");

    // Return the number of rows inserted, use the link
    // if it was sent

    if ($this->link)
      return mysql_affected_rows($this->link);
    else
      return mysql_affected_rows();

  }



  //// Selects or inserts data and returns the id.

  function selectInsertID() {

    //// What we're doing here trying select_item and returning the id if 
    //// succesful. Else, trying insertID, returning the new if successful.
    //// Else, returning zero.

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the table, where clause, and set clause sent

    list($id,$table,$where,$set) = Relations_rearrange(array('ID','TABLE','WHERE','SET'),$arg_list);

    // If the data's already there.

    if ($old_id = $this->selectField($id,$table,$where)) {

      // Return the old id

      return $old_id;

    }

    // If we could add the data

    if ($new_id = $this->insertID($table,$set)) {

      // Return the new id.

      return $new_id;

    }

    // If we've come this far, then neither was successful. Indicate this.

    return $this->reportError("selectInsertID failed: id: $id table: $table where: $where set: $set\n");


  }



  //// Updates rows of data in a table and returns the number of updated rows. 

  function updateRows() {

    //// What we're doing here is sending the query string to the link, and 
    //// returning the number of rows affected, unless there's an error. If
    //// there's an error, we'll send back a 0.

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the table where clause, and set clause sent

    list($table,$where,$set) = Relations_rearrange(array('TABLE','WHERE','SET'),$arg_list);

    // Get the info for the set and where clause;

    $set = Relations_assignClause($set);
    $where = Relations_equalsClause($where);

    // Form query

    $query = "update $table set $set where $where"; 

    // Run the query and get the result, let 
    // 'em know if something went wrong

    if (!($result = $this->runQuery($query)))
      return $this->reportError("updateRows failed: $query\n");

    // Return the number of rows updated, use the link
    // if it was sent

    if ($this->link)
      return mysql_affected_rows($this->link);
    else
      return mysql_affected_rows();

  }



  //// Deletes rows from a table and returns the number of deleted rows.

  function deleteRows() {

    //// What we're doing here is sending the query string to the link, and 
    //// returning the number of rows affected, unless there's an error. If
    //// there's an error, we'll send back a 0.

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the table and where clause criteria sent

    list($table,$where) = Relations_rearrange(array('TABLE','WHERE'),$arg_list);

    // Get the info where clause;

    $where = Relations_equalsClause($where);

    // Form query

    $query = "delete from $table where $where"; 

    // Run the query and get the result, let 
    // 'em know if something went wrong

    if (!($result = $this->runQuery($query)))
      return $this->reportError("deleteRows failed: $query\n");

    // Return the number of rows deleted, use the link
    // if it was sent

    if ($this->link)
      return mysql_affected_rows($this->link);
    else
      return mysql_affected_rows();

  }



  //// Reports a failed routine if PrintError is enabled in the link.

  function reportError($message) {

    // Tell the user what's up.

    print($message);

    return;

  }

}
