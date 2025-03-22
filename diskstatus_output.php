<?php

require 'vendor/autoload.php'; // Убедитесь, что вы используете Composer для установки phpseclib

use phpseclib3\Net\SSH2;

// Логирование ошибок
function logMessage($message) {
    echo date("[Y-m-d H:i:s] ") . $message . "\n"; // Используем echo для логирования
}

// Подключение к базе данных
$dbHost = 'localhost';
$dbName = 'name';
$dbUser = 'user';
$dbPassword = 'pass';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logMessage("Подключение к базе данных успешно.");
} catch (PDOException $e) {
    logMessage("Ошибка подключения к базе данных: " . $e->getMessage());
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получение уникальных номеров дисков для выпадающего списка
$diskQuery = $pdo->query("SELECT DISTINCT disk FROM disk_state_log ORDER BY disk ASC");
$diskOptions = $diskQuery->fetchAll(PDO::FETCH_COLUMN);

// Получение данных формы
$selectedDisk = $_GET['disk'] ?? null;
$selectedDate1 = $_GET['date1'] ?? null;
$selectedDate2 = $_GET['date2'] ?? null;
$selectedNode = $_GET['node'] ?? null; // Получаем выбранную ноду

// Форма для выбора диска, ноды и двух дат
echo "<form method='GET' action='/cloudop/addonmodules.php'>
        <input type='hidden' name='module' value='diskstatus'>
        <label for='disk'>Выберите диск:</label>
        <select name='disk' id='disk'>
            <option value=''>-- Все диски --</option>";
foreach ($diskOptions as $disk) {
    $selected = ($disk == $selectedDisk) ? "selected" : "";
    echo "<option value='{$disk}' {$selected}>Диск {$disk}</option>";
}
echo "</select>
        <label for='node'>Выберите ноду:</label>
        <select name='node' id='node'>
            <option value=''>-- Все ноды --</option>
            <option value='6' " . ($selectedNode == '6' ? "selected" : "") . ">Нода 1</option>
            <option value='7' " . ($selectedNode == '7' ? "selected" : "") . ">Нода 2</option>
			<option value='8' " . ($selectedNode == '8' ? "selected" : "") . ">Нода 3 (тех)</option>
        </select>
        <label for='date1'>Первая дата:</label>
        <input type='date' name='date1' id='date1' value='{$selectedDate1}'>
        <label for='date2'>Вторая дата:</label>
        <input type='date' name='date2' id='date2' value='{$selectedDate2}'>
        <button type='submit'>Показать</button>
      </form>";

function getDiskData($pdo, $disk, $date, $node) {
    if (!$date) {
        return [];
    }

    $startOfDay = strtotime($date . ' 00:00:00');
    $endOfDay = strtotime($date . ' 23:59:59');

    $query = "SELECT * FROM disk_state_log WHERE date BETWEEN :startOfDay AND :endOfDay";
    $params = [
        ':startOfDay' => $startOfDay,
        ':endOfDay' => $endOfDay
    ];

    if ($disk !== null) { // Проверяем, что диск не равен null
        $query .= " AND disk = :disk"; // Убираем приведение к числу
        $params[':disk'] = (string)$disk; // Приводим диск к строковому типу
    }

    if ($node) {
        $query .= " AND nodenumber = :node"; // Фильтруем по ноде
        $params[':node'] = $node;
    }

    $query .= " ORDER BY date DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Получение данных для двух дат с учетом выбранной ноды
$dataDate1 = getDiskData($pdo, $selectedDisk, $selectedDate1, $selectedNode);
$dataDate2 = getDiskData($pdo, $selectedDisk, $selectedDate2, $selectedNode);

// Вывод данных в две колонки
echo "<div style='display: flex;'>
        <div style='width: 50%; padding: 10px; border-right: 1px solid #ccc;'>
            <h2>Состояние на {$selectedDate1}" . ($selectedNode ? " (Нода s{$selectedNode})" : "") . "</h2>";
foreach ($dataDate1 as $row) {
    echo "<div class='disk-output'>
            <h3>Диск {$row['disk']} (Дата: " . date("Y-m-d H:i:s", $row['date']) . ")</h3>
            <pre class='output'>{$row['text']}</pre>
          </div>";
}
echo "</div>
        <div style='width: 50%; padding: 10px;'>
            <h2>Состояние на {$selectedDate2}" . ($selectedNode ? " (Нода s{$selectedNode})" : "") . "</h2>";
foreach ($dataDate2 as $row) {
    echo "<div class='disk-output'>
            <h3>Диск {$row['disk']} (Дата: " . date("Y-m-d H:i:s", $row['date']) . ")</h3>
            <pre class='output'>{$row['text']}</pre>
          </div>";
}
echo "</div>
      </div>";
?>


<style>
.disk-container {
    display: flex; /* Используем flexbox для выстраивания блоков в строку */
    flex-wrap: wrap; /* Позволяем переносить блоки на следующую строку при нехватке места */
    justify-content: flex-start; /* Выравнивание по началу строки */
}

.disk-status {
    display: flex; /* Используем flexbox для внутреннего выстраивания */
    flex-direction: column; /* Выстраиваем элементы внутри в столбик */
    align-items: center; /* Центрируем элементы по вертикали */
    justify-content: center; /* Центрируем элементы по горизонтали */
    border: 1px solid #ccc;
    border-radius: 5px;
    padding: 20px;
    margin: 10px;
    text-align: center;
    width: calc(24% - 20px); /* Учитываем отступы, чтобы не выходило за пределы */
}

.disk-image {
    width: 100%;
    height: 100px;
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
}

.disk-label {
	padding: 15px;
}

.disk-output pre {
    max-height: 600px; /* Максимальная высота блока */
    overflow-y: auto; /* Включение вертикальной прокрутки */
    padding: 10px; /* Отступы внутри блока */
    border: 1px solid #ccc; /* Граница для удобства */
    background-color: #f9f9f9; /* Фоновый цвет для читаемости */
    white-space: pre-wrap; /* Разрыв строк для длинного текста */
    word-wrap: break-word; /* Перенос длинных слов */
}

</style>

<script>
  function convertToGB(text) {
    return text.replace(/\b(\d{11,})\b/g, function(match) {
      const value = parseInt(match);
      const bytes = value * 512; // Переводим LBAs в байты
      const gb = (bytes / 1073741824).toFixed(2); // Переводим байты в гигабайты
      return gb + " GB"; // Возвращаем результат
    });
  }

  // Находим все элементы <pre> с классом "output"
  const preElements = document.querySelectorAll('pre.output');
  
  // Проходим по каждому элементу и преобразуем его текст
  preElements.forEach(function(preElement) {
    preElement.textContent = convertToGB(preElement.textContent);
  });
</script>