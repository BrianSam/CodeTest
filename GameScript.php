<?php
$db_host = '127.0.0.1'; // MySQL server host
$db_user = 'root'; // MySQL username
$db_pass = 'passsword'; // MySQL password
$db_name = 'GameDB'; // Name of the MySQL database

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function login($conn) {
    echo "Welcome to the Coin Game!\n";
    $username = readline("Enter username: ");
    $password = readline("Enter password: ");

    global $conn;
    $stmt = $conn->prepare('SELECT * FROM users WHERE username=? AND password=?');
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    $user = $result->fetch_assoc();

    if ($user) {
        echo "Login successful!\n";
        return $user['username'];
    } else {
        echo "Invalid username or password. Please try again.\n";
        return null;
    }
}

function play_game() {
    $coins = 21;

    while ($coins > 0) {
        echo "\nCoins left: $coins\n";

        $player_pick = (int) readline("Pick 1, 2, 3, or 4 coins: ");
        while ($player_pick < 1 || $player_pick > 4 || $player_pick > $coins) {
            $player_pick = (int) readline("Invalid input. Pick 1, 2, 3, or 4 coins: ");
        }
        $coins -= $player_pick;

        if ($coins <= 0) {
            echo "You picked the last coin. You lose!\n";
            return "Lost";
        }

        $ai_pick = rand(1, min($coins, 4));
        echo "AI picked $ai_pick coins.\n";
        $coins -= $ai_pick;

        if ($coins <= 0) {
            echo "AI picked the last coin. You win!\n";
            return "Won";
        }
    }
}

function update_history($conn, $username, $result) {
    global $conn;
    $user_id = get_user_id($conn, $username);
    $stmt = $conn->prepare('INSERT INTO game_history (user_id, result) VALUES (?, ?)');
    $stmt->bind_param('is', $user_id, $result);
    $stmt->execute();
}

function get_user_id($conn, $username) {
    global $conn;
    $stmt = $conn->prepare('SELECT id FROM users WHERE username=?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $user = $result->fetch_assoc();
    return $user['id'];
}

function show_history($conn, $username) {
    global $conn;
    $user_id = get_user_id($conn, $username);
    $stmt = $conn->prepare('SELECT * FROM game_history WHERE user_id=?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "\nGame History:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['result'] . "\n";
    }
}

$user = null;

while (!$user) {
    $user = login($conn);
}

while (true) {
    $choice = strtolower(readline("\nDo you want to play the game? (yes/no): "));

    if ($choice === "yes") {
        $result = play_game();
        update_history($conn, $user, $result);
    } elseif ($choice === "no") {
        show_history($conn, $user);
        echo "Thanks for playing!\n";
        break;
    } else {
        echo "Invalid choice. Please enter 'yes' or 'no'.\n";
    }
}

$conn->close();
?>
