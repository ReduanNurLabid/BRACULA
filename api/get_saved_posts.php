<?php
// Redirect to new location
header("Location: posts/get_saved_posts.php" . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;
?>