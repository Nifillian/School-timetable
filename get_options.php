<?php
require_once 'setting.php';

// Connect to SQL
$connection = new mysqli($host,$user,$pass,$data);
if ($connection->connect_error) die('Error connection');

if ($_GET['cret'] === 'Класс') {
    $sql_query = "SELECT id, name FROM classes";
} elseif ($_GET['cret'] === 'Урок') {
    $sql_query = "SELECT id, name FROM subjects";
} elseif ($_GET['cret'] === 'Кабинет') {
    $sql_query = "SELECT id, name FROM cabinets";
} elseif ($_GET['cret'] === 'Учитель') {
    $sql_query = "SELECT id, name FROM teachers";
}

$result = mysqli_query($connection, $sql_query);

$options = array();
while ($row = mysqli_fetch_assoc($result)) {
    $option = array(
        'value' => $row['name'],
        'text' => $row['name']
    );
    $options[] = $option;
}

header('Content-Type: application/json');
echo json_encode($options);
?>
