<?php
// Start the session
session_start();

// Check if the student is logged in
if (!isset($_SESSION['gjest_id'])) {
    // Not logged in, redirect to login page
    header("Location: ../pages/login.php");
    exit();
}
require 'db.php';

try {
    // Opprett databasetilkobling med gjest-rolle
    $conn = get_db_connection('guest');

    // Hent alle meldinger for emnet
    $emne_id = $_SESSION['emne_id'];
    $stmt = $conn->prepare("CALL get_course_messages(?)");
    if (!$stmt) {
        throw new Exception("Feil ved forberedelse av get_course_messages");
    }

    $stmt->bind_param("i", $emne_id);
    if (!$stmt->execute()) {
        throw new Exception("Feil ved henting av meldinger");
    }

    $meldinger_result = $stmt->get_result();
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
    <style>
        .message-container {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .reply {
            background-color: #f0f0f0;
            padding: 10px;
            margin-top: 10px;
            border-radius: 3px;
        }
        .comments {
            margin-top: 15px;
            border-top: 1px dashed #aaa;
            padding-top: 10px;
        }
        .comment {
            background-color: #f8f8f8;
            padding: 8px;
            margin-bottom: 8px;
            border-radius: 3px;
        }
        .add-comment, .report-message {
            margin-top: 15px;
        }
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .success {
            color: #28a745;
            padding: 10px;
            margin: 10px 0;
            background-color: #d4edda;
            border-radius: 3px;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            margin: 10px 0;
            background-color: #f8d7da;
            border-radius: 3px;
        }
    </style>
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
                    <p>Dette emnet er undervist av <?php echo htmlspecialchars($_SESSION['foreleser_fornavn'] . ' ' . $_SESSION['foreleser_etternavn']); ?>.</p>
                    <?php if (isset($_SESSION['foreleser_bilde'])): ?>
                        <img class="pfp" src="<?php echo htmlspecialchars($_SESSION['foreleser_bilde']); ?>" alt="Foreleser profilbilde">
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="meldinger">
            <h2>Meldinger</h2>
            <div class="container">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <div style="max-width: 800px; margin: 0 auto;">
                    <?php if ($meldinger_result->num_rows > 0): ?>
                        <?php while ($row = $meldinger_result->fetch_assoc()): ?>
                            <div class="message-container">
                                <h3><?php echo htmlspecialchars($row['emne_navn']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($row['melding_innhold'])); ?></p>
                                <small>Sendt: <?php echo $row['melding_tidspunkt']; ?></small>

                                <?php if (!empty($row['svar_innhold'])): ?>
                                    <div class="reply">
                                        <p><?php echo nl2br(htmlspecialchars($row['svar_innhold'])); ?></p>
                                        <small>Besvart: <?php echo $row['svar_tidspunkt']; ?></small>
                                        <br>
                                        <small>Foreleser: <?php echo htmlspecialchars($row['foreleser_navn']); ?></small>
                                    </div>
                                <?php endif; ?>

                                <?php
                                try {
                                    // Hent kommentarer for denne meldingen
                                    $stmt = $conn->prepare("CALL get_message_comments(?)");
                                    if (!$stmt) {
                                        throw new Exception("Feil ved forberedelse av get_message_comments");
                                    }

                                    $stmt->bind_param("i", $row['melding_id']);
                                    if (!$stmt->execute()) {
                                        throw new Exception("Feil ved henting av kommentarer");
                                    }

                                    $kommentarer_result = $stmt->get_result();
                                    $stmt->close();
                                ?>
                                    <div class="comments">
                                        <?php if ($kommentarer_result->num_rows > 0): ?>
                                            <?php while ($comment = $kommentarer_result->fetch_assoc()): ?>
                                                <div class="comment">
                                                    <p><?php echo nl2br(htmlspecialchars($comment['innhold'])); ?></p>
                                                    <small>Kommentert: <?php echo $comment['tidspunkt']; ?></small>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <p>Ingen kommentarer ennå.</p>
                                        <?php endif; ?>
                                    </div>
                                <?php
                                } catch (Exception $e) {
                                    error_log("Feil ved henting av kommentarer: " . $e->getMessage());
                                }
                                ?>

                                <div class="add-comment">
                                    <form action="submit_comment.php" method="post">
                                        <input type="hidden" name="melding_id" value="<?php echo $row['melding_id']; ?>">
                                        <input type="hidden" name="gjest_id" value="<?php echo $_SESSION['gjest_id']; ?>">
                                        <textarea name="innhold" placeholder="Skriv din kommentar her..." required></textarea>
                                        <button type="submit">Send kommentar</button>
                                    </form>
                                </div>

                                <div class="report-message">
                                    <form action="submit_rapport.php" method="post">
                                        <input type="hidden" name="melding_id" value="<?php echo $row['melding_id']; ?>">
                                        <input type="hidden" name="gjest_id" value="<?php echo $_SESSION['gjest_id']; ?>">
                                        <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                        <textarea name="grunn" placeholder="Hvorfor ønsker du å rapportere denne meldingen?" required></textarea>
                                        <button type="submit">Rapporter melding</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
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
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>