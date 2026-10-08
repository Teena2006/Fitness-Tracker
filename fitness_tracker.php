<?php
session_start();

/* =========================================================
   FITNESS TRACKER - SINGLE FILE PROJECT
   Frontend + Backend + MySQL
   File: fitness_tracker.php
   ========================================================= */

$host = "localhost";
$user = "root";
$pass = "";
$db   = "fitness_tracker";

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("Database connection failed: " . $conn->connect_error);

$conn->query("CREATE DATABASE IF NOT EXISTS `$db`");
$conn->select_db($db);
$conn->set_charset("utf8mb4");

/* ---------- TABLES ---------- */
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    age INT DEFAULT 0,
    height DECIMAL(5,2) DEFAULT 0,
    weight DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_date DATE NOT NULL,
    steps INT DEFAULT 0,
    water DECIMAL(5,2) DEFAULT 0,
    calories INT DEFAULT 0,
    exercise_minutes INT DEFAULT 0,
    sleep_hours DECIMAL(4,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

/* ---------- LOGOUT ---------- */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: fitness_tracker.php");
    exit;
}

/* ---------- REGISTER ---------- */
$message = "";

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $age = (int)$_POST['age'];
    $height = (float)$_POST['height'];
    $weight = (float)$_POST['weight'];

    if ($name == "" || $email == "" || $password == "") {
        $message = "Please fill all required fields.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO users(name,email,password,age,height,weight)
             VALUES(?,?,?,?,?,?)"
        );
        $stmt->bind_param(
            "sssidd",
            $name, $email, $password, $age, $height, $weight
        );

        if ($stmt->execute()) {
            $message = "Registration successful. Please login.";
        } else {
            $message = "Email already exists.";
        }
    }
}

/* ---------- LOGIN ---------- */
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email=? AND password=?"
    );
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $_SESSION['user'] = $result->fetch_assoc();
        header("Location: fitness_tracker.php?page=dashboard");
        exit;
    } else {
        $message = "Invalid email or password.";
    }
}

/* ---------- UPDATE PROFILE ---------- */
if (isset($_POST['update_profile']) && isset($_SESSION['user'])) {
    $uid = (int)$_SESSION['user']['id'];

    $name = trim($_POST['name']);
    $age = (int)$_POST['age'];
    $height = (float)$_POST['height'];
    $weight = (float)$_POST['weight'];

    $stmt = $conn->prepare(
        "UPDATE users SET name=?, age=?, height=?, weight=? WHERE id=?"
    );
    $stmt->bind_param(
        "siddi",
        $name, $age, $height, $weight, $uid
    );
    $stmt->execute();

    $result = $conn->query("SELECT * FROM users WHERE id=$uid");
    $_SESSION['user'] = $result->fetch_assoc();

    $message = "Profile updated successfully.";
}

/* ---------- ADD ACTIVITY ---------- */
if (isset($_POST['add_activity']) && isset($_SESSION['user'])) {
    $uid = (int)$_SESSION['user']['id'];

    $date = $_POST['activity_date'];
    $steps = (int)$_POST['steps'];
    $water = (float)$_POST['water'];
    $calories = (int)$_POST['calories'];
    $exercise = (int)$_POST['exercise_minutes'];
    $sleep = (float)$_POST['sleep_hours'];

    $stmt = $conn->prepare(
        "INSERT INTO activities
        (user_id,activity_date,steps,water,calories,exercise_minutes,sleep_hours)
        VALUES(?,?,?,?,?,?,?)"
    );

    $stmt->close();

    $stmt = $conn->prepare(
        "INSERT INTO activities
        (user_id,activity_date,steps,water,calories,exercise_minutes,sleep_hours)
        VALUES(?,?,?,?,?,?,?)"
    );
    $stmt->bind_param(
        "isididi",
        $uid, $date, $steps, $water, $calories, $exercise, $sleep
    );
    $stmt->execute();

    $message = "Fitness activity added successfully.";
}

/* ---------- DELETE ACTIVITY ---------- */
if (isset($_GET['delete_activity']) && isset($_SESSION['user'])) {
    $uid = (int)$_SESSION['user']['id'];
    $id = (int)$_GET['delete_activity'];

    $conn->query(
        "DELETE FROM activities WHERE id=$id AND user_id=$uid"
    );

    header("Location: fitness_tracker.php?page=activities");
    exit;
}

$page = $_GET['page'] ?? 'home';

/* ---------- USER DATA ---------- */
$userData = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fitness Tracker</title>

<style>
*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Arial,sans-serif;
    background:#f4f7f5;
    color:#222;
}

nav{
    background:#14532d;
    color:white;
    padding:15px 6%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
}

.logo{
    font-size:24px;
    font-weight:bold;
}

nav a{
    color:white;
    text-decoration:none;
    margin:5px 10px;
}

.container{
    width:90%;
    max-width:1100px;
    margin:30px auto;
}

.hero{
    background:#166534;
    color:white;
    text-align:center;
    padding:70px 25px;
    border-radius:18px;
}

.hero h1{
    font-size:42px;
    margin:10px;
}

.hero p{
    font-size:18px;
}

.btn{
    display:inline-block;
    padding:11px 18px;
    background:#16a34a;
    color:white;
    border:0;
    border-radius:7px;
    text-decoration:none;
    cursor:pointer;
    margin:4px;
}

.btn.red{
    background:#dc2626;
}

.btn.gray{
    background:#6b7280;
}

.form-box{
    background:white;
    max-width:500px;
    margin:30px auto;
    padding:30px;
    border-radius:12px;
    box-shadow:0 3px 15px #ddd;
}

input{
    width:100%;
    padding:12px;
    margin:8px 0 15px;
    border:1px solid #ccc;
    border-radius:6px;
}

.alert{
    max-width:700px;
    margin:15px auto;
    padding:14px;
    background:#fff3cd;
    border-radius:7px;
    text-align:center;
}

.card-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:20px;
}

.card{
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 3px 12px #ddd;
}

.stat{
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 3px 12px #ddd;
    text-align:center;
}

.stat h2{
    color:#15803d;
    font-size:30px;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

th,td{
    padding:12px;
    border:1px solid #ddd;
    text-align:center;
}

th{
    background:#14532d;
    color:white;
}

footer{
    margin-top:50px;
    background:#14532d;
    color:white;
    text-align:center;
    padding:20px;
}
</style>
</head>

<body>

<nav>
    <div class="logo">💪 Fitness Tracker</div>

    <div>
        <a href="fitness_tracker.php">Home</a>

        <?php if(isset($_SESSION['user'])): ?>

            <a href="fitness_tracker.php?page=dashboard">
                Dashboard
            </a>

            <a href="fitness_tracker.php?page=add_activity">
                Add Activity
            </a>

            <a href="fitness_tracker.php?page=activities">
                History
            </a>

            <a href="fitness_tracker.php?page=profile">
                Profile
            </a>

            <a href="fitness_tracker.php?logout=1">
                Logout
            </a>

        <?php else: ?>

            <a href="fitness_tracker.php?page=login">
                Login
            </a>

            <a href="fitness_tracker.php?page=register">
                Register
            </a>

        <?php endif; ?>
    </div>
</nav>

<div class="container">

<?php if($message): ?>
<div class="alert">
<?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<?php
/* ================= HOME ================= */
if($page == 'home'):
?>

<div class="hero">
    <h1>Fitness Tracker</h1>
    <p>
        Track your daily steps, water, calories,
        exercise and sleep.
    </p>

    <?php if(isset($_SESSION['user'])): ?>

    <a class="btn"
       href="fitness_tracker.php?page=dashboard">
       Open Dashboard
    </a>

    <?php else: ?>

    <a class="btn"
       href="fitness_tracker.php?page=register">
       Get Started
    </a>

    <?php endif; ?>
</div>

<br>

<div class="card-grid">

<div class="card">
<h2>👣 Steps</h2>
<p>Track your daily walking steps.</p>
</div>

<div class="card">
<h2>💧 Water</h2>
<p>Monitor your daily water intake.</p>
</div>

<div class="card">
<h2>🔥 Calories</h2>
<p>Record calories burned.</p>
</div>

<div class="card">
<h2>😴 Sleep</h2>
<p>Track your daily sleep hours.</p>
</div>

</div>

<?php
/* ================= REGISTER ================= */
elseif($page == 'register'):
?>

<div class="form-box">

<h2>Create Fitness Account</h2>

<form method="post">

<input type="text"
       name="name"
       placeholder="Full Name"
       required>

<input type="email"
       name="email"
       placeholder="Email"
       required>

<input type="password"
       name="password"
       placeholder="Password"
       required>

<input type="number"
       name="age"
       placeholder="Age"
       min="1">

<input type="number"
       name="height"
       placeholder="Height (cm)"
       step="0.01">

<input type="number"
       name="weight"
       placeholder="Weight (kg)"
       step="0.01">

<button class="btn" name="register">
Register
</button>

</form>

<p>
Already have an account?
<a href="fitness_tracker.php?page=login">
Login
</a>
</p>

</div>

<?php
/* ================= LOGIN ================= */
elseif($page == 'login'):
?>

<div class="form-box">

<h2>Fitness Tracker Login</h2>

<form method="post">

<input type="email"
       name="email"
       placeholder="Email"
       required>

<input type="password"
       name="password"
       placeholder="Password"
       required>

<button class="btn" name="login">
Login
</button>

</form>

<p>
New user?
<a href="fitness_tracker.php?page=register">
Create Account
</a>
</p>

</div>

<?php
/* ================= DASHBOARD ================= */
elseif($page == 'dashboard'):

if(!isset($_SESSION['user'])):
?>

<div class="form-box">
<h2>Please login first.</h2>
<a class="btn" href="fitness_tracker.php?page=login">
Login
</a>
</div>

<?php
else:

$uid = (int)$_SESSION['user']['id'];

$today = date('Y-m-d');

$todayResult = $conn->query(
    "SELECT
     COALESCE(SUM(steps),0) steps,
     COALESCE(SUM(water),0) water,
     COALESCE(SUM(calories),0) calories,
     COALESCE(SUM(exercise_minutes),0) exercise,
     COALESCE(AVG(sleep_hours),0) sleep
     FROM activities
     WHERE user_id=$uid
     AND activity_date='$today'"
);

$todayData = $todayResult->fetch_assoc();

$activityCount = $conn->query(
    "SELECT COUNT(*) c FROM activities WHERE user_id=$uid"
)->fetch_assoc()['c'];

?>

<h2>Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?> 👋</h2>

<div class="card-grid">

<div class="stat">
<h2>👣 <?php echo $todayData['steps']; ?></h2>
<p>Today's Steps</p>
</div>

<div class="stat">
<h2>💧 <?php echo number_format($todayData['water'],1); ?> L</h2>
<p>Water Intake</p>
</div>

<div class="stat">
<h2>🔥 <?php echo $todayData['calories']; ?></h2>
<p>Calories Burned</p>
</div>

<div class="stat">
<h2>🏃 <?php echo $todayData['exercise']; ?> min</h2>
<p>Exercise</p>
</div>

<div class="stat">
<h2>😴 <?php echo number_format($todayData['sleep'],1); ?> h</h2>
<p>Sleep</p>
</div>

<div class="stat">
<h2><?php echo $activityCount; ?></h2>
<p>Total Records</p>
</div>

</div>

<br>

<a class="btn"
   href="fitness_tracker.php?page=add_activity">
   + Add Today's Activity
</a>

<a class="btn gray"
   href="fitness_tracker.php?page=activities">
   View History
</a>

<?php
endif;

/* ================= ADD ACTIVITY ================= */
elseif($page == 'add_activity'):

if(!isset($_SESSION['user'])):
?>

<div class="form-box">
<h2>Please login first.</h2>
</div>

<?php
else:
?>

<div class="form-box">

<h2>Add Daily Fitness Data</h2>

<form method="post">

<label>Date</label>
<input type="date"
       name="activity_date"
       value="<?php echo date('Y-m-d'); ?>"
       required>

<label>Steps</label>
<input type="number"
       name="steps"
       value="0"
       min="0"
       required>

<label>Water Intake (Litres)</label>
<input type="number"
       name="water"
       value="0"
       min="0"
       step="0.1"
       required>

<label>Calories Burned</label>
<input type="number"
       name="calories"
       value="0"
       min="0"
       required>

<label>Exercise Minutes</label>
<input type="number"
       name="exercise_minutes"
       value="0"
       min="0"
       required>

<label>Sleep Hours</label>
<input type="number"
       name="sleep_hours"
       value="0"
       min="0"
       max="24"
       step="0.1"
       required>

<button class="btn" name="add_activity">
Save Activity
</button>

</form>

</div>

<?php
endif;

/* ================= ACTIVITY HISTORY ================= */
elseif($page == 'activities'):

if(!isset($_SESSION['user'])):
?>

<div class="form-box">
<h2>Please login first.</h2>
</div>

<?php
else:

$uid = (int)$_SESSION['user']['id'];

$q = $conn->query(
    "SELECT * FROM activities
     WHERE user_id=$uid
     ORDER BY activity_date DESC, id DESC"
);
?>

<h2>Fitness History</h2>

<table>

<tr>
<th>Date</th>
<th>Steps</th>
<th>Water</th>
<th>Calories</th>
<th>Exercise</th>
<th>Sleep</th>
<th>Action</th>
</tr>

<?php while($a=$q->fetch_assoc()): ?>

<tr>

<td><?php echo $a['activity_date']; ?></td>

<td><?php echo $a['steps']; ?></td>

<td><?php echo number_format($a['water'],1); ?> L</td>

<td><?php echo $a['calories']; ?></td>

<td><?php echo $a['exercise_minutes']; ?> min</td>

<td><?php echo number_format($a['sleep_hours'],1); ?> h</td>

<td>
<a class="btn red"
   href="fitness_tracker.php?page=activities&delete_activity=<?php echo $a['id']; ?>"
   onclick="return confirm('Delete this activity?')">
Delete
</a>
</td>

</tr>

<?php endwhile; ?>

</table>

<?php
endif;

/* ================= PROFILE ================= */
elseif($page == 'profile'):

if(!isset($_SESSION['user'])):
?>

<div class="form-box">
<h2>Please login first.</h2>
</div>

<?php
else:
$u = $_SESSION['user'];
?>

<div class="form-box">

<h2>My Profile</h2>

<form method="post">

<label>Name</label>
<input type="text"
       name="name"
       value="<?php echo htmlspecialchars($u['name']); ?>"
       required>

<label>Email</label>
<input type="email"
       value="<?php echo htmlspecialchars($u['email']); ?>"
       disabled>

<label>Age</label>
<input type="number"
       name="age"
       value="<?php echo $u['age']; ?>"
       min="1">

<label>Height (cm)</label>
<input type="number"
       name="height"
       value="<?php echo $u['height']; ?>"
       step="0.01">

<label>Weight (kg)</label>
<input type="number"
       name="weight"
       value="<?php echo $u['weight']; ?>"
       step="0.01">

<button class="btn" name="update_profile">
Update Profile
</button>

</form>

</div>

<?php
endif;

else:
?>

<div class="form-box">
<h2>Page not found.</h2>
<a class="btn" href="fitness_tracker.php">
Go Home
</a>
</div>

<?php endif; ?>

</div>

<footer>
Fitness Tracker | DBMS Mini Project
</footer>

</body>
</html>
