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
 * @subpackage Pdo
 */

// Require used classes and interfaces
require_once LIBRARY_PATH.'/pdo/SqlExpr.php';
require_once LIBRARY_PATH.'/pdo/DbAdapter.php';
require_once LIBRARY_PATH.'/data-source/DataSource.php';

/**
 * PDO-based data access model to a relational database.
 * 
 * This is the advanced version that uses PHP 5.3 functionality for some
 * additional ease of use.
 *
 * Depends on database library.
 *
 * @id $Id: PdoModel.php 122 2011-05-18 12:10:10Z kallaspriit $
 * @author $Author: kallaspriit $
 * @version $Revision: 122 $
 * @modified $Date: 2011-05-18 15:10:10 +0300 (Wed, 18 May 2011) $
 * @package Lightspeed
 * @subpackage Model
 */
class PdoModel implements Iterator, DataSource {

	/**
	 * The default PDO connection to use.
	 * 
	 * This should be set using the static method
	 * {@see PdoModel::setDefaultConnection()}.
	 *
	 * This can be overriden for any instance using
	 * {@see PdoModel::setConnection()}.
	 * 
	 * @var PDO
	 */
	protected static $_defaultConnection;

	/**
	 * Database adapter to use by default.
	 *
	 * @var DbAdapter
	 */
	protected static $_defaultAdapter;

	/**
	 * The actual connection to use for given instance.
	 *
	 * This defaults to the one set by {@see PdoModel::setDefaultConnection()}
	 * but can be over-ridden with {@see PdoModel::setConnection()}.
	 *
	 * @var PDO
	 */
	protected $_connection;

	/**
	 * The database adapter strategy used.
	 * 
	 * @var DbAdapter
	 */
	protected $_adapter;

	/**
	 * Primary key name.
	 *
	 * If needed, you can just set this in your derived model class to use
	 * something other than "id".
	 *
	 * @var string
	 */
	protected $_primaryKeyName = 'id';

	/**
	 * Array of property names that have been set to null.
	 *
	 * @var array
	 */
	protected $_setNull = array();

	/**
	 * Holds the last query that was assembled and prepared by the model.
	 *
	 * @var string
	 */
	protected $_lastQuery;

	/**
	 * Last PDOStatement returned by operations such as {@see PdoModel::find()},
	 * used to iterate over the results.
	 *
	 * @var PDOStatement
	 */
	protected $_lastStatement;

	/**
	 * Contains the data that was binded to the las query or is to be binded.
	 *
	 * @var array
	 */
	protected $_lastBind;
	
	/**
	 * The fetch decorator called for every row.
	 * 
	 * @var function 
	 */
	protected $_decorator;

	/**
	 * Last number of items a statmenet matched.
	 *
	 * @var integer
	 */
	protected $_lastCount;

	/**
	 * Last resultset returned by iterable operation such as
	 * {@see PdoModel::find()}. As this class implements Iterator, you can
	 * iterate over it to get the results.
	 *
	 * @var array
	 */
	protected $_resultset;

	/**
	 * Current key offset of resultset.
	 *
	 * Incremented while the object is iterated.
	 *
	 * @var integer
	 */
	protected $_resultKey;

	/**
	 * Constructs the model.
	 *
	 * May optionally override default connection and adapter.
	 *
	 * @param PDO $connection Connection to use instead of default
	 * @param DbAdapter Database adapter, default one is used if not provided
	 */
	public function  __construct(
		PDO $connection = null,
		DbAdapter $adapter = null
	) {
		if ($connection !== null) {
			$this->setConnection($connection);
		} else {
			$this->_connection = self::$_defaultConnection;
		}

		if ($adapter !== null) {
			$this->setAdapter($adapter);
		} else {
			$this->_adapter = self::$_defaultAdapter;
		}
	}
	
	/**
	 * Returns instance of the derived model class.
	 * 
	 * @param PDO $connection Connection to use instead of default
	 * @param DbAdapter Database adapter, default one is used if not provided
	 * @return PdoModel Model instance
	 */
	public static function getInstance(
		PDO $connection = null,
		DbAdapter $adapter = null
	) {
		$className = get_called_class();
		
		return new $className($connection, $adapter);
	}

	/**
	 * Sets default PDO connection to use.
	 *
	 * The connection should already been established. Can be over-ridden with
	 * {@see PdoModel::setConnection()} for any instance.
	 *
	 * @param PDO $connection Connection
	 */
	public static function setDefaultConnection(PDO $connection) {
		self::$_defaultConnection = $connection;
	}

	/**
	 * Returns default PDO connection.
	 *
	 * @return PDO
	 */
	public static function getDefaultConnection() {
		return self::$_defaultConnection;
	}
	
	
	/**
	 * Sets default databasde adapter to use.
	 *
	 * The adapter should already been established. Can be over-ridden with
	 * {@see PdoModel::setAdapter()} for any instance.
	 *
	 * @param DbAdapter $adapter Adapter
	 */
	public static function setDefaultAdapter(DbAdapter $adapter) {
		self::$_defaultAdapter = $adapter;
	}

	/**
	 * Returns default database adapter.
	 *
	 * @return DbAdapter
	 */
	public static function getDefaultAdapter() {
		return self::$_defaultAdapter;
	}
	
	/**
	 * Starts a transaction.
	 * 
	 * @param PDO $connection Optional, uses default if not set
	 * @return boolean Was starting the transaction successful 
	 */
	public static function beginTransaction(PDO $connection = null) {
		if (!isset($connection)) {
			$connection = self::$_defaultConnection;
		}
		
		return $connection->beginTransaction();
	}
	
	/**
	 * Commits current transaction.
	 * 
	 * @param PDO $connection Optional, uses default if not set
	 * @return boolean Was commiting the transaction successful 
	 */
	public static function commit(PDO $connection = null) {
		if (!isset($connection)) {
			$connection = self::$_defaultConnection;
		}
		
		return $connection->commit();
	}
	
	/**
	 * Attempts to roll back current transaction.
	 * 
	 * @param PDO $connection Optional, uses default if not set
	 * @return boolean Was rollong back the transaction successful 
	 */
	public static function rollBack(PDO $connection = null) {
		if (!isset($connection)) {
			$connection = self::$_defaultConnection;
		}
		
		return $connection->rollBack();
	}

	/**
	 * Sets the PDO connection to use.
	 *
	 * The connection should already been established.
	 *
	 * @param PDO $connection Connection
	 */
	public function setConnection(PDO $connection) {
		$this->_connection = $connection;
	}

	/**
	 * Returns currently used PDO connection.
	 *
	 * @return PDO
	 */
	public function getConnection() {
		return $this->_connection;
	}

	/**
	 * Sets the database adapter to use.
	 *
	 * The adapter should already been established.
	 *
	 * @param DbAdapter $adapter Adapter
	 */
	public function setAdapter(DbAdapter $adapter) {
		$this->_adapter = $adapter;
	}

	/**
	 * Returns currently used database adapter.
	 *
	 * @return DbAdapter
	 */
	public function getAdapter() {
		return $this->_adapter;
	}

	/**
	 * Sets model primary key name.
	 *
	 * You can just set the $_primaryKeyName property in derived class
	 * definition.
	 *
	 * @param string $name Primary key name
	 */
	public function setPrimaryKeyName($name) {
		$this->_primaryKeyName = $name;
	}

	/**
	 * Returns model primary key name.
	 */
	public function getPrimaryKeyName() {
		return $this->_primaryKeyName;
	}

	/**
	 * Populates model with data.
	 *
	 * Array keys must match model properties.
	 *
	 * @param array $data Data to use
	 */
	public function populate(array $data) {
		foreach ($data as $key => $value) {
			$this->$key = $value;
		}
	}

	/**
	 * Sets a property null
	 *
	 * Next time the row is updated, this property is set to null
	 *
	 * @param string $propertyName Property to update
	 */
	public function setNull($propertyName) {
		$this->$propertyName = null;
		$this->_setNull[] = $propertyName;
	}

	/**
	 * Returns the last query prepared by the model.
	 *
	 * Might be null if none performed.
	 *
	 * @return string|null Last prepared query
	 */
	public function getLastQuery() {
		return $this->_lastQuery;
	}

	/**
	 * Returns the last statement that was used in any of the actions performed
	 * by the model.
	 *
	 * Might be null if none performed.
	 *
	 * @return PDOStatement|null Last generated PDO statement
	 */
	public function getLastStatement() {
		return $this->_lastStatement;
	}

	/**
	 * Returns array of bind keys and values that were binded to the last
	 * statement or should be binded next.
	 *
	 * Might be null if none performed.
	 *
	 * @return array|null Last bind values
	 */
	public function getLastBind() {
		return $this->_lastBind;
	}

	/**
	 * Returns model data as an associative array
	 *
	 * @param boolean $notNullOnly Should only set values be returned
	 *
	 * @return array
	 */
	public function getData($notNullOnly = false) {
		$columns = self::getColumnNames();
		$data = array();

		foreach ($columns as $column) {
			if (!$notNullOnly || in_array($column, $this->_setNull) || isset($this->$column)) {
				$data[$column] = $this->$column;
			}
		}

		return $data;
	}

	/**
	 * Prepares a query and returns prepared statement.
	 *
	 * @param string $query Query to prepare
	 * @return PDOStatement Prepared statement
	 * @throws Exception If something goes wrong
	 */
	public static function prepare($query) {
		return self::getInstance()->_prepare($query);
	}
	
	/**
	 * Prepares a query and returns prepared statement.
	 *
	 * @param string $query Query to prepare
	 * @return PDOStatement Prepared statement
	 * @throws Exception If something goes wrong
	 */
	public function _prepare($query) {
		$this->_lastQuery = $query;
		$this->_lastStatement = $this->_connection->prepare($query);
		
		if (!isset($this->_lastStatement)) {
			throw new Exception(
				'Preparing statement for query "'.$query.'" failed'
			);
		}

		return $this->_lastStatement;
	}

	/**
	 * Loads data into the model by primary key value.
	 *
	 * Returns true if an entry was found and false otherwise. The data can
	 * be accessed from the public fields that represent columns.
	 *
	 * @param mixed $primaryKeyValue Value of the primary key to load by.
	 * @return PdoModel|null The model or null if not found
	 */
	public static function load($primaryKeyValue) {
		$model = self::getInstance();

		if ($model->_load($primaryKeyValue)) {
			return $model;
		} else {
			return null;
		}
	}

	/**
	 * Loads data into the model by primary key value.
	 *
	 * Returns true if an entry was found and false otherwise. The data can
	 * be accessed from the public fields that represent columns.
	 *
	 * @param mixed $primaryKeyValue Value of the primary key to load by.
	 * @return boolean Was loading the data successful.
	 */
	public function _load($primaryKeyValue) {
		$pkName = $this->_primaryKeyName;
		
		return $this->_loadWhere(array($pkName => $primaryKeyValue));
	}

	/**
	 * Loads an entry to the model by given conditions.
	 *
	 * If multiple rows match the conditions, the first one is chosen without
	 * any ordering so be careful and avoid such conditions.
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
	 * @param array $where
	 */
	public static function loadWhere(array $where) {
		$model = self::getInstance();
		
		if ($model->_loadWhere($where)) {
			return $model;
		} else {
			return null;
		}
	}
	
	/**
	 * Loads an entry to the model by given conditions.
	 *
	 * If multiple rows match the conditions, the first one is chosen without
	 * any ordering so be careful and avoid such conditions.
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
	 * @param array $where
	 */
	public function _loadWhere(array $where) {
		$tableName = self::getTableName();
		$columns = self::getColumnNames();
		$whereBind = array();

		$query = $this->_adapter->assembleLoadWhereQuery(
			$tableName,
			$columns,
			$where,
			$whereBind
		);

		$statement = $this->_prepare($query);

		foreach ($whereBind as $column => $value) {
			$statement->bindValue(':'.$column, $value);
		}

		$this->_lastBind = $whereBind;

		$statement->execute();

		$data = $statement->fetch(PDO::FETCH_ASSOC);
		
		if ($data === false) {
			return false;
		}

		foreach ($columns as  $column) {
			if (array_key_exists($column, $data)) {
				$this->$column = $data[$column];
			}
		}

		return true;
	}

	/**
	 * Inserts a new row to the database.
	 * 
	 * Creates an instance of the model, populates it and saves off to the db.
	 *
	 * @param array $populate Data to populate model with or null if using current
	 * @return mixed Inserted row primary key value
	 * @throws Exception If adding entry failed
	 */
	public static function insert(array $populate) {
		return self::getInstance()->save($populate, true);
	}

	/**
	 * Saves data represented by the model to the database.
	 *
	 * If primary key has value, an existing row in the database is updated and
	 * boolean value is returned whether the query succeeded or not.
	 *
	 * If primary key does not have a value, a new row is added and the value
	 * of the primary key of the added entry is returned.
	 *
	 * You may optionally provide data to populate the model just before the
	 * save operation.
	 *
	 * @param array|null $populate Data to populate model with or null if using current
	 * @param boolean $forceInsert Should insert query be issued even if id exists
	 * @return boolean|mixed Was update successful or inserted row pk value
	 * @throws Exception If adding entry failed
	 */
	public function save(array $populate = null, $forceInsert = false) {
		if (is_array($populate)) {
			$this->populate($populate);
		}

		$tableName = self::getTableName();
		$data = $this->getData();
		$pkName = $this->_primaryKeyName;
		$pkValue = $this->$pkName;
		$columns = array_keys($data);

		// if primary key is set, update
		if (isset($data[$this->_primaryKeyName]) && !$forceInsert) {
			$bind = array();
			$query = $this->_adapter->assembleUpdateQuery(
				$tableName,
				$data,
				array($pkName => $pkValue),
				$bind
			);
			
			$statement = $this->_prepare($query);

			foreach ($bind as $column => $value) {
				$statement->bindValue(':'.$column, $value);
			}

			$this->_lastBind = $bind;

			if ($statement->execute() === false) {
				$errorInfo = $statement->errorInfo();
				
				//@codeCoverageIgnoreStart
				throw new Exception(
					'Updating entry in "'.$tableName.'" with primary key '.
					'"'.$pkValue.'" failed ['.$errorInfo[0].'-'.$errorInfo[1].
					']: '.$errorInfo[2]
				);
				//@codeCoverageIgnoreEnd
			}

			return true;
		} else {
			// primary key has not been set, insert new row
			$bind = array();
			$query = $this->_adapter->assembleInsertQuery($tableName, $data, $bind);

			$statement = $this->_prepare($query);

			foreach ($bind as $column => $value) {
				$statement->bindValue(':'.$column, $value);
			}

			$this->_lastBind = $bind;

			if (!$statement->execute()) {
				$errorInfo = $statement->errorInfo();
				
				//@codeCoverageIgnoreStart
				throw new Exception(
					'Adding new entry to "'.$tableName.'" failed ['.
					$errorInfo[0].'-'.$errorInfo[1].']: '.$errorInfo[2]
				);
				//@codeCoverageIgnoreEnd
			}

			$primaryKeyName = $this->_primaryKeyName;
			$this->$primaryKeyName = $this->_connection->lastInsertId();

			return $this->$primaryKeyName;
		}
	}

	/**
	 * Deletes currently loaded entry from the database or by primary key given
	 * as parameter.
	 *
	 * If the parameter is set, it is used instead of primary key value in the
	 * model. If model does not contain primary key valye and it is not given
	 * as parameter, an exception is thrown.
	 *
	 * @param mixed $primaryKeyValue Optional primary key value to delete by
	 * @return boolean Was deleting the entry successful
	 * @throws Exception If deleting failed
	 */
	public function delete($primaryKeyValue = null) {
		$tableName = self::getTableName();
		$primaryKeyName = $this->_primaryKeyName;
		
		if ($primaryKeyValue === null) {
			$primaryKeyValue = $this->$primaryKeyName;
		}

		if ($primaryKeyValue === null) {
			throw new Exception(
				'Unable to delete entry, primary key valye has not been set'
			);
		}

		$rowsDeleted = $this->deleteWhere(
			array($primaryKeyName => $primaryKeyValue)
		);

		return $rowsDeleted === 1;
	}
	
	/**
	 * Deletes an entry by primary key value.
	 * 
	 * @param mixed $primaryKeyValue Primary key value
	 */
	public static function deleteByPK($primaryKeyValue) {
		$model = self::load($primaryKeyValue);
		
		if ($model !== null) {
			return $model->delete();
		} else {
			return false;
		}
	}

	/**
	 * Deletes entries by given conditions.
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
	 * @param array $where The conditions
	 * @return integer The number or rows deleted
	 * @throws Exception if something goes wrong
	 */
	public static function deleteWhere(array $where) {
		return self::getInstance()->_deleteWhere($where);
	}

	/**
	 * Deletes entries by given conditions.
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
	 * @param array $where The conditions
	 * @return integer The number or rows deleted
	 * @throws Exception if something goes wrong
	 */
	public function _deleteWhere(array $where) {
		$tableName = self::getTableName();
		
		$whereBind = array();

		$query = $this->_adapter->assembleDeleteWhereQuery($tableName, $where, $whereBind);

		$statement = $this->_prepare($query);

		foreach ($whereBind as $column => $value) {
			$statement->bindValue($column, $value);
		}

		$this->_lastBind = $whereBind;

		if (!$statement->execute()) {
			//@codeCoverageIgnoreStart
			throw new Exception(
				'Deleting entry from "'.$tableName.'" failed'
			);
			//@codeCoverageIgnoreEnd
		}

		return $statement->rowCount();
	}

	/**
	 * Finds rows from database that match conditions.
	 *
	 * Without the conditions, it just finds all entries.
	 *
	 * Notice that this method does not even actually prepare and execute a
	 * database statement, this is done the first time the data is used.
	 *
	 * You can use {@see PdoModel::count()} to find out how many rows this
	 * statement matched.
	 *
	 * If you wish to limit the results by some conditions, provide the $where
	 * array with keys of field names and values of required values. If you wish
	 * to use a predicate other than equals (=), you can define it for every
	 * condition by prepending it at the end of the column name with a colon. Conditions may include {@see SqlExpr}.
	 * For example:
	 *
	 * array(
	 *	'age' => 23,
	 *	'deleted:<>' => 0,
	 *	'friends:>' => 3
	 * )
	 *
	 * This would return everyone that is 23 years old, NOT deleted and has more
	 * than three friends.
	 *
	 * Line like "'friends:>' => 3" can be read as "friends is greater than 3".
	 *
	 * The $order is expected to a string like "`name` ASC, `age` DESC". It is
	 * quoted for security should you get this information from user.
	 *
	 * As this class implements Iterator, you can just call this method and not
	 * even store the result anywhere but rather just iterate over the model to
	 * get the results. For example, this works:
	 *
	 * $userModel = new UserModel();
	 *
	 * $userModel->find(array(
	 *     'name:<>' => 'John Smith',
	 *     'email:<>' => 'chuck@norris.com',
	 *     'id:>' => 1
	 * ), '`name` ASC, `email` DESC');
	 *
	 * foreach ($userModel as $key => $user) {
	 *     // do something with the user
	 * }
	 *
	 * @param array|null $where The WHERE conditions
	 * @param string|null $order The order statements
	 * @return PdoModel The model
	 * @throws Exception if something goes wrong
	 */
	public static function find(array $where = null, $order = null) {
		$model = self::getInstance();
		$model->_find($where, $order);
		
		return $model;
	}
	
	/**
	 * Finds rows from database that match conditions.
	 *
	 * Without the conditions, it just finds all entries.
	 *
	 * Notice that this method does not even actually prepare and execute a
	 * database statement, this is done the first time the data is used.
	 *
	 * You can use {@see PdoModel::count()} to find out how many rows this
	 * statement matched.
	 *
	 * If you wish to limit the results by some conditions, provide the $where
	 * array with keys of field names and values of required values. If you wish
	 * to use a predicate other than equals (=), you can define it for every
	 * condition by prepending it at the end of the column name with a colon. Conditions may include {@see SqlExpr}.
	 * For example:
	 *
	 * array(
	 *	'age' => 23,
	 *	'deleted:<>' => 0,
	 *	'friends:>' => 3
	 * )
	 *
	 * This would return everyone that is 23 years old, NOT deleted and has more
	 * than three friends.
	 *
	 * Line like "'friends:>' => 3" can be read as "friends is greater than 3".
	 *
	 * The $order is expected to a string like "`name` ASC, `age` DESC". It is
	 * quoted for security should you get this information from user.
	 *
	 * As this class implements Iterator, you can just call this method and not
	 * even store the result anywhere but rather just iterate over the model to
	 * get the results. For example, this works:
	 *
	 * $userModel = new UserModel();
	 *
	 * $userModel->find(array(
	 *     'name:<>' => 'John Smith',
	 *     'email:<>' => 'chuck@norris.com',
	 *     'id:>' => 1
	 * ), '`name` ASC, `email` DESC');
	 *
	 * foreach ($userModel as $key => $user) {
	 *     // do something with the user
	 * }
	 *
	 * @param array|null $where The WHERE conditions
	 * @param string|null $order The order statements
	 * @return boolean Did everything go well
	 * @throws Exception if something goes wrong
	 */
	public function _find(array $where = null, $order = null) {
		$tableName = self::getTableName();
		$columns = self::getColumnNames();

		$query = 'SELECT `'.implode('`, `', $columns).'` FROM `'.$tableName.'`';

		$whereBind = array();

		if (is_array($where) && !empty($where)) {
			$query .= ' WHERE '.$this->_adapter->assembleConditions($where, $whereBind);
		}

		if ($order != null) {
			$query .= ' ORDER BY '.$this->_quote($order);
		}

		$this->_lastQuery = $query;
		$this->_lastBind = $whereBind;
		$this->_lastStatement = null;
		$this->_lastCount = null;
		$this->_resultset = null;
		
		return true;
	}

	/**
	 * Static method to find data by a SQL query.
	 *
	 * The result returned is an instance of PdoModel and can be iterated over
	 * or paginated.
	 *
	 * The query is not actually executed before the result is iterated over or
	 * counted.
	 * 
	 * The optional decorator is expected to be a callable function or method
	 * that is called for every row that is fetched with the row passed in
	 * by reference so the decorator may choose to change this data in any way.
	 *
	 * @param string $query The query to execute
	 * @param array $bind Values to bind to the query
	 * @param function $decorator Callback function to decorate every result
	 * @return PdoModel Instance of self
	 */
	public static function fetch(
		$query,
		array $bind = array(),
		$decorator = null
	) {
		$model = new self();
		
		$model->_lastQuery = $query;
		$model->_lastBind = $bind;
		$model->_decorator = $decorator;
		$model->_lastStatement = null;
		$model->_lastCount = null;
		$model->_resultset = null;

		return $model;
	}
	
	/**
	 * Fetches and returns a single item matching given query.
	 * 
	 * The result is the same as calling PdoModel::fetch()->getItem().
	 * 
	 * @param string $query The query to execute
	 * @param array $bind Values to bind to the query
	 * @param function $decorator Callback function to decorate every result
	 * @return array|null Array of data or null if nothing matches.
	 */
	public static function fetchOne(
		$query,
		array $bind = array(),
		$decorator = null
	) {
		return self::fetch($query, $bind, $decorator)->getItem();
	}
	
	/**
	 * Fetches and returns the first column value of first row.
	 * 
	 * @param string $query The query to execute
	 * @param array $bind Values to bind to the query
	 * @param function $decorator Callback function to decorate every result
	 * @return array|null Array of data or null if nothing matches.
	 */
	public static function fetchColumn(
		$query,
		array $bind = array(),
		$decorator = null
	) {
		$row = self::fetchOne($query, $bind, $decorator);
		
		return array_pop($row);
	}
	
	/**
	 * Executes a SQL query.
	 * 
	 * @param string $query The query to execute
	 * @param array $bind Values to bind to the query
	 * @return boolean Did executing the query succeed
	 */
	public static function execute($query, array $bind = array()) {
		$db = self::$_defaultConnection;
		
		if (!isset($db)) {
			throw new Exception(
				'Unable to execute query "'.$query.'", default database '.
				'connection has not been set'
			);
		}
		
		$statement = $db->prepare($query);
		
		foreach ($bind as $name => $value) {
			$statement->bindValue($name, $value);
		}
		
		return $statement->execute();
	}

	/**
	 * Quotes a string to be safely used in a query.
	 *
	 * @param string $string String to quote
	 * @return string The quoted string
	 */
	public static function quote($string) {
		return self::getInstance()->_quote($string);
	}

	/**
	 * Quotes a string to be safely used in a query.
	 *
	 * @param string $string String to quote
	 * @return string The quoted string
	 */
	public function _quote($string) {
		return $this->_adapter->quote($this->_connection, $string);
	}

	/**
	 * Returns the table name that corresponds to given model class.
	 *
	 * The transformation is done in following steps:
	 * 1. remove "Model" from the end of class name
	 * 2. add a underscore before every upper-case word except first
	 * 3. make everything lowercase
	 *
	 * So for example, class names to table names:
	 * - UserModel > user
	 * - ForumTopicsModel > forum_topics
	 *
	 * @param boolean $useCache Should cache be used to speed this up
	 * @return string The table name
	 */
	public static function getTableName($useCache = LS_USE_SYSTEM_CACHE) {
		$className = get_called_class();

		$cacheKey = 'lightspeed.pdo-model-table|'.$className;
		$tableName = $useCache ? Cache::fetchLocal($cacheKey) : false;

		if ($tableName !== false) {
			return $tableName;
		}

		$components = preg_split(
			'/([A-Z][^A-Z]*)/',
			substr($className, 0, -5),
			-1,
			PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
		);

		$tableName = mb_strtolower(implode('_', $components));

		Cache::storeLocal($cacheKey, $tableName, LS_TTL_MODEL_TABLE);

		return $tableName;
	}

	/**
	 * Returns the column names of the model.
	 *
	 * The column names are determined from public class fields. Use this method
	 * instead of the {@see PdoModel::$_columns} directly, because the column
	 * names are lazy-loaded when first requested.
	 *
	 * @return array
	 */
	public static function getColumnNames() {
		return array_keys(
			_get_class_public_vars(get_called_class())
		);
	}

	/**
	 * Rewinds the resultset to it's first element.
	 */
	public function rewind() {
		if (empty($this->_lastQuery)) {
			throw new Exception('Unable to rewind, no query has been set');
		}

		$this->_resultKey = 0;
        $this->_resultset = $this->createStatement(
			$this->_lastQuery,
			$this->_lastBind
		)->fetch(PDO::FETCH_ASSOC);
    }

	/**
	 * Returns current resultset value.
	 *
	 * @return mixed
	 */
    public function current() {
        return $this->_resultset;
    }

	/**
	 * Returns key of the element currently pointed to.
	 *
	 * @return mixed
	 */
    public function key() {
        return $this->_resultKey;
    }

	/**
	 * Advances the internal resultset pointer to next value.
	 * 
	 * @return mixed Value at next position or false if there are no more.
	 */
    public function next() {
		$this->_resultset = $this->_lastStatement->fetch(PDO::FETCH_ASSOC);
		$this->_resultKey++;

        return $this->_resultset !== false;
    }

	/**
	 * Returns whether current element pointed to is valud.
	 *
	 * @return boolean
	 */
    public function valid() {
		return $this->_resultset !== false;
    }

	/**
	 * Returns the number of items the last multirow-statement matched.
	 *
	 * The calls to this method are cached so the count-query is not executed
	 * unneedlessly and the cache is reset by calls to query-changing methods
	 * such as {@see PdoModel::find()} and {@see PdoModel::fetch()).
	 *
	 * @return integer
	 */
	public function count() {
		if ($this->_lastCount !== null) {
			return $this->_lastCount;
		}

		$this->_lastCount = $this->_adapter->count(
			$this->_connection,
			$this->_lastQuery,
			$this->_lastBind
		);

		return $this->_lastCount;
	}

	/**
	 * Returns $limit items starting from $offset. Offset is zero-based.
	 *
	 * @param integer $offset Offset from where to slice data from
	 * @param integer $limit Maximum number of items to take from offset
	 * @return array
	 */
	public function getItems($offset = 0, $limit = null) {
		if (!isset($limit)) {
			$limit = $this->count();
		}
		
		$lastQuery = trim($this->_lastQuery);

		if (empty($lastQuery)) {
			throw new Exception(
				'Unable to get items, there is no query, forgot to call find()?'
			);
		}

		$items = $this->_adapter->getItems(
			$this->_connection,
			$lastQuery,
			$this->_lastBind,
			$offset,
			$limit
		);
		
		if (isset($this->_decorator)) {
			if (!is_callable($this->_decorator)) {
				throw new Exception('Given fetch decorator is not callable');
			}
			
			foreach ($items as $key => $item) {
				$items[$key] = call_user_func_array(
					$this->_decorator,
					array($item)
				);
			}
		}
		
		return $items;
	}
	
	/**
	 * Returns $limit item first column values starting from $offset.
	 * 
	 * Offset is zero-based.
	 *
	 * @param integer $offset Offset from where to slice data from
	 * @param integer $limit Maximum number of items to take from offset
	 * @return array
	 */
	public function getColumns($offset = 0, $limit = null) {
		$data = $this->getItems($offset, $limit);
		$columns = array();
		
		foreach ($data as $row) {
			$columns[] = array_shift($row);
		}
		
		return $columns;
	}
	
	/**
	 * Returns the first items that matched the fetch query.
	 * 
	 * @return array|null Array of data or null if nothing was matched 
	 */
	public function getItem() {
		$items = $this->getItems(0, 1);
		
		if (is_array($items) && count($items) > 0) {
			return $items[0];
		} else {
			return null;
		}
	}

	/**
	 * Creates a statement based on given query and data to bind.
	 *
	 * @param string $query SQL query
	 * @param array $bind Data to bind
	 * @return PDOStatement
	 */
	public function createStatement($query, array $bind = array()) {
		$statement = $this->_prepare($query);

		if ($statement === false) {
			//@codeCoverageIgnoreStart
			throw new Exception(
				'Preparing find query "'.$query.'" failed'
			);
			//@codeCoverageIgnoreEnd
		}
		
		if (!empty($bind)) {
			foreach ($bind as $column => $value) {
				$statement->bindValue(':'.$column, $value);
			}
		}

		if ($statement->execute() === false) {
			$errorInfo = $statement->errorInfo();
			
			//@codeCoverageIgnoreStart
			throw new Exception(
				'Executing statement for find query "'.$query.'" failed: ['.
				$errorInfo[0].'/'.$errorInfo[1].'] '.$errorInfo[2]
			);
			//@codeCoverageIgnoreEnd
		}

        return $statement;
	}
}

/**
 * Returns publicly available properties of a class.
 *
 * @param string $class_name Class name
 * @return array Public var names
 */
function _get_class_public_vars($class_name) {
	return get_class_vars($class_name);
}
