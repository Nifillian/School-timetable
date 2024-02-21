<?php
require_once 'setting.php';

// Connect to SQL
$connection = new mysqli($host, $user, $pass, $data);
if ($connection->connect_error) {
    die('Error connecting to the database.');
}

// Get the class ID from the query string
if (isset($_GET['id'])) {
    $class_id = $_GET['id'];

    // Fetch the class data from the database based on the ID
    $sql = "SELECT * FROM classes WHERE id = $class_id";
    $result = $connection->query($sql);

    if ($result && $result->num_rows > 0) {
        $class_data = $result->fetch_assoc();
        $class_name = $class_data['name'];
    } else {
        echo "Class not found.";
        exit;
    }
} else {
    echo "Class ID not provided.";
    exit;
}

// Function to fetch class schedule data
function getClassData($connection, $class_id, $startOfWeek, $endOfWeek)
{
    $sql = "SELECT lesson.id, lesson.number, subjects.name AS subjects, cabinets.name AS cabinets, teachers.name AS teachers, lesson.date_of_day AS date
            FROM lesson
            JOIN subjects ON lesson.subject_id = subjects.id
            JOIN cabinets ON lesson.cabinet_id = cabinets.id
            JOIN teachers ON lesson.teacher_id = teachers.id
            WHERE lesson.class_id = '$class_id'
            AND lesson.date_of_day BETWEEN '$startOfWeek' AND '$endOfWeek'
            ORDER BY lesson.date_of_day ASC, lesson.number ASC";
    $result = $connection->query($sql);

    if (!$result) {
        die("Error executing query: " . $connection->error);
    }

    return $result;
}

// Update class schedule if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $lessons = $_POST['lessons'];
    $errors = array();

    foreach ($lessons as $lesson_id => $lesson) {
        $number = $lesson['number'];
        $subject = $lesson['subjects'];
        $cabinet = $lesson['cabinets'];
        $teacher = $lesson['teachers'];

        // Check for conflicts with other lessons
        $conflict_query = "SELECT id FROM lesson WHERE class_id = '$class_id' AND (cabinet_id = '$cabinet' OR teacher_id = '$teacher') AND date_of_day = (SELECT date_of_day FROM lesson WHERE id = '$lesson_id') AND id != '$lesson_id'";
        $conflict_result = $connection->query($conflict_query);

        if ($conflict_result && $conflict_result->num_rows > 0) {
            $errors[] = "Есть совпадение для урока № $number, учитель $teacher, кабинет $cabinet.";
        } else {
            // Update the lesson in the database
            $sql = "UPDATE lesson SET number = '$number', subject_id = '$subject', cabinet_id = '$cabinet', teacher_id = '$teacher' WHERE id = '$lesson_id'";
            $result = $connection->query($sql);

            if (!$result) {
                die("Error updating lesson: " . $connection->error);
            }
        }
    }

    if (!empty($errors)) {
        echo "<div class='error'>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        // Redirect back to the class schedule page
        header("Location: class.php?id=$class_id");
        exit;
    }
}

function getCurrentWeekDays() {
    $startOfWeek = '2023-05-29';
    $endOfWeek = '2023-06-03';
    $weekDays = array();

    $currentDate = $startOfWeek;
    while ($currentDate <= $endOfWeek) {
        $weekDays[] = $currentDate;
        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    }

    return $weekDays;
}

// Function to get the day of the week
function getDayOfWeek($date)
{
    $daysOfWeek = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    $dayOfWeekNumber = date('w', strtotime($date));

    return $daysOfWeek[$dayOfWeekNumber];
}

$startOfWeek = '2023-05-29';
$endOfWeek = '2023-06-03';

$class_data = getClassData($connection, $class_id, $startOfWeek, $endOfWeek);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Class Schedule</title>
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
<h1 class="head">Изменение уроков класса <?php echo $class_name ?> класса</h1>

</header>
<div class=container>
    <form method="POST">
    <nav class="edit">
        <input type="submit" name="save" value="Сохранить изменения">
    </nav>
    <?php
        
        $tables_by_day = array();

        while ($row = mysqli_fetch_assoc($class_data)) {
            $date = $row['date'];
            $dayOfWeek = getDayOfWeek($date);
            if (!isset($tables_by_day[$dayOfWeek])) {
                $tables_by_day[$dayOfWeek] = array();
            }
            $tables_by_day[$dayOfWeek][] = $row;
        }

        $weekDays = getCurrentWeekDays();

        foreach ($weekDays as $day) {
            $dayOfWeek = getDayOfWeek($day);
            $class_data = getClassData($connection, $class_id, $day, $day);

            if ($class_data->num_rows > 0) {
                echo "<h2 class=table>" . $dayOfWeek . "</h2>";
                echo "<table>";
                echo "<thead><tr><th class=numb_up> № </th><th> Урок </th><th> Кабинет </th><th> Учитель </th></tr></thead>";
                echo "<tbody>";
                while ($row = $class_data->fetch_assoc()) {
                    $lesson_id = $row['id'];
                    $lesson_number = $row['number'];
                    $subject = $row['subjects'];
                    $cabinet = $row['cabinets'];
                    $teacher = $row['teachers'];
                    $date = $row['date'];

                    echo "<tr class=upd>";
                    echo "<td><input type='text' name='lessons[$lesson_id][number]' value='$lesson_number' autocomplete='off'></td>";
                    echo "<td>";
                    echo "<select name='lessons[$lesson_id][subjects]'>";

                    // Fetch subjects from the database and generate options
                    $subject_query = "SELECT id, name FROM subjects";
                    $subject_result = mysqli_query($connection, $subject_query);

                    if ($subject_result && mysqli_num_rows($subject_result) > 0) {
                        while ($subject_row = mysqli_fetch_assoc($subject_result)) {
                            $subject_id = $subject_row['id'];
                            $subject_name = $subject_row['name'];
                            $selected = ($subject_name == $subject) ? "selected" : "";
                            echo "<option value='$subject_id' $selected>$subject_name</option>";
                        }
                    }

                    echo "</select>";
                    echo "</td>";
                    echo "<td>";
                    echo "<select name='lessons[$lesson_id][cabinets]'>";
                    // Fetch cabinets from the database and generate options
                    $cabinet_query = "SELECT id, name FROM cabinets";
                    $cabinet_result = mysqli_query($connection, $cabinet_query);

                    if ($cabinet_result && mysqli_num_rows($cabinet_result) > 0) {
                        while ($cabinet_row = mysqli_fetch_assoc($cabinet_result)) {
                            $cabinet_id = $cabinet_row['id'];
                            $cabinet_name = $cabinet_row['name'];
                            $selected = ($cabinet_name == $cabinet) ? "selected" : "";
                            echo "<option value='$cabinet_id' $selected>$cabinet_name</option>";
                        }
                    }

                    echo "</select>";
                    echo "</td>";
                    echo "<td>";
                    echo "<select name='lessons[$lesson_id][teachers]'>";
                    // Fetch teachers from the database and generate options
                    $teacher_query = "SELECT id, name FROM teachers";
                    $teacher_result = mysqli_query($connection, $teacher_query);

                    if ($teacher_result && mysqli_num_rows($teacher_result) > 0) {
                        while ($teacher_row = mysqli_fetch_assoc($teacher_result)) {
                            $teacher_id = $teacher_row['id'];
                            $teacher_name = $teacher_row['name'];
                            $selected = ($teacher_name == $teacher) ? "selected" : "";
                            echo "<option value='$teacher_id' $selected>$teacher_name</option>";
                        }
                    }

                    echo "</select>";
                    echo "</td>";
                    echo "</tr>";
                }
            }
            echo "</tbody>";
            echo "</table>";
        }
        ?>
    </table><br>
    </form>
    </div>
</body>
</html>
