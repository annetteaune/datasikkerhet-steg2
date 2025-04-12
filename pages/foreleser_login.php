<?php
require_once '../lib/rate_limiter.php';
use CleanSteg1\Security\RateLimiter;
use function CleanSteg1\Security\applyRateLimit;
applyRateLimit();

require_once '../lib/security_headers.php';
setSecurityHeaders();

session_start();
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foreleser innlogging - HearMeOut</title>
    <link rel="stylesheet" href="../styling.css"> 
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo-link"><h1>HearMeOut</h1></a>
            <ul class="nav-links">
                <li><a href="../index.php">Hjem</a></li>
                <li><a href="registrer_student.php">Registrer som student</a></li>
                <li><a href="registrer_foreleser.php">Registrer som foreleser</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="hero-section">
            <div class="container">
                <h1 class="hero-title">Foreleser innlogging</h1>
                <p class="hero-text">Logg inn for å få tilgang til dine emner</p>
            </div>
        </div>

        <div class="form-container">
            <?php if (isset($_SESSION['error'])) : ?>
                <div class="error-message">
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']); // Fjern feilmeldingen etter visning
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['teacher_not_found'])) : ?>
                <div class="form-error"><?php echo htmlspecialchars($_GET['teacher_not_found']); ?></div>
            <?php endif; ?>

            <form action="../lib/login_action_foreleser.php" method="post">
                <div class="form-group">
                    <label class="form-label" for="email">E-post</label>
                    <input class="form-input" type="email" id="email" 
                        name="email" required placeholder="din@epost.no">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Passord</label>
                    <input class="form-input" type="password" id="password" 
                        name="password" required placeholder="Skriv inn passord">
                </div>
                
                <button type="submit" class="form-submit">Logg inn</button>
            </form>

            <div class="form-divider">
                <span class="form-divider-text">eller</span>
            </div>

            <div class="form-footer">
                <p>Glemt passord? <a href="../lib/glemt_passord.php">Klikk her</a></p>
                <p class="mt-2">Har du ikke en bruker? 
                        <a href="registrer_foreleser.php">Registrer deg som foreleser</a></p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 HearMeOut. Alle rettigheter reservert.</p>
        </div>
    </footer>
</body>
</html>