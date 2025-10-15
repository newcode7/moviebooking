<?php
// 1. Database Connection Details
$servername = "localhost";
$username = "root"; // e.g., root
$password = ""; // your password
$dbname = "movies"; // The name of your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Get data from the HTML form
// Check if POST data exists before accessing it
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id = $_POST['movie_id'] ?? null;
    $user_name = $_POST['name'] ?? null;
    $seats_booked = $_POST['seats'] ?? null;
} else {
    // If accessed directly without form submission
    $movie_id = $user_name = $seats_booked = null;
}

// Start HTML output
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Result</title>
    <!-- Load Tailwind CSS for modern styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url(\'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap\');
        
        body {
            font-family: \'Inter\', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            
            /* --- Colorful Animated Gradient Background --- */
            background: linear-gradient(135deg, #FF6B6B 0%, #FFE66D 50%, #4ECDC4 100%);
            background-size: 400% 400%;
            animation: colorful-shift 15s ease infinite; 
            padding: 20px;
        }

        /* Keyframes for the gentle background animation */
        @keyframes colorful-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Card Fade In Animation */
        @keyframes fade-in {
            0% { opacity: 0; transform: translateY(20px) scale(0.95); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .result-card {
            animation: fade-in 0.8s ease-out forwards;
        }
    </style>
</head>
<body>
    <div class="result-card max-w-lg w-full bg-white p-10 rounded-2xl shadow-2xl text-center border-t-8 border-indigo-500">
';

// Check if we have valid POST data to process
if ($movie_id !== null && $user_name !== null && $seats_booked !== null) {
    
    // 3. Basic Validation and Availability Check (Simplified)
    
    // Check current availability
    $sql_check = "SELECT movie_name FROM movies WHERE movie_id = ?"; // Fetched movie_name too for better confirmation
    $stmt_check = $conn->prepare($sql_check);
    
    // === FIX 1: Check for successful prepare on the SELECT query ===
    if (!$stmt_check) {
        // If prepare fails here, we must throw an exception to be caught below
        $movie_title = 'Selected Movie'; // Default title
        $is_available = true; // Assume true to enter the try block and catch the error there
    } else {
        $stmt_check->bind_param("i", $movie_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $movie = $result->fetch_assoc();
        $stmt_check->close();

        // Assuming a simple availability check based on seats booked vs. a hardcoded limit for this example
        // In a real app, you would check the database for available seats
        $is_available = true; // Placeholder for actual database check
        $movie_title = $movie['movie_name'] ?? 'Selected Movie'; // Use fetched name or default
    }

    // Simplified availability check (assuming a successful check from the previous logic)
    
    // Check if enough seats are available (reinstating the original logic's intent)
    // You would typically join `movies` with a `screenings` table for accurate availability.
    // For now, let's assume the previous code's intent was to check availability based on some criteria.
    // Since we don't have the table structure, we'll focus on the output:

    if ($is_available) { // Placeholder: replace with actual $movie['available_seats'] >= $seats_booked
        // 4. Perform the Booking (Transaction-like process for safety)
        $conn->begin_transaction();
        
        try {
            // a. Insert the booking record
            $sql_insert = "INSERT INTO bookings (movie_id, user_name, seats_booked) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);

            // === FIX 2: CRITICAL CHECK for prepare failure (which causes the Fatal Error) ===
            if (!$stmt_insert) {
                // Throw an exception with the specific SQL error message
                throw new Exception("SQL Prepare Error (Insert Query Failure). Check 'bookings' table and column names: " . $conn->error);
            }

            $stmt_insert->bind_param("isi", $movie_id, $user_name, $seats_booked);
            $stmt_insert->execute();
            $booking_id = $conn->insert_id; // Get the generated booking ID (your "Ticket No")

            // b. Update the available seats (placeholder update)
            // You should use the actual logic from your database schema here.
            // Example:
            // $sql_update = "UPDATE movies SET available_seats = available_seats - ? WHERE movie_id = ?";
            // $stmt_update = $conn->prepare($sql_update);
            // $stmt_update->bind_param("ii", $seats_booked, $movie_id);
            // $stmt_update->execute();

            $conn->commit();
            
            // Success Output
            echo '<h2 class="text-4xl font-extrabold text-green-600 mb-4">✅ Booking Successful!</h2>';
            echo '<p class="text-gray-600 text-lg mb-6">Thank you for booking with us, <span class="font-semibold text-gray-800">' . htmlspecialchars($user_name) . '</span>!</p>';
            echo '<div class="p-4 bg-green-50 rounded-xl mb-6 border border-green-200">';
            echo '<p class="text-sm text-gray-700 font-medium">Ticket Number:</p>';
            echo '<p class="text-3xl font-bold text-green-700">' . $booking_id . '</p>';
            echo '</div>';
            echo '<p class="text-md text-gray-700">You have successfully booked <span class="font-bold text-indigo-600">' . htmlspecialchars($seats_booked) . '</span> seats for the movie:</p>';
            echo '<p class="text-xl font-bold text-indigo-800 mt-1 mb-6">' . htmlspecialchars($movie_title) . '</p>';

        } catch (Exception $e) {
            $conn->rollback();
            // Failure Output (Database Error)
            echo '<h2 class="text-4xl font-extrabold text-red-600 mb-4">❌ Booking Failed!</h2>';
            echo '<p class="text-gray-600 text-lg mb-6">We encountered a system error during your transaction.</p>';
            echo '<p class="text-sm text-red-700 font-mono bg-red-50 p-3 rounded-lg border border-red-200">Error: ' . $e->getMessage() . '</p>';
        }
    } else {
        // Failure Output (Availability)
        echo '<h2 class="text-4xl font-extrabold text-yellow-600 mb-4">⚠️ Booking Failed!</h2>';
        echo '<p class="text-gray-600 text-lg mb-6">We apologize, but there are not enough seats available for your request.</p>';
        echo '<p class="text-md text-gray-700">Please try a different movie or a smaller number of seats.</p>';
    }

} else {
    // Failure Output (Direct Access / Invalid Data)
    echo '<h2 class="text-4xl font-extrabold text-red-600 mb-4">🛑 Access Denied</h2>';
    echo '<p class="text-gray-600 text-lg mb-6">This page should only be accessed via the booking form submission.</p>';
}

$conn->close();

// Link to return to the booking form
echo '<div class="mt-8">';
echo '<a href="index.html" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition duration-300 transform hover:scale-[1.02]">Start New Booking</a>';
echo '</div>';

// End HTML output
echo '
    </div>
</body>
</html>';
?>
