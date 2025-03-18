<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrer som foreleser - HearMeOut</title>
    <link rel="stylesheet" href="../styling.css">
    <style>
        .file-upload-container {
            border: 2px dashed var(--border-color);
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 15px;
            background-color: var(--background-color);
            transition: border-color 0.3s ease;
        }

        .file-upload-container:hover {
            border-color: var(--primary-color);
        }

        .file-upload-container input[type="file"] {
            display: none;
        }

        .file-upload-label {
            cursor: pointer;
            display: block;
            padding: 10px;
        }

        .file-upload-button {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .file-name-display {
            margin-top: 10px;
            font-size: 0.9em;
            color: var(--text-color);
        }
    </style>
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
                <?php if (isset($_GET['error_empty_form'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($_GET['error_empty_form']); ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="fname">Fornavn</label>
                    <input class="form-input" type="text" id="fname" name="fname" required placeholder="Skriv inn fornavn">
                </div>

                <div class="form-group">
                    <label class="form-label" for="lname">Etternavn</label>
                    <input class="form-input" type="text" id="lname" name="lname" required placeholder="Skriv inn etternavn">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">E-post</label>
                    <input class="form-input" type="email" id="email" name="email" required placeholder="din@epost.no">
                    <?php if (isset($_GET['error_email_already_in_use'])): ?>
                        <div class="form-error"><?php echo htmlspecialchars($_GET['error_email_already_in_use']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Passord</label>
                    <input class="form-input" type="password" id="password" name="password" required placeholder="Velg et sterkt passord">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Bekreft passord</label>
                    <input class="form-input" type="password" id="confirm_password" name="confirm_password" required placeholder="Gjenta passord">
                    <?php if (isset($_GET['error_confirm_password'])): ?>
                        <div class="form-error"><?php echo htmlspecialchars($_GET['error_confirm_password']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Profilbilde</label>
                    <div class="file-upload-container">
                        <label class="file-upload-label" for="profile_picture">
                            <div class="file-upload-button">Velg profilbilde</div>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required onchange="updateFileName(this)">
                            <div id="file-name-display" class="file-name-display">Ingen fil valgt</div>
                        </label>
                    </div>
                    <p class="form-help-text">Profilbilde bør være i 1:1 format for best resultat</p>
                </div>

                <div class="form-section">
                    <h2>Emne informasjon</h2>
                    
                    <div class="form-group">
                        <label class="form-label" for="subject">Navn på emnet</label>
                        <input class="form-input" type="text" id="subject" name="subject" required placeholder="F.eks. Programmering 101">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pin">PIN-kode for emnet</label>
                        <input class="form-input" type="text" id="pin" name="pin" required placeholder="Velg en 4-sifret PIN-kode">
                        <?php if (isset($_GET['error_pin_already_in_use'])): ?>
                            <div class="form-error"><?php echo htmlspecialchars($_GET['error_pin_already_in_use']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="form-submit">Registrer</button>
            </form>

            <div class="form-footer">
                <p>Har du allerede en bruker? <a href="foreleser_login.php">Logg inn her</a></p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 HearMeOut. Alle rettigheter reservert.</p>
        </div>
    </footer>

    <script>
        function updateFileName(input) {
            const fileNameDisplay = document.getElementById('file-name-display');
            if (input.files && input.files[0]) {
                fileNameDisplay.textContent = input.files[0].name;
            } else {
                fileNameDisplay.textContent = 'Ingen fil valgt';
            }
        }
    </script>
</body>
</html>