<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$apiKey = "e2acebbebc03ce4dd555558a86b812b3";
$city = isset($_GET['city']) ? $_GET['city'] : '';
$wybranaData = isset($_GET['selected_date']) ? $_GET['selected_date'] : '';
$wybranyParametr = isset($_GET['parameter']) ? $_GET['parameter'] : 'temp';
$units = isset($_GET['units']) ? $_GET['units'] : 'metric';
$weatherData = [];
$error = '';
$selectedTime = null;
$selectedForecast = null;
$timezone = 0;

if ($city) {
    $url = "https://api.openweathermap.org/data/2.5/forecast?q=" . urlencode($city) . "&appid=$apiKey&units=$units&selected_date=$wybranaData&lang=pl";
    $response = @file_get_contents($url);

    if ($response === false) {
        $error = "Błąd: Nie udało się pobrać danych lub miasto nie istnieje.";
    } else {
        $weatherData = json_decode($response, true);

        if ($weatherData['cod'] != "200") {
            $error = "Błąd: " . ($weatherData['message'] ?? "Nieznany błąd");
        } else {
            
            $timezone = $weatherData['city']['timezone'];
            $weatherData['grouped'] = [];
            foreach ($weatherData['list'] as $forecast) {
                $datedt = $forecast['dt'];
                $date = substr($forecast['dt_txt'], 0, 10);
                $weatherData['grouped'][$date]['date'] = $date;
                $weatherData['grouped'][$date]['forecasts'][] = [
                    'datedt' => $datedt,
                    'temp' => $forecast['main']['temp'],
                    'humidity' => $forecast['main']['humidity'],
                    'pop' => $forecast['pop'],
                    'wind_speed' => $forecast['wind']['speed'],
                    'wind_deg' => $forecast['wind']['deg'],
                    'wind_gust' => $forecast['wind']['gust'],
                    'description' => $forecast['weather'][0]['description'],
                    'icon' => $forecast['weather'][0]['icon'],
                    'timezone' => $timezone
                ];
            }

            $weatherData['grouped'] = array_values($weatherData['grouped']);
            
            if (!empty($weatherData['grouped']) && count($weatherData['grouped'][0]['forecasts']) < 8 && count($weatherData['grouped']) > 1) {
                $missingCount = 8 - count($weatherData['grouped'][0]['forecasts']);
                $nextDayForecasts = array_slice($weatherData['grouped'][1]['forecasts'], 0, $missingCount);
                $weatherData['grouped'][0]['forecasts'] = array_merge($weatherData['grouped'][0]['forecasts'], $nextDayForecasts);
            }

            if (!$wybranaData && !empty($weatherData['grouped'])) {
                $wybranaData = $weatherData['grouped'][0]['date'];
            }
           

            foreach ($weatherData['grouped'] as $day) {
                if ($day['date'] === $wybranaData) {
                    $selectedForecast = $day['forecasts'] ?? null;
                    $selectedTime = $selectedForecast[0]['datedt'] ?? null;
                    break;
                }
            }
  
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Prognoza pogody</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

</head>
<body>
<form method="GET">
    <div class="search-bar">
  <input type="text" name="city" id="city" value="<?php echo htmlspecialchars($city); ?>" placeholder="Wpisz miasto" required>
  <button type="button" class="clear-btn" onclick="document.getElementById('city').value=''">
    <i class="fa-solid fa-xmark"></i>
  </button>
  <button type="submit" class="search-btn">
    <i class="fa-solid fa-magnifying-glass"></i>
  </button>
</div>
    <input type="hidden" name="selected_date" value="<?php echo htmlspecialchars($wybranaData); ?>">
    <input type="hidden" name="parameter" value="<?php echo htmlspecialchars($wybranyParametr); ?>">
    <input type="hidden" name="units" value="<?php echo htmlspecialchars($units); ?>">
</form>

<?php if (isset($weatherData['city']['name'])): ?>
    <p style="text-align: left; margin: 5px 0 20px 0;">
        Wyniki wyszukiwania dla lokalizacji 
        <span style="font-weight: bold; color: black;">
            <?php echo htmlspecialchars($weatherData['city']['name']); ?>
        </span>
    </p>
<?php endif; ?>
<?php if (!empty($weatherData)): ?>
<div class = "description">
        <img id="weather-icon" src="" alt="Weather Icon">
        <h2 id = "temp-description"></h2>

    <div class="description-btn "> <a href="?city=<?= urlencode($city) ?>&selected_date=<?= urlencode($wybranaData) ?>&units=metric&parameter=<?= urlencode($wybranyParametr); ?>">
               <button class="<?= $units === 'metric' ? 'btn-active' : '' ?>" <?= $units === 'metric' ? 'disabled' : '' ?>>°C</button></a>
               <a href="?city=<?= urlencode($city) ?>&selected_date=<?= urlencode($wybranaData) ?>&units=imperial&parameter=<?= urlencode($wybranyParametr); ?>">
            <button class="<?= $units === 'imperial' ? 'btn-active' : '' ?>" <?= $units === 'imperial' ? 'disabled' : '' ?>>K</button></a>
   </div>

    <div class = 'description-list'>
     <p id="temperature"></p>
    <p id="humidity"></p>
    <p id="wind"></p>
    </div>
    <div>
       
     <h2>Pogoda</h2>

      
         <p id="date"></p>
             <p id="description"></p>
    </div>
</div>
<div class='buttons'>
<a href="?city=<?php echo urlencode($city); ?>&selected_date=<?php echo urlencode($wybranaData); ?>&parameter=temp&units=<?php echo $units; ?>" 
   class="<?php echo $wybranyParametr === 'temp' ? 'underline' : ''; ?>">
   <button >Temperatura</button>    </a>
</a>

    <div class="vertical-line"></div>
    <a href="?city=<?php echo urlencode($city); ?>&selected_date=<?php echo urlencode($wybranaData); ?>&parameter=pop&units=<?php echo $units; ?>"
    class="<?php echo $wybranyParametr === 'pop' ? 'underline' : ''; ?>">
    <button>Opady</button>    </a>
    </a>
        <div class="vertical-line"></div>

    <a href="?city=<?php echo urlencode($city); ?>&selected_date=<?php echo urlencode($wybranaData); ?>&parameter=wind_speed&units=<?php echo $units; ?>"
    class="<?php echo $wybranyParametr === 'wind_speed' ? 'underline' : ''; ?>">
           <button>Wiatr</button>    </a>
    </a>
</div>
<?php endif; ?>
<?php if (!empty($weatherData['grouped'])): ?>
   
<?php elseif ($error): ?>
    <p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<div class="plot"></div>

<script src="plot.js"></script>
<div>
   <?php if (!empty($weatherData['grouped'])): ?>
    <?php
    // Get the selected_date from the query string
   $selectedDate = isset($_GET['selected_date']) ? $_GET['selected_date'] : '';

if (empty($selectedDate) && !empty($weatherData['grouped'])) {
    $selectedDate = $weatherData['grouped'][0]['date'];
}

    ?>
    <?php
$daysPL = [
    'Sunday'    => 'niedziela',
    'Monday'    => 'poniedziałek',
    'Tuesday'   => 'wtorek',
    'Wednesday' => 'środa',
    'Thursday'  => 'czwartek',
    'Friday'    => 'piątek',
    'Saturday'  => 'sobota'
];
?>
    <?php foreach ($weatherData['grouped'] as $day): ?>
         <?php 
        $englishDay = date('l', $day['forecasts'][0]['datedt']);
        $dayName = $daysPL[$englishDay];
    ?>
        <a href="?city=<?php echo urlencode($city); ?>&selected_date=<?php echo urlencode($day['date']); ?>&parameter=<?php echo urlencode($wybranyParametr); ?>&units=<?php echo $units; ?>">
            <button style="<?php echo ($day['date'] === $selectedDate) ? 'background-color: #C7CFD4; border-radius: 20px' : ''; ?>">
<p><?php echo htmlspecialchars(ucfirst($dayName)); ?></p>                <img src="http://openweathermap.org/img/wn/<?php echo htmlspecialchars($day['forecasts'][0]['icon']); ?>@2x.png">
                <div style="display: flex; justify-content: space-around">
                    <p style="font-size: 12px; font-weight: bold"><?php echo intval(max(array_column($day['forecasts'], 'temp'))); ?><?php echo $units === 'metric' ? '°C' : '°F'; ?></p>
                    <p style="font-size: 12px"><?php echo (int) min(array_column($day['forecasts'], 'temp')); ?><?php echo $units === 'metric' ? '°C' : '°F'; ?></p>
                </div>
            </button>
        </a>
    <?php endforeach; ?>
<?php endif; ?>


</div>




<script>
const selectedForecast = <?php echo json_encode($selectedForecast); ?>;
const units = '<?php echo $units; ?>';
const parameter = '<?php echo $wybranyParametr; ?>';

if (selectedForecast) {
    // Dodajemy pola temp_display i wind_display z jednostkami dla description()
    selectedForecast.forEach(forecast => {
        forecast.temp_display = `${forecast.temp}`;
        forecast.wind_display = `${forecast.wind_speed}`;
    });

    plot('<?php echo $units; ?>',parameter, selectedForecast, <?php echo isset($selectedTime) ? json_encode($selectedTime) : 'null'; ?>, );
}
</script>
</body>
</html>
