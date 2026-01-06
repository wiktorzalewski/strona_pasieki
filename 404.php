<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Nie znaleziono strony | Pasieka Pod Gruszką</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-color: #121212;
            --text-color: #e0e0e0;
            --primary-color: #ffc107;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Outfit', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        /* Tło animowane (pszczoły latające w tle - uproszczone kropki) */
        .background-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: radical-gradient(circle at center, #1a1a1a 0%, #000 100%);
        }

        .container {
            text-align: center;
            z-index: 10;
            padding: 20px;
        }

        .err-code {
            font-family: 'Playfair Display', serif;
            font-size: 8rem;
            color: var(--primary-color);
            margin: 0;
            line-height: 1;
            text-shadow: 0 0 20px rgba(255, 193, 7, 0.4);
            animation: pulse 2s infinite;
        }

        .bee-icon {
            font-size: 4rem;
            color: var(--text-color);
            margin: 20px 0;
            animation: bounce 2s infinite ease-in-out;
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        p {
            color: #888;
            font-size: 1.1rem;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-home {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary-color);
            color: #000;
            text-decoration: none;
            font-weight: bold;
            border-radius: 50px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.3);
        }

        .btn-secondary {
            display: inline-block;
            padding: 12px 30px;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
            border-radius: 50px;
            margin-left: 15px;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: rgba(255, 193, 7, 0.1);
            color: #fff;
        }

        @keyframes pulse {

            0%,
            100% {
                text-shadow: 0 0 20px rgba(255, 193, 7, 0.4);
            }

            50% {
                text-shadow: 0 0 40px rgba(255, 193, 7, 0.8);
            }
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px) rotate(10deg);
            }
        }
    </style>
</head>

<body>

    <div class="background-particles"></div>

    <div class="container">
        <h1 class="err-code">404</h1>
        <div class="bee-icon">
            <i class="fas fa-bug"></i>
        </div>
        <h2>Ups! Pszczoła zgubiła drogę.</h2>
        <p>
            Strona, której szukasz, odleciała do innego ula lub nigdy nie istniała.
            Wróć do pasieki, zanim zrobi się ciemno!
        </p>
        
        <div class="btn-group">
            <a href="/" class="btn-home"><i class="fas fa-home"></i> Główna</a>
            <a href="contact" class="btn-secondary">Kontakt</a>
        </div>
    </div>

</body>

</html>
