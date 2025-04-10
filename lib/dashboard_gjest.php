<?php

/**
 * Guest Dashboard
 *
 * This file contains the guest dashboard functionality for viewing course messages,
 * adding comments, and reporting inappropriate content.
 *
 * PHP version 7.4
 *
 * @category   Dashboard
 * @package    CleanSteg1
 * @subpackage Guest
 * @author     Your Name <your.email@example.com>
 * @license    MIT License
 * @link       https://github.com/yourusername/cleanSteg1
 */

session_start();


require_once __DIR__ . '/bootstrap.php';


header('Content-Type: text/html; charset=utf-8');


mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

require_once 'rate_limiter.php';
require_once 'db.php';

use CleanSteg1\Security\RateLimiter;
use function CleanSteg1\Security\applyRateLimit;
use CleanSteg1\Database\Database;

applyRateLimit();

//  session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_secure', 1);

//  error logging
error_log("Guest dashboard accessed. Session data: " . json_encode($_SESSION));

// Sjekk om gjest er innlogget
if (!isset($_SESSION['gjest_id'])) {
    error_log("No gjest_id in session - redirecting to login");
    header("Location: /steg2/pages/login.php");
    exit();
}

try {
    // Opprett databasetilkobling
    $conn = Database::getConnection('guest');

    // Hent alle meldinger for emnet
    $emne_id = $_SESSION['emne_id'];
    $pin_kode = $_SESSION['pin_kode'];
    $stmt = $conn->prepare("CALL get_course_messages(?, ?)");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av get_course_messages");
    }

    $stmt->bind_param("is", $emne_id, $pin_kode);
    if (!$stmt->execute()) {
        throw new Exception("Feil ved henting av meldinger");
    }

    // Håndter første resultset (SUCCESS/ERROR)
    $result = $stmt->get_result();
    $status = $result->fetch_assoc();
    error_log("First result set: " . print_r($status, true));

    if ($status['result'] !== 'SUCCESS') {
        throw new Exception(
            $status['message'] ?? "Feil ved henting av meldinger"
        );
    }

    // Gå til neste resultset som inneholder meldingene
    $stmt->next_result();
    $meldinger_result = $stmt->get_result();
    $num_messages = $meldinger_result ? $meldinger_result->num_rows : 0;
    error_log("Number of messages found: " . $num_messages);

    $stmt->close();
} catch (Exception $e) {
    error_log("Feil i dashboard_gjest.php: " . $e->getMessage());
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
    <title>HearMeOut - Gjest Dashboard</title>
    <link rel="stylesheet" href="../styling.css">
    <link rel="stylesheet" href="lib-css.css">
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo-link"><h1>HearMeOut</h1></a>
            <ul class="nav-links">
                <li><a href="logout.php">Logg ut</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="intro">
            <div class="container">
                <h2>
                    <?php echo htmlspecialchars($_SESSION['emne_navn']); ?> - 
                    <?php echo htmlspecialchars($_SESSION['emne_kode']); ?>
                </h2>
                <div class="flex-container">
                    <p>
                        Dette emnet er undervist av 
                        <?php
                        $foreleser_navn = $_SESSION['foreleser_fornavn'] . ' ' .
                            $_SESSION['foreleser_etternavn'];
                        echo htmlspecialchars($foreleser_navn);
                        ?>.
                    </p>
                    <?php if (isset($_SESSION['foreleser_bilde'])) : ?>
                        <img class="pfp" 
                             src="<?php echo htmlspecialchars($_SESSION['foreleser_bilde']); ?>" 
                             alt="Foreleser profilbilde">
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="meldinger">
            <h2>Meldinger</h2>
            <div class="container">
                <?php if (isset($_SESSION['success'])) : ?>
                    <div class="success">
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])) : ?>
                    <div class="error">
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <div style="max-width: 800px; margin: 0 auto;">
                    <?php if ($meldinger_result && $meldinger_result->num_rows > 0) : ?>
                        <?php
                        while ($row = $meldinger_result->fetch_assoc()) :
                            // Debug-logging
                            error_log("Message row data: " . print_r($row, true));
                            ?>
                            <div class="message-container">
                                <?php if (isset($row['emne_navn']) && $row['emne_navn']) : ?>
                                    <h3><?php echo htmlspecialchars($row['emne_navn']); ?></h3>
                                <?php endif; ?>

                                <?php if (isset($row['melding_innhold']) && $row['melding_innhold']) : ?>
                                    <p class="message-content">
                                        <?php echo nl2br(htmlspecialchars($row['melding_innhold'])); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (isset($row['melding_tidspunkt']) && $row['melding_tidspunkt']) : ?>
                                    <small>
                                        Sendt: <?php echo htmlspecialchars($row['melding_tidspunkt']); ?>
                                    </small>
                                <?php endif; ?>

                                <?php if (isset($row['svar_innhold']) && !empty($row['svar_innhold'])) : ?>
                                    <div class="reply">
                                        <p>
                                            <?php echo nl2br(htmlspecialchars($row['svar_innhold'])); ?>
                                        </p>
                                        <?php if (isset($row['svar_tidspunkt'])) : ?>
                                            <small>
                                                Besvart: <?php echo htmlspecialchars($row['svar_tidspunkt']); ?>
                                            </small>
                                            <br>
                                        <?php endif; ?>
                                        <?php if (
                                        isset($row['foreleser_fornavn'])
                                            && isset($row['foreleser_etternavn'])
) : ?>
                                            <small>
                                                Foreleser: 
                                                <?php
                                                $foreleser = $row['foreleser_fornavn'] . ' ' .
                                                    $row['foreleser_etternavn'];
                                                echo htmlspecialchars($foreleser);
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php
                                if (isset($row['melding_id'])) {
                                    try {
                                        // Hent kommentarer for denne meldingen
                                        $stmt = $conn->prepare("CALL get_message_comments(?)");
                                        if (!$stmt) {
                                            throw new Exception(
                                                "Feil ved forberedelse av get_message_comments"
                                            );
                                        }

                                        $stmt->bind_param("i", $row['melding_id']);
                                        if (!$stmt->execute()) {
                                            throw new Exception("Feil ved henting av kommentarer");
                                        }

                                        // Håndter første resultset (SUCCESS/ERROR)
                                        $result = $stmt->get_result();
                                        $status = $result->fetch_assoc();
                                        error_log("Comments status: " . print_r($status, true));

                                        if ($status['result'] === 'SUCCESS') {
                                            // Gå til neste resultset som inneholder kommentarene
                                            $stmt->next_result();
                                            $kommentarer_result = $stmt->get_result();
                                            $num_comments = $kommentarer_result ?
                                                $kommentarer_result->num_rows : 0;
                                            error_log("Number of comments found: " . $num_comments);

                                            // Vis kommentarene
                                            if ($kommentarer_result && $kommentarer_result->num_rows > 0) {
                                                echo '<div class="comments-section">';
                                                echo '<h4>Kommentarer:</h4>';
                                                while ($comment = $kommentarer_result->fetch_assoc()) {
                                                    echo '<div class="comment">';
                                                    echo '<p>' .
                                                         nl2br(htmlspecialchars($comment['innhold'])) .
                                                         '</p>';
                                                    echo '<small>Kommentert: ' .
                                                         htmlspecialchars($comment['tidspunkt']) .
                                                         '</small>';
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                            }
                                        } else {
                                            $error_msg = $status['message'] ?? "Ukjent feil";
                                            error_log("Feil ved henting av kommentarer: " . $error_msg);
                                        }

                                        $stmt->close();

                                        // Håndter multiple resultsets
                                        while ($conn->more_results() && $conn->next_result()) {
                                            if ($res = $conn->store_result()) {
                                                $res->free();
                                            }
                                        }
                                        ?>
                                        <div class="action-buttons">
                                            <button type="button" class="comment-btn" 
                                                    onclick="toggleCommentForm(<?php
                                                    echo $row['melding_id'];
                                                    ?>)">
                                                Kommenter
                                            </button>
                                            <button type="button" class="report-btn" 
                                                    onclick="toggleReportForm(<?php
                                                    echo $row['melding_id'];
                                                    ?>)">
                                                Rapporter
                                            </button>
                                        </div>

                                        <div id="comment-form-<?php echo $row['melding_id']; ?>" 
                                             class="add-comment" style="display: none;">
                                            <form action="submit_comment.php" method="post">
                                                <input type="hidden" name="melding_id" 
                                                       value="<?php
                                                        echo htmlspecialchars($row['melding_id']);
                                                        ?>">
                                                <?php if (isset($_SESSION['gjest_id'])) : ?>
                                                    <input type="hidden" name="gjest_id" 
                                                           value="<?php
                                                            echo htmlspecialchars($_SESSION['gjest_id']);
                                                            ?>">
                                                <?php endif; ?>
                                                <textarea name="innhold" 
                                                          placeholder="Skriv din kommentar her..." 
                                                          required></textarea>
                                                <div class="form-buttons">
                                                    <button type="submit">Send kommentar</button>
                                                    <button type="button" 
                                                            onclick="toggleCommentForm(<?php
                                                            echo $row['melding_id'];
                                                            ?>)">
                                                        Avbryt
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <div id="report-form-<?php echo $row['melding_id']; ?>" 
                                             class="report-message" style="display: none;">
                                            <form action="submit_rapport.php" method="post">
                                                <input type="hidden" name="melding_id" 
                                                       value="<?php
                                                        echo htmlspecialchars($row['melding_id']);
                                                        ?>">
                                                <?php if (isset($_SESSION['gjest_id'])) : ?>
                                                    <input type="hidden" name="gjest_id" 
                                                           value="<?php
                                                            echo htmlspecialchars($_SESSION['gjest_id']);
                                                            ?>">
                                                <?php endif; ?>
                                                <?php if (isset($row['student_id'])) : ?>
                                                    <input type="hidden" name="student_id" 
                                                           value="<?php
                                                            echo htmlspecialchars($row['student_id']);
                                                            ?>">
                                                <?php endif; ?>
                                                <textarea name="grunn" 
                                                          placeholder="Hvorfor ønsker du å rapportere 
                                                          denne meldingen?" 
                                                          required></textarea>
                                                <div class="form-buttons">
                                                    <button type="submit">Send rapport</button>
                                                    <button type="button" 
                                                            onclick="toggleReportForm(<?php
                                                            echo $row['melding_id'];
                                                            ?>)">
                                                        Avbryt
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        <?php
                                    } catch (Exception $e) {
                                        error_log("Feil ved henting av kommentarer: " . $e->getMessage());
                                    }
                                }
                                ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <p>Ingen meldinger funnet.</p>
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

    <script>
        var toggleCommentForm = function(messageId) {
            const form = document.getElementById(
                'comment-form-' + messageId
            );
            const btn = event.target;
            if (form.style.display === 'none') {
                form.style.display = 'block';
                btn.textContent = 'Skjul kommentarfelt';
            } else {
                form.style.display = 'none';
                btn.textContent = 'Kommenter';
            }
        };

        var toggleReportForm = function(messageId) {
            const form = document.getElementById(
                'report-form-' + messageId
            );
            const btn = event.target;
            if (form.style.display === 'none') {
                form.style.display = 'block';
                btn.textContent = 'Skjul rapportskjema';
            } else {
                form.style.display = 'none';
                btn.textContent = 'Rapporter';
            }
        };
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>