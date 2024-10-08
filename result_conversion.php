<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Import Conversion Tool</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Conversion Result</h1>
        <?php
		session_start();
        if (isset($_SESSION['tabs_with_data']) && !empty($_SESSION['tabs_with_data'])) {
            echo "<p>The following tabs were successfully extracted:</p>";
            echo "<ul>";
            foreach ($_SESSION['tabs_with_data'] as $tab) {
                echo "<li>" . htmlspecialchars($tab) . "</li>";
            }
            echo "</ul>";
            // Clear the session data after displaying it
            unset($_SESSION['tabs_with_data']);
        } else {
            echo "<p>No data was extracted from any tabs.</p>";
        }
        ?>

        <!-- Back to upload button -->
        <button class="btn" onclick="window.location.href='index.html';">Back to Upload</button>
    </div>
</body>
</html>
