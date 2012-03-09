Lightspeed-PHP PDO database layer
=================================

![Lightspeed-PHP logo](http://lightspeed-php.com/images/logo.png "Lightspeed-PHP")

**[LIGHTSPEED-PHP](http://lightspeed-php.com) IS A MINIMALISTIC AND FAST PHP FRAMEWORK** aiming to provide basic structure that helps you build your applications faster and more efficiently on solid architecture. It's designed to be small, fast, easy to understand and extend.


How to install
--------------
Simply download the archive and unpack it to the root directory of your project. Creates a "pdo" and "data-source" directories under "library".


How to set it up
----------------
You will need to include and initiate it in your bootstrap.

* Open Bootstrap.php in your "application" folder.
* Add the following lines to somewhere at the top to include it:

```php
// Get pdo model and database adapter
require_once LIBRARY_PATH.'/pdo/PdoModel.php';
require_once LIBRARY_PATH.'/pdo/Adapters/MysqlDbAdapter.php';
```

* Now we need to open the connection and set the PdoModel default connection. First create the PDO variable in bootstrap class:
```php
/**
 * PDO database connection.
 * 
 * @var PDO
 */
private $pdo;
```
*Now create a new method into your application/Bootstrap.php:
/**
 * Initiates a database connection.
 */
private function initDatabaseConnection() {
    $this->pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USERNAME, DB_PASSWORD
    );
 
    PdoModel::setDefaultAdapter(new MysqlDbAdapter());
    PdoModel::setDefaultConnection($this->pdo);
 
    $this->pdo->exec('SET NAMES UTF8');
}
This method needs to be called somehow, update the Boostrapper::bootstrapApplication() method and add the following line:

1
$this->initDatabaseConnection();
As you may have noticed, we are using constants to define the database authentication properties. Define these in your server-specific application/config/config.php file:

1
2
3
4
5
6
7
/**
 * Database configuration.
 */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'lightspeed');
define('DB_USERNAME', 'lightspeed');
define('DB_PASSWORD', '');
Make sure you replace the values according to your database privilege settings.

How to use it
You should name your models according to CamelCase convention and the filename should match the class name and end with "Model". By default, the model files are stored in /application/models.

Creating models
Each model corresponds to a database table. Extend the PdoModel class and create public member variables for all the columns in your table. As class variables have some naming limits, you should use underscore "_" for seperating words, the same for class name.

For example if you have a table:

1
2
3
4
5
CREATE TABLE `search_results` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `search_phrase` VARCHAR(255) UNSIGNED NOT NULL,
  `results` int(10) UNSIGNED NOT NULL
)
The corresponding model class in /application/models/SearchResultsModel.php would be at minimal:

1
2
3
4
5
class SearchResultsModel extends PdoModel {
    public $id;
    public $search_phrase;
    public $results;
}
The class name SearchResultsModel is mapped to "search_results" table name and the public members define the table columns. This is useful for auto-complete functionality of more advanced IDEs.

There are some methods in the PdoModel implementation that expects the primary key to be called "id". Should you call it anything else and still want to use methods like PdoModel::load(), you can set custom primary key name by setting $_primaryKeyName in your extended class.

Static methods
The PdoModel includes some useful static methods that can be used to quickly manipulate the database. Selection of the most useful ones include:

load($primaryKeyValue)

Very useful method for retrieving a single row from the database by primary key value. Override $_primaryKeyName if you dont use "id". Returns instance of the model if a row is found and null otherwise. Access the column values using the public class variables.

loadWhere(array $where)

Loads a single row from the database that match certain criteria. Read the method-level documentation to see exactly how to use it.

find(array $where = null, $order = null)

Searches for any number of rows matching given conditions. Does not actually fetch the results before the data is iterated over or you call getItems() method.

fetch($query, array $bind = array(), $decorator = null)

Very useful and similar to above but you build the query yourself, see the documentation for details. As it's not table-specific, you can call it directly on the PdoModel class, not any of the extended ones.

fetchOne($query, array $bind = array(), $decorator = null)

Same as above but instancly fetches and returns the first matching row.

fetchColumn($query, array $bind = array(), $decorator = null)

Fetches and returns the first column of the first matching row.

execute($query, array $bind = array())

Executes a generic database query, for example to update or delete something. Returns whether it was successful.

insert(array $populate)

Inserts a new row into the database. The model is populated from the given array, keys of which should match the column names. You could also create an instance of a model, fill in its column values and call save().

deleteByPK($primaryKeyValue)

Deletes a row by primary key values.

deleteWhere(array $where)

Deletes rows that match certain conditions. Read the method documentation on how to use the $where array.

setDefaultConnection(PDO $connection)

You will usually call it once in your bootstrapper to set the default connection that following instances of models will use. There is a getter for this too.

setDefaultAdapter(DbAdapter $adapter)

Similarly to the connection method above, you will usually call it once in your bootstrapper to set the default adapter that following instances of models will use.

beginTransaction(PDO $connection = null)

Starts a new transaction. As always, uses the default connection if not specified.

commit(PDO $connection = null)

Commits current transaction.

rollBack(PDO $connection = null)

Rolls back ongoing transaction.

If you already have an instance
Once you've got an instance of a model for example by calling $row = SearchResultsModel::load(1);, the following methods might be useful to know:

save(array $populate = null, $forceInsert = false)

Make whatever changes to your data and call this to store it. If the primary key column has a value, updating an existing row is attempted, else a new one is inserted.

populate(array $data)

Instead of setting each column value separately, you can set all of them using this method.

setNull($propertyName)

If you need to make a column value NULL, doing simple $model->column = null; wont have effect. Use this method instead.

getLastQuery()

If using any of the magic methods of this class, you can call this to see what query was last used. Useful for debugging. Use getLastBind() to get the data that was binded to the query.

SQL expressions as column values
Sometimes you want to include SQL expressions in your values and you don't want the model to escape these for you. To accomplish this, create an instance of SqlExpr class.

For example to update the modified date:

1
2
3
$model = UserModel::load($userId);
$model->modified_date = new SqlExpr('NOW()');
$model->save();
Other databases
Default implementation only includes an adapter for MySQL but since it's based on PDO, it can be easily extended to support other databases.