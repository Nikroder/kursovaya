<?php

require 'vendor/autoload.php'; // Убедитесь, что вы используете Composer для установки phpseclib

use phpseclib3\Net\SSH2;

// Логирование ошибок в файл
$logFile = '/var/www/hostetski_ge_usr/data/www/hostetski.ge/modules/addons/diskstatus/log.txt';

// Данные для подключения к базе данных
$dbHost = 'localhost';
$dbName = 'name';
$dbUser = 'user';
$dbPassword = 'pass';

// Данные для подключения по SSH для первой ноды
$sshHost1 = '192.168.178.243';
$sshPort = 22;  // Порт по умолчанию
$sshUser = 'root';
$sshPassword1 = 'pass';

// Данные для подключения по SSH для второй ноды
$sshHost2 = '192.168.206.123';  // IP второй ноды
$sshPassword2 = 'pass';  // Замените на правильный пароль

// Данные для подключения по SSH для третьей ноды
$sshHost3 = '192.168.207.231';  // IP третьей ноды
$sshPassword3 = 'pass';  // Замените на правильный пароль

function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date("[Y-m-d H:i:s] ") . $message . "\n", FILE_APPEND);
}

try {
    // Подключение к базе данных
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logMessage("Подключение к базе данных успешно.");
} catch (PDOException $e) {
    logMessage("Ошибка подключения к базе данных: " . $e->getMessage());
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для записи данных в таблицу
function logDiskStatus($diskNumber, $output, $pdo, $nodeNumber) {
    try {
        $stmt = $pdo->prepare("INSERT INTO disk_state_log (date, text, nodenumber, disk) VALUES (:date, :text, :nodenumber, :disk)");
        $stmt->execute([
            ':date' => time(),
            ':text' => $output,
            ':nodenumber' => $nodeNumber,
            ':disk' => $diskNumber
        ]);
        logMessage("Данные для диска $diskNumber (нода $nodeNumber) успешно добавлены в базу данных.");
    } catch (PDOException $e) {
        logMessage("Ошибка записи в базу данных для диска $diskNumber (нода $nodeNumber): " . $e->getMessage());
    }
}

// Функция для выполнения команды smartctl по SSH
function fetchDiskInfo($sshConnection, $diskNumber) {
    $command = "smartctl -a /dev/sda -d cciss,$diskNumber --all";
    $output = $sshConnection->exec($command);
    logMessage("Команда выполнена успешно для диска $diskNumber.");
    return $output;
}

// Выполнение SSH подключения и обновление данных для первой ноды
$sshConnection1 = new SSH2($sshHost1);
if ($sshConnection1->login($sshUser, $sshPassword1)) {
    logMessage("Подключение по SSH к первой ноде успешно.");
    for ($diskNumber = 0; $diskNumber <= 3; $diskNumber++) {
        $output = fetchDiskInfo($sshConnection1, $diskNumber);
        logDiskStatus($diskNumber, $output, $pdo, 6);  // Нода 6 для первой ноды
    }
} else {
    logMessage("Ошибка аутентификации SSH для первой ноды.");
}

// Выполнение SSH подключения и обновление данных для второй ноды
$sshConnection2 = new SSH2($sshHost2);
if ($sshConnection2->login($sshUser, $sshPassword2)) {
    logMessage("Подключение по SSH ко второй ноде успешно.");
    for ($diskNumber = 0; $diskNumber <= 3; $diskNumber++) {
        $output = fetchDiskInfo($sshConnection2, $diskNumber);
        logDiskStatus($diskNumber, $output, $pdo, 7);  // Нода 7 для второй ноды
    }
} else {
    logMessage("Ошибка аутентификации SSH для второй ноды.");
}

// Выполнение SSH подключения и обновление данных для второй ноды
$sshConnection3 = new SSH2($sshHost3);
if ($sshConnection3->login($sshUser, $sshPassword3)) {
    logMessage("Подключение по SSH к третьей ноде успешно.");
    for ($diskNumber = 0; $diskNumber <= 3; $diskNumber++) {
        $output = fetchDiskInfo($sshConnection3, $diskNumber);
        logDiskStatus($diskNumber, $output, $pdo, 8);  // Нода 8 для третьей ноды
    }
} else {
    logMessage("Ошибка аутентификации SSH для третьей ноды.");
}

logMessage("Обновление состояния дисков завершено.");
?>
