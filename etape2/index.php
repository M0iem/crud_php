<?php
/******************************************************
 * index.php – CRUD dans un seul fichier
 * Entité : book (id, title, author, year)
 ******************************************************/

// ---------- CONFIG BDD ----------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ---------- CONFIG BDD ----------
$dsn = 'mysql:host=localhost:8889;dbname=tp2_crud_php;charset=utf8mb4';
$dbUser = 'root';
$dbPass = 'root'; // si ton MAMP MySQL a mot de passe root

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connexion à tp2_crud_php OK!";
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// ---------- ROUTAGE ----------
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

ob_start(); // buffer pour le contenu central

switch ($action) {
    case 'list':
        action_list($pdo);
        break;

    case 'create':
        action_create($pdo);
        break;

    case 'view':
        action_view($pdo, $id);
        break;

    case 'edit':
        action_edit($pdo, $id);
        break;

    case 'delete':
        action_delete($pdo, $id);
        break;

    default:
        echo "<h2>Erreur 404</h2><p>Action inconnue.</p>";
        break;
}

$content = ob_get_clean();
render_layout($content);


// ===================================================
// ================   ACTIONS   ======================
// ===================================================

function action_list(PDO $pdo)
{
    $stmt = $pdo->query("SELECT * FROM book ORDER BY id DESC");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Liste des livres</h1>";
    echo '<p><a href="index.php?action=create">+ Ajouter un livre</a></p>';

    if (empty($books)) {
        echo "<p>Aucun livre pour le moment.</p>";
        return;
    }

    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Titre</th><th>Auteur</th><th>Année</th><th>Actions</th></tr>";

    foreach ($books as $b) {
        echo "<tr>";
        echo "<td>{$b['id']}</td>";
        echo "<td>" . htmlspecialchars($b['title']) . "</td>";
        echo "<td>" . htmlspecialchars($b['author']) . "</td>";
        echo "<td>" . htmlspecialchars($b['year']) . "</td>";
        echo "<td>";
        echo "<a href='index.php?action=view&id={$b['id']}'>Voir</a> | ";
        echo "<a href='index.php?action=edit&id={$b['id']}'>Éditer</a> | ";
        echo "<a href='index.php?action=delete&id={$b['id']}'>Supprimer</a>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</table>";
}

function action_create(PDO $pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title  = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $year   = trim($_POST['year'] ?? '');

        if ($title === '' || $author === '') {
            echo "<p style='color:red;'>Titre et auteur sont obligatoires.</p>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO book (title, author, year) VALUES (:title, :author, :year)");
            $stmt->execute([
                ':title'  => $title,
                ':author' => $author,
                ':year'   => ($year === '' ? null : (int)$year),
            ]);

            header("Location: index.php?action=list");
            exit;
        }
    }

    echo "<h1>Ajouter un livre</h1>";
    echo '<form method="post" action="index.php?action=create">
        <p>
            <label>Titre :<br>
                <input type="text" name="title">
            </label>
        </p>
        <p>
            <label>Auteur :<br>
                <input type="text" name="author">
            </label>
        </p>
        <p>
            <label>Année :<br>
                <input type="number" name="year">
            </label>
        </p>
        <p>
            <button type="submit">Enregistrer</button>
            <a href="index.php?action=list">Annuler</a>
        </p>
    </form>';
}

function action_view(PDO $pdo, ?int $id)
{
    if (!$id) {
        echo "<p>ID manquant.</p>";
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM book WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        echo "<p>Livre introuvable.</p>";
        return;
    }

    echo "<h1>Détail du livre #{$book['id']}</h1>";
    echo "<p><strong>Titre :</strong> " . htmlspecialchars($book['title']) . "</p>";
    echo "<p><strong>Auteur :</strong> " . htmlspecialchars($book['author']) . "</p>";
    echo "<p><strong>Année :</strong> " . htmlspecialchars($book['year']) . "</p>";
    echo '<p>
            <a href="index.php?action=edit&id=' . $book['id'] . '">Éditer</a> |
            <a href="index.php?action=list">Retour à la liste</a>
        </p>';
}

function action_edit(PDO $pdo, ?int $id)
{
    if (!$id) {
        echo "<p>ID manquant.</p>";
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM book WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        echo "<p>Livre introuvable.</p>";
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title  = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $year   = trim($_POST['year'] ?? '');

        if ($title === '' || $author === '') {
            echo "<p style='color:red;'>Titre et auteur sont obligatoires.</p>";
        } else {
            $stmt = $pdo->prepare("UPDATE book
                                   SET title = :title, author = :author, year = :year
                                   WHERE id = :id");
            $stmt->execute([
                ':title'  => $title,
                ':author' => $author,
                ':year'   => ($year === '' ? null : (int)$year),
                ':id'     => $id,
            ]);

            header("Location: index.php?action=view&id=" . $id);
            exit;
        }
    }

    echo "<h1>Modifier le livre #{$book['id']}</h1>";
    echo '<form method="post" action="index.php?action=edit&id=' . $book['id'] . '">
        <p>
            <label>Titre :<br>
                <input type="text" name="title" value="' . htmlspecialchars($book['title']) . '">
            </label>
        </p>
        <p>
            <label>Auteur :<br>
                <input type="text" name="author" value="' . htmlspecialchars($book['author']) . '">
            </label>
        </p>
        <p>
            <label>Année :<br>
                <input type="number" name="year" value="' . htmlspecialchars($book['year']) . '">
            </label>
        </p>
        <p>
            <button type="submit">Enregistrer</button>
            <a href="index.php?action=list">Annuler</a>
        </p>
    </form>';
}

function action_delete(PDO $pdo, ?int $id)
{
    if (!$id) {
        echo "<p>ID manquant.</p>";
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
            $stmt = $pdo->prepare("DELETE FROM book WHERE id = :id");
            $stmt->execute([':id' => $id]);
        }
        header("Location: index.php?action=list");
        exit;
    }

    echo "<h1>Supprimer le livre #$id</h1>";
    echo "<p>Es-tu sûr de vouloir supprimer ce livre ?</p>";
    echo '<form method="post" action="index.php?action=delete&id=' . $id . '">
            <button type="submit" name="confirm" value="yes">Oui, supprimer</button>
            <button type="submit" name="confirm" value="no">Non, annuler</button>
        </form>';
}


// ===================================================
// ================   LAYOUT   =======================
// ===================================================

function render_layout(string $content)
{
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TP CRUD PHP/MySQL - Étape 2</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">         
     
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            nav a { margin-right: 10px; }
            table { border-collapse: collapse; margin-top: 10px; }
            th, td { padding: 6px 10px; }
        </style>
    </head>
    <body>
        <nav>
            <a href="index.php?action=list">Liste</a>
            <a href="index.php?action=create">Créer</a>
        </nav>
        <hr>
        <?= $content ?>

         <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

?>