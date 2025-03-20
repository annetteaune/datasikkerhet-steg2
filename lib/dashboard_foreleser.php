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
    error_log("Attempting to get messages for foreleser_id: " . $foreleser_id);
    
    $stmt = $conn->prepare("CALL get_lecturer_unanswered_messages(?)");
    if (!$stmt) {
        error_log("Failed to prepare get_lecturer_unanswered_messages: " . $conn->error);
        throw new Exception("Feil ved forberedelse av get_lecturer_unanswered_messages");
    }

    $stmt->bind_param("i", $foreleser_id);
    if (!$stmt->execute()) {
        error_log("Failed to execute get_lecturer_unanswered_messages: " . $stmt->error);
        throw new Exception("Feil ved henting av meldinger: " . $stmt->error);
    }

    $meldinger_result = $stmt->get_result();
    if ($meldinger_result) {
        error_log("Number of messages found: " . $meldinger_result->num_rows);
        if ($meldinger_result->num_rows > 0) {
            $first_row = $meldinger_result->fetch_assoc();
            error_log("First message data: " . print_r($first_row, true));
            $meldinger_result->data_seek(0);
        }
    } else {
        error_log("No result set returned from get_lecturer_unanswered_messages");
    }
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
                <div class="header">
                    <h1>Foreleser Dashboard</h1>
                    <div class="user-info">
                        <span>Velkommen, <?php echo htmlspecialchars($_SESSION['foreleser_fname'] . ' ' . $_SESSION['foreleser_lname']); ?></span>
                        <a href="logout.php" class="logout-btn">Logg ut</a>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="success-message">
                        <?php 
                        echo htmlspecialchars($_SESSION['success_message']);
                        unset($_SESSION['success_message']); // Fjern meldingen etter visning
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']); // Fjern feilmeldingen etter visning
                        ?>
                    </div>
                <?php endif; ?>

                <div class="messages-section">
                    <h3>Ubesvarte meldinger <?php 
                        if ($meldinger_result) {
                            $antall = $meldinger_result->num_rows;
                            echo "($antall)";
                        }
                    ?></h3>
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
                                            echo htmlspecialchars($row['emne_navn']) . ": " . 
                                                 htmlspecialchars(substr($row['innhold'], 0, 30)) . "..."; 
                                            ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </select>
                            <div id="messagePreview" class="message-preview"></div>
                            <div id="replySection" class="form-group" style="display: none;">
                                <label for="innhold">Ditt svar:</label>
                                <textarea name="innhold" id="innhold" rows="4" required></textarea>
                                <button type="submit">Send svar</button>
                            </div>
                        </form>
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
            var replySection = document.getElementById('replySection');
            
            if (fullMessage && preview) {
                preview.innerHTML = '<strong>Fullstendig melding:</strong><br>' + fullMessage;
                preview.style.display = 'block';
                replySection.style.display = 'block';
            } else {
                preview.style.display = 'none';
                replySection.style.display = 'none';
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
