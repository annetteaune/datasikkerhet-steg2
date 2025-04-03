<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meldinger - HearMeOut</title>
    <link rel="stylesheet" href="../styling.css">
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['student_id']) && !isset($_SESSION['foreleser_id'])) {
        header("Location: login.php");
        exit();
    }

    require_once("../lib/db.php");

    use CleanSteg1\Database\Database;

    try {
        $subject_id = isset($_GET['emne_id']) ? $_GET['emne_id'] : null;
        $is_teacher = isset($_SESSION['foreleser_id']);
        $user_id = $is_teacher ? $_SESSION['foreleser_id'] : $_SESSION['student_id'];
        $user_name = $is_teacher ? $_SESSION['foreleser_navn'] : $_SESSION['student_navn'];
        $role = $is_teacher ? 'lecturer' : 'student';

        // Opprett databasetilkobling
        $conn = Database::getConnection('student');

        // Hent emne informasjon
        if ($subject_id) {
            $stmt = $conn->prepare("CALL get_subject_info(?)");
            $stmt->bind_param("i", $subject_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $subject = $result->fetch_assoc();
            $stmt->close();

            if (!$subject) {
                throw new Exception("Kunne ikke finne emnet.");
            }
        }
    } catch (Exception $e) {
        error_log("Feil i meldinger.php: " . $e->getMessage());
        $_SESSION['error'] = "En feil oppstod ved lasting av emnet. Vennligst prøv igjen senere.";
        header("Location: " . ($is_teacher ? 'foreleser_logged_in.php' : 'student_logged_in.php'));
        exit();
    }
    ?>

    <header>
        <nav>
            <a href="../index.php" class="logo-link"><h1>HearMeOut</h1></a>
            <ul class="nav-links">
                <li><a href="../index.php">Hjem</a></li>
                <li><a href="<?php echo $is_teacher ? 'foreleser_logged_in.php' :
                    'student_logged_in.php'; ?>">Dashboard</a></li>
                <li><a href="../lib/logout.php">Logg ut</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="hero-section">
            <div class="container">
                <h1 class="hero-title">Meldinger</h1>
                <?php if ($subject_id && $subject) : ?>
                    <p class="hero-text">Emne: <?php echo htmlspecialchars($subject['subject_name']); ?></p>
                <?php else : ?>
                    <p class="hero-text">Velg et emne fra dashbordet for å se meldinger</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($subject_id && $subject) : ?>
            <div class="messages-container">
                <?php if (isset($_SESSION['success'])) : ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])) : ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="messages-list">
                    <?php
                    try {
                        $stmt = $conn->prepare("CALL get_course_messages(?)");
                        $stmt->bind_param("i", $subject_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $is_own_message = ($row['sender_type'] == ($is_teacher ? 'lecturer' : 'student')) &&
                                                ($row['sender_id'] == $user_id);

                                echo '<div class="message ' . ($is_own_message ? 'message-own' :
                                    'message-other') . '">';
                                echo '<div class="message-header">';
                                echo '<span class="message-sender">' . htmlspecialchars($row['sender_name']) .
                                    '</span>';
                                echo '<span class="message-time">' . htmlspecialchars(date(
                                    'd.m.Y H:i',
                                    strtotime($row['timestamp'])
                                )) . '</span>';
                                echo '</div>';
                                echo '<div class="message-content">' .
                                    htmlspecialchars($row['message_text']) . '</div>';

                                // Hent kommentarer for meldingen
                                $stmt2 = $conn->prepare("CALL get_message_comments(?)");
                                $stmt2->bind_param("i", $row['message_id']);
                                $stmt2->execute();
                                $comments = $stmt2->get_result();

                                if ($comments->num_rows > 0) {
                                    echo '<div class="message-comments">';
                                    while ($comment = $comments->fetch_assoc()) {
                                        echo '<div class="comment">';
                                        echo '<span class="comment-sender">' .
                                            htmlspecialchars($comment['sender_name']) . ':</span> ';
                                        echo '<span class="comment-text">' .
                                            htmlspecialchars($comment['comment_text']) . '</span>';
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                }
                                $stmt2->close();

                                echo '</div>';
                            }
                        } else {
                            echo '<p class="no-messages">Ingen meldinger i dette emnet ennå.</p>';
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        error_log("Feil ved henting av meldinger: " . $e->getMessage());
                        echo '<p class="error-message">Kunne ikke laste meldinger. Vennligst oppdater siden.</p>';
                    }
                    ?>
                </div>

                <form action="../lib/send_message.php" method="post" class="message-form">
                    <input type="hidden" name="emne_id" value="<?php echo htmlspecialchars($subject_id); ?>">
                    <input type="hidden" name="sender_type" value="<?php echo $is_teacher ? 'lecturer' : 'student'; ?>">
                    <input type="hidden" name="sender_id" value="<?php echo htmlspecialchars($user_id); ?>">
                    
                    <div class="form-group">
                        <textarea class="form-input message-input" name="message_text" required 
                                placeholder="Skriv din melding her..." 
                                maxlength="1000"></textarea>
                        <small class="form-help">Maks 1000 tegn</small>
                    </div>
                    
                    <button type="submit" class="form-submit">Send melding</button>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> HearMeOut. Alle rettigheter reservert.</p>
        </div>
    </footer>

    <?php
    // Lukk databaseforbindelse
    Database::closeConnection($conn);
    ?>
</body>
</html>
