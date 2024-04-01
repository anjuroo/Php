<?php
session_start();
include_once 'includes/dbc.inc.php';

// Initialize currentRecord as an empty array
$currentRecord = [];

// Display status message if provided
$status = $_GET['status'] ?? '';

// Fetch all records from the database
$query = "SELECT * FROM student";
$stmt = $pdo->query($query);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine the current record index
$current_index = isset($_SESSION['current_index']) ? $_SESSION['current_index'] : 0;
$record_count = count($records);

// Handle navigation logic for "Back" and "Next" buttons
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['back'])) {
        $current_index = ($current_index - 1 + $record_count) % $record_count;
    } elseif (isset($_POST['next'])) {
        $current_index = ($current_index + 1) % $record_count;
    }
    $_SESSION['current_index'] = $current_index;

    // Fetch the current record from the database
    $currentRecord = $records[$current_index] ?? [];
}

// Handle delete operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'Delete') {
    // Extract first name, last name, and ID number from the form
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $idNumber = $_POST['idNumber'];

    // Find the ID of the record matching the provided first name, last name, and ID number
    $query = "SELECT id FROM student WHERE firstName = :firstName AND lastName = :lastName AND idNumber = :idNumber";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':firstName', $firstName);
    $stmt->bindParam(':lastName', $lastName);
    $stmt->bindParam(':idNumber', $idNumber);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // If a matching record is found, delete it
        $id = $result['id'];
        $query = "DELETE FROM student WHERE id = :id";
        try {
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            header("Location: index.php?status=deleted");
            exit();
        } catch (PDOException $e) {
            echo "Error: Data could not be deleted.";
        }
    } else {
        // If no matching record is found, display an error message
        echo "Error: No matching record found for deletion.";
    }
}

// Handle save operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'Save') {
    // Save operation
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $idNumber = $_POST['idNumber'];

    $query = "INSERT INTO student (firstName, lastName, idNumber) VALUES (:firstName, :lastName, :idNumber)";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":firstName", $firstName);
        $stmt->bindParam(":lastName", $lastName);
        $stmt->bindParam(":idNumber", $idNumber);
        $stmt->execute();

        header("Location: index.php?status=success");
        exit();
    } catch (PDOException $e) {
        echo "Error: Data could not be saved.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grabe</title>
</head>
<body>
    <?php 
    if ($status === 'success') {
        echo '<p>Data saved successfully!</p>';
    } elseif ($status === 'deleted') {
        echo '<p>Data deleted successfully!</p>';
    } elseif ($status === 'back') {
        echo '<p>Previous record displayed.</p>';
    } elseif ($status === 'next') {
        echo '<p>Next record displayed.</p>';
    }
    ?>

    <form action="index.php" method="post">
        <?php if (!empty($currentRecord)): ?>
            <input type="hidden" name="id" value="<?php echo $currentRecord['id']; ?>">
            <input type="text" name="firstName" placeholder="First Name" value="<?php echo $currentRecord['firstName']; ?>">
            <input type="text" name="lastName" placeholder="Last Name" value="<?php echo $currentRecord['lastName']; ?>">
            <input type="text" name="idNumber" placeholder="ID Number" value="<?php echo $currentRecord['idNumber']; ?>">
        <?php else: ?>
            <input type="hidden" name="id" value="">
            <input type="text" name="firstName" placeholder="First Name" value="">
            <input type="text" name="lastName" placeholder="Last Name" value="">
            <input type="text" name="idNumber" placeholder="ID Number" value="">
        <?php endif; ?>
        <input type="submit" name="back" value="Back">
        <input type="submit" name="next" value="Next">
        <input type="submit" name="action" value="Save">
        <input type="submit" name="action" value="Delete">
    </form>
</body>
</html>
