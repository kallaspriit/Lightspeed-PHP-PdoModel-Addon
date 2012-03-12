<?php
/**
 * Lightspeed high-performance hiphop-php optimized PHP framework
 *
 * Copyright (C) <2011> by <Priit Kallas>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author Priit Kallas <kallaspriit@gmail.com>
 * @package Lightspeed
 * @subpackage Model
 */

/**
 * Interface for a database adapter strategy.
 *
 * Adapters need to implement given methods used by PdoModel so the PdoModel
 * can be used to communicate with different database vendors.
 *
 * @id $Id: DbAdapter.php 65 2011-03-01 16:01:06Z kallaspriit $
 * @author $Author: kallaspriit $
 * @version $Revision: 65 $
 * @modified $Date: 2011-03-01 18:01:06 +0200 (Tue, 01 Mar 2011) $
 * @package Lightspeed
 * @subpackage Model
 */
interface DbAdapter {

	/**
	 * Assembles the query to load an entry by given conditions.
	 *
	 * The $where is should be an array with keys of field names and values of
	 * required values. If you wish to use a predicate other than equals (=),
	 * you can define it for every condition by prepending it at the end of the
	 * column name with a colon. Conditions may include {@see SqlExpr}.
	 *
	 * For example:
	 *
	 * array(
	 *	'age' => 23,
	 *	'deleted:<>' => 0,
	 *	'friends:>' => 3
	 * )
	 *
	 * This would mean everyone that is 23 years old, NOT deleted and has more
	 * than three friends.
	 *
	 * Line like "'friends:>' => 3" can be read as "friends is greater than 3".
	 *
	 * @param string $tableName Name of the table to load from
	 * @param array $columns Array of columns to load, provide empty for all
	 * @param array $where The WHERE conditions
	 * @param array &$whereBind Columns to bind are added to this array
	 * @return string The assembled query
	 */
	public static function assembleLoadWhereQuery(
		$tableName,
		array $columns,
		array $where,
		array &$whereBind = array()
	);

	/**
	 * Assembles a query that updates given table with given data for given
	 * conditions.
	 *
	 * The values are not actually included in the query, they are expected to
	 * be binded on later (except for expressions).
	 *
	 * The $where is should be an array with keys of field names and values of
	 * required values. If you wish to use a predicate other than equals (=),
	 * you can define it for every condition by prepending it at the end of the
	 * column name with a colon. Conditions may include {@see SqlExpr}.
	 *
	 * For example:
	 *
	 * array(
	 *	'age' => 23,
	 *	'deleted:<>' => 0,
	 *	'friends:>' => 3
	 * )
	 *
	 * @param string $tableName Name of the table to update
	 * @param array $data Data to update with
	 * @param array $where The where conditions
	 * @param array &$bind Columns to bind are added to this array
	 */
	public static function assembleUpdateQuery(
		$tableName,
		array $data,
		array $where = array(),
		array &$bind = array()
	);

	/**
	 * Assembles a query that inserts data to given table.
	 *
	 * The values are not actually included in the query, they are expected to
	 * be binded on later.
	 *
	 * @param string $tableName Name of the table to update
	 * @param array $data Data to insert
	 * @param array &$bind Columns to bind are added to this array
	 */
	public static function assembleInsertQuery(
		$tableName,
		array $data,
		array &$bind = array()
	);

	/**
	 * Assembles a query that can be used to delete entry given conditions.
	 *
	 * This method does not actually require values as they should be binded on
	 * later.
	 *
	 * The $where is should be an array with keys of field names and values of
	 * required values. If you wish to use a predicate other than equals (=),
	 * you can define it for every condition by prepending it at the end of the
	 * column name with a colon. Conditions may include {@see SqlExpr}.
	 *
	 * For example:
	 *
	 * array(
	 *	'age' => 23,
	 *	'deleted:<>' => 0,
	 *	'friends:>' => 3
	 * )
	 *
	 * This would mean everyone that is 23 years old, NOT deleted and has more
	 * than three friends.
	 *
	 * Line like "'friends:>' => 3" can be read as "friends is greater than 3".
	 *
	 * @param string $tableName Name of the table
	 * @param array $where The WHERE conditions
	 * @param array &$whereBind Columns to bind are added to this array
	 * @return string The assembled query
	 */
	public static function assembleDeleteWhereQuery(
		$tableName,
		array $where,
		array &$whereBind = array()
	);

	/**
	 * Assembles a where clause of a query.
	 *
	 * The $where is should be an array with keys of field names and values of
	 * required values. If you wish to use a predicate other than equals (=),
	 * you can define it for every condition by prepending it at the end of the
	 * column name with a colon. Conditions may include {@see SqlExpr}.
	 *
	 * For example:
	 *
	 * array(
	 *	'age' => 23,
	 *	'deleted:<>' => 0,
	 *	'friends:>' => 3
	 * )
	 *
	 * This would mean everyone that is 23 years old, NOT deleted and has more
	 * than three friends.
	 *
	 * Line like "'friends:>' => 3" can be read as "friends is greater than 3".
	 *
	 * If there are several conditions for a single column, then the bind names
	 * get their index added to them.
	 *
	 * For example, the conditions array(
	 *     'id:<>' => 1,
	 *     'id:<' => 4,
	 *     'name:<>' => 'Chuck Norris'
	 * )
	 *
	 * Get turned into conditions:
	 *     `id` <> :id AND `id` < :id2 AND `name` <> :name
	 *
	 * The by-reference $bind array also contains the id, id2 and name columns
	 * as keys.
	 *
	 * @param array $conditions Array of conditions.
	 * @param array &$bind Bind array by reference that will include values that
	 *					  have the predicate parts removed from keys
	 * @return string Assembled where clause
	 */
	public static function assembleConditions(
		array $conditions,
		array &$bind = array()
	);

	/**
	 * Quotes a string to be safely used in a query.
	 *
	 * @param PDO $connection Database connection
	 * @param string $string String to quote
	 * @return string The quoted string
	 */
	public static function quote(PDO $connection, $string);

	/**
	 * Returns the number of items given query matches.
	 *
	 * @param PDO $connection The database connection
	 * @param string $query The query to count results of
	 * @param array $bind The data to bind to query
	 * @return integer
	 */
	public static function count(
		PDO $connection,
		$query,
		array $bind = array()
	);

	/**
	 * Returns $limit items starting from $offset. Offset is zero-based.
	 *
	 * @param PDO $connection The PDO connection
	 * @param string $query Query to get items of
	 * @param array $bind Data to bind to the query
	 * @param integer $offset Offset from where to slice data from
	 * @param integer $limit Maximum number of items to take from offset
	 * @return array
	 */
	public static function getItems(
		PDO $connection,
		$query,
		array $bind,
		$offset,
		$limit
	);
}