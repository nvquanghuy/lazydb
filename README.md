LazyDB is an simple, elegant PHP mySQL database library. As the name suggests, it's meant for lazy developers and requires minimal amount of PHP code to work with mySQL database.

**Features:**
+   Fetch and transform table's records into PHP array.
+   Batch insert, batch update
+   Log SQL error to file
+   Automatic escaping of string value (through `mysql_real_escape_string`)

See how it works below

Select Data
----------
The simpicity of LazyDB is in its rich set of functions to help grab data in different formats and parse them into PHP array.


**Query list of records**
```php
$students = $db->query_select("SELECT * FROM students");

foreach ($students as $student) {
  // Deal with $student
}
```

**List of records, with array's key to be 1 field**
```php
$students = $db->query_select_manualkey("SELECT * FROM students", "email");

foreach ($students as $email => $student) {
  // Deal with this student
}
```

**Query only 1 record**
```php
$first_student = $db->query_row("SELECT * FROM students ORDER BY `id` ASC LIMIT 0, 1");

// Deal with $first_student
```

**Query only 1 column**
```php
$names = $db->query_col("SELECT name FROM students");

foreach ($names as $name) {
  // Deal with $name
}
```

**Return single scalar value**

```php
$random_name = $db->query_scalar("SELECT name FROM students ORDER BY RAND() LIMIT 0, 1");
print $random_name;
```

**In summary**, pick one of the following functions to query data effectively:
* `query_select`
* `query_select_manualkey`
* `query_row`
* `query_col`
* `query_scalar`

Insert Data Using Array
-----------------------
Use `$db->insert`:

```php
  // Single insert
  $student = array(
    'name'    => 'Johny',
    'email'   => 'john@random.email',
  );
  $student_id = $db->insert("students", $student);
```

Batch Insert
------------
Use `$db->insert_batch`:

```php
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
Use `$db->update`

```php
    $student = $db->query_row("SELECT * FROM students WHERE id = 1");
    $student['name'] = "Peter O' Really";
    $student['email'] = "peter@email.com";
    $db->update("students", $student, "id = 1");
```

Batch Update
------------
Use `$db->insert_batch` and make use of SQL syntax `INSERT ... ON DUPLICATE KEY` as follows:

```php
    $students = $db->query_select("SELECT * FROM students");

    // Assuming we'll get 3 records, change the records' values now
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


Storing array in fields
---------------------------------------
LazyDB automatically serializes and deserializes PHP array when storing and retrieving from database:

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
