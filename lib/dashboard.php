<?php

session_start();

// Sjekk om innlogget
if (!isset($_SESSION['student_fname'])) {
    // Hvis ikke, redirect til login 
    header("Location: ../pages/login.php");
    exit();
}

require 'db.php';

try {
    // Opprett databasetilkobling med student-rolle
    $conn = get_db_connection('student');

    // Hent alle emner
    $stmt = $conn->prepare("SELECT emne_id, emne_navn FROM emner ORDER BY emne_navn");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av emne-spørring");
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Feil ved henting av emner");
    }
    
    $emner_result = $stmt->get_result();
    $stmt->close();

    // Hent studentens meldinger med svar og foreleserinfo
    $student_id = $_SESSION['student_id'];
    $stmt = $conn->prepare("SELECT * FROM student_messages_view WHERE student_id = ? ORDER BY melding_tidspunkt DESC");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av meldingsspørring");
    }

    $stmt->bind_param("i", $student_id);
    if (!$stmt->execute()) {
        throw new Exception("Feil ved henting av meldinger");
    }

    $meldinger_result = $stmt->get_result();
    $stmt->close();

} catch (Exception $e) {
    error_log("Feil i dashboard.php: " . $e->getMessage());
    $_SESSION['error'] = "En feil oppstod ved henting av data: " . $e->getMessage();
    if (isset($conn)) {
        close_db_connection($conn);
    }
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
                <h2>Velkommen, <?php echo htmlspecialchars($_SESSION['student_fname'] . ' ' . $_SESSION['student_lname']); ?>!</h2>
                <li><a href="logout.php">Logg ut</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="messages-section">
            <div class="container">
                <h2>Send ny melding</h2>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-message">
                        <?php 
                        echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <form action="student_send_melding.php" method="POST" class="message-form">
                    <div class="form-group">
                        <label for="emne_id">Velg emne:</label>
                        <select name="emne_id" id="emne_id" required>
                            <option value="">--Velg et emne--</option>
                            <?php if ($emner_result && $emner_result->num_rows > 0): ?>
                                <?php while ($row = $emner_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($row['emne_id']); ?>">
                                        <?php echo htmlspecialchars($row['emne_navn']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="melding">Din melding:</label>
                        <textarea name="melding" id="melding" rows="4" required></textarea>
                    </div>

                    <button type="submit">Send melding</button>
                </form>

                <h2>Dine meldinger</h2>
                <div class="messages-list">
                    <?php if ($meldinger_result && $meldinger_result->num_rows > 0): ?>
                        <?php while ($melding = $meldinger_result->fetch_assoc()): ?>
                            <div class="message-container">
                                <div class="message-header">
                                    <h3><?php echo htmlspecialchars($melding['emne_navn']); ?></h3>
                                    <span class="message-time">Sendt: <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($melding['melding_tidspunkt']))); ?></span>
                                </div>
                                
                                <div class="message-content">
                                    <p><?php echo nl2br(htmlspecialchars($melding['melding_innhold'])); ?></p>
                                </div>

                                <?php if ($melding['svar_innhold']): ?>
                                    <div class="message-response">
                                        <h4>Svar fra <?php echo htmlspecialchars($melding['foreleser_fornavn'] . ' ' . $melding['foreleser_etternavn']); ?></h4>
                                        <span class="response-time">Besvart: <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($melding['svar_tidspunkt']))); ?></span>
                                        <p><?php echo nl2br(htmlspecialchars($melding['svar_innhold'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Hent kommentarer fra message_comments_view
                                $stmt = $conn->prepare("SELECT * FROM message_comments_view WHERE melding_id = ? ORDER BY tidspunkt ASC");
                                if ($stmt) {
                                    $stmt->bind_param("i", $melding['melding_id']);
                                    $stmt->execute();
                                    $comments = $stmt->get_result();
                                    
                                    if ($comments && $comments->num_rows > 0): ?>
                                        <div class="message-comments">
                                            <h4>Kommentarer</h4>
                                            <?php while ($comment = $comments->fetch_assoc()): ?>
                                                <div class="comment">
                                                    <p><?php echo nl2br(htmlspecialchars($comment['innhold'])); ?></p>
                                                    <span class="comment-time">
                                                        <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($comment['tidspunkt']))); ?>
                                                    </span>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php endif;
                                    $stmt->close();
                                }
                                ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>Du har ingen meldinger enda.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="password-section">
            <div class="container">
                <h2>Bytt passord</h2>
                <div class="password-form">
                    <form action="bytt_pw.php" method="POST">
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
</body>
</html>
<?php
if (isset($conn)) {
    close_db_connection($conn);
}
?>
