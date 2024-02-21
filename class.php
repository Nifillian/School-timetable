<?php
  require_once 'setting.php';

  // Connect to SQL
  $connection = new mysqli($host,$user,$pass,$data);
  if ($connection->connect_error) die('Error connection');

  // Get the class ID from the query string
  if(isset($_GET['id'])) {
      $class_id = $_GET['id'];
      // Fetch the data of the class from the database based on the id
      $sql = "SELECT * FROM classes WHERE id = $class_id";
      $result = mysqli_query($connection, $sql);

      // Check if the query was successful and the class data was found
      if ($result && mysqli_num_rows($result) > 0) {
          $class_data = mysqli_fetch_assoc($result);
          $class_name = $class_data['name'];
      }
  } else {
      echo "Class ID not provided.";
      exit;
  }

  function getClassData($connection, $class_id, $startOfWeek, $endOfWeek) {
    $sql = "SELECT lesson.number, subjects.name AS subjects, cabinets.name AS cabinets, teachers.name AS teachers, lesson.date_of_day AS date
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

  function getSubstitutionClassData($connection, $class_id, $startOfWeek, $endOfWeek) {
    $sql = "SELECT substitutions.number, subjects.name AS subjects, cabinets.name AS cabinets, teachers.name AS teachers, substitutions.date_of_day AS date
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

  function getDayOfWeek($date) {
    $daysOfWeek = array('Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота');
    $dayOfWeekNumber = date('w', strtotime($date));
    return $daysOfWeek[$dayOfWeekNumber];
  }
  $startOfWeek = '2023-05-29';
  $endOfWeek = '2023-06-03';
  
  // Retrieve the data for the selected class
  $data = getClassData($connection, $class_id, $startOfWeek, $endOfWeek);

  $authorized = false; // Пользователь не авторизован по умолчанию
  if (isset($_COOKIE['auth'])) {
      $authData = explode(':', $_COOKIE['auth']);
      if (count($authData) === 2 && $authData[0] === 'admin' && $authData[1] === md5('admin:123')) {
          $authorized = true; // Пользователь успешно аутентифицирован
      }
  }
?>

<!DOCTYPE html>
<html>
<head>
  <title>Расписание <?php echo $class_name ?> класса</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>

  <nav><a class="button" class="head" href="index.php">Главная</a></nav>
  <h1 class="head">Расписание <?php echo $class_name ?> класса</h1>

    <nav><form action="add_lesson.php" method="post">
    <label class="table" for="showSubstitutions">
      <input class="check" type="checkbox" id="showSubstitutions" checked>
      Замены
    </label>
    </nav>

</header>
<div class=container>
  <?php if ($authorized): ?>
  <nav class="edit">
    <a class=button href='add_lesson.php?class_id=<?php echo $class_id; ?>'>Добавить урок</a></form>
    <a class=button href='update_lesson.php?id=<?php echo $class_id; ?>;'>Изменить урок</a>
    <a class=button href='add_substitution.php?class_id=<?php echo $class_id; ?>'>Добавить замену</a></form>
    <a class=button href='update_substitution.php?id=<?php echo $class_id; ?>;'>Изменить замену</a>
    <a class=button href='reports.php'>Отчеты</a>
  </nav>
  <?php endif;

    // Define an array to store the table data by date
  $tables_by_day = array();

    // Loop through the query result and group the data by date
  while ($row = mysqli_fetch_assoc($data)) {
    $date = $row['date'];
    $dayOfWeek = getDayOfWeek($date);
    if (!isset($tables_by_day[$dayOfWeek])) {
        $tables_by_day[$dayOfWeek] = array();
    }
    $tables_by_day[$dayOfWeek][] = $row;
  }

  // Получение списка будних дней текущей недели
  $weekDays = getCurrentWeekDays();

  $currentDaySub = date('w'); // Номер текущего дня недели (0 - Воскресенье, 1 - Понедельник, и т.д.)
  $startOfWeekSub = date('Y-m-d', strtotime('-' . ($currentDaySub - 1) . ' days')); // Начало текущей недели (Понедельник)
  $endOfWeekSub = date('Y-m-d', strtotime('+' . (5 - $currentDaySub) . ' days')); // Конец текущей недели (Пятница)

  $currentDateSub = $startOfWeekSub;

  // Цикл по будним дням текущей недели
  foreach ($weekDays as $day) {
    $dayOfWeek = getDayOfWeek($day);
    // Получение данных для текущего дня из базы данных phpMyAdmin
    $data = getClassData($connection, $class_id, $day, $day);

    $fullScheduleData = getClassData($connection, $class_id, $day, $day);
    $substitutionsData = getSubstitutionClassData($connection, $class_id, $currentDateSub, $currentDateSub);

    // Проверка, есть ли записи для текущего дня
    if ($data->num_rows > 0 || $substitutionsData->num_rows > 0) {
      echo "<h2 class=table>" . $dayOfWeek . "</h2>";
      echo "<table>";
      echo "<thead><tr><th class=numb> № </th><th> Время </th><th> Урок </th><th> Кабинет </th><th> Учитель </th></tr></thead>";
      echo "<tbody>";
      $regularLessons = array();
      $substitutionLessons = array();

      if ($substitutionsData->num_rows > 0) {
        while ($substitutionRow = $substitutionsData->fetch_assoc()) {
          $substitutionLessons[] = $substitutionRow;
        }
      }

      if ($fullScheduleData->num_rows > 0 ) {
        while ($fullScheduleRow = $fullScheduleData->fetch_assoc()) {
          $regularLessons[] = $fullScheduleRow;
        }
      }

      // Выводим уроки и замены в таблицу 
      for ($i=1; $i <= 7; $i++){
        foreach ($substitutionLessons as $substitutionLesson) {
          if ($substitutionLesson['number'] == $i){
            echo "<tr class='substitution' data-lesson-number=" . $i . " data-lesson-date=" . $dayOfWeek . ">";
            echo "<td class=numb>" . $substitutionLesson['number'] . "</td>";
            if ($substitutionLesson['number'] == 1) {
                echo "<td>8:30-9:15</td>";
            } elseif ($substitutionLesson['number'] == 2) {
                echo "<td>9:30-10:15</td>";
            } elseif ($substitutionLesson['number'] == 3) {
                echo "<td>10:30-11:15</td>";
            } elseif ($substitutionLesson['number'] == 4) {
                echo "<td>11:30-12:15</td>";
            } elseif ($substitutionLesson['number'] == 5) {
                echo "<td>12:35-13:20</td>";
            } elseif ($substitutionLesson['number'] == 6) {
                echo "<td>13:30-14:15</td>";
            } elseif ($substitutionLesson['number'] == 7) {
                echo "<td>14:25-15:10</td>";
            }
            echo "<td>" . $substitutionLesson['subjects'] . "</td>";
            echo "<td>" . $substitutionLesson['cabinets'] . "</td>";
            echo "<td>" . $substitutionLesson['teachers'] . "</td>";
            echo "</tr>";
          }
        }
        foreach ($regularLessons as $regularLesson) {
          if ($regularLesson['number'] == $i){
            echo "<tr class='regular-lesson' data-lesson-number=" . $i . " data-lesson-date=" . $dayOfWeek . ">";
            echo "<td class=numb>" . $regularLesson['number'] . "</td>";
            if ($regularLesson['number'] == 1) {
                echo "<td>8:30-9:15</td>";
            } elseif ($regularLesson['number'] == 2) {
                echo "<td>9:30-10:15</td>";
            } elseif ($regularLesson['number'] == 3) {
                echo "<td>10:30-11:15</td>";
            } elseif ($regularLesson['number'] == 4) {
                echo "<td>11:30-12:15</td>";
            } elseif ($regularLesson['number'] == 5) {
                echo "<td>12:35-13:20</td>";
            } elseif ($regularLesson['number'] == 6) {
                echo "<td>13:30-14:15</td>";
            } elseif ($regularLesson['number'] == 7) {
                echo "<td>14:25-15:10</td>";
            }
            echo "<td>" . $regularLesson['subjects'] . "</td>";
            echo "<td>" . $regularLesson['cabinets'] . "</td>";
            echo "<td>" . $regularLesson['teachers'] . "</td>";
            echo "</tr>";
          }
        }
      }
    }
    echo "</tbody>";
    echo "</table>";
    $currentDateSub = date('Y-m-d', strtotime($currentDateSub . ' +1 day'));
  }
  // Close the database connection
  $connection->close();

  ?>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      var showSubstitutionsCheckbox = document.getElementById("showSubstitutions");
      var substitutionRows = document.getElementsByClassName("substitution");
      var regularLessonRows = document.getElementsByClassName("regular-lesson");

      showSubstitutionsCheckbox.addEventListener("change", function() {
        var showSubstitutions = showSubstitutionsCheckbox.checked;

        // Определяем, какие строки замен скрыть или показать
        for (var i = 0; i < substitutionRows.length; i++) {
          var substitutionRow = substitutionRows[i];
          var lessonNumber = substitutionRow.getAttribute("data-lesson-number");
          var lessonDate = substitutionRow.getAttribute("data-lesson-date");
          var correspondingRegularLessonRow = document.querySelector(
            '.regular-lesson[data-lesson-number="' + lessonNumber + '"][data-lesson-date="' + lessonDate + '"]'
          );
          substitutionRow.style.display = showSubstitutions ? "table-row" : "none";
          correspondingRegularLessonRow.style.display = showSubstitutions ? "none" : "table-row";
        }
      });
      function hideMatchingLessons() {
        var substitutionRows = document.getElementsByClassName('substitution');
        var regularLessonRows = document.getElementsByClassName('regular-lesson');

        for (var i = 0; i < substitutionRows.length; i++) {
          var substitutionLessonNumber = substitutionRows[i].getAttribute('data-lesson-number');
          var substitutionLessonDate = substitutionRows[i].getAttribute('data-lesson-date');

          for (var j = 0; j < regularLessonRows.length; j++) {
              var regularLessonNumber = regularLessonRows[j].getAttribute('data-lesson-number');
              var regularLessonDate = regularLessonRows[j].getAttribute('data-lesson-date');

              if (substitutionLessonNumber === regularLessonNumber && substitutionLessonDate === regularLessonDate) {
                  regularLessonRows[j].style.display = 'none';
              }
            }
          }
      }
      // Скрыть совпадающие уроки при загрузке страницы
      hideMatchingLessons();
    });
  </script>
  </div>
</body>
</html>