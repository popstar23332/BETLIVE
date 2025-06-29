<!DOCTYPE html>
<html>
<head>
  <title>USSD Simulator</title>
</head>
<body>
  <h2>USSD Simulation Form</h2>
  <form method="POST">
    <label>USSD Input:</label><br>
    <input type="text" name="text" placeholder="e.g. 1 or 1*12345678 or 2" required><br><br>
    <input type="hidden" name="phoneNumber" value="0727430534">
    <button type="submit">Submit</button>
  </form>

  <?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $text = $_POST['text'];
      $phone = $_POST['phoneNumber'];
      $url = 'http://localhost/ussd_betting_system/index.php'; // ✅ Make sure this path matches your actual folder name

      // Log for debugging
      file_put_contents("simulation_debug.txt", date("Y-m-d H:i:s") . " | TEXT: $text | PHONE: $phone\n", FILE_APPEND);

      $data = http_build_query(['text' => $text, 'phoneNumber' => $phone]);
      $options = [
          'http' => [
              'header'  => "Content-type: application/x-www-form-urlencoded",
              'method'  => 'POST',
              'content' => $data,
          ],
      ];
      $context = stream_context_create($options);
      $response = file_get_contents($url, false, $context);

      echo "<h3>USSD Response:</h3><pre>" . htmlspecialchars($response) . "</pre>";
  }
  ?>
</body>
</html>
