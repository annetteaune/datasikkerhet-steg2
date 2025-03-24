<?php
// Start the session
session_start();

// Check if the student is logged in
if (!isset($_SESSION['student_fname'])) {
    // Not logged in, redirect to login page
    header("Location: ../pages/login.php");
    exit();
}

require 'db.php';

try {
    // Opprett databasetilkobling med student-rolle
    $conn = get_db_connection('student');

    // Hent alle emner med direkte SQL-spørring istedenfor prosedyre
    $stmt = $conn->prepare("SELECT emne_id, emne_navn FROM emner ORDER BY emne_navn");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av emne-spørring");
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Feil ved henting av emner");
    }
    
    $emner_result = $stmt->get_result();
    $stmt->close();

    // Hent alle meldinger for innlogget student
    $student_id = $_SESSION['student_id'];
    error_log("Attempting to get messages for student_id: " . $student_id);
    
    $stmt = $conn->prepare("CALL get_student_messages(?)");
    if (!$stmt) {
        error_log("Failed to prepare get_student_messages statement: " . $conn->error);
        throw new Exception("Feil ved forberedelse av get_student_messages");
    }

    $stmt->bind_param("i", $student_id);
    if (!$stmt->execute()) {
        error_log("Failed to execute get_student_messages: " . $stmt->error);
        throw new Exception("Feil ved henting av meldinger");
    }

    $meldinger_result = $stmt->get_result();
    if ($meldinger_result) {
        error_log("Number of messages found: " . $meldinger_result->num_rows);
        // Debug first row if exists
        if ($meldinger_result->num_rows > 0) {
            $first_row = $meldinger_result->fetch_assoc();
            error_log("First message data: " . print_r($first_row, true));
            // Move pointer back to beginning
            $meldinger_result->data_seek(0);
        }
    } else {
        error_log("No result set returned from get_student_messages");
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Feil i dashboard.php: " . $e->getMessage());
    $_SESSION['error'] = "En feil oppstod ved henting av data";
    header("Location: ../pages/error.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../styling.css">
    <link rel="stylesheet" href="lib-css.css">
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo-link"><h1>HearMeOut</h1></a>
            <ul class="nav-links">
                <h2>Velkommen, <?php echo htmlspecialchars($_SESSION['student_fname']); ?>!</h2>
                <li><a href="logout.php">Logg ut</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="intro">
            <div class="container">
                <h2>Velkommen til HearMeOut!</h2>
                <p>Velg emne for å sende melding.</p>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <form action="student_send_melding.php" class="pin" method="POST">
                    <label for="emne">Emne:</label>
                    <select name="emne_id" id="emne" required>
                        <option value="">--Velg emne--</option>
                        <?php while ($row = $emner_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['emne_id']; ?>">
                                <?php echo htmlspecialchars($row['emne_navn']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <br><br>
                    <label for="melding">Melding:</label>
                    <div style="max-width: 500px; width: 100%; margin: 0 auto;">
                        <textarea name="melding" id="melding" rows="5" cols="40" placeholder="Skriv melding her..." required></textarea>
                    </div>
                    <br><br>
                    <button type="submit">Send melding</button>
                </form>
            </div>
        </section>

        <section class="meldinger">
            <h2>Meldinger</h2>
            <div class="container">
                <div style="max-width: 500px; width: 100%; margin: 0 auto;">
                    <?php 
                    $messages_shown = false;
                    if ($meldinger_result): 
                        while ($row = $meldinger_result->fetch_assoc()):
                            if (isset($row['emne_navn']) && isset($row['innhold']) && isset($row['tidspunkt'])):
                                $messages_shown = true;
                    ?>
                            <div class="message-container">
                                <h3><?php echo htmlspecialchars($row['emne_navn']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($row['innhold'])); ?></p>
                                <small>Sendt: <?php echo htmlspecialchars($row['tidspunkt']); ?></small>

                                <?php if (isset($row['svar']) && !empty($row['svar'])): ?>
                                    <div class="reply">
                                        <p><?php echo nl2br(htmlspecialchars($row['svar'])); ?></p>
                                        <small>Besvart: <?php echo isset($row['svar_dato']) ? htmlspecialchars($row['svar_dato']) : ''; ?></small>
                                        <?php if (isset($row['foreleser_navn'])): ?>
                                            <br>
                                            <small>Foreleser: <?php echo htmlspecialchars($row['foreleser_navn']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php 
                            endif;
                        endwhile;
                    endif;

                    if (!$messages_shown): ?>
                        <p>Ingen meldinger</p>
                    <?php endif; ?>
                </div>      
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <a href="#">Tilbake til toppen</a>
            <p>© 2024 HearMeOut - Et kommunikasjonsverktøy for studenter og forelesere</p>
        </div>
    </footer>

</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>
