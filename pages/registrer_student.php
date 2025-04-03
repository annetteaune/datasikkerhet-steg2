<?php
session_start();
?>
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
            <?php 
            // Vis feilmelding hvis den eksisterer
            if (isset($_SESSION['error'])) {
                echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']); // slett feilmeldingen etter visning
            }
            
            // Vis success-melding hvis den eksisterer
            if (isset($_SESSION['success'])) {
                echo '<div class="success-message">' . htmlspecialchars($_SESSION['success']) . '</div>';
                unset($_SESSION['success']); // slett success-meldingen etter visning
            }
            ?>

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
                    <label class="form-label" for="epost">E-post</label>
                    <input class="form-input" type="email" id="epost" name="epost" required placeholder="din@epost.no">
                </div>

                <div class="form-group">
                    <label class="form-label" for="passord">Passord</label>
                    <input class="form-input" type="password" id="passord" name="passord" required 
                           pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$"
                           title="Passordet må være minst 8 tegn langt og inneholde minst én stor bokstav, ett tall og ett spesialtegn">
                    <div class="password-requirements">
                        <ul>
                            <li id="length-check">Minst 8 tegn</li>
                            <li id="uppercase-check">Minst én stor bokstav</li>
                            <li id="number-check">Minst ett tall</li>
                            <li id="special-check">Minst ett spesialtegn</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="bekreft_passord">Bekreft passord</label>
                    <input class="form-input" type="password" id="bekreft_passord" name="bekreft_passord" required>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('passord');
        const confirmPasswordInput = document.getElementById('bekreft_passord');
        
        // Passordvalidering
        const checks = {
            length: {
                regex: /.{8,}/,
                element: document.getElementById('length-check')
            },
            uppercase: {
                regex: /[A-Z]/,
                element: document.getElementById('uppercase-check')
            },
            number: {
                regex: /[0-9]/,
                element: document.getElementById('number-check')
            },
            special: {
                regex: /[^A-Za-z0-9]/,
                element: document.getElementById('special-check')
            }
        };
        
        function validatePassword(password) {
            for (const [key, check] of Object.entries(checks)) {
                if (check.regex.test(password)) {
                    check.element.classList.add('valid');
                    check.element.classList.remove('invalid');
                } else {
                    check.element.classList.add('invalid');
                    check.element.classList.remove('valid');
                }
            }
        }
        
        // Real-time passordvalidering
        passwordInput.addEventListener('input', function() {
            validatePassword(this.value);
        });
        
        // Passordbekreftelse
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('Passordene stemmer ikke overens');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Vis feilmelding hvis den eksisterer
        <?php if (isset($_SESSION['error_message'])): ?>
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = '<?php echo htmlspecialchars($_SESSION['error_message']); ?>';
            document.querySelector('.form-container').insertBefore(errorDiv, document.querySelector('.form-group'));
        <?php endif; ?>
    });
    </script>
</body>
</html>