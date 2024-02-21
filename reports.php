<?php
// Импортируем необходимые классы из библиотеки PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require __DIR__ . '/vendor/autoload.php';
require_once 'setting.php';

$connection = new mysqli($host, $user, $pass, $data);
if ($connection->connect_error) die('Error connection');

// создание SQL запроса для выборки преподавателей из базы данных
$sql_teachers = "SELECT id, name FROM teachers";

// выполнение запроса
$result_teachers = $connection->query($sql_teachers);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Отчеты</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>

<nav><a class=button-rep onclick="goBack()">Назад</a></nav>
<script>
function goBack() {
  window.history.back();
}
</script>
<h1 class="head-report">Отчет</h1>

</header>
<div class=container>
    <form method='post'>
    <nav class=report>
    <select class=upd name='teacher'>

    <?php // вывод опций для выбора преподавателя
    while($row_teachers = $result_teachers->fetch_assoc()) {
        echo "<option value='" . $row_teachers['id'] . "'>" . $row_teachers['name'] . "</option>";
    }?>
    </select><br>
    <input class=upd_date type='date' name='start_date'><br>
    <input class=upd_date type='date' name='end_date'><br>
    <input class=upd type='submit' name='submit' value='Показать'><br>
    <input class=upd type='submit' name='export' value='Сохранить в Excel'>
    </nav>
    </form>
    

<?php
// если кнопка нажата и даты введены
if(isset($_POST['submit']) && !empty($_POST['start_date']) && !empty($_POST['end_date']) && !empty($_POST['teacher'])) {
  
  // получение выбранных дат и преподавателя
  $start_date = $_POST['start_date'];
  $end_date = $_POST['end_date'];
  $teacher = $_POST['teacher'];
  $stmt_teacher = $connection->prepare("SELECT name FROM teachers WHERE id = ?");
  $stmt_teacher->bind_param("i", $teacher);
  $stmt_teacher->execute();
  $stmt_teacher->bind_result($teacher_name);
  $stmt_teacher->fetch();
  $stmt_teacher->close();

  // форматирование дат в нужный вид
  $start_date = date('Y-m-d', strtotime($start_date));
  $end_date = date('Y-m-d', strtotime($end_date));

  // создание SQL запроса для выборки записей из базы данных
  $sql = "SELECT class_id, COUNT(*) AS num_lessons, classes.name AS class_name FROM (
          SELECT class_id, teacher_id, date_of_day FROM lesson
          UNION ALL
          SELECT class_id, teacher_id, date_of_day FROM substitutions
        ) AS combined
        JOIN classes ON combined.class_id = classes.id
        WHERE teacher_id = '$teacher' AND date_of_day >= '$start_date' AND date_of_day <= '$end_date'
        GROUP BY class_id";

  // выполнение запроса
  $result = $connection->query($sql);

  // проверка наличия результатов
  if ($result->num_rows > 0) {
    // вывод заголовка таблицы
    $months = array(
      'январь',
      'февраль',
      'март',
      'апрель',
      'май',
      'июнь',
      'июль',
      'август',
      'сентябрь',
      'октябрь',
      'ноябрь',
      'декабрь'
    );

    $start_day = date('d', strtotime($start_date));
    $start_month = $months[date('n', strtotime($start_date)) - 1];
    $start_year = date('Y', strtotime($start_date));
    $end_day = date('d', strtotime($end_date));
    $end_month = $months[date('n', strtotime($end_date)) - 1];
    $end_year = date('Y', strtotime($end_date));

    echo "<h2 class=table>" . htmlspecialchars($teacher_name) . " (" . $start_day . " " . $start_month . " " . $start_year . " - " . $end_day . " " . $end_month . " " . $end_year . ")</h2>";

    echo "<table>";
    echo "<thead><tr><th>Класс</th><th>Количество часов</th></tr></thead>";
  
    // цикл по результатам запроса
    while($row = $result->fetch_assoc()) {
      // вывод строки таблицы
      echo "<tr class=report><td>" . $row["class_name"] . "</td><td>" . $row["num_lessons"] . "</td></tr>";
    }
    
    // вывод конца таблицы
    echo "</table>";
  } else {
    echo "No results found";
  }
}

// Если кнопка "Сохранить в Excel" нажата
if (isset($_POST['export'])) {
  // получение выбранных дат и преподавателя
  $start_date = $_POST['start_date'];
  $end_date = $_POST['end_date'];
  $teacher = $_POST['teacher'];
  $stmt_teacher = $connection->prepare("SELECT name FROM teachers WHERE id = ?");
  $stmt_teacher->bind_param("i", $teacher);
  $stmt_teacher->execute();
  $stmt_teacher->bind_result($teacher_name);
  $stmt_teacher->fetch();
  $stmt_teacher->close();

  // форматирование дат в нужный вид
  $start_date = date('Y-m-d', strtotime($start_date));
  $end_date = date('Y-m-d', strtotime($end_date));

  // создание SQL запроса для выборки записей из базы данных
  $sql = "SELECT class_id, COUNT(*) AS num_lessons, classes.name AS class_name FROM (
          SELECT class_id, teacher_id, date_of_day FROM lesson
          UNION ALL
          SELECT class_id, teacher_id, date_of_day FROM substitutions
        ) AS combined
        JOIN classes ON combined.class_id = classes.id
        WHERE teacher_id = '$teacher' AND date_of_day >= '$start_date' AND date_of_day <= '$end_date'
        GROUP BY class_id";

  // выполнение запроса
  $result = $connection->query($sql);

  // проверка наличия результатов
  if ($result->num_rows > 0) {
  // Создаем новый объект Spreadsheet
  $spreadsheet = new Spreadsheet();
  $sheet = $spreadsheet->getActiveSheet();

  // Устанавливаем заголовки столбцов
  $sheet->setCellValue('A1', 'Класс');
  $sheet->setCellValue('B1', 'Количество часов');

  // Устанавливаем значения ячеек таблицы
  $rowNumber = 2;
  while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowNumber, $row["class_name"]);
    $sheet->setCellValue('B' . $rowNumber, $row["num_lessons"]);
    $rowNumber++;
  }

  // Устанавливаем ширины столбцов
  $sheet->getColumnDimension('A')->setWidth(20);
  $sheet->getColumnDimension('B')->setWidth(20);

  // Создаем объект Writer для сохранения файла в формате Excel
  $writer = new Xlsx($spreadsheet);

  // Сохраняем файл
  $filename = "отчет_" . htmlspecialchars($teacher_name) . ' ' . $start_date . '-' . $end_date . ".xlsx";
  $writer->save($filename);

  // Скачиваем файл
  $filesize = filesize($filename);
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Content-Length: ' . $filesize);
  header('Cache-Control: public');
  header('Content-Description: File Transfer');
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  header('Pragma: public');
  ob_clean();
  flush();
  readfile($filename);
  exit;
  }
}
// закрытие соединения с базой данных
$connection->close();
?>
</div>
</body>
</html>
