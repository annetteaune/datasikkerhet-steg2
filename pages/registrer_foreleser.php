<?php
require_once '../lib/security_headers.php';
setSecurityHeaders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrer som foreleser - HearMeOut</title>
    <link rel="stylesheet" href="../styling.css">
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo-link"><h1>HearMeOut</h1></a>
            <ul class="nav-links">
                <li><a href="../index.php">Hjem</a></li>
                <li><a href="foreleser_login.php">Logg inn</a></li>
                <li><a href="registrer_student.php">Registrer som student</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="hero-section">
            <div class="container">
                <h1 class="hero-title">Registrer som foreleser</h1>
                <p class="hero-text">Opprett en konto for å administrere dine emner</p>
            </div>
        </div>

        <div class="form-container">
            <form class="registration-form" action="../lib/register_teacher_action.php" method="post" enctype="multipart/form-data">
                <?php
                session_start();
                if (isset($_SESSION['error_message'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_messages'])): ?>
                    <?php foreach ($_SESSION['error_messages'] as $error): ?>
                        <div class="form-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['error_messages']); ?>
                <?php endif; ?>

                <?php if (isset($_GET['error_empty_form'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($_GET['error_empty_form']); ?></div>
                <?php endif; ?>

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
                    <?php if (isset($_GET['error_email_already_in_use'])): ?>
                        <div class="form-error"><?php echo htmlspecialchars($_GET['error_email_already_in_use']); ?></div>
                    <?php endif; ?>
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

                <div class="form-group">
                    <label class="form-label" for="emne_navn">Emnenavn</label>
                    <input class="form-input" type="text" id="emne_navn" name="emne_navn" required placeholder="Skriv inn emnenavn">
                </div>

                <div class="form-group">
                    <label class="form-label" for="emne_kode">Emnekode</label>
                    <input class="form-input" type="text" id="emne_kode" name="emne_kode" required placeholder="F.eks. DAT100">
                </div>

                <div class="form-group">
                    <label class="form-label" for="pin_kode">PIN-kode (4 siffer)</label>
                    <input class="form-input" type="text" id="pin_kode" name="pin_kode" required 
                           pattern="[0-9]{4}" maxlength="4" placeholder="Fire siffer">
                </div>

                <div class="form-group">
                    <label class="form-label" for="bilde">Profilbilde (valgfritt)</label>
                    <input class="form-input" type="file" id="bilde" name="bilde" accept="image/*">
                </div>

                <button type="submit" class="form-submit">Registrer deg</button>
            </form>

            <div class="form-divider">eller</div>

            <div class="form-links">
                <p>Har du allerede en konto? <a href="foreleser_login.php">Logg inn her</a></p>
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
            
            // PIN-kodevalidering
            const pinInput = document.getElementById('pin_kode');
            pinInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
                if (this.value.length === 4) {
                    this.setCustomValidity('');
                } else {
                    this.setCustomValidity('PIN-koden må være 4 siffer');
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