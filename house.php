<?php
// house.php — MOST RECENT WORKING VERSION
// Features: logo in header, breaker panel size selection (6/12/24/28), top-down reversed numbering, panel delete working, all tabs

include 'config.php';

$house_id = intval($_GET['id'] ?? 0);
if ($house_id !== 1 && $house_id !== 2) {
    die("Invalid house ID");
}

$sql = "SELECT name, address, latitude, longitude, tax_number, map_zoom FROM houses WHERE id = $house_id";
$result = $conn->query($sql);
$house = $result->fetch_assoc();
$house_name = htmlspecialchars($house['name'] ?? 'Unknown House');

// Development error display — remove/comment in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Upload limits
ini_set('upload_max_filesize', '32M');
ini_set('post_max_size', '64M');
ini_set('max_file_uploads', '20');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // PERMANENT ITEMS UPDATE (with 'ac')
    if (isset($_POST['update_permanent'])) {
        $item_types = ['furnace', 'water_heater', 'dishwasher', 'washer', 'dryer', 'ac'];
        foreach ($item_types as $type) {
            $brand = mysqli_real_escape_string($conn, $_POST[$type . '_brand'] ?? '');
            $model = mysqli_real_escape_string($conn, $_POST[$type . '_model'] ?? '');
            $sn = mysqli_real_escape_string($conn, $_POST[$type . '_sn'] ?? '');
            $efficiency = mysqli_real_escape_string($conn, $_POST[$type . '_efficiency'] ?? '');
            $kwh = floatval($_POST[$type . '_kwh'] ?? 0);
            $capacity = intval($_POST[$type . '_capacity'] ?? 0);
            $sql = "UPDATE permanent_items SET brand='$brand', model='$model', sn='$sn', efficiency='$efficiency', kwh=$kwh, capacity=$capacity WHERE house_id=$house_id AND item_type='$type'";
            $conn->query($sql);
        }
    }

    // UTILITY SERVICES - METER
    if (isset($_POST['update_meter'])) {
        $meter_number = mysqli_real_escape_string($conn, $_POST['meter_number'] ?? '');
        $company = mysqli_real_escape_string($conn, $_POST['company'] ?? '');
        $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
        $sql = "UPDATE electric_meters SET meter_number='$meter_number', company='$company', phone='$phone' WHERE house_id=$house_id";
        $conn->query($sql);
    }

    // UTILITY SERVICES - GENERATOR
    if (isset($_POST['update_generator'])) {
        $brand = mysqli_real_escape_string($conn, $_POST['brand'] ?? '');
        $model = mysqli_real_escape_string($conn, $_POST['model'] ?? '');
        $sn = mysqli_real_escape_string($conn, $_POST['sn'] ?? '');
        $efficiency = mysqli_real_escape_string($conn, $_POST['efficiency'] ?? '');
        $kwh = floatval($_POST['kwh'] ?? 0);
        $fuel_type = $_POST['fuel_type'] ?? 'LP';
        $sql = "UPDATE generators SET brand='$brand', model='$model', sn='$sn', efficiency='$efficiency', kwh=$kwh, fuel_type='$fuel_type' WHERE house_id=$house_id";
        $conn->query($sql);
    }

    // UTILITY SERVICES - SOLAR INVERTER
    if (isset($_POST['update_inverter'])) {
        $brand = mysqli_real_escape_string($conn, $_POST['brand'] ?? '');
        $model = mysqli_real_escape_string($conn, $_POST['model'] ?? '');
        $sn = mysqli_real_escape_string($conn, $_POST['sn'] ?? '');
        $kwh = floatval($_POST['kwh'] ?? 0);
        $sql = "UPDATE solar_inverters SET brand='$brand', model='$model', sn='$sn', kwh=$kwh WHERE house_id=$house_id";
        $conn->query($sql);
    }

    // UTILITY SERVICES - SOLAR STRING ADD
    if (isset($_POST['add_solar_string'])) {
        $connection_type = $_POST['connection_type'] ?? 'Series';
        $sql = "INSERT INTO solar_strings (house_id, connection_type) VALUES ($house_id, '$connection_type')";
        $conn->query($sql);
    }

    // UTILITY SERVICES - SOLAR PANEL ADD
    if (isset($_POST['add_solar_panel'])) {
        $string_id = intval($_POST['string_id']);
        $brand = mysqli_real_escape_string($conn, $_POST['brand'] ?? '');
        $watts = intval($_POST['watts'] ?? 0);
        $sql = "INSERT INTO solar_panels (string_id, brand, watts) VALUES ($string_id, '$brand', $watts)";
        $conn->query($sql);
    }

    // UTILITY SERVICES - SOLAR PANEL DELETE
    if (isset($_POST['delete_solar_panel'])) {
        $panel_id = intval($_POST['panel_id']);
        $sql = "DELETE FROM solar_panels WHERE id = $panel_id";
        $conn->query($sql);
    }

    // UTILITY SERVICES - BATTERY STRING ADD
    if (isset($_POST['add_battery_string'])) {
        $connection_type = $_POST['connection_type'] ?? 'Parallel';
        $sql = "INSERT INTO battery_strings (house_id, connection_type) VALUES ($house_id, '$connection_type')";
        $conn->query($sql);
    }

    // UTILITY SERVICES - BATTERY ADD
    if (isset($_POST['add_battery'])) {
        $string_id = intval($_POST['string_id']);
        $brand = mysqli_real_escape_string($conn, $_POST['brand'] ?? '');
        $watts = intval($_POST['watts'] ?? 0);
        $sql = "INSERT INTO batteries (string_id, brand, watts) VALUES ($string_id, '$brand', $watts)";
        $conn->query($sql);
    }

    // UTILITY SERVICES - BATTERY DELETE
    if (isset($_POST['delete_battery'])) {
        $battery_id = intval($_POST['battery_id']);
        $sql = "DELETE FROM batteries WHERE id = $battery_id";
        $conn->query($sql);
    }

    // ELECTRIC PANEL ADD with size selection
    if (isset($_POST['add_electric_panel'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? 'New Panel');
        $spaces = intval($_POST['spaces'] ?? 28);
        if (!in_array($spaces, [6, 12, 24, 28])) $spaces = 28;

        $sql = "INSERT INTO electric_panels (house_id, name, spaces) VALUES ($house_id, '$name', $spaces)";
        $conn->query($sql);
        $panel_id = $conn->insert_id;

        $rows = ceil($spaces / 2);
        for ($row = 1; $row <= $rows; $row++) {
            for ($col = 1; $col <= 2; $col++) {
                $sql = "INSERT INTO breakers (panel_id, column_num, row_num) VALUES ($panel_id, $col, $row)";
                $conn->query($sql);
            }
        }
    }

    // BREAKERS UPDATE
    if (isset($_POST['update_breakers'])) {
        $panel_id = intval($_POST['panel_id']);
        $rows = $conn->query("SELECT spaces FROM electric_panels WHERE id = $panel_id")->fetch_assoc()['spaces'] ?? 28;
        $rows = ceil($rows / 2);

        for ($row = 1; $row <= $rows; $row++) {
            for ($col = 1; $col <= 2; $col++) {
                $room = mysqli_real_escape_string($conn, $_POST["room_{$row}_{$col}"] ?? '');
                $amp = intval($_POST["amp_{$row}_{$col}"] ?? 0);
                $sql = "UPDATE breakers SET room='$room', amp=$amp WHERE panel_id=$panel_id AND column_num=$col AND row_num=$row";
                $conn->query($sql);
            }
        }
    }

    // ELECTRIC PANEL DELETE (working version)
    if (isset($_POST['delete_panel'])) {
        $panel_id = intval($_POST['panel_id']);
        // Delete child breakers first (required if no ON DELETE CASCADE)
        $sql = "DELETE FROM breakers WHERE panel_id = $panel_id";
        $conn->query($sql);
        // Delete the panel
        $sql = "DELETE FROM electric_panels WHERE id = $panel_id AND house_id = $house_id";
        $conn->query($sql);
        echo "<div style='background:#d4edda;color:#155724;padding:12px;margin:15px 0;border-radius:6px;'>
              Panel deleted successfully!</div>";
    }

    // HOUSEHOLD ITEMS - ADD
    if (isset($_POST['add_household'])) {
        $type = $_POST['type'] ?? 'TV';
        $brand = mysqli_real_escape_string($conn, $_POST['brand'] ?? '');
        $model = mysqli_real_escape_string($conn, $_POST['model'] ?? '');
        $sn = mysqli_real_escape_string($conn, $_POST['sn'] ?? '');
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        $sql = "INSERT INTO household_items (house_id, type, brand, model, sn, notes) VALUES ($house_id, '$type', '$brand', '$model', '$sn', '$notes')";
        $conn->query($sql);
    }

    // HOUSEHOLD ITEMS - UPDATE
    if (isset($_POST['update_household'])) {
        $item_id = intval($_POST['item_id']);
        $brand = mysqli_real_escape_string($conn, $_POST['brand'] ?? '');
        $model = mysqli_real_escape_string($conn, $_POST['model'] ?? '');
        $sn = mysqli_real_escape_string($conn, $_POST['sn'] ?? '');
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        $sql = "UPDATE household_items SET brand='$brand', model='$model', sn='$sn', notes='$notes' WHERE id=$item_id AND house_id=$house_id";
        $conn->query($sql);
    }

    // HOUSEHOLD ITEMS - DELETE
    if (isset($_POST['delete_household'])) {
        $item_id = intval($_POST['item_id']);
        $sql = "DELETE FROM household_items WHERE id=$item_id AND house_id=$house_id";
        $conn->query($sql);
    }

    // PHOTOS - MULTI UPLOAD
    if (isset($_POST['upload_photo']) && !empty($_FILES['photos']['name'][0])) {
        $section = $_POST['section'] ?? 'Interior';
        $is_ir = intval($_POST['is_ir'] ?? 0);
        $target_dir = "uploads/photos/";
        $count = 0;
        $max = 15;
        $allowed = ['jpg','jpeg','png','gif','webp'];

        foreach ($_FILES['photos']['tmp_name'] as $k => $tmp) {
            if ($count >= $max) break;
            if ($_FILES['photos']['error'][$k] !== UPLOAD_ERR_OK) continue;

            $name = basename($_FILES['photos']['name'][$k]);
            $target = $target_dir . time() . '_' . $name;
            $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && getimagesize($tmp)) {
                if (move_uploaded_file($tmp, $target)) {
                    $fn = basename($target);
                    $sql = "INSERT INTO photos (house_id, section, filename, is_ir, upload_date) VALUES ($house_id, '$section', '$fn', $is_ir, NOW())";
                    $conn->query($sql);
                    $count++;
                }
            }
        }
        if ($count > 0) {
            echo "<div style='background:#d4edda;color:#155724;padding:12px;margin:15px 0;border-radius:6px;'>
                  Uploaded $count photo" . ($count > 1 ? 's' : '') . "!</div>";
        }
    }

    // PHOTOS - DELETE
    if (isset($_POST['delete_photo'])) {
        $pid = intval($_POST['photo_id']);
        $r = $conn->query("SELECT filename FROM photos WHERE id=$pid AND house_id=$house_id");
        if ($r && $row = $r->fetch_assoc()) {
            $path = "uploads/photos/" . $row['filename'];
            if (file_exists($path)) unlink($path);
            $conn->query("DELETE FROM photos WHERE id=$pid");
        }
    }

    // DESIGNS - MULTI UPLOAD with PDF THUMBNAILS
    if (isset($_POST['upload_designs']) && !empty($_FILES['designs']['name'][0])) {
        $target_dir = "uploads/designs/";
        $thumb_dir = $target_dir . "thumbs/";
        if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0775, true);
        $count = 0;
        $max = 20;
        $allowed = ['vsdx','vsd','pdf','xps','doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp','zip'];

        foreach ($_FILES['designs']['tmp_name'] as $k => $tmp) {
            if ($count >= $max) break;
            if ($_FILES['designs']['error'][$k] !== UPLOAD_ERR_OK) continue;

            $name = basename($_FILES['designs']['name'][$k]);
            $target = $target_dir . time() . '_' . $name;
            $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                if (move_uploaded_file($tmp, $target)) {
                    $fn = basename($target);
                    $thumb_path = null;

                    if ($ext === 'pdf' && extension_loaded('imagick')) {
                        try {
                            $imagick = new Imagick();
                            $imagick->setResolution(150, 150);
                            $imagick->readImage($target . '[0]');
                            $imagick->setImageFormat('png');
                            $imagick->thumbnailImage(200, 200, true);
                            $thumb_filename = 'thumb_' . time() . '_' . pathinfo($name, PATHINFO_FILENAME) . '.png';
                            $thumb_full = $thumb_dir . $thumb_filename;
                            $imagick->writeImage($thumb_full);
                            $imagick->clear();
                            $imagick->destroy();
                            $thumb_path = 'thumbs/' . $thumb_filename;
                        } catch (Exception $e) {
                            error_log("Imagick thumbnail failed: " . $e->getMessage());
                        }
                    }

                    $thumb_sql = $thumb_path ? "'$thumb_path'" : 'NULL';
                    $sql = "INSERT INTO designs (house_id, filename, thumbnail, upload_date) VALUES ($house_id, '$fn', $thumb_sql, NOW())";
                    $conn->query($sql);
                    $count++;
                }
            }
        }
        if ($count > 0) {
            echo "<div style='background:#d4edda;color:#155724;padding:12px;margin:15px 0;border-radius:6px;'>
                  Uploaded $count design file" . ($count > 1 ? 's' : '') . "!
                  </div>";
        }
    }

    // DESIGNS - DELETE
    if (isset($_POST['delete_design'])) {
        $did = intval($_POST['design_id']);
        $r = $conn->query("SELECT filename, thumbnail FROM designs WHERE id=$did AND house_id=$house_id");
        if ($r && $row = $r->fetch_assoc()) {
            $path = "uploads/designs/" . $row['filename'];
            if (file_exists($path)) unlink($path);
            if ($row['thumbnail']) {
                $thumb_path = "uploads/designs/" . $row['thumbnail'];
                if (file_exists($thumb_path)) unlink($thumb_path);
            }
            $conn->query("DELETE FROM designs WHERE id=$did");
        }
    }

    // MAP LOCATION UPDATE
    if (isset($_POST['update_map'])) {
        $new_address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
        $new_lat = floatval($_POST['latitude'] ?? 0);
        $new_lng = floatval($_POST['longitude'] ?? 0);
        $new_tax = mysqli_real_escape_string($conn, $_POST['tax_number'] ?? '');
        $new_zoom = intval($_POST['map_zoom'] ?? 20);

        $sql = "UPDATE houses SET 
                address='$new_address', 
                latitude=$new_lat, 
                longitude=$new_lng, 
                tax_number='$new_tax', 
                map_zoom=$new_zoom 
                WHERE id=$house_id";
        $conn->query($sql);

        // Refresh house data
        $house = $conn->query("SELECT name, address, latitude, longitude, tax_number, map_zoom FROM houses WHERE id = $house_id")->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $house_name; ?> - Home Reporting System</title>
    <link rel="stylesheet" href="styles.css">
    <script src="scripts.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { font-family: Arial, sans-serif; background:#f8f9fa; color:#333; margin:0; padding:20px; }
        .container { max-width:1200px; margin:0 auto; }
        header { margin-bottom:30px; display:flex; align-items:center; flex-wrap:wrap; gap:30px; }
        .logo-container { display:flex; flex-direction:column; align-items:flex-start; }
        .logo-container img { max-width:220px; height:auto; }
        .logo-text { margin-top:10px; font-size:1.5em; font-weight:bold; color:#2c3e50; }
        h1 { color:#2c3e50; margin:0; }
        .tablink { background:#34495e; color:white; border:none; padding:12px 24px; margin:5px; cursor:pointer; border-radius:6px; font-size:1.1em; }
        .tablink:hover { background:#1abc9c; }
        .tab { display:none; padding:20px; background:white; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
        .section-card { background:#fff; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
        .delete-btn { background:#e74c3c; color:white; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; }
        .delete-btn:hover { background:#c0392b; }
        .photo-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:20px; }
        .photo-item { text-align:center; position:relative; }
        .photo-item img { max-width:100%; height:auto; border-radius:6px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
        table.breaker-table { width:100%; border-collapse:collapse; }
        table.breaker-table th, table.breaker-table td { border:1px solid #ddd; padding:10px; text-align:center; }
        table.breaker-table th { background:#f1f1f1; }
        .panel-header { cursor:pointer; background:#f0f0f0; padding:12px; border-radius:6px; font-weight:bold; margin-bottom:10px; }
        .panel-content { display:none; padding:15px; border:1px solid #ddd; border-radius:0 0 6px 6px; }
    </style>
</head>
<body>
<div class="container">

    <header>
        <div class="logo-container">
            <img src="logo.png" alt="Home Reporting System" style="max-width:220px; height:auto;">
            <span class="logo-text"></span>
        </div>
        
        <div style="margin-left:auto; display:flex; align-items:center; gap:20px;">
            <h1 style="margin:0;"><?php echo $house_name; ?></h1>
            <a href="index.php" style="background:#34495e; color:white; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:bold;"
               onmouseover="this.style.backgroundColor='#1abc9c';" onmouseout="this.style.backgroundColor='#34495e';">
                ← Back to Houses List
            </a>
        </div>
    </header>

    <button class="tablink" onclick="openTab(event, 'permanent')">Permanent Items</button>
    <button class="tablink" onclick="openTab(event, 'utility')">Utility Services</button>
    <button class="tablink" onclick="openTab(event, 'household')">Household Items</button>
    <button class="tablink" onclick="openTab(event, 'photos')">Photos</button>
    <button class="tablink" onclick="openTab(event, 'designs')">Designs</button>
    <button class="tablink" onclick="openTab(event, 'map')">Map Location</button>

    <!-- PERMANENT ITEMS -->
    <div id="permanent" class="tab">
        <h2>Permanent Items</h2>
        <form method="post">
            <?php
            $conn = new mysqli($servername, $username, $password, $dbname);
            $item_types = ['furnace', 'water_heater', 'dishwasher', 'washer', 'dryer', 'ac'];
            foreach ($item_types as $type) {
                $sql = "SELECT * FROM permanent_items WHERE house_id = $house_id AND item_type = '$type'";
                $result = $conn->query($sql);
                $row = $result->fetch_assoc() ?? [];
                $display_name = $type === 'ac' ? 'Air Conditioner' : ucfirst(str_replace('_', ' ', $type));
                echo "<div class='section-card'><h3>$display_name</h3>";
                echo "Brand: <input type='text' name='{$type}_brand' value='" . htmlspecialchars($row['brand'] ?? '') . "'><br><br>";
                echo "Model: <input type='text' name='{$type}_model' value='" . htmlspecialchars($row['model'] ?? '') . "'><br><br>";
                echo "SN: <input type='text' name='{$type}_sn' value='" . htmlspecialchars($row['sn'] ?? '') . "'><br><br>";
                echo "Efficiency: <input type='text' name='{$type}_efficiency' value='" . htmlspecialchars($row['efficiency'] ?? '') . "'><br><br>";
                echo "KWH: <input type='number' step='0.01' name='{$type}_kwh' value='" . ($row['kwh'] ?? 0) . "'><br><br>";
                if (in_array($type, ['water_heater','dishwasher'])) {
                    echo "Capacity: <input type='number' name='{$type}_capacity' value='" . ($row['capacity'] ?? 0) . "'><br><br>";
                }
                echo "</div>";
            }
            $conn->close();
            ?>
            <input type="submit" name="update_permanent" value="Update Permanent Items">
        </form>
    </div>

    <!-- UTILITY SERVICES -->
    <div id="utility" class="tab">
        <h2>Utility Services</h2>

        <?php
        $conn = new mysqli($servername, $username, $password, $dbname);
        $meter = $conn->query("SELECT * FROM electric_meters WHERE house_id = $house_id")->fetch_assoc() ?? [];
        echo "<div class='section-card'><h3>Electric Meter</h3><form method='post'>
              Meter Number: <input type='text' name='meter_number' value='" . htmlspecialchars($meter['meter_number'] ?? '') . "'><br><br>
              Company: <input type='text' name='company' value='" . htmlspecialchars($meter['company'] ?? '') . "'><br><br>
              Phone: <input type='text' name='phone' value='" . htmlspecialchars($meter['phone'] ?? '') . "'><br><br>
              <input type='submit' name='update_meter' value='Update Meter'></form></div>";

        $gen = $conn->query("SELECT * FROM generators WHERE house_id = $house_id")->fetch_assoc() ?? [];
        echo "<div class='section-card'><h3>Generator</h3><form method='post'>
              Brand: <input type='text' name='brand' value='" . htmlspecialchars($gen['brand'] ?? '') . "'><br><br>
              Model: <input type='text' name='model' value='" . htmlspecialchars($gen['model'] ?? '') . "'><br><br>
              SN: <input type='text' name='sn' value='" . htmlspecialchars($gen['sn'] ?? '') . "'><br><br>
              Efficiency: <input type='text' name='efficiency' value='" . htmlspecialchars($gen['efficiency'] ?? '') . "'><br><br>
              KWH: <input type='number' step='0.01' name='kwh' value='" . ($gen['kwh'] ?? 0) . "'><br><br>
              Fuel Type: <select name='fuel_type'><option value='LP' " . (($gen['fuel_type'] ?? 'LP') == 'LP' ? 'selected' : '') . ">LP</option>
              <option value='NG' " . (($gen['fuel_type'] ?? 'LP') == 'NG' ? 'selected' : '') . ">NG</option></select><br><br>
              <input type='submit' name='update_generator' value='Update Generator'></form></div>";

        $inv = $conn->query("SELECT * FROM solar_inverters WHERE house_id = $house_id")->fetch_assoc() ?? [];
        echo "<div class='section-card'><h3>Solar Inverter</h3><form method='post'>
              Brand: <input type='text' name='brand' value='" . htmlspecialchars($inv['brand'] ?? '') . "'><br><br>
              Model: <input type='text' name='model' value='" . htmlspecialchars($inv['model'] ?? '') . "'><br><br>
              SN: <input type='text' name='sn' value='" . htmlspecialchars($inv['sn'] ?? '') . "'><br><br>
              KWH: <input type='number' step='0.01' name='kwh' value='" . ($inv['kwh'] ?? 0) . "'><br><br>
              <input type='submit' name='update_inverter' value='Update Inverter'></form></div>";
        $conn->close();
        ?>

        <!-- SOLAR PANELS BY STRING -->
        <div class="section-card">
            <h3>Solar Panels by String</h3>
            <form method="post">
                Connection Type: <select name="connection_type">
                    <option value="Series">Series</option>
                    <option value="Parallel">Parallel</option>
                </select>
                <input type="submit" name="add_solar_string" value="Add New String">
            </form>
            <?php
            $conn = new mysqli($servername, $username, $password, $dbname);
            $strings = $conn->query("SELECT * FROM solar_strings WHERE house_id = $house_id");
            while ($string = $strings->fetch_assoc()) {
                $string_id = $string['id'];
                echo "<div class='section-card' style='margin-top:20px;'>";
                echo "<h4>String #{$string_id} - {$string['connection_type']}</h4>";
                echo "<table><tr><th>Brand</th><th>Watts</th><th>Action</th></tr>";
                $panels = $conn->query("SELECT * FROM solar_panels WHERE string_id = $string_id");
                while ($panel = $panels->fetch_assoc()) {
                    echo "<tr><td>" . htmlspecialchars($panel['brand']) . "</td><td>{$panel['watts']}</td><td>";
                    echo "<form method='post'><input type='hidden' name='panel_id' value='{$panel['id']}'><input type='submit' name='delete_solar_panel' value='Delete' class='delete-btn'></form>";
                    echo "</td></tr>";
                }
                echo "</table>";
                echo "<form method='post' style='margin-top:10px;'>";
                echo "Brand: <input type='text' name='brand'><br><br>";
                echo "Watts: <input type='number' name='watts'><br><br>";
                echo "<input type='hidden' name='string_id' value='$string_id'>";
                echo "<input type='submit' name='add_solar_panel' value='Add Panel'>";
                echo "</form>";
                echo "</div>";
            }
            $conn->close();
            ?>
        </div>

        <!-- BATTERY BANK -->
        <div class="section-card">
            <h3>Battery Bank</h3>
            <form method="post">
                Connection Type: <select name="connection_type">
                    <option value="Parallel">Parallel</option>
                    <option value="Series">Series</option>
                </select>
                <input type="submit" name="add_battery_string" value="Add New String">
            </form>
            <?php
            $conn = new mysqli($servername, $username, $password, $dbname);
            $strings = $conn->query("SELECT * FROM battery_strings WHERE house_id = $house_id");
            while ($string = $strings->fetch_assoc()) {
                $string_id = $string['id'];
                echo "<div class='section-card' style='margin-top:20px;'>";
                echo "<h4>String #{$string_id} - {$string['connection_type']}</h4>";
                echo "<table><tr><th>Brand</th><th>Watts</th><th>Action</th></tr>";
                $batteries = $conn->query("SELECT * FROM batteries WHERE string_id = $string_id");
                while ($battery = $batteries->fetch_assoc()) {
                    echo "<tr><td>" . htmlspecialchars($battery['brand']) . "</td><td>{$battery['watts']}</td><td>";
                    echo "<form method='post'><input type='hidden' name='battery_id' value='{$battery['id']}'><input type='submit' name='delete_battery' value='Delete' class='delete-btn'></form>";
                    echo "</td></tr>";
                }
                echo "</table>";
                echo "<form method='post' style='margin-top:10px;'>";
                echo "Brand: <input type='text' name='brand'><br><br>";
                echo "Watts: <input type='number' name='watts'><br><br>";
                echo "<input type='hidden' name='string_id' value='$string_id'>";
                echo "<input type='submit' name='add_battery' value='Add Battery'>";
                echo "</form>";
                echo "</div>";
            }
            $conn->close();
            ?>
        </div>

        <!-- CIRCUIT BREAKER DIRECTORY -->
        <div class="section-card">
            <h3>Circuit Breaker Directory</h3>
            <form method="post">
                <label>Panel Name:</label><br>
                <input type="text" name="name" placeholder="e.g. Main Panel" required><br><br>

                <label>Panel Size (total breaker spaces):</label><br>
                <select name="spaces" required>
                    <option value="6">6 spaces (3 rows)</option>
                    <option value="12">12 spaces (6 rows)</option>
                    <option value="24">24 spaces (12 rows)</option>
                    <option value="28" selected>28 spaces (14 rows) — standard</option>
                </select><br><br>

                <input type="submit" name="add_electric_panel" value="Add Panel">
            </form>

            <?php
            $conn = new mysqli($servername, $username, $password, $dbname);
            $panels = $conn->query("SELECT id, name, spaces FROM electric_panels WHERE house_id = $house_id");
            while ($panel = $panels->fetch_assoc()) {
                $panel_id = $panel['id'];
                $panel_name = htmlspecialchars($panel['name']);
                $spaces = (int)$panel['spaces'];
                $rows = ceil($spaces / 2);

                echo "<div class='section-card' style='margin-top:20px;'>";
                echo "<h4 onclick='togglePanel($panel_id)' class='panel-header'>Panel: $panel_name (#$panel_id, $spaces spaces) - Click to expand/collapse</h4>";
                echo "<div id='panel-content-$panel_id' class='panel-content' style='display:block;'>";
                echo "<form method='post'>";
                echo "<table class='breaker-table'>";
                echo "<tr><th>Breaker</th><th>Location</th><th>Amp</th><th>Breaker</th><th>Location</th><th>Amp</th></tr>";

                // Top row = highest numbers, bottom row = 1 & 2
                for ($r = $rows; $r >= 1; $r--) {
                    $left_breaker = $spaces - (($rows - $r) * 2);
                    $right_breaker = $left_breaker - 1;

                    $odd_room = $conn->query("SELECT room FROM breakers WHERE panel_id = $panel_id AND column_num = 1 AND row_num = $r")->fetch_assoc()['room'] ?? '';
                    $odd_amp  = $conn->query("SELECT amp FROM breakers WHERE panel_id = $panel_id AND column_num = 1 AND row_num = $r")->fetch_assoc()['amp'] ?? 0;
                    $even_room = $conn->query("SELECT room FROM breakers WHERE panel_id = $panel_id AND column_num = 2 AND row_num = $r")->fetch_assoc()['room'] ?? '';
                    $even_amp  = $conn->query("SELECT amp FROM breakers WHERE panel_id = $panel_id AND column_num = 2 AND row_num = $r")->fetch_assoc()['amp'] ?? 0;

                    echo "<tr>";
                    echo "<td>$left_breaker</td>";
                    echo "<td><input type='text' name='room_{$r}_1' value='" . htmlspecialchars($odd_room) . "'></td>";
                    echo "<td><input type='number' name='amp_{$r}_1' value='$odd_amp'></td>";
                    echo "<td>$right_breaker</td>";
                    echo "<td><input type='text' name='room_{$r}_2' value='" . htmlspecialchars($even_room) . "'></td>";
                    echo "<td><input type='number' name='amp_{$r}_2' value='$even_amp'></td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<input type='hidden' name='panel_id' value='$panel_id'>";
                echo "<input type='submit' name='update_breakers' value='Update Panel' style='margin-top:15px;'>";
                echo "</form>";

                // Delete Panel button
                echo "<form method='post' style='margin-top:15px;' onsubmit='return confirm(\"Delete panel $panel_name and all its breakers? This cannot be undone.\");'>";
                echo "<input type='hidden' name='panel_id' value='$panel_id'>";
                echo "<input type='submit' name='delete_panel' value='Delete Panel' class='delete-btn' style='background:#e74c3c; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer;'>";
                echo "</form>";

                echo "<form method='post' action='export_breakers.php' style='margin-top:10px;'>";
                echo "<input type='hidden' name='panel_id' value='$panel_id'>";
                echo "<input type='submit' value='Export to CSV' style='padding:8px 16px; background:#3498db; color:white; border:none; border-radius:4px; cursor:pointer;'>";
                echo "</form>";

                echo "</div></div>";
            }
            $conn->close();
            ?>
        </div>
    </div>

    <!-- HOUSEHOLD ITEMS -->
    <div id="household" class="tab">
        <h2>Household Items</h2>

        <div class="section-card">
            <h3>Add New Item</h3>
            <form method="post">
                Type: <select name="type">
                    <option value="TV">TV</option>
                    <option value="Server">Server</option>
                </select><br><br>
                Brand: <input type="text" name="brand"><br><br>
                Model: <input type="text" name="model"><br><br>
                SN: <input type="text" name="sn"><br><br>
                Notes: <textarea name="notes" rows="3" style="width:100%;"></textarea><br><br>
                <input type="submit" name="add_household" value="Add Item">
            </form>
        </div>

        <?php
        $conn = new mysqli($servername, $username, $password, $dbname);
        $sql = "SELECT * FROM household_items WHERE house_id = $house_id ORDER BY type, id";
        $items = $conn->query($sql);
        if ($items->num_rows > 0) {
            while ($item = $items->fetch_assoc()) {
                echo "<div class='section-card' style='margin-top:20px;'>";
                echo "<h3>{$item['type']} #{$item['id']}</h3>";
                echo "<form method='post'>";
                echo "Brand: <input type='text' name='brand' value='" . htmlspecialchars($item['brand'] ?? '') . "'><br><br>";
                echo "Model: <input type='text' name='model' value='" . htmlspecialchars($item['model'] ?? '') . "'><br><br>";
                echo "SN: <input type='text' name='sn' value='" . htmlspecialchars($item['sn'] ?? '') . "'><br><br>";
                echo "Notes: <textarea name='notes' rows='4' style='width:100%;'>" . htmlspecialchars($item['notes'] ?? '') . "</textarea><br><br>";
                echo "<input type='hidden' name='item_id' value='{$item['id']}'>";
                echo "<input type='submit' name='update_household' value='Update'>";
                echo "</form>";
                echo "<form method='post' style='margin-top:10px;'>";
                echo "<input type='hidden' name='item_id' value='{$item['id']}'>";
                echo "<input type='submit' name='delete_household' value='Delete' class='delete-btn' onclick='return confirm(\"Delete this {$item['type']}?\");'>";
                echo "</form>";
                echo "</div>";
            }
        } else {
            echo "<p style='color:#777;'>No household items added yet.</p>";
        }
        $conn->close();
        ?>
    </div>

    <!-- PHOTOS -->
    <div id="photos" class="tab">
        <h2>Photos & Scans</h2>

        <?php
        $conn = new mysqli($servername, $username, $password, $dbname);

        $photos_sort = $_GET['photos_sort'] ?? 'date_desc';
        $photos_filter = $_GET['photos_filter'] ?? 'all';

        $order_by = 'upload_date DESC';
        if ($photos_sort == 'date_asc') $order_by = 'upload_date ASC';
        if ($photos_sort == 'name_asc') $order_by = 'filename ASC';
        if ($photos_sort == 'name_desc') $order_by = 'filename DESC';

        $where = '';
        if ($photos_filter != 'all') {
            $where = "AND section = '$photos_filter'";
        }

        function photo_grid($conn, $house_id, $section, $is_ir, $order_by, $where) {
            $title = $is_ir ? "IR " . ucfirst($section) : "Regular " . ucfirst($section);
            $sql = "SELECT * FROM photos WHERE house_id = $house_id AND section = '$section' AND is_ir = $is_ir";
            if ($where) $sql .= " $where";
            $sql .= " ORDER BY $order_by";
            $photos = $conn->query($sql);
            echo "<div class='photo-grid'>";
            if ($photos->num_rows == 0) {
                echo "<p style='color:#777;'>No items in this section yet.</p>";
            } else {
                while ($photo = $photos->fetch_assoc()) {
                    $fn = htmlspecialchars($photo['filename']);
                    $date = date('M j, Y g:i A', strtotime($photo['upload_date']));
                    $full_path = "uploads/photos/" . $fn;
                    $size = file_exists($full_path) ? filesize($full_path) : 0;
                    $size_str = $size > 1024*1024 ? round($size / (1024*1024), 1) . ' MB' : round($size / 1024, 1) . ' KB';

                    $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                    $icon = 'fa-image';
                    $icon_color = '#6b7280';
                    if ($ext === 'jpg' || $ext === 'jpeg') {
                        $icon = 'fa-file-image';
                        $icon_color = '#f59e0b';
                    } else if ($ext === 'png') {
                        $icon = 'fa-file-image';
                        $icon_color = '#3b82f6';
                    } else if ($ext === 'gif') {
                        $icon = 'fa-file-image';
                        $icon_color = '#8b5cf6';
                    } else if ($ext === 'webp') {
                        $icon = 'fa-file-image';
                        $icon_color = '#10b981';
                    }

                    echo "<div class='photo-item' style='text-align:center;'>";
                    echo "<img src='uploads/photos/$fn' alt='$title' style='max-width:100%; height:auto; border-radius:4px;'>";
                    echo "<p style='margin:8px 0; font-size:0.95em;'>";
                    echo "<i class='fa-solid $icon' style='color:$icon_color; margin-right:6px; font-size:1.2em;'></i>";
                    echo "<a href='uploads/photos/$fn' target='_blank' download>$fn</a></p>";
                    echo "<p style='font-size:0.85em; color:#666;'>$size_str • $date</p>";
                    echo "<form method='post' style='margin:0;'>";
                    echo "<input type='hidden' name='photo_id' value='{$photo['id']}'>";
                    echo "<input type='submit' name='delete_photo' value='Delete' class='delete-btn' onclick='return confirm(\"Delete this photo?\");'>";
                    echo "</form>";
                    echo "</div>";
                }
            }
            echo "</div>";
        }
        ?>

        <div class="section-card" style="margin-bottom:20px;">
            <form method="get" style="display:flex; gap:15px; flex-wrap:wrap; align-items:center;">
                <input type="hidden" name="id" value="<?php echo $house_id; ?>">
                <label>Sort by:</label>
                <select name="photos_sort">
                    <option value="date_desc" <?php echo ($photos_sort == 'date_desc') ? 'selected' : ''; ?>>Newest first</option>
                    <option value="date_asc" <?php echo ($photos_sort == 'date_asc') ? 'selected' : ''; ?>>Oldest first</option>
                    <option value="name_asc" <?php echo ($photos_sort == 'name_asc') ? 'selected' : ''; ?>>File name A-Z</option>
                    <option value="name_desc" <?php echo ($photos_sort == 'name_desc') ? 'selected' : ''; ?>>File name Z-A</option>
                </select>

                <label>Filter:</label>
                <select name="photos_filter">
                    <option value="all" <?php echo ($photos_filter == 'all') ? 'selected' : ''; ?>>All Sections</option>
                    <option value="Interior" <?php echo ($photos_filter == 'Interior') ? 'selected' : ''; ?>>Interior</option>
                    <option value="Exterior" <?php echo ($photos_filter == 'Exterior') ? 'selected' : ''; ?>>Exterior</option>
                </select>

                <input type="submit" value="Apply">
            </form>
        </div>

        <div class="section-card">
            <h3>Regular Interior Photos</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="photos[]" accept="image/*" multiple>
                <small>Select up to 15 photos</small><br><br>
                <input type="hidden" name="section" value="Interior">
                <input type="hidden" name="is_ir" value="0">
                <input type="submit" name="upload_photo" value="Upload Regular Interior">
            </form>
            <?php photo_grid($conn, $house_id, 'Interior', 0, $order_by, $where); ?>
        </div>

        <div class="section-card">
            <h3>Regular Exterior Photos</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="photos[]" accept="image/*" multiple>
                <small>Select up to 15 photos</small><br><br>
                <input type="hidden" name="section" value="Exterior">
                <input type="hidden" name="is_ir" value="0">
                <input type="submit" name="upload_photo" value="Upload Regular Exterior">
            </form>
            <?php photo_grid($conn, $house_id, 'Exterior', 0, $order_by, $where); ?>
        </div>

        <div class="section-card">
            <h3>IR Interior Scans</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="photos[]" accept="image/*" multiple>
                <small>Select up to 15 scans</small><br><br>
                <input type="hidden" name="section" value="Interior">
                <input type="hidden" name="is_ir" value="1">
                <input type="submit" name="upload_photo" value="Upload IR Interior">
            </form>
            <?php photo_grid($conn, $house_id, 'Interior', 1, $order_by, $where); ?>
        </div>

        <div class="section-card">
            <h3>IR Exterior Scans</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="photos[]" accept="image/*" multiple>
                <small>Select up to 15 scans</small><br><br>
                <input type="hidden" name="section" value="Exterior">
                <input type="hidden" name="is_ir" value="1">
                <input type="submit" name="upload_photo" value="Upload IR Exterior">
            </form>
            <?php photo_grid($conn, $house_id, 'Exterior', 1, $order_by, $where); ?>
        </div>
        <?php $conn->close(); ?>
    </div>

    <!-- DESIGNS -->
    <div id="designs" class="tab">
        <h2>Designs / Drawings / Plans</h2>

        <div class="section-card">
            <h3>Upload Design Files</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="designs[]" multiple>
                <br><small>Select multiple files at once.<br>Allowed: Visio (.vsd/.vsdx), PDF, XPS, Office docs, LibreOffice, ZIP</small><br><br>
                <input type="submit" name="upload_designs" value="Upload Files">
            </form>
        </div>

        <div class="section-card">
            <h3>Uploaded Designs</h3>

            <?php
            $designs_sort = $_GET['designs_sort'] ?? 'date_desc';
            $designs_filter = $_GET['designs_filter'] ?? 'all';

            $order_by = 'upload_date DESC';
            if ($designs_sort == 'date_asc') $order_by = 'upload_date ASC';
            if ($designs_sort == 'name_asc') $order_by = 'filename ASC';
            if ($designs_sort == 'name_desc') $order_by = 'filename DESC';

            $where = '';
            if ($designs_filter != 'all') {
                $where = "AND LOWER(filename) LIKE '%.$designs_filter%'";
            }
            ?>

            <form method="get" style="display:flex; gap:15px; flex-wrap:wrap; align-items:center; margin-bottom:15px;">
                <input type="hidden" name="id" value="<?php echo $house_id; ?>">
                <label>Sort by:</label>
                <select name="designs_sort">
                    <option value="date_desc" <?php echo ($designs_sort == 'date_desc') ? 'selected' : ''; ?>>Newest first</option>
                    <option value="date_asc" <?php echo ($designs_sort == 'date_asc') ? 'selected' : ''; ?>>Oldest first</option>
                    <option value="name_asc" <?php echo ($designs_sort == 'name_asc') ? 'selected' : ''; ?>>File name A-Z</option>
                    <option value="name_desc" <?php echo ($designs_sort == 'name_desc') ? 'selected' : ''; ?>>File name Z-A</option>
                </select>

                <label>Filter by type:</label>
                <select name="designs_filter">
                    <option value="all" <?php echo ($designs_filter == 'all') ? 'selected' : ''; ?>>All</option>
                    <option value="pdf" <?php echo ($designs_filter == 'pdf') ? 'selected' : ''; ?>>PDF</option>
                    <option value="doc" <?php echo ($designs_filter == 'doc') ? 'selected' : ''; ?>>Word</option>
                    <option value="xls" <?php echo ($designs_filter == 'xls') ? 'selected' : ''; ?>>Excel</option>
                    <option value="ppt" <?php echo ($designs_filter == 'ppt') ? 'selected' : ''; ?>>PowerPoint</option>
                    <option value="vsd" <?php echo ($designs_filter == 'vsd') ? 'selected' : ''; ?>>Visio</option>
                    <option value="zip" <?php echo ($designs_filter == 'zip') ? 'selected' : ''; ?>>ZIP</option>
                </select>

                <input type="submit" value="Apply">
            </form>

            <?php
            $conn = new mysqli($servername, $username, $password, $dbname);
            $sql = "SELECT * FROM designs WHERE house_id = $house_id";
            if ($where) $sql .= " $where";
            $sql .= " ORDER BY $order_by";
            $result = $conn->query($sql);

            if ($result->num_rows == 0) {
                echo "<p style='color:#777; font-style:italic;'>No design files match the filter.</p>";
            } else {
                echo "<div class='photo-grid'>";
                while ($file = $result->fetch_assoc()) {
                    $filename = htmlspecialchars($file['filename']);
                    $thumb = $file['thumbnail'] ? 'uploads/designs/' . htmlspecialchars($file['thumbnail']) : null;
                    $date = date('M j, Y g:i A', strtotime($file['upload_date']));
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    $full_path = "uploads/designs/" . $file['filename'];
                    $size = file_exists($full_path) ? filesize($full_path) : 0;
                    $size_str = $size > 1024*1024 ? round($size / (1024*1024), 1) . ' MB' : round($size / 1024, 1) . ' KB';

                    $icon = 'fa-file';
                    $icon_color = '#6b7280';
                    if (strpos($ext, 'pdf') !== false) {
                        $icon = 'fa-file-pdf';
                        $icon_color = '#dc2626';
                    } else if (strpos($ext, 'doc') !== false || strpos($ext, 'odt') !== false) {
                        $icon = 'fa-file-word';
                        $icon_color = '#2563eb';
                    } else if (strpos($ext, 'xls') !== false || strpos($ext, 'ods') !== false) {
                        $icon = 'fa-file-excel';
                        $icon_color = '#15803d';
                    } else if (strpos($ext, 'ppt') !== false || strpos($ext, 'odp') !== false) {
                        $icon = 'fa-file-powerpoint';
                        $icon_color = '#c2410c';
                    } else if (strpos($ext, 'vsd') !== false) {
                        $icon = 'fa-file-lines';
                        $icon_color = '#1d4ed8';
                    } else if ($ext === 'zip') {
                        $icon = 'fa-file-zipper';
                        $icon_color = '#ea580c';
                    }

                    $preview = $thumb 
                        ? "<img src='$thumb' alt='Preview of $filename' style='max-width:100%; height:auto; border-radius:4px; object-fit:contain;'>"
                        : '<div style="height:140px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;border-radius:6px;font-weight:bold;color:#6c757d;border:1px solid #dee2e6;">.' . strtoupper($ext) . '</div>';

                    echo "<div class='photo-item' style='text-align:center;'>";
                    echo $preview;
                    echo "<p style='margin:8px 0; font-size:0.95em;'>";
                    echo "<i class='fa-solid $icon' style='color:$icon_color; margin-right:6px; font-size:1.2em;'></i>";
                    echo "<a href='uploads/designs/$filename' target='_blank' download>$filename</a></p>";
                    echo "<p style='font-size:0.85em; color:#666;'>$size_str • Uploaded: $date</p>";
                    echo "<form method='post' style='margin:0;'>";
                    echo "<input type='hidden' name='design_id' value='{$file['id']}'>";
                    echo "<input type='submit' name='delete_design' value='Delete' class='delete-btn' onclick='return confirm(\"Delete $filename?\");'>";
                    echo "</form>";
                    echo "</div>";
                }
                echo "</div>";
            }
            $conn->close();
            ?>
        </div>
    </div>

    <!-- MAP LOCATION TAB -->
    <div id="map" class="tab">
        <h2>Map Location</h2>

        <?php
        $conn = new mysqli($servername, $username, $password, $dbname);
        $house_data = $conn->query("SELECT address, latitude, longitude, tax_number, map_zoom FROM houses WHERE id = $house_id")->fetch_assoc();
        $conn->close();

        $address = htmlspecialchars($house_data['address'] ?? '');
        $lat = $house_data['latitude'] ?? '';
        $lng = $house_data['longitude'] ?? '';
        $tax = htmlspecialchars($house_data['tax_number'] ?? '');
        $zoom = intval($house_data['map_zoom'] ?? 20);
        ?>

        <div class="section-card">
            <h3>Property Details</h3>
            <form method="post">
                <label>Address:</label><br>
                <textarea name="address" rows="3" style="width:100%;"><?php echo $address; ?></textarea><br><br>

                <label>GPS Coordinates:</label><br>
                <input type="text" name="latitude" placeholder="Latitude (e.g. 42.322785)" value="<?php echo htmlspecialchars($lat); ?>" style="width:48%; margin-right:2%;">
                <input type="text" name="longitude" placeholder="Longitude (e.g. -88.282123)" value="<?php echo htmlspecialchars($lng); ?>" style="width:48%;"><br><br>

                <label>Map Zoom Level (1–20, default 20):</label><br>
                <input type="number" name="map_zoom" min="1" max="20" value="<?php echo $zoom; ?>" style="width:100px;"><br><br>

                <label>Tax/Parcel Number:</label><br>
                <input type="text" name="tax_number" value="<?php echo $tax; ?>" style="width:100%;"><br><br>

                <input type="submit" name="update_map" value="Save Location Info">
            </form>
        </div>

        <?php if ($lat && $lng && $lat != 0 && $lng != 0): ?>
            <div class="section-card" style="margin-top:20px;">
                <h3>Map View (Zoom: <?php echo $zoom; ?>)</h3>
                <iframe 
                    width="100%" 
                    height="500" 
                    frameborder="0" 
                    scrolling="no" 
                    marginheight="0" 
                    marginwidth="0" 
                    src="https://www.openstreetmap.org/export/embed.html?bbox=<?php 
                        $delta = 0.0005; 
                        $bbox_left = $lng - $delta; 
                        $bbox_right = $lng + $delta; 
                        $bbox_bottom = $lat - $delta; 
                        $bbox_top = $lat + $delta; 
                        echo urlencode("$bbox_left,$bbox_bottom,$bbox_right,$bbox_top"); 
                    ?>&amp;layer=mapnik&amp;marker=<?php echo urlencode("$lat,$lng"); ?>"
                    style="border: 1px solid #ccc;">
                </iframe>
                <br/>
                <small>
                    <a href="https://www.openstreetmap.org/?mlat=<?php echo $lat; ?>&amp;mlon=<?php echo $lng; ?>#map=<?php echo $zoom; ?>/<?php echo $lat; ?>/<?php echo $lng; ?>" 
                       target="_blank" 
                       style="color:#555; text-decoration:underline;">
                       View Larger Map
                    </a>
                </small>
                <p style="margin-top:10px; font-size:0.95em; color:#555;">
                    <strong>Coordinates:</strong> <?php echo $lat; ?>, <?php echo $lng; ?><br>
                    <strong>Address:</strong> <?php echo $address ?: 'Not set'; ?><br>
                    <strong>Tax/Parcel #:</strong> <?php echo $tax ?: 'Not set'; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="section-card" style="margin-top:20px; background:#f8f9fa; border:1px solid #dee2e6;">
                <p style="color:#555; font-style:italic; text-align:center; padding:20px;">
                    Enter valid GPS coordinates above and save to display the map.
                </p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
// Tab switching
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablink");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

// Collapse/expand breaker panels
function togglePanel(panelId) {
    var content = document.getElementById('panel-content-' + panelId);
    if (content.style.display === "none") {
        content.style.display = "block";
    } else {
        content.style.display = "none";
    }
}
</script>

</body>
</html>
