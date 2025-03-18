<?php
// Start the session
session_start();

// Check if the lecturer is logged in
if (!isset($_SESSION['foreleser_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'lecturer') {
    // Not logged in, redirect to login page
    header("Location: ../pages/foreleser_login.php");
    exit();
}

require 'db.php';

try {
    // Opprett databasetilkobling med foreleser-rolle
    $conn = get_db_connection('lecturer');

    // Hent ubesvarte meldinger for innlogget foreleser
    $foreleser_id = $_SESSION['foreleser_id'];
    $stmt = $conn->prepare("CALL get_lecturer_unanswered_messages(?)");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av get_lecturer_unanswered_messages");
    }

    $stmt->bind_param("i", $foreleser_id);
    if (!$stmt->execute()) {
        throw new Exception("Feil ved henting av meldinger: " . $stmt->error);
    }

    $meldinger_result = $stmt->get_result();
    $stmt->close();

    // Håndter multiple resultsets
    while ($conn->more_results() && $conn->next_result()) {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }

    // Hent meldinger for foreleserens emner
    $stmt = $conn->prepare("CALL get_course_messages(?, ?)");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av get_course_messages");
    }

    $limit = 10; // Vis de 10 siste meldingene
    $stmt->bind_param("ii", $foreleser_id, $limit);
    if (!$stmt->execute()) {
        throw new Exception("Feil ved henting av emnets meldinger: " . $stmt->error);
    }

    $emner_meldinger_result = $stmt->get_result();
    $stmt->close();

    // Håndter multiple resultsets
    while ($conn->more_results() && $conn->next_result()) {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }

} catch (Exception $e) {
    error_log("Feil i dashboard_foreleser.php: " . $e->getMessage());
    $_SESSION['error'] = "En feil oppstod ved henting av data. Vennligst prøv igjen senere.";
    close_db_connection($conn);
    header("Location: ../pages/error.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foreleser Dashboard</title>
    <link rel="stylesheet" href="../styling.css"> 
    <link rel="stylesheet" href="lib-css.css">
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo-link"><h1>HearMeOut</h1></a>
            <ul class="nav-links">
                <h2>Velkommen, <?php echo htmlspecialchars($_SESSION['foreleser_fname'] . ' ' . $_SESSION['foreleser_lname']); ?>!</h2>
                <li><a href="logout.php">Logg ut</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="intro">
            <div class="container">
                <h2>Velkommen til HearMeOut!</h2>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="messages-section">
                    <h3>Ubesvarte meldinger</h3>
                    <?php if ($meldinger_result && $meldinger_result->num_rows > 0): ?>
                        <form action="svar.php" method="POST">
                            <label for="melding_id">Velg melding å svare på:</label>
                            <select name="melding_id" id="melding_id" required>
                                <option value="">--Velg en melding--</option>
                                <?php while ($row = $meldinger_result->fetch_assoc()): ?>
                                    <?php if (isset($row['melding_id']) && isset($row['innhold'])): ?>
                                        <option 
                                            value="<?php echo htmlspecialchars($row['melding_id']); ?>" 
                                            data-full="<?php echo htmlspecialchars($row['innhold']); ?>"
                                        >
                                            <?php 
                                            echo "Melding #" . htmlspecialchars($row['melding_id']) . " - " . 
                                                htmlspecialchars(substr($row['innhold'], 0, 30)) . "..."; 
                                            ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </select>
                            <div id="messagePreview" class="message-preview"></div>
                            <button type="submit">Svar på melding</button>
                        </form>
                    <?php else: ?>
                        <p>Ingen nye meldinger.</p>
                    <?php endif; ?>
                </div>

                <div class="course-messages-section">
                    <h3>Alle meldinger i dine emner</h3>
                    <?php if ($emner_meldinger_result && $emner_meldinger_result->num_rows > 0): ?>
                        <div class="messages-list">
                            <?php 
                            $has_valid_messages = false;
                            while ($row = $emner_meldinger_result->fetch_assoc()): 
                                if (isset($row['melding_id']) && isset($row['innhold']) && isset($row['emne_navn']) && isset($row['dato'])):
                                    $has_valid_messages = true;
                            ?>
                                <div class="message-card">
                                    <h4>Melding #<?php echo htmlspecialchars($row['melding_id']); ?></h4>
                                    <p><?php echo htmlspecialchars($row['innhold']); ?></p>
                                    <div class="message-meta">
                                        <span>Emne: <?php echo htmlspecialchars($row['emne_navn']); ?></span>
                                        <span>Dato: <?php echo htmlspecialchars($row['dato']); ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endwhile; ?>
                            <?php if (!$has_valid_messages): ?>
                                <p>Ingen nye meldinger.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p>Ingen nye meldinger.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="password-section">
            <div class="container">
                <h2>Bytt passord</h2>
                <div class="password-form">
                    <form action="bytt_foreleser_pw.php" method="POST">
                        <?php if (isset($_SESSION['pw_message'])): ?>
                            <div class="<?php echo strpos($_SESSION['pw_message'], 'feil') !== false ? 'error' : 'success'; ?>">
                                <?php 
                                echo htmlspecialchars($_SESSION['pw_message']); 
                                unset($_SESSION['pw_message']);
                                ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="current_pw">Nåværende passord:</label>
                            <input type="password" name="current_pw" id="current_pw" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_pw">Nytt passord:</label>
                            <input type="password" name="new_pw" id="new_pw" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_pw">Bekreft nytt passord:</label>
                            <input type="password" name="confirm_pw" id="confirm_pw" required>
                        </div>
                        
                        <button type="submit">Bytt passord</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> HearMeOut - Et kommunikasjonsverktøy for studenter og forelesere</p>
        </div>
    </footer>

    <script>
        document.getElementById('melding_id').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var fullMessage = selectedOption.getAttribute('data-full');
            var preview = document.getElementById('messagePreview');
            
            if (fullMessage && preview) {
                preview.innerHTML = '<strong>Fullstendig melding:</strong><br>' + fullMessage;
                preview.style.display = 'block';
            } else if (preview) {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>

<?php
if (isset($conn)) {
    close_db_connection($conn);
}
?>
