<!DOCTYPE html>
<html>
<head>
	<title>School TimeTable</title>
    <link rel="stylesheet" href="css/style.css">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            $('input[name="search"]').on('input', function(){
                var search = $(this).val();
                $.ajax({
                    url: 'search.php',
                    type: 'GET',
                    data: {search: search},
                    success: function(response){
                        $('ul#search-results').html(response);
                    }
                });
            });
        });
    </script>
</head>
<body>
<header>

	<?php
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
    <nav><a class="button" class="head" href="index.php">Главная</a></nav>
    <h1 class="head-main">Главная страница</h1>
	<nav><input type="text" name="search" value="<?php echo $search; ?>"></nav>

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
	<ul id="search-results">

		<?php

            require_once 'setting.php';

            // Connect to SQL
            $connection = new mysqli($host,$user,$pass,$data);
            if ($connection->connect_error) die('Error connection');

			// Check if search query was submitted
			if (isset($_GET['search'])) {
				$search = $_GET['search'];

				// Check if search query matches a class name
				$sql = "SELECT id FROM classes WHERE name LIKE '%$search%'";
				$result = $connection->query($sql);
				if ($result->num_rows > 0) {
					$row = $result->fetch_assoc();
					$id = $row['id'];
					header("Location: class.php?id=$id");
					exit();
				}
				
				// Check if search query matches a teacher name
				$sql = "SELECT id FROM teachers WHERE name LIKE '%$search%'";
				$result = $connection->query($sql);
				if ($result->num_rows > 0) {
					$row = $result->fetch_assoc();
					$id = $row['id'];
					header("Location: teacher_table.php?id=$id");
					exit();
				}

				// Check if search query matches a cabinet number
				$sql = "SELECT id FROM cabinets WHERE number LIKE '%$search%'";
				$result = $connection->query($sql);
				if ($result->num_rows > 0) {
					$row = $result->fetch_assoc();
					$id = $row['id'];
					header("Location: cabinet_table.php?id=$id");
					exit();
				}
			}
			// Query the classes table for class names
			$sql_cl = "SELECT id, name FROM classes";
			$result = $connection->query($sql_cl);

            echo "<h2>Классы</h2>";
			if ($result->num_rows > 0) {
			    // Output data of each row
			    while($row = $result->fetch_assoc()) {
			        // Display each class name as a clickable link
			        echo "<li><a href='class.php?id=" . $row["id"] . "'>" . $row["name"] . "</a></li>";
			    }
			} else {
			    echo "0 results";
			}

            $sql_tc = "SELECT id, name FROM teachers";
			$result = $connection->query($sql_tc);

            echo "<h2>Преподаватели</h2>";
			if ($result->num_rows > 0) {
			    // Output data of each row
			    while($row = $result->fetch_assoc()) {
			        // Display each class name as a clickable link
			        echo "<li><a href='teacher_table.php?id=" . $row["id"] . "'>" . $row["name"] . "</a></li>";
			    }
			} else {
			    echo "0 results";
			}

            $sql_cb = "SELECT id, name FROM cabinets";
			$result = $connection->query($sql_cb);

            echo "<h2>Кабинеты</h2>";
			if ($result->num_rows > 0) {
			    // Output data of each row
			    while($row = $result->fetch_assoc()) {
			        // Display each class name as a clickable link
			        echo "<li><a href='cabinet_table.php?id=" . $row["id"] . "'>" . $row["name"] . "</a></li>";
			    }
			} else {
			    echo "0 results";
			}

			$connection->close();
		?>
	</ul>
</div>
</body>
</html>