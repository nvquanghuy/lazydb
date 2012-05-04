LazyDB is an simple, elegant PHP mySQL database library. As the name suggests, it requires minimal line of PHP code.

**Features:**
+   Fetch and transform table's records into PHP array.
+   Batch insert, batch update
+   Log SQL error to file
+   Automatic escaping of string value (through `mysql_real_escape_string`)

See how it works below

Select Data
----------
The simpicity of LazyDB is in its rich set of functions to help grab data in different formats and parse them into PHP array.


Return the resultset as an array of records:

```php
    $students = $db->query_select("SELECT * FROM students");
    print_r($students);
```
```
    Array(
      [0] => Array (
              [id] => 1
              [name] => Peter O' Really
              [email] => peter@email.com
          )
      [1] => Array (
              [id] => 2
              [name] => Alexander the 1-th
              [email] => alex_1@email.com
          )
    )
```

If you want the array's keys to be a table's column (instead of increasing number), use `query_select_manualkey` and supply the column name:

```php
    $students = $db->query_select_manualkey("SELECT * FROM students", "email");
    print_r($students);
```
```
    Array (
      [peter@email.com] => Array (
              [id] => 1
              [name] => Peter O' Really
              [email] => peter@email.com
          )
      [alex_1@email.com] => Array (
              [id] => 2
              [name] => Alexander the 1-th
              [email] => alex_1@email.com
          )
    )
```

Select 1 row:

```php
    $first_student = $db->query_row("SELECT * FROM students ORDER BY `id` ASC LIMIT 0, 1");
```
```
Array (
  [id] => 1
  [name] => Johny
  [email] => john@email.com
)
```

Select 1 column:

```php
    $names = $db->query_col("SELECT name FROM students");
    print_r($names);
```
``` 
    Array (
      [0] => Johny
      [1] => Alexander the 1-th
      [2] => Alexander the 2-th
    )
```

Return a single scalar value:

```php
    $random_name = $db->query_scalar("SELECT name FROM students ORDER BY RAND() LIMIT 0, 1");
    print $random_name;
```

    John


In summary, pick one of the following functions to query data effectively:
* `query_select`
* `query_select_manualkey`
* `query_row`
* `query_col`
* `query_scalar`

Insert Data
-----------

Use `$db->insert` and `$db->insert_batch` for data insertion:

```php
  // Single insert
  $student = array(
    'name'    => 'Johny',
    'email'   => 'john@random.email',
  );
  $student_id = $db->insert("students", $student);

  // Batch insert
  $students = array();
  for ($i = 1; $i <= 2; $i++) {
    $students[] = array(
      'name'  => "Alexander the {$i}-th", 
      'email' => "alex_{$i}@email.com"
    );
  }
  $db->insert_batch("students", $students);
```

Update Data
-----------
Use `$db->update` to update record.

```php
    $student = $db->query_row("SELECT * FROM students WHERE id = 1");
    $student['name'] = "Peter O' Really";
    $student['email'] = "peter@email.com";
    $db->update("students", $student, "id = 1");
```


To perform batch update, use `$db->insert_batch` and make use of SQL syntax `INSERT ... ON DUPLICATE KEY` as follows:

```php
    $students = $db->query_select("SELECT * FROM students");

    // Assuming we'll get 3 records, change the records' values and update them back
    $students[0]['name'] = "Alice";
    $students[0]['email'] = "alice@random.email";
    $students[1]['name'] = "Bob";
    $students[1]['email'] = "bob@random.email";
    $students[2]['name'] = "Clara";
    $students[2]['email'] = "clara@random.email";

    // BATCH UPDATE
    $db->insert_batch("students", $students, "ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `email` = VALUES(`email`)");
```


General Query
-------------
If none of the above fits:
```php
  // Drop table
  $db->query("DROP TABLE IF EXISTS `students`");
  // Delete record
  $db->query("DELETE FROM `students` WHERE id = 1");
```

Insert mySQL's expression as field's data
---------------------------------------
If you want to insert a mysql's expression as a field's value, wrap the expression with `LazyDB::E($exp)`. For example:

    INSERT INTO students (`name`, `created`) VALUES ('John', NOW());

become:

```php
  $student  = array(
    'name'      => 'John',
    'created'   => LazyDB::E('NOW()')
  );
  $student_id = $db->insert('students', $student);
```


Automatic serialization of array value:
---------------------------------------
```php

  $extra = array(
    'age'  => 20,
    'gender' => male
  );

  $student = array(
    'name'    => 'John',
    'email'   => 'john@email.com',
    'extra'    => $extra, // will automatically be serialized
  );
  $student_id = $db->insert("students", $student);

  // Now query that data
  $student = $db->query_single("SELECT * FROM students WHERE id = $student_id");
  print "Age: " . $student['extra']['age'];
```



Download & Usage
----------------
1/ Download and extract: https://github.com/nvquanghuy/lazydb/zipball/master

2/ Modify database information in example.php. Run it to see a demo. 

3/ If you want to log down SQL errors, create folder `errorlogs` in webroot and make it writable.

4/ Initiate a lazydb object to connect to the database server using:

```php
    require "lazydb.php";
    $db = new LazyDB("localhost", "root", "", "test");
```

Feedback & Suggestions
----------------------
You're welcome to fork, comments, give feedback or suggestion at nvquanghuy@gmail.com
