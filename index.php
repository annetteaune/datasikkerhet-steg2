<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HearMeOut</title>
    <link rel="stylesheet" href="styling.css"> 
</head>
<body>
    <header>
        <nav>
            <h1>HearMeOut</h1>
            <ul class="nav-links">
                <li><a href="pages/login.php">Logg inn</a></li>
                <li><a href="pages/registrer_student.php">Registrer som student</a></li>
                <li><a href="pages/registrer_foreleser.php">Registrer som foreleser</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="hero-section">
            <div class="container">
                <h1 class="hero-title">Velkommen til HearMeOut!</h1>
                <p class="hero-text">Din plattform for enkel kommunikasjon mellom studenter og forelesere.</p>
            </div>
        </div>

        <div class="form-container">
            <div class="action-cards">
                <div class="action-card">
                    <h2>Logg inn</h2>
                    <p>Se meldinger tilknyttet dine emner</p>
                    <a href="pages/login.php" class="form-submit">Logg inn</a>
                </div>

                <div class="form-divider">
                    <span class="form-divider-text">eller</span>
                </div>

                <div class="action-card">
                    <h2>Gå direkte til emne</h2>
                    <p>Fyll inn PIN-kode for å få tilgang til et emne</p>
                    <form action="lib/login_gjest.php" method="post">
                        <div class="form-group">
                            <label class="form-label" for="emneID">Emne PIN</label>
                            <input class="form-input" type="text" inputmode="numeric" id="emneID" name="emneID" required placeholder="Skriv inn PIN-kode">
                            <?php if (isset($_GET['no_pin'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($_GET['no_pin']); ?></div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="form-submit">Gå til emne</button>
                    </form>
                </div>
            </div>

            <div class="features-section">
                <h2>Hvorfor velge HearMeOut?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <h3>Enkel kommunikasjon</h3>
                        <p>Del spørsmål og tilbakemeldinger direkte med forelesere</p>
                    </div>
                    <div class="feature-card">
                        <h3>Rask tilgang</h3>
                        <p>Få tilgang til emner med en enkel PIN-kode</p>
                    </div>
                    <div class="feature-card">
                        <h3>Alltid tilgjengelig</h3>
                        <p>Få svar på dine spørsmål når som helst</p>
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
    <script>
        document.getElementById("toggleComments").addEventListener("click", function() {
            const wrapper = document.getElementById("commentWrapper");
            
            // Sjekk nåværende visningstilstand
            if (wrapper.style.display === "none") {
                wrapper.style.display = "block"; // Vis feltet
            } else {
                wrapper.style.display = "none"; // Skjul feltet
            }
        });

        document.getElementById("toggleAddComment").addEventListener("click", function() {
            const wrapper = document.getElementById("commentInputWrapper");
            
            // Sjekk nåværende visningstilstand
            if (wrapper.style.display === "none") {
                wrapper.style.display = "flex"; // Vis feltet
            } else {
                wrapper.style.display = "none"; // Skjul feltet
            }
        });

        document.getElementById("toggleReportMessage").addEventListener("click", function() {
            const wrapper = document.getElementById("reportInputWrapper");
            
            // Sjekk nåværende visningstilstand
            if (wrapper.style.display === "none") {
                wrapper.style.display = "flex"; // Vis feltet
            } else {
                wrapper.style.display = "none"; // Skjul feltet
            }
        });
    </script>
</body>
</html>
