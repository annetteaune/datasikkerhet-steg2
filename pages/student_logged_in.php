<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - HearMeOut</title>
    <link rel="stylesheet" href="../styling.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .dashboard-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .subject-card {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            transition: transform 0.2s;
        }
        .subject-card:hover {
            transform: translateY(-5px);
        }
        .card-link {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 10px;
            transition: background-color 0.2s;
        }
        .card-link:hover {
            background: #0056b3;
        }
        .register-subject-form {
            max-width: 400px;
            margin: 20px auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-submit {
            width: 100%;
            padding: 10px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .form-submit:hover {
            background: #218838;
        }
        .form-error {
            color: #dc3545;
            padding: 10px;
            margin: 10px 0;
            background-color: #f8d7da;
            border-radius: 4px;
        }
        .form-success {
            color: #28a745;
            padding: 10px;
            margin: 10px 0;
            background-color: #d4edda;
            border-radius: 4px;
        }
        .no-subjects {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    
    if (!isset($_SESSION['student_id'])) {
        header("Location: student_login.php");
        exit();
    }

    require_once("../lib/db.php");

    try {
        // Opprett databasetilkobling med student-rolle
        $conn = get_db_connection('student');

        // Hent studentprofil
        $stmt = $conn->prepare("CALL student_profile_view(?)");
        if (!$stmt) {
            throw new Exception("Feil ved forberedelse av student_profile_view");
        }

        $student_id = $_SESSION['student_id'];
        $stmt->bind_param("i", $student_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Feil ved henting av studentprofil");
        }

        $profile_result = $stmt->get_result();
        $profile = $profile_result->fetch_assoc();
        $stmt->close();

        // Hent studentens emner
        $stmt = $conn->prepare("CALL student_courses_view(?)");
        if (!$stmt) {
            throw new Exception("Feil ved forberedelse av student_courses_view");
        }

        $stmt->bind_param("i", $student_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Feil ved henting av emner");
        }

        $emner_result = $stmt->get_result();
        $stmt->close();

    } catch (Exception $e) {
        error_log("Feil i student_logged_in.php: " . $e->getMessage());
        $_SESSION['error'] = "En feil oppstod ved henting av data";
        header("Location: error.php");
        exit();
    }
    ?>

    <header>
        <nav>
            <a href="../index.php" class="logo-link"><h1>HearMeOut</h1></a>
            <ul class="nav-links">
                <li><a href="../index.php">Hjem</a></li>
                <li><a href="meldinger.php">Meldinger</a></li>
                <li><a href="../lib/logout.php">Logg ut</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="hero-section">
            <div class="container">
                <h1 class="hero-title">Velkommen, <?php echo htmlspecialchars($profile['student_name']); ?>!</h1>
                <p class="hero-text">Her er en oversikt over dine emner og meldinger</p>
            </div>
        </div>

        <div class="dashboard-container">
            <section class="dashboard-section">
                <h2>Dine emner</h2>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="form-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <div class="subject-grid">
                    <?php if ($emner_result->num_rows > 0): ?>
                        <?php while ($row = $emner_result->fetch_assoc()): ?>
                            <div class="subject-card">
                                <h3><?php echo htmlspecialchars($row['emne_navn']); ?></h3>
                                <p>Foreleser: <?php echo htmlspecialchars($row['foreleser_navn']); ?></p>
                                <a href="meldinger.php?emne_id=<?php echo htmlspecialchars($row['emne_id']); ?>" class="card-link">Se meldinger</a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-subjects">Du er ikke registrert i noen emner ennå.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="dashboard-section">
                <h2>Registrer deg i nytt emne</h2>
                <form action="../lib/register_subject.php" method="post" class="register-subject-form">
                    <div class="form-group">
                        <label class="form-label" for="pin_kode">Emne PIN</label>
                        <input class="form-input" type="text" id="pin_kode" name="pin_kode" required 
                               placeholder="Skriv inn PIN-kode for emnet" pattern="[0-9]{4}" 
                               title="PIN-koden må bestå av 4 siffer">
                    </div>
                    <button type="submit" class="form-submit">Registrer emne</button>
                </form>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 HearMeOut - Et kommunikasjonsverktøy for studenter og forelesere</p>
        </div>
    </footer>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>