<?php
require_once 'setting.php';

$class_id = $_GET['class_id'] ?? NULL;

// Connect to SQL
$connection = new mysqli($host,$user,$pass,$data);
if ($connection->connect_error) die('Error connection');

$class_sql = "SELECT * FROM classes WHERE id = $class_id";
$res = mysqli_query($connection, $class_sql);

// Check if the query was successful and the class data was found
if ($res && mysqli_num_rows($res) > 0) {
    $class_data = mysqli_fetch_assoc($res);
    $class_name = $class_data['name'];
}

// Get the list of subjects
$sql_subjects = "SELECT * FROM subjects";
$result_subjects = mysqli_query($connection, $sql_subjects);

// Get the list of teachers
$sql_teachers = "SELECT * FROM teachers";
$result_teachers = mysqli_query($connection, $sql_teachers);

// Get the list of cabinets
$sql_cabinets = "SELECT * FROM cabinets";
$result_cabinets = mysqli_query($connection, $sql_cabinets);
?>

<!DOCTYPE html>
<html>
<head>
<title>Добавление урока</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>

<nav><a class=button onclick="goBack()">Назад</a></nav>
<script>
function goBack() {
  window.history.back();
}
</script>
<h1 class="head">Добавление урока классу <?php echo $class_name ?> класса</h1>

</header>
<div class=container-add>
<nav class=fields>
<form method="post">
    <label for="number">№ урока:</label>
    <input type="number" id="number" name="number" min="1" max="7" value="1-7" autocomplete="off" required><br>

    <input type="hidden" name="class_id" value="<?php echo $_GET['class_id'];?>">

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

    <label for="teacher">Преподаватель:</label>
    <select id="teacher" name="teacher" required>
        <?php while ($row = mysqli_fetch_assoc($result_teachers)) { ?>
            <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
        <?php } ?>
    </select><br>

    <label for="date">Дата:</label>
    <input type="date" id="date" name="date" required>


    <br><button type="submit">Добавить</button>
</form>
</nav>
</div>
</body>
</html>

<?php
// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $number = $_POST['number'] ?? NULL;
    $class_id = $_POST['class_id'] ?? NULL;
    $subject_id = $_POST['subject'] ?? NULL;
    $cabinet_id = $_POST['cabinet'] ?? NULL;
    $teacher_id = $_POST['teacher'] ?? NULL;
    $date_of_day = $_POST['date'] ?? NULL;

    // Check if the selected cabinet is free on this day and time
    $sql_check_cabinet = "SELECT * FROM lesson WHERE cabinet_id = $cabinet_id AND teacher_id = $teacher_id AND date_of_day = '$date_of_day' AND number = $number";
    $result_check_cabinet = mysqli_query($connection, $sql_check_cabinet);

    $sql_check_lesson = "SELECT * FROM lesson WHERE date_of_day = '$date_of_day' AND class_id = $class_id AND number = $number";
    $result_check_lesson = mysqli_query($connection, $sql_check_lesson);

    if (mysqli_num_rows($result_check_cabinet) == 0 && mysqli_num_rows($result_check_lesson) == 0) {
        // Insert the data into the database
        $sql = "INSERT INTO lesson (number, class_id, subject_id, cabinet_id, teacher_id, date_of_day) VALUES ('$number', '$class_id', '$subject_id', '$cabinet_id','$teacher_id', '$date_of_day')";
        if (mysqli_query($connection, $sql)) {
            echo 'New record created successfully.';
            exit; // Stop executing the rest of the script
        } else {
            echo 'Error: ' . mysqli_error($connection);
        }
    } else {
        echo "<br><p>Кабинет занят на эту дату и время.</p>";
    }
}
?>

