<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrer som student - HearMeOut</title>
    <link rel="stylesheet" href="../styling.css">
</head>
<body>
    <header>
        <nav>
            <h1>HearMeOut</h1>
            <ul class="nav-links">
                <li><a href="../index.php">Hjem</a></li>
                <li><a href="student_login.php">Student innlogging</a></li>
                <li><a href="foreleser_login.php">Foreleser innlogging</a></li>
                <li><a href="registrer_foreleser.php">Registrer som foreleser</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="hero-section">
            <div class="container">
                <h1 class="hero-title">Registrer deg som student</h1>
                <p class="hero-text">Opprett en konto for å få tilgang til dine emner og meldinger</p>
            </div>
        </div>

        <div class="form-container">
            <?php if (isset($_GET['error'])): ?>
                <div class="form-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <form action="../lib/register_student_action.php" method="post">
                <div class="form-group">
                    <label class="form-label" for="fornavn">Fornavn</label>
                    <input class="form-input" type="text" id="fornavn" name="fornavn" required placeholder="Skriv inn fornavn">
                </div>

                <div class="form-group">
                    <label class="form-label" for="etternavn">Etternavn</label>
                    <input class="form-input" type="text" id="etternavn" name="etternavn" required placeholder="Skriv inn etternavn">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">E-post</label>
                    <input class="form-input" type="email" id="email" name="email" required placeholder="din@epost.no">
                </div>

                <div class="form-group">
                    <label class="form-label" for="passord">Passord</label>
                    <input class="form-input" type="password" id="passord" name="passord" required placeholder="Velg et sterkt passord">
                </div>

                <div class="form-group">
                    <label class="form-label" for="bekreft_passord">Bekreft passord</label>
                    <input class="form-input" type="password" id="bekreft_passord" name="bekreft_passord" required placeholder="Gjenta passord">
                </div>

                <button type="submit" class="form-submit">Registrer deg</button>
            </form>

            <div class="form-divider">eller</div>

            <div class="form-links">
                <p>Har du allerede en konto? <a href="student_login.php">Logg inn her</a></p>
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