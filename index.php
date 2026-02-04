<?php
// index.php — Houses List

include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home reporting System - House Records</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background:#f8f9fa; color:#333; margin:0; padding:20px; }
        .container { max-width:1200px; margin:0 auto; }
        header { margin-bottom:40px; text-align:center; }
        .logo-container { display:flex; flex-direction:column; align-items:center; margin-bottom:20px; }
        .logo-container img { max-width:220px; height:auto; }
        .logo-text { font-size:2em; font-weight:bold; color:#2c3e50; margin-top:10px; }
        h1 { color:#2c3e50; margin:0; }
        .houses-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:25px; }
        .house-card { background:white; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); overflow:hidden; transition:0.2s; }
        .house-card:hover { transform:translateY(-8px); box-shadow:0 12px 30px rgba(0,0,0,0.15); }
        .house-card a { text-decoration:none; color:inherit; display:block; }
        .house-image { height:180px; background:#e9ecef; display:flex; align-items:center; justify-content:center; font-size:1.2em; color:#6c757d; }
        .house-content { padding:20px; }
        .house-name { font-size:1.6em; margin:0 0 10px; color:#2c3e50; }
        .house-address { color:#555; margin-bottom:15px; font-size:0.95em; }
        .view-btn { display:inline-block; background:#3498db; color:white; padding:10px 20px; border-radius:6px; font-weight:bold; }
        .view-btn:hover { background:#2980b9; }
    </style>
</head>
<body>
<div class="container">

    <header>
        <div class="logo-container">
            <img src="logo.png" alt="Drowning Fish Rescue">
            <span class="logo-text">Drowning Fish Rescue</span>
        </div>
        <h1>House Records</h1>
    </header>

    <div class="houses-grid">
        <?php
        $houses = $conn->query("SELECT id, name, address FROM houses ORDER BY id");
        while ($house = $houses->fetch_assoc()) {
            $id = $house['id'];
            $name = htmlspecialchars($house['name']);
            $address = htmlspecialchars($house['address'] ?? 'No address set');
            echo "<div class='house-card'>";
            echo "<a href='house.php?id=$id'>";
            echo "<div class='house-image'>House $id</div>";
            echo "<div class='house-content'>";
            echo "<h2 class='house-name'>$name</h2>";
            echo "<p class='house-address'>$address</p>";
            echo "<span class='view-btn'>View Details →</span>";
            echo "</div>";
            echo "</a>";
            echo "</div>";
        }
        $conn->close();
        ?>
    </div>

</div>
</body>
</html>

