```php
<?php
session_start();

// Remove all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Redirect to login page
header('Location: auth.php?msg=logged_out');
exit;
?>
```
