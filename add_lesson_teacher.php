<?php
require_once 'setting.php';

$teacher_id = $_GET['teacher_id'] ?? NULL;

// Connect to SQL
$connection = new mysqli($host,$user,$pass,$data);
if ($connection->connect_error) die('Error connection');

$teacher_sql = "SELECT * FROM teachers WHERE id = $teacher_id";
$res = mysqli_query($connection, $teacher_sql);

// Check if the query was successful and the teacher data was found
if ($res && mysqli_num_rows($res) > 0) {
    $teacher_data = mysqli_fetch_assoc($res);
    $teacher_name = $teacher_data['name'];
}

// Get the list of subjects
$sql_subjects = "SELECT * FROM subjects";
$result_subjects = mysqli_query($connection, $sql_subjects);

// Get the list of cabinets
$sql_cabinets = "SELECT * FROM cabinets";
$result_cabinets = mysqli_query($connection, $sql_cabinets);

// Get the list of classes
$sql_classes = "SELECT * FROM classes";
$result_classes = mysqli_query($connection, $sql_classes);

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $number = $_POST['number'] ?? NULL;
    $class_id = $_POST['class_id'] ?? NULL;
    $subject_id = $_POST['subject'] ?? NULL;
    $cabinet_id = $_POST['cabinet'] ?? NULL;
    $teacher_id = $_POST['teacher'] ?? NULL;
    $date_of_day = $_POST['date'] ?? NULL;

    // Insert the data into the database
    $sql = "INSERT INTO lesson (number, class_id, subject_id, cabinet_id, teacher_id, date_of_day) VALUES ('$number', '$class_id', '$subject_id', '$cabinet_id','$teacher_id', '$date_of_day')";
    if (mysqli_query($connection, $sql)) {
        echo 'New record created successfully.';
        header("Location: teacher_table.php?id=$teacher_id");
        exit(); // Stop executing the rest of the script
    } else {
        echo 'Error: ' . mysqli_error($connection);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Добавление урока</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<button onclick="goBack()">Назад</button>

<script>
function goBack() {
  window.history.back();
}
</script>
<h1>Добавление урока преподавателю <?php echo $teacher_name ?> </h1>
<form method="post">
    <label for="number">№ урока:</label>
    <input type="number" id="number" name="number" min="1" max="6" value="1-6" required><br>

    <label for="class">Класс:</label>
    <select id="class" name="class_id" required>
        <?php while ($row = mysqli_fetch_assoc($result_classes)) { ?>
            <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
        <?php } ?>
    </select><br>

    <label for="subject">Предмет:</label>
    <select id="subject" name="subject" required>
        <?php while ($row = mysqli_fetch_assoc($result_subjects)) { ?>
            <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
        <?php } ?>
    </select><br>

    <label for="cabinet">Кабинет:</label>
    <select id="cabinet" name="cabinet" required>
        <?php while ($row = mysqli_fetch_assoc($result_cabinets)) { ?>
            <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
        <?php } ?>
    </select><br>

    <input type="hidden" name="teacher" value="<?php echo $teacher_id;?>">

    <label for="date">Дата:</label>
    <input type="date" id="date" name="date" required
       min="<?php echo date('Y-m-d', strtotime('next Monday - 7 days')); ?>"
       max="<?php echo date('Y-m-d', strtotime('next Monday - 3 days')); ?>">

    <button type="submit">Добавить</button>
</form>
</body>
</html>
