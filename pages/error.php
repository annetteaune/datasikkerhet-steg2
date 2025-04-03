<?php
session_start();
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feil - HearMeOut</title>
    <link rel="stylesheet" href="../styling.css">
    <link rel="stylesheet" href="../lib/lib-css.css">
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo-link"><h1>HearMeOut</h1></a>
        </nav>
    </header>

    <main>
        <div class="container">
            <h2>Beklager, det oppstod en feil</h2>
            <?php if (isset($_SESSION['error'])) : ?>
                <div class="error-message">
                    <?php
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php else : ?>
                <div class="error-message">
                    En ukjent feil har oppstått. Vennligst prøv igjen senere.
                </div>
            <?php endif; ?>
            <p>
                <a href="../index.php" class="button">Gå til forsiden</a>
            </p>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> HearMeOut. Alle rettigheter reservert.</p>
    </footer>
</body>
</html> 
