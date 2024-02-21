<!DOCTYPE html>
<html>
<head>
    <title>School TimeTable</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
			var searchInput = $('input[name="search"]');
			var searchImg = $('img[name="search"]');
    		var searchResults = $('ul#search-results');	

            searchInput.on('input', function(){
                var search = $(this).val();
				if (search === '') {
					// Если строка поиска пустая, скрываем выпадающий список
					searchResults.html('');
					searchResults.removeClass('colored');
           			searchResults.addClass('transparent');
					return;
				}
                $.ajax({
                    url: 'search.php',
                    type: 'GET',
                    data: {search: search},
                    success: function(response){
                        searchResults.html(response);
                		searchResults.removeClass('transparent');
						searchResults.addClass('colored');
                    }
                });
            });

			searchInput.on('click', function(event){

				searchImg.removeClass('show');
    			searchImg.addClass('hide');

				if (!searchResults.hasClass('colored')) {
					// Если выпадающий список скрыт, при нажатии на строку поиска снова показываем его
					var search = searchInput.val();
					if (search !== '') {
						$.ajax({
							url: 'search.php',
							type: 'GET',
							data: {search: search},
							success: function(response){
								searchResults.html(response);
								searchResults.removeClass('transparent');
								searchResults.addClass('colored');
								searchImg.removeClass('show');
								searchImg.addClass('hide');
							}
						});
					}
				}
			});
			
			$(document).on('click', function(event){
				var target = $(event.target);
				if (!target.is(searchInput) && !target.is(searchResults) && !target.closest(searchResults).length) {
					// Если клик был вне строки поиска и выпадающего списка, скрываем выпадающий список
					searchResults.html('');
					searchResults.removeClass('colored');
            		searchResults.addClass('transparent');

					var search = searchInput.val();
					if (search === '') {
						searchImg.removeClass('hide');
						searchImg.addClass('show');
					}
				}
			});
        });
    </script>
</head>
<body>
<header>
    <?php

	require_once 'setting.php';

	// Connect to SQL
	$connection = new mysqli($host,$user,$pass,$data);
	if ($connection->connect_error) die('Error connection');

    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    ?>
    <?php
    $authorized = false; // Пользователь не авторизован по умолчанию
    if (isset($_COOKIE['auth'])) {
        $authData = explode(':', $_COOKIE['auth']);
        if (count($authData) === 2 && $authData[0] === 'admin' && $authData[1] === md5('admin:123')) {
            $authorized = true; // Пользователь успешно аутентифицирован
        }
    }
    ?>
    <nav class="search">
		<img class="show" name="search" src="images/search.png">
		<input type="text" name="search" autocomplete="off" value="<?php echo $search ?>">
        <ul class="transparent" id="search-results"></ul>
	</nav>
    <h1 class="head-main">Главная страница</h1>
	<button class="help" onclick="openHelp()">?</button>
	<script>
		function openHelp() {
			window.open('Справка.pdf', '_blank');
		}
	</script>
	<nav>
    <label class="main" for="showSubstitutions">
      <input class="check" type="checkbox" id="showSubstitutions" checked>
      Замены
    </label>
    </nav>

    <?php
    // Проверка на наличие куки
    if(isset($_COOKIE['auth'])){
        $authData = $_COOKIE['auth'];
        list($login, $hash) = explode(':', $authData);
        if(md5($login.':123') === $hash){
            echo '<nav><form method="post"><button type="submit" name="logout">Выход</button></form></nav>';
            if(isset($_POST['logout'])){
                setcookie('auth', '', time()-3600);
                header('Location: index.php');
                exit;
            }
        }
    } else {
        echo '<nav><form method="post" action="login.php"><button type="submit">Вход</button></form></nav>';
    } ?>

</header>
<div class=container>
<form method="POST" id="sortForm" onsubmit="return sortTable(event)">

	<input type="checkbox" id="hmt" class="hidden-menu-ticker">

	<label class="btn-menu" for="hmt">
		<span class="first"></span>
		<span class="second"></span>
		<span class="third"></span>
	</label>

	<ul class="hidden-menu">
		<select name='cret' onchange="updateSortingOptions()">
			<option value=' '></option>
			<option value='Класс'>Класс</option>
			<option value='Урок'>Урок</option>
			<option value='Кабинет'>Кабинет</option>
			<option value='Учитель'>Учитель</option>
		</select>
		<select name='sorting' id="sortingSelect">
		</select>
		<input type="submit" name="sort" value="Сортировать">
	</ul>
	</form>

	<script>
    function updateSortingOptions() {
        const selectCret = document.querySelector('select[name="cret"]');
        const selectSorting = document.querySelector('select[name="sorting"]');
        const selectedCret = selectCret.value;

        // Очистить список
        selectSorting.innerHTML = '';

        if (selectedCret !== '') {
            fetch('get_options.php?cret=' + selectedCret)
                .then(response => response.json())
                .then(data => {
                    // Добавить опции в список
                    for (const option of data) {
                        const optionElement = document.createElement('option');
                        optionElement.value = option.value;
                        optionElement.textContent = option.text;
                        selectSorting.appendChild(optionElement);
                    }
                })
                .catch(error => console.error('Ошибка получения данных:', error));
        }
    }
    // Обновить список сразу при загрузке страницы, если значение в cret выбрано
    document.addEventListener('DOMContentLoaded', updateSortingOptions);

	function sortTable(event) {
		event.preventDefault(); // Предотвращаем отправку формы и перезагрузку страницы

        const selectSorting = document.querySelector('select[name="sorting"]');
		const selectCret = document.querySelector('select[name="cret"]');
		const selectedCret = selectCret.value;
        const selectedSorting = selectSorting.value;

        const tableRows = document.querySelectorAll('table tbody tr');
        tableRows.forEach(row => {
			const lessonClass = row.getAttribute('data-lesson-class');
        	const lessonTeacher = row.getAttribute('data-lesson-teacher')
			const lessonSubject = row.getAttribute('data-lesson-subject')
			const lessonCabinet = row.getAttribute('data-lesson-cabinet')

			if (selectedCret === '' || selectedSorting === '') {
				// Если выбрано пустое значение, показываем все строки
				row.style.display = '';
			} else if (selectedCret === 'Класс' && selectedSorting === lessonClass) {
				row.style.display = ''; // Показать строку
			} else if (selectedCret === 'Учитель' && selectedSorting === lessonTeacher) {
				row.style.display = ''; // Показать строку
			} else if (selectedCret === 'Урок' && selectedSorting === lessonSubject) {
				row.style.display = ''; // Показать строку
			} else if (selectedCret === 'Кабинет' && selectedSorting === lessonCabinet) {
				row.style.display = ''; // Показать строку
			} else {
				row.style.display = 'none'; // Скрыть строку
			}
        });
		return false;
    }
	</script>

    <ul id="search-results">
        <?php

        // Check if search query was submitted
        if (isset($_GET['search'])) {
            $search = $_GET['search'];

            // Check if search query matches a class name
            $sql = "SELECT id FROM classes WHERE name LIKE '%$search%' LIMIT 5";
            $result = $connection->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $id = $row['id'];
                    echo "<li class='search-item' data-id='$id'>" . $row["name"] . "</li>";
                }
                exit();
            }

            // Check if search query matches a teacher name
            $sql = "SELECT id FROM teachers WHERE name LIKE '%$search%' LIMIT 5";
            $result = $connection->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $id = $row['id'];
                    echo "<li class='search-item' data-id='$id'>" . $row["name"] . "</li>";
                }
                exit();
            }

            // Check if search query matches a cabinet number
            $sql = "SELECT id FROM cabinets WHERE number LIKE '%$search%' LIMIT 5";
            $result = $connection->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $id = $row['id'];
                    echo "<li class='search-item' data-id='$id'>" . $row["name"] . "</li>";
                }
                exit();
            }
        }
		?>
    </ul>
		<?php

		function getData($connection, $startOfWeek, $endOfWeek) {
			$sql = "SELECT lesson.number, subjects.name AS subjects, classes.name AS classes, cabinets.name AS cabinets, teachers.name AS teachers, lesson.date_of_day AS date
					FROM lesson
					JOIN subjects ON lesson.subject_id = subjects.id
					JOIN classes ON lesson.class_id = classes.id
					JOIN cabinets ON lesson.cabinet_id = cabinets.id
					JOIN teachers ON lesson.teacher_id = teachers.id
					WHERE lesson.date_of_day BETWEEN '$startOfWeek' AND '$endOfWeek'
					ORDER BY lesson.date_of_day ASC, lesson.number ASC";
			$result = $connection->query($sql);
			if (!$result) {
				die("Error executing query: " . $connection->error);
			}
			return $result;
		}
		
		function getSubstitutionData($connection, $startOfWeek, $endOfWeek) {
			$sql = "SELECT substitutions.number, subjects.name AS subjects, classes.name AS classes, cabinets.name AS cabinets, teachers.name AS teachers, substitutions.date_of_day AS date
				FROM substitutions
				JOIN subjects ON substitutions.subject_id = subjects.id
				JOIN classes ON substitutions.class_id = classes.id
				JOIN cabinets ON substitutions.cabinet_id = cabinets.id
				JOIN teachers ON substitutions.teacher_id = teachers.id
				WHERE substitutions.date_of_day BETWEEN '$startOfWeek' AND '$endOfWeek'
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
		
		// Retrieve the data
		$data = getData($connection, $startOfWeek, $endOfWeek);

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
			$data = getData($connection, $day, $day);

			$fullScheduleData = getData($connection, $day, $day);
			$substitutionsData = getSubstitutionData($connection, $currentDateSub, $currentDateSub);

			// Проверка, есть ли записи для текущего дня
			if ($data->num_rows > 0 || $substitutionsData->num_rows > 0) {
				echo "<h2 class=table>" . $dayOfWeek . "</h2>";
				echo "<table>";
				echo "<thead><tr><th class=numb> № </th><th> Класс </th><th> Урок </th><th> Кабинет </th><th> Учитель </th></tr></thead>";
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
					echo "<tr class='substitution' data-lesson-number=" . $i . "' data-lesson-class='" . $substitutionLesson['classes'] . "' data-lesson-teacher='" . $substitutionLesson['teachers'] . "' data-lesson-cabinet='" . $substitutionLesson['cabinets'] . "' data-lesson-subject='" . $substitutionLesson['subjects'] . "' data-lesson-date='" . $dayOfWeek . "'>";
					echo "<td class=numb>" . $substitutionLesson['number'] . "</td>";
					echo "<td>" . $substitutionLesson['classes'] . "</td>";
					echo "<td>" . $substitutionLesson['subjects'] . "</td>";
					echo "<td>" . $substitutionLesson['cabinets'] . "</td>";
					echo "<td>" . $substitutionLesson['teachers'] . "</td>";
					echo "</tr>";
					}
				}
				foreach ($regularLessons as $regularLesson) {
					if ($regularLesson['number'] == $i){ // Сделать доп проверку на номер урока (1 урок, * по списку и тд)
					echo "<tr class='regular-lesson' data-lesson-number=" . $i . "' data-lesson-class='" . $regularLesson['classes'] . "' data-lesson-teacher='" . $regularLesson['teachers'] . "' data-lesson-cabinet='" . $regularLesson['cabinets'] . "' data-lesson-subject='" . $regularLesson['subjects'] . "' data-lesson-date='" . $dayOfWeek . "'>";
					echo "<td class=numb>" . $regularLesson['number'] . "</td>";
					echo "<td>" . $regularLesson['classes'] . "</td>";
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
		///////////
        
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
          var lessonNumber = substitutionRow.getAttribute('data-lesson-number');
		  var lessonClass = substitutionRow.getAttribute('data-lesson-class');
          var lessonDate = substitutionRow.getAttribute('data-lesson-date');
		  var correspondingRegularLessonRow = document.querySelector(
		      '.regular-lesson[data-lesson-number="' + lessonNumber + '"][data-lesson-class="' + lessonClass.replace(/"/g, '\\"') + '"][data-lesson-date="' + lessonDate + '"]'
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
		  var substitutionLessonClass = substitutionRows[i].getAttribute('data-lesson-class');
          var substitutionLessonDate = substitutionRows[i].getAttribute('data-lesson-date');

          for (var j = 0; j < regularLessonRows.length; j++) {
              var regularLessonNumber = regularLessonRows[j].getAttribute('data-lesson-number');
			  var regularLessonClass = regularLessonRows[j].getAttribute('data-lesson-class');
              var regularLessonDate = regularLessonRows[j].getAttribute('data-lesson-date');

              if (substitutionLessonNumber === regularLessonNumber && substitutionLessonClass === regularLessonClass && substitutionLessonDate === regularLessonDate) {
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


