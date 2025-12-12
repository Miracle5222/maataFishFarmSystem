<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maata Fish Farm | Quality Fish Products</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Roboto', sans-serif;
            color: #333;
            line-height: 1.6;
            background-color: #f8f9fa;
        }
        header {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo i {
            font-size: 28px;
        }
        nav {
            display: flex;
            gap: 30px;
            list-style: none;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        nav a:hover {
            color: #52be80;
        }
        .nav-buttons {
            display: flex;
            gap: 15px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #52be80;
            color: white;
        }
        .btn-primary:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        .btn-secondary {
            background-color: transparent;
            color: white;
            border: 2px solid white;
        }
        .btn-secondary:hover {
            background-color: white;
            color: #27ae60;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        footer {
            background-color: #1a1a1a;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 50px;
        }
        footer p {
            margin: 5px 0;
        }
        @media (max-width: 768px) {
            nav {
                display: none;
            }
            .header-container {
                flex-direction: column;
                gap: 15px;
            }
            .nav-buttons {
                width: 100%;
                justify-content: space-around;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-fish"></i>
                Maata Fish Farm
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="menu.php">Menu</a>
                <a href="booking.php">Book Now</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
            </nav>
            <div class="nav-buttons">
                <a href="booking.php" class="btn btn-primary"><i class="fas fa-calendar"></i> Book</a>
            </div>
        </div>
    </header>
