<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glemt passord - HearMeOut</title>
    <link rel="stylesheet" href="../styling.css"> 
    <link rel="stylesheet" href="lib-css.css">
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo-link"><h1>HearMeOut</h1></a>
            <ul class="nav-links">
                <li><a href="../index.php">Hjem</a></li>
                <li><a href="../pages/registrer_student.php">Registrer som student</a></li>
                <li><a href="../pages/registrer_foreleser.php">Registrer som foreleser</a></li>
                <li><a href="../pages/foreleser_login.php">Logg inn som foreleser</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="hero-section">
            <div class="container">
                <h1 class="hero-title">Glemt passord?</h1>
                <p class="hero-text">Fyll ut følgende detaljer for å finne din profil</p>
            </div>
        </div>

        <div class="form-container">
            <?php if (isset($_GET['tomme_felt'])): ?>
                <div class="form-error"><?php echo htmlspecialchars($_GET['tomme_felt']); ?></div>
            <?php endif; ?>

            <form action="tilbakestill_passord.php" method="post">
                <div class="form-group">
                    <label class="form-label" for="email">E-post</label>
                    <input class="form-input" type="email" id="email" name="email" required placeholder="din@epost.no">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="emne_kode">Emne kode</label>
                    <input class="form-input" type="text" id="emne_kode" name="emne_kode" required placeholder="Skriv inn emnekode">
                </div>

                <div class="form-group">
                    <label class="form-label" for="pin_kode">Emne PIN</label>
                    <input class="form-input" type="text" id="pin_kode" name="pin_kode" required placeholder="Skriv inn PIN-kode">
                </div>
                
                <button type="submit" class="form-submit">Tilbakestill passord</button>
            </form>

            <?php if (isset($_GET['pw_byttet'])): ?>
                <div class="form-success"><?php echo htmlspecialchars($_GET['pw_byttet']); ?></div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 HearMeOut. Alle rettigheter reservert.</p>
        </div>
    </footer>
</body>
</html>
