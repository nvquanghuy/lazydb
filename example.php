<?php
// Example usage of lazydb

require "lazydb.php";

// Please modify the below to fit your database config
// host, username, pw, database
$db = new LazyDB("localhost", "root", "root", "test");


print "1. Attemping to create table `students`...<br/>";
$db->query("DROP TABLE IF EXISTS `students`");

$sql = "
  CREATE TABLE `students` (
    `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(55) NOT NULL,
    `email` VARCHAR(55) NOT NULL,
    `created` DATETIME,
    `data` TEXT
  )
";
$db->query($sql);
print "..created.<br/><br/>";


print "2. Inserting data using <code>\$db->insert</code> and <code>\$db->insert_batch</code> <br/><br/>";
// Single insertion

$data = array(
  'age'     => 20,
  'gender'  => 'male',
  'desc'    => "I love brian o'connell"
);

$student = array(
  'name'    => "Johny",
  'email'   => 'john@random.email',
  'created' => LazyDB::E('NOW()'),
  'data'    => $data
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

$student['name'] = "Peter O' Really";
$student['email'] = "peter@random.email";
$db->update("students", $student, "id = $student_id");  

print "3. Get All students using <code>\$db->query_select</code><br/>";
// query the data out
$students = $db->query_select("SELECT * FROM students");
print "<pre>" . print_r($students, true) . "</pre><br/>";


print "4. Get first student using <code>\$db->query_row</code><br/>";
// query 1 single row
$first_student = $db->query_row("SELECT * FROM students ORDER BY `id` ASC LIMIT 0, 1");
$first_student['data'] = unserialize($first_student['data']);
print "<pre>" . print_r($first_student, true) . "</pre><br/>";


print "5. Get student names using <code>\$db->query_col</code><br/>";  
// query multiple rows of 1 field
$names = $db->query_col("SELECT name FROM students");
print "<pre>" . print_r($names, true) . "</pre>";


print "6. Get 1 random student name using <code>\$db->query_scalar</code><br/>";
// query 1 single value
print "Random student name: <code>" . $db->query_scalar("SELECT name FROM students ORDER BY RAND() LIMIT 0, 1") . "</code>";


// BATCH UPDATING
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

  
?>
