<?php
session_start();

/*
|--------------------------------------------------------------------------
| DATABASE CONFIGURATION
|--------------------------------------------------------------------------
*/

$host = "localhost";
$user = "root";
$password = "";
$dbname = "fingerprint_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/*
|--------------------------------------------------------------------------
| CREATE TABLE AUTOMATICALLY
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(150) NOT NULL,
        fingerprint_id INT NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";


/*
|--------------------------------------------------------------------------
| REGISTER STUDENT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["register"])) {

    $full_name = trim($_POST["full_name"] ?? "");
    $fingerprint_id = intval($_POST["fingerprint_id"] ?? 0);

    if ($full_name === "") {

        $message = "Please enter a name.";
        $message_type = "error";

    } elseif ($fingerprint_id <= 0) {

        $message = "Please enter a valid fingerprint ID.";
        $message_type = "error";

    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK FINGERPRINT ID
        |--------------------------------------------------------------------------
        */

        $check = $conn->prepare("
            SELECT id
            FROM students
            WHERE fingerprint_id = ?
        ");

        $check->bind_param(
            "i",
            $fingerprint_id
        );

        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Fingerprint ID is already registered.";
            $message_type = "error";

        } else {

            /*
            |--------------------------------------------------------------------------
            | SAVE NAME + FINGERPRINT ID
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                INSERT INTO students
                (full_name, fingerprint_id)
                VALUES (?, ?)
            ");

            $stmt->bind_param(
                "si",
                $full_name,
                $fingerprint_id
            );

            if ($stmt->execute()) {

                $message = "Registration successful!";
                $message_type = "success";

            } else {

                $message = "Registration failed.";
                $message_type = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}


/*
|--------------------------------------------------------------------------
| DELETE STUDENT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["delete"])) {

    $id = intval($_POST["id"] ?? 0);

    if ($id > 0) {

        $stmt = $conn->prepare("
            DELETE FROM students
            WHERE id = ?
        ");

        $stmt->bind_param(
            "i",
            $id
        );

        if ($stmt->execute()) {

            $message = "Student deleted successfully.";
            $message_type = "success";

        } else {

            $message = "Delete failed.";
            $message_type = "error";
        }

        $stmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| GET STUDENTS
|--------------------------------------------------------------------------
*/

$students = $conn->query("
    SELECT
        id,
        full_name,
        fingerprint_id,
        created_at
    FROM students
    ORDER BY id DESC
");

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Fingerprint Registration System
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1e293b
        );

    min-height: 100vh;

    padding: 20px;
}

.container {

    width: 100%;

    max-width: 900px;

    margin: auto;
}

.card {

    background: white;

    border-radius: 15px;

    padding: 25px;

    margin-bottom: 20px;

    box-shadow:
        0 10px 30px
        rgba(0,0,0,0.15);
}

.header {

    text-align: center;

    color: white;

    margin-bottom: 20px;
}

.header h1 {

    margin-bottom: 5px;
}

.header p {

    margin-top: 0;

    opacity: 0.85;
}

label {

    display: block;

    font-weight: bold;

    margin-bottom: 7px;
}

input {

    width: 100%;

    padding: 13px;

    border:
        1px solid #cbd5e1;

    border-radius: 8px;

    font-size: 16px;

    margin-bottom: 15px;
}

button {

    width: 100%;

    padding: 13px;

    border: none;

    border-radius: 8px;

    background: #2563eb;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;
}

button:hover {

    background: #1d4ed8;
}

.delete-button {

    background: #dc2626;

    padding: 8px 12px;

    width: auto;

    font-size: 13px;
}

.delete-button:hover {

    background: #b91c1c;
}

.alert {

    padding: 13px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-weight: bold;
}

.success {

    background: #dcfce7;

    color: #166534;
}

.error {

    background: #fee2e2;

    color: #991b1b;
}

.table-container {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 600px;
}

th {

    background: #eff6ff;

    color: #1e293b;
}

th,
td {

    padding: 12px;

    border-bottom:
        1px solid #e2e8f0;

    text-align: left;
}

tr:hover {

    background: #f8fafc;
}

.fingerprint {

    font-size: 25px;

    text-align: center;

    margin-bottom: 10px;
}

.empty {

    text-align: center;

    color: #64748b;

    padding: 30px;
}

.footer {

    text-align: center;

    color: white;

    font-size: 13px;

    margin-top: 20px;
}

</style>

</head>

<body>

<div class="container">

    <div class="header">

        <div class="fingerprint">
            🔐
        </div>

        <h1>
            Fingerprint Registration System
        </h1>

        <p>
            Register a person's name and fingerprint ID
        </p>

    </div>


    <!-- REGISTRATION -->

    <div class="card">

        <h2>
            Register Person
        </h2>

        <?php if ($message !== ""): ?>

            <div class="alert <?= $message_type ?>">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <form method="POST">

            <label>
                Full Name
            </label>

            <input
                type="text"
                name="full_name"
                placeholder="Enter full name"
                maxlength="150"
                required
            >


            <label>
                Fingerprint ID
            </label>

            <input
                type="number"
                name="fingerprint_id"
                placeholder="Example: 1"
                min="1"
                max="127"
                required
            >


            <button
                type="submit"
                name="register"
            >

                🔐 Register Fingerprint

            </button>

        </form>

    </div>


    <!-- REGISTERED USERS -->

    <div class="card">

        <h2>
            Registered Persons
        </h2>

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Fingerprint ID
                        </th>

                        <th>
                            Date Registered
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if ($students->num_rows > 0): ?>

                    <?php while (
                        $row =
                        $students->fetch_assoc()
                    ): ?>

                        <tr>

                            <td>
                                <?= (int)$row["id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["full_name"]
                                ) ?>
                            </td>

                            <td>
                                🔐
                                <?= (int)$row[
                                    "fingerprint_id"
                                ] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["created_at"]
                                ) ?>
                            </td>

                            <td>

                                <form
                                    method="POST"
                                    onsubmit="
                                    return confirm(
                                    'Delete this person?'
                                    );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$row["id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete"
                                        class="delete-button"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="5"
                            class="empty"
                        >
                            No registered persons yet.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <div class="footer">

        Fingerprint Registration System

    </div>

</div>

</body>

</html>