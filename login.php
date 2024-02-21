<!DOCTYPE html>
<html>
<head>
    <title>Вход</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>

  <nav><a class="button" class="head" href="index.php">Главная</a></nav>
  <h1 class="head-login">Вход</h1>

</header>
<div class=container-login>
<?php
        if(isset($_COOKIE['auth'])){
            $authData = $_COOKIE['auth'];
            list($login, $hash) = explode(':', $authData);
            if(md5($login.':123') === $hash){
                echo '<form method="post"><button type="submit" name="logout">Выход</button></form>';
                if(isset($_POST['logout'])){
                    setcookie('auth', '', time()-3600);
                    header('Location: login.php');
                    exit;
                }
            }
        } else {
            echo '<form method="post">
                    <label for="login">Логин</label><br>
                    <input type="text" id="login" name="login" autocomplete="off"><br><br>
                    <label for="password">Пароль</label><br>
                    <input type="password" id="password" name="password"><br><br>
                    <button type="submit">Вход</button>
                </form>';
            if(isset($_POST['login']) && isset($_POST['password'])){
                $login = $_POST['login'];
                $password = $_POST['password'];
                if ($login === 'admin' && $password === '123') {
                    $authData = $login.':'.md5($login.':'.$password);
                    setcookie('auth', $authData);
                    header('Location: index.php');
                    exit;
                } else {
                    echo '<p>Ошибка ввода логина или пароля</p>';
                }
            }
        }
    ?>
</div>
</body>
</html>
