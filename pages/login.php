<?php
require_once '../lib/security_headers.php';
setSecurityHeaders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logg inn - HearMeOut</title>
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
                <h1 class="hero-title">Logg inn på HearMeOut</h1>
                <p class="hero-text">Velg hvordan du vil logge inn</p>
            </div>
        </div>

        <div class="form-container">
            <div class="action-cards">
                <?php if (isset($_GET['student_registered_successfully'])) : ?>
                    <div class="form-success"><?php echo htmlspecialchars($_GET['student_registered_successfully']);
                    ?></div>
                <?php endif; ?>

                <div class="action-card">
                    <h2>Student</h2>
                    <p>Logg inn som student for å se dine emner</p>
                    <a href="student_login.php" class="form-submit">Student innlogging</a>
                </div>

                <div class="form-divider">
                    <span class="form-divider-text">eller</span>
                </div>

                <div class="action-card">
                    <h2>Foreleser</h2>
                    <p>Logg inn som foreleser for å administrere dine emner</p>
                    <a href="foreleser_login.php" class="form-submit">Foreleser innlogging</a>
                </div>
            </div>

            <div class="features-section">
                <h2>Ny bruker?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <h3>Student</h3>
                        <p>Opprett en studentkonto for å få tilgang til dine emner</p>
                        <a href="registrer_student.php" class="form-submit">Registrer som student</a>
                    </div>
                    <div class="feature-card">
                        <h3>Foreleser</h3>
                        <p>Opprett en foreleserkonto for å administrere dine emner</p>
                        <a href="registrer_foreleser.php" class="form-submit">Registrer som foreleser</a>
                    </div>
                </div>
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