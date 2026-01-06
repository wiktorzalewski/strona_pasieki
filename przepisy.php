<?php
require_once 'includes/db.php';

$pageTitle = 'Przepisy z Miodem - Kuchnia Cioci Agnieszki | Pasieka Pod Gruszką';
$pageDesc = 'Sprawdzone przepisy na ciasta, napoje i dania z miodem. Miodownik, lemoniada i kurczak w miodzie.';
$ogImage = 'https://pasiekapodgruszka.pl/assets/images/recipes/miodownik.jpg';

// Pobierz przepisy z bazy
try {
    $stmt = $pdo->query("SELECT * FROM recipes WHERE is_active = 1 ORDER BY sort_order ASC");
    $recipes = $stmt->fetchAll();
} catch (PDOException $e) {
    $recipes = [];
}

// Generowanie danych JS dla modala
$jsRecipes = [];
foreach ($recipes as $recipe) {
    $jsRecipes[$recipe['slug']] = [
        'title' => $recipe['title'],
        'img' => $recipe['image_path'],
        'ingredients' => json_decode($recipe['ingredients'], true),
        'steps' => json_decode($recipe['steps'], true)
    ];
}

$extraCss = '
    <style>
        /* --- STYLE KART PRZEPISÓW --- */
        .recipe-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }

        .recipe-card {
            background: #1a1a1a;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #333;
            display: flex;
            flex-direction: column;
        }

        .recipe-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 193, 7, 0.15);
            border-color: var(--c-gold, #ffc107);
        }

        .recipe-img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-bottom: 3px solid var(--c-gold, #ffc107);
        }

        .recipe-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .recipe-title {
            color: var(--c-gold, #ffc107);
            font-size: 1.4rem;
            margin-bottom: 10px;
        }

        .recipe-desc {
            color: #bbb;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .recipe-meta {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 15px;
            border-top: 1px solid #333;
            padding-top: 10px;
        }

        .recipe-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 99999;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            padding: 20px;
        }

        .recipe-modal-overlay.active {
            opacity: 1;
        }

        .recipe-modal-box {
            background: #1a1a1a;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            border-radius: 15px;
            overflow-y: auto;
            position: relative;
            border: 1px solid #444;
            transform: scale(0.8);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .recipe-modal-overlay.active .recipe-modal-box {
            transform: scale(1);
        }

        .rm-header {
            position: relative;
        }

        .rm-img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
        }

        .rm-title-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent);
            padding: 20px;
            padding-top: 60px;
        }

        .rm-title {
            color: var(--c-gold, #ffc107);
            font-size: 2rem;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }

        .rm-body {
            padding: 30px;
            color: #ddd;
        }

        .rm-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 40px;
        }

        .ingredients-list {
            list-style: none;
            padding: 0;
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
        }

        .ingredients-list h4 {
            color: var(--c-gold, #ffc107);
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }

        .ingredients-list li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
            font-size: 0.95rem;
        }

        .ingredients-list li::before {
            content: "✔";
            color: var(--c-orange, #e67e22);
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        .steps-box h4 {
            color: var(--c-gold, #ffc107);
            margin-top: 0;
            font-size: 1.3rem;
        }

        .step-item {
            margin-bottom: 20px;
        }

        .step-num {
            display: inline-block;
            background: var(--c-gold, #ffc107);
            color: #000;
            font-weight: bold;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            text-align: center;
            line-height: 25px;
            margin-right: 10px;
        }

        .close-recipe {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            z-index: 10;
            transition: background 0.3s;
        }

        .close-recipe:hover {
            background: var(--c-gold, #ffc107);
            color: #000;
        }

        @media (max-width: 768px) {
            .rm-grid {
                grid-template-columns: 1fr;
            }

            .rm-img {
                height: 200px;
            }

            .rm-title {
                font-size: 1.5rem;
            }
        }
    </style>';

$extraScripts = '
    <script>
        const recipeDB = ' . json_encode($jsRecipes, JSON_UNESCAPED_UNICODE) . ';

        function openRecipe(id) {
            const data = recipeDB[id];
            if (!data) return;

            const modal = document.getElementById("recipe-modal");

            document.getElementById("rm-title").innerText = data.title;

            const img = document.getElementById("rm-img");
            img.src = data.img;
            img.onerror = function () { this.src = "https://placehold.co/800x400?text=Smacznego"; };

            const ingList = document.getElementById("rm-ingredients");
            ingList.innerHTML = "<h4>Składniki:</h4>";
            data.ingredients.forEach(item => {
                ingList.innerHTML += `<li>${item}</li>`;
            });

            const stepsDiv = document.getElementById("rm-steps");
            stepsDiv.innerHTML = "";
            data.steps.forEach((step, index) => {
                stepsDiv.innerHTML += `
                    <div class="step-item">
                        <span class="step-num">${index + 1}</span>
                        <span>${step}</span>
                    </div>
                `;
            });

            modal.style.display = "flex";
            setTimeout(() => modal.classList.add("active"), 10);
            document.body.style.overflow = "hidden";
        }

        function closeRecipe() {
            const modal = document.getElementById("recipe-modal");
            modal.classList.remove("active");
            setTimeout(() => {
                modal.style.display = "none";
                document.body.style.overflow = "auto";
            }, 300);
        }

        window.onclick = function (e) {
            const modal = document.getElementById("recipe-modal");
            if (e.target === modal) closeRecipe();
        }
    </script>';

include 'includes/header.php';
?>

    <main class="container">
        <h1 class="section-title">Przepisy Cioci Agnieszki</h1>
        <p
            style="text-align:center; color:#888; margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto;">
            Miód to nie tylko dodatek do herbaty! Odkryj nasze rodzinne przepisy na pyszne ciasta, orzeźwiające napoje i
            wykwintne dania obiadowe.
        </p>

        <div class="recipe-grid">
            <?php foreach ($recipes as $recipe): ?>
                <div class="recipe-card">
                    <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" class="recipe-img" alt="<?php echo htmlspecialchars($recipe['title']); ?>"
                        onerror="this.src='https://placehold.co/600x400?text=Przepis'">
                    <div class="recipe-content">
                        <h3 class="recipe-title"><?php echo htmlspecialchars($recipe['title']); ?></h3>
                        <div class="recipe-meta">
                            <span><i class="far fa-clock"></i> <?php echo htmlspecialchars($recipe['prep_time']); ?></span>
                            <span><i class="fas fa-signal"></i> <?php echo htmlspecialchars($recipe['difficulty']); ?></span>
                        </div>
                        <p class="recipe-desc"><?php echo htmlspecialchars($recipe['short_desc']); ?></p>
                        <button class="btn-main" onclick="openRecipe('<?php echo $recipe['slug']; ?>')">ZOBACZ PRZEPIS</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="recipe-modal" class="recipe-modal-overlay">
        <div class="recipe-modal-box">
            <button class="close-recipe" onclick="closeRecipe()">×</button>

            <div class="rm-header">
                <img src="" id="rm-img" class="rm-img" alt="Zdjęcie potrawy">
                <div class="rm-title-overlay">
                    <h2 id="rm-title" class="rm-title">Tytuł Przepisu</h2>
                </div>
            </div>

            <div class="rm-body">
                <div class="rm-grid">
                    <div>
                        <ul class="ingredients-list" id="rm-ingredients">
                        </ul>
                    </div>

                    <div class="steps-box">
                        <h4>Sposób przygotowania:</h4>
                        <div id="rm-steps">
                        </div>

                        <div
                            style="margin-top: 30px; border-top: 1px solid #444; padding-top: 15px; text-align: right;">
                            <span
                                style="color:var(--c-gold, #ffc107); font-weight:bold; font-family: 'Playfair Display', serif; font-size: 1.2rem;">Smacznego!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
