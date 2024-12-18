<!DOCTYPE html>
<html>
<head>
    <style>
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .custom_logout {
            margin-top: 35px;
        }
    </style>
    <title>User Registration & Login With Upload Functionality</title>
</head>
<body>
    <?php
        session_start();

        $usersFile = __DIR__ . '/users.json';
        if (!file_exists($usersFile)) {
            file_put_contents($usersFile, json_encode([]));
        }

        $users = json_decode(file_get_contents($usersFile), true);

        // Registration Logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

            if (!array_key_exists($username, $users)) {
                $users[$username] = $password;
                file_put_contents($usersFile, json_encode($users));
                $message = "User registered successfully!";
            } else {
                $message = "Username already exists!";
            }
        }

        // Login Logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            if (array_key_exists($username, $users) && password_verify($password, $users[$username])) {
                $_SESSION['user'] = $username;
                $message = "Login successful! Welcome, $username.";
            } else {
                $message = "Invalid username or password!";
            }
        }

        // File Upload Logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload']) && isset($_SESSION['user'])) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadFile = $uploadDir . basename($_FILES['uploaded_file']['name']);
            $allowedExtensions = ['csv'];

            $fileExtension = pathinfo($uploadFile, PATHINFO_EXTENSION);

            if (in_array($fileExtension, $allowedExtensions)) {
                if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $uploadFile)) {
                    $message = "File uploaded successfully!";
                    // Process the CSV file
                    $file = fopen($uploadFile, 'r');
                    while (($data = fgetcsv($file)) !== false) {
                        // Assuming the CSV has columns: username, password
                        $username = $data[0];
                        $password = $data[1];
                        if (!array_key_exists($username, $users)) {
                            $users[$username] = password_hash($password, PASSWORD_BCRYPT);
                        }
                    }
                    fclose($file);
                    file_put_contents($usersFile, json_encode($users));
                    $message .= " Users added successfully from the file.";
                } else {
                    $message = "File upload failed!";
                }
            } else {
                $message = "Invalid file type. Please upload a CSV file.";
            }
        }

        // Logout Logic
        if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
            session_unset();
            session_destroy();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    ?>

    <?php if (isset($message)): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['user'])): ?>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></h1>
        <h2>Upload File</h2>
        <a href="/sample_csv/user_register_sample.csv" class="btn" download>Download Sample Excel File</a>
        <form method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="uploaded_file" accept=".csv" required>
            <button type="submit" name="upload">Upload</button>
        </form>
        <a href="?logout=true" class="btn custom_logout">Logout</a>
    <?php else: ?>
        <h1>Login</h1>
        <form method="POST">
            @csrf
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>

        <h1>Register</h1>
        <form method="POST">
            @csrf
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register">Register</button>
        </form>
    <?php endif; ?>
</body>
</html>
