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
    $sql = "SELECT substitutions.id, substitutions.number, subjects.name AS subjects, cabinets.name AS cabinets, teachers.name AS teachers, substitutions.date_of_day AS date
            FROM substitutions
            JOIN subjects ON substitutions.subject_id = subjects.id
            JOIN cabinets ON substitutions.cabinet_id = cabinets.id
            JOIN teachers ON substitutions.teacher_id = teachers.id
            WHERE substitutions.class_id = '$class_id'
            AND substitutions.date_of_day BETWEEN '$startOfWeek' AND '$endOfWeek'
            ORDER BY substitutions.date_of_day ASC, substitutions.number ASC";
    $result = $connection->query($sql);

    if (!$result) {
        die("Error executing query: " . $connection->error);
    }

    return $result;
}

// Function to check if the cabinet is available for the lesson and date
function isCabinetAvailable($connection, $cabinet_id, $date, $number, $substitution_id)
{
    $sql = "SELECT id FROM substitutions WHERE cabinet_id = '$cabinet_id' AND date_of_day = '$date' AND number = '$number' AND id != '$substitution_id'";
    $result = $connection->query($sql);

    if ($result && $result->num_rows > 0) {
        return false; // Cabinet is not available
    }

    return true; // Cabinet is available
}

// Function to check if the teacher is available for the lesson and date
function isTeacherAvailable($connection, $teacher_id, $date, $number, $substitution_id)
{
    $sql = "SELECT id FROM substitutions WHERE teacher_id = '$teacher_id' AND date_of_day = '$date' AND number = '$number' AND id != '$substitution_id'";
    $result = $connection->query($sql);

    if ($result && $result->num_rows > 0) {
        return false; // Teacher is not available
    }

    return true; // Teacher is available
}

// Update class schedule if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $substitutions = $_POST['substitutions'];

    foreach ($substitutions as $substitution_id => $substitution) {
        $number = $substitution['number'];
        $subject = $substitution['subjects'];
        $cabinet = $substitution['cabinets'];
        $teacher = $substitution['teachers'];

        // Get the date of the substitution from the database
        $sql = "SELECT date_of_day FROM substitutions WHERE id = '$substitution_id'";
        $result = $connection->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $date = $row['date_of_day'];
        } else {
            echo "Substitution not found.";
            exit;
        }

        // Check if the cabinet and teacher are available
        if (!isCabinetAvailable($connection, $cabinet, $date, $number, $substitution_id)) {
            header("Location: update_substitution.php?id=$class_id");
            echo "Кабинет недоступен в это время и дату.";
            exit;
        }

        if (!isTeacherAvailable($connection, $teacher, $date, $number, $substitution_id)) {
            header("Location: update_substitution.php?id=$class_id");
            echo "Преподаватель недоступен в это время и дату.";
            exit;
        }

        // Update the substitution in the database
        $sql = "UPDATE substitutions SET number = '$number', subject_id = '$subject', cabinet_id = '$cabinet', teacher_id = '$teacher' WHERE id = '$substitution_id'";
        $result = $connection->query($sql);

        if (!$result) {
            die("Error updating substitution: " . $connection->error);
        }
    }
    echo 'Замена успешно изменена';
    // Redirect back to the class schedule page
    header("Location: class.php?id=$class_id");
    exit;
}

function getCurrentWeekDays() {
    $currentDay = date('w'); // Номер текущего дня недели (0 - Воскресенье, 1 - Понедельник, и т.д.)
    $startOfWeek = date('Y-m-d', strtotime('-' . ($currentDay - 1) . ' days')); // Начало текущей недели (Понедельник)
    $endOfWeek = date('Y-m-d', strtotime('+' . (5 - $currentDay) . ' days')); // Конец текущей недели (Пятница)
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

$currentDay = date('w'); // Current day of the week (0 - Sunday, 1 - Monday, etc.)
$startOfWeek = date('Y-m-d', strtotime('-' . ($currentDay - 1) . ' days')); // Start of the current week (Monday)
$endOfWeek = date('Y-m-d', strtotime('+' . (5 - $currentDay) . ' days')); // End of the current week (Friday)

$class_data = getClassData($connection, $class_id, $startOfWeek, $endOfWeek);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Изменение замен</title>
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
<h1 class="head-updt-sub">Изменение замен класса <?php echo $class_name ?> класса</h1>

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
                    $substitution_id = $row['id'];
                    $substitution_number = $row['number'];
                    $subject = $row['subjects'];
                    $cabinet = $row['cabinets'];
                    $teacher = $row['teachers'];
                    $date = $row['date'];

                    echo "<tr class=upd>";
                    echo "<td><input type='text' name='substitutions[$substitution_id][number]' value='$substitution_number' autocomplete='off'></td>";
                    echo "<td>";
                    echo "<select name='substitutions[$substitution_id][subjects]'>";

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
                    echo "<select name='substitutions[$substitution_id][cabinets]'>";
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
                    echo "<select name='substitutions[$substitution_id][teachers]'>";
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
    </table>
    </form>
    </div>
</body>
</html>
