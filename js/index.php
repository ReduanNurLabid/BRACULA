<?php
// Redirect JS requests to the new location
$file = basename($_SERVER["REQUEST_URI"]);
if ($file != "index.php") {
    header("Location: public/js/" . $file);
    exit;
}
?>