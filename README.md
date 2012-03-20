LazyDB class is equipped with functions that help PHP programmers interact with database easier. Currently LazyDB supports PHP5 (preferbly 5.3 and above) and mySQL.

Features
========

 * Minimize the amount of php code needed
 * Pre-made functions to support SELECT, INSERT, UPDATE queries.
 * Batch insertion.
 * Batch updating.
 * Supports logging SQL error to a log file so that sysadmin can easily debug and avoiding exposing database structure to visitors.
 * Automatic detect string value passed to escape (using `mysql_real_escape_string`)



Using LazyDB
============

The following example uses a database `test` with the following sample table:

    CREATE TABLE `students` (
      `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
      `name` VARCHAR(55) NOT NULL,
      `email` VARCHAR(55) NOT NULL
    )

Copy lazydb.php to any folder in your application. Create a folder name `errorlogs` in your web app and make it writeable. This folder will contain sql error logs from the app. Initiate a lazydb object to connect to the database server using:

    require "lazydb.php";
    
    // if you only have 1 database to work on
    $db = new LazyDB("localhost", "root", "", "test");
    
    // or multiple databases
    $db = new LazyDB("localhost", "root", "");
    // ...
    // Interact with database server like $db->get_databases()
    // ...
    $db->select_db("test"); // select specific database when needed

To insert a record into database, we use `$db->insert` and `$db->insert_batch` for batch insertion. The following code insert 3 students to the database.

    // Single insertion

    $data = array(
      'age'  => 20,
      'gender' => male,
    );

    $student = array(
      'name'    => 'Johny',
      'email'   => 'john@random.email',
      'created' => LazyDB::createExpression('NOW()'),
      'data'    => $data, // will automatically be serialized
    );
    $student_id = $db->insert("students", $student);
    
    // Batch insertions
    $students = array();
    for ($i = 1; $i <= 2; $i++) {
      $students[] = array(
        'name'  => "Alexander the {$i}-th", 
        'email' => "alex_{$i}@random.email",
      );
    }
    $db->insert_batch("students", $students);

To update a record:

    $student['name'] = "Peter O' Really";
    $student['email'] = "peter@random.email";
    $db->update("students", $student, "id = $student_id");

Note: You don't need to do quote escaping on string values, lazydb is smart enough to check if value passed is string and apply `mysql_real_escape_string` to it.



To execute SELECT statement and return the resultset as an array of rows:

    $students = $db->query_select("SELECT * FROM students");
    print "<pre>" . print_r($students, true) . "</pre>";

    "Array
    (
      [0] => Array
          (
              [id] => 1
              [name] => Peter O' Really
              [email] => peter@random.email
          )

      [1] => Array
          (
              [id] => 2
              [name] => Alexander the 1-th
              [email] => alex_1@random.email
          )

      [2] => Array
          (
              [id] => 3
              [name] => Alexander the 2-th
              [email] => alex_2@random.email
          )
    )"

If you want the keys of the array to take database's value instead of default increasing number

    $students = $db->query_select_manualkey("SELECT * FROM students", "email");
    print "<pre>" . print_r($students, true) . "</pre>";

    "Array
    (
      [peter@random.email] => Array
          (
              [id] => 1
              [name] => Peter O' Really
              [email] => peter@random.email
          )

      [alex_1@random.email] => Array
          (
              [id] => 2
              [name] => Alexander the 1-th
              [email] => alex_1@random.email
          )

      [alex_2@random.email] => Array
          (
              [id] => 3
              [name] => Alexander the 2-th
              [email] => alex_2@random.email
          )
    )"


Execute SELECT statement that only gets 1 row and return that row as an php array.

    $first_student = $db->query_row("SELECT * FROM students ORDER BY `id` ASC LIMIT 0, 1");
    print "<pre>" . print_r($first_student, true) . "</pre>";
    
    "Array
    (
      [id] => 1
      [name] => Johny
      [email] => john@random.email
    )"


Execute SELECT statement that only query 1 field. Returning an array contains values of that field.


    $names = $db->query_col("SELECT name FROM students");
    print "<pre>" . print_r($names, true) . "</pre>";
    
    "Array
    (
      [0] => Johny
      [1] => Alexander the 1-th
      [2] => Alexander the 2-th
    )"


Execute SELECT statement that returns 1 single scalar value:

    $random_name = $db->query_scalar("SELECT name FROM students ORDER BY RAND() LIMIT 0, 1");
    print "Random student name: $random_name";


*And if your query doesn't fall into any of the above functions, you can always use `$db->query($sql)` which ultimately will call `mysql_query` function, for example to initially create the database:*

  $db->query("DROP TABLE IF EXISTS `students`");
  $sql = "
    CREATE TABLE `students` (
      `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
      `name` VARCHAR(55) NOT NULL,
      `email` VARCHAR(55) NOT NULL
    )
  ";
  $db->query($sql);



Some other functions:

* `select_db($dbname)`
* `array get_databases()`
* `array get_tables()`
* `get_insert_id()`
* `fetch_row()`
* `fetch_num()`
* `get_affected_rows()`
* `get_num_rows()`


Batch Updating
Currently the library doesn't allow batch updates, but you could easily do that by utilizing the syntax `INSERT ... ON DUPLICATE KEY `, as shown below:

    $students = $db->query_select("SELECT * FROM students");
    // Assuming we'll get 3 records
    $students[0]['name'] = "Alice";
    $students[0]['email'] = "alice@random.email";
    $students[1]['name'] = "Bob";
    $students[1]['email'] = "bob@random.email";
    $students[2]['name'] = "Clara";
    $students[2]['email'] = "clara@random.email";

    // BATCH UPDATE
    $db->insert_batch("students", $students, "ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `email` = VALUES(`email`)");

 

