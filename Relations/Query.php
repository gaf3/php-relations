<?php

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Relations-Query version 0.93                                               |
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

  This package contains a class for SQL query creation and 
  manipluation. It has only been tested with MySQL, but it
  should work with other databases as well.

  HOW IT WORKS

  The object receives what the info to place in the query in
  either string, array, or associative array form. You can 
  create the query with info, add info to specific clauses of
  the query, or override specific clauses of the query. Then
  you can request the entire query as a string to send to a
  database.

*/

require_once('Relations.php');

class Relations_Query {
    
  //// Creates a Relations_Query object. It takes
  //// info for each part of the query, and stores
  //// it into the new object.

  function Relations_Query() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the arguments passed, which are named 
    // for their part of the query.

    list(
      $select,
      $from,
      $where,
      $group_by,
      $having,
      $order_by,
      $limit,
      $options
    ) = Relations_rearrange(array(
      'SELECT',
      'FROM',
      'WHERE',
      'GROUP_BY',
      'HAVING',
      'ORDER_BY',
      'LIMIT',
      'OPTIONS'
    ),$arg_list);

    // Add the sent info into the hash

    $this->select = Relations_asClause($select);
    $this->from = Relations_asClause($from);
    $this->where = Relations_equalsClause($where);
    $this->group_by = Relations_commaClause($group_by);
    $this->having = Relations_equalsClause($having);
    $this->order_by = Relations_commaClause($order_by);
    $this->limit = Relations_commaClause($limit);
    $this->options = Relations_optionsClause($options);

  }



  //// Gets the query for the object in string form.

  function get() {

    $query = array();

    // Add info where appropriate.

    if (strlen($this->select . $this->options) > 0) 
      $query[] = "select $this->options $this->select";

    if (strlen($this->from) > 0) 
      $query[] = "from $this->from";

    if (strlen($this->where) > 0) 
      $query[] = "where $this->where";

    if (strlen($this->group_by) > 0) 
      $query[] = "group by $this->group_by";

    if (strlen($this->having) > 0) 
      $query[] = "having $this->having";

    if (strlen($this->order_by) > 0) 
      $query[] = "order by $this->order_by";

    if (strlen($this->limit) > 0) 
      $query[] = "limit $this->limit";

    // Return the info, delimitted by a space.

    return join(' ', $query);

  }



  //// Adds data to the existing clauses of the query.

  function add() {
    
    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the arguments passed, which are named 
    // for their part of the query.

    list(
      $select,
      $from,
      $where,
      $group_by,
      $having,
      $order_by,
      $limit,
      $options
    ) = Relations_rearrange(array(
      'SELECT',
      'FROM',
      'WHERE',
      'GROUP_BY',
      'HAVING',
      'ORDER_BY',
      'LIMIT',
      'OPTIONS'
    ),$arg_list);

    // Concatente info into the self hash, prefixing it if there's
    // already something there, only if something's actually been 
    // sent.

    $this->select =   Relations_asClauseAdd($this->select,$select);
    $this->from =     Relations_asClauseAdd($this->from,$from);
    $this->where =    Relations_equalsClauseAdd($this->where,$where);
    $this->group_by = Relations_commaClauseAdd($this->group_by,$group_by);
    $this->having =   Relations_equalsClauseAdd($this->having,$having);
    $this->order_by = Relations_commaClauseAdd($this->order_by,$order_by);
    $this->limit =    Relations_commaClauseAdd($this->limit,$limit);
    $this->options =  Relations_optionsClauseAdd($this->options,$options);

  }



  //// Sets the existing settings of a query.

  function set() {
    
    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the arguments passed, which are named 
    // for their part of the query.

    list(
      $select,
      $from,
      $where,
      $group_by,
      $having,
      $order_by,
      $limit,
      $options
    ) = Relations_rearrange(array(
      'SELECT',
      'FROM',
      'WHERE',
      'GROUP_BY',
      'HAVING',
      'ORDER_BY',
      'LIMIT',
      'OPTIONS'
    ),$arg_list);

    // Put info into the self hash, only if something's actually been 
    // sent.

    $this->select =   Relations_asClauseSet($this->select,$select);
    $this->from =     Relations_asClauseSet($this->from,$from);
    $this->where =    Relations_equalsClauseSet($this->where,$where);
    $this->group_by = Relations_commaClauseSet($this->group_by,$group_by);
    $this->having =   Relations_equalsClauseSet($this->having,$having);
    $this->order_by = Relations_commaClauseSet($this->order_by,$order_by);
    $this->limit =    Relations_commaClauseSet($this->limit,$limit);
    $this->options =  Relations_optionsClauseSet($this->options,$options);

  }



  //// Gets the string form of the query object, and accepts 
  //// extra info to temporarily add on to the current 
  //// clause. The added info will be in the returned string,
  //// but will not be stored in the query object.

  function getAdd() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Create a clone of ourselves

    $get_add = clone $this;

    // Add the stuff sent to our clone

    call_user_method_array('add',$get_add,$arg_list);

    // Return our fattened clone's query

    return $get_add->get();
                                  
  }



  //// Gets the string form of the query object, and accepts 
  //// extra info to temporarily overwrite the current 
  //// clause. The set info will be in the returned string,
  //// but will not be stored in the query object.

  function getSet() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Create a clone of ourselves

    $get_set = clone $this;

    // Set the stuff sent to our clone

    call_user_method_array('set',$get_set,$arg_list);
    
    // Return our altered clone's query

    return $get_set->get();
                                  
  }



  //// Returns HTML info about the Relations_Query 
  //// object. Useful for debugging and export purposes.

  function toHTML() {

    // Create a html string to hold everything

    $html = '';

    // 411

    $html .= "<b>Relations_Query: $this</b>";

    $html .= "<ul>";

    $html .= "<li>Select: $this->select</li>";
    $html .= "<li>From: $this->from</li>";
    $html .= "<li>Where: $this->where</li>";
    $html .= "<li>Group By: $this->group_by</li>";
    $html .= "<li>Having: $this->having</li>";
    $html .= "<li>Order By: $this->order_by</li>";
    $html .= "<li>Limit: $this->limit</li>";
    $html .= "<li>Options: $this->options</li>";

    $html .= "</ul>";

    // Return the html

    return $html;

  }

}

//// Takes a hash ref, Relations_Query object, or string
//// and returns a string.

function Relations_Query_toString($query) {

  // If we were sent a hash reference, create a new
  // Relations_Query object.

  if (gettype($query) == 'array')
    $query = new Relations_Query($query);

  // If we were sent a query object, get the query 
  // string from it. 

  if (gettype($query) == 'object') 
    $query = $query->get();

  // Return the query string

  return $query;

}

?>