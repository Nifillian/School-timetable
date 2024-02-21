<?php

require_once 'setting.php';

// Connect to SQL
$connection = new mysqli($host, $user, $pass, $data);
if ($connection->connect_error) die('Error connection');

// Get search value from GET request
$search = $_GET['search'];

// Search for matching classes
$sql_cl = "SELECT id, name FROM classes WHERE name LIKE '%$search%'";
$result_cl = $connection->query($sql_cl);

// Search for matching teachers
$sql_tc = "SELECT id, name FROM teachers WHERE name LIKE '%$search%'";
$result_tc = $connection->query($sql_tc);

// Search for matching cabinets
$sql_cb = "SELECT id, name FROM cabinets WHERE name LIKE '%$search%'";
$result_cb = $connection->query($sql_cb);

// Display results for matching classes
if ($result_cl->num_rows > 0) {
  while ($row = $result_cl->fetch_assoc()) {
    echo "<li><a href='class.php?id=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
  }
}

// Display results for matching teachers
if ($result_tc->num_rows > 0) {
  while ($row = $result_tc->fetch_assoc()) {
    echo "<li><a href='teacher_table.php?id=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
  }
}

// Display results for matching cabinets
if ($result_cb->num_rows > 0) {
  while ($row = $result_cb->fetch_assoc()) {
    echo "<li><a href='cabinet_table.php?id=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
  }
}

// Close SQL connection
$connection->close();
