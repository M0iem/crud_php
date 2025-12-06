<?php
/******************************************************
 * index.php – CRUD avec layout moderne Bootstrap
 * Entité : book (id, title, author, year)
 ******************************************************/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ---------- CONFIG BDD ----------
$dsn = 'mysql:host=localhost:8889;dbname=tp2_crud_php;charset=utf8mb4';
$dbUser = 'root';
$dbPass = 'root'; // mot de passe MAMP

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// ---------- ROUTAGE ----------
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

ob_start(); // buffer pour le contenu central

switch ($action) {
    case 'list': action_list($pdo); break;
    case 'create': action_create($pdo); break;
    case 'view': action_view($pdo, $id); break;
    case 'edit': action_edit($pdo, $id); break;
    case 'delete': action_delete($pdo, $id); break;
    default: echo "<h2>Erreur 404</h2><p>Action inconnue.</p>"; break;
}

$content = ob_get_clean();
render_layout($content);


// ===================================================
// =================== ACTIONS ======================
// ===================================================

function action_list(PDO $pdo) {
    $stmt = $pdo->query("SELECT * FROM book ORDER BY id DESC");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo '<div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Liste des livres</h1>
            <a href="index.php?action=create" class="btn btn-success"><i class="bi bi-plus"></i> Ajouter un livre</a>
          </div>';

    if (empty($books)) {
        echo "<p>Aucun livre pour le moment.</p>";
        return;
    }

    echo '<div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Auteur</th>
                        <th>Année</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($books as $b) {
        echo "<tr>";
        echo "<td>{$b['id']}</td>";
        echo "<td>" . htmlspecialchars($b['title']) . "</td>";
        echo "<td>" . htmlspecialchars($b['author']) . "</td>";
        echo "<td>" . htmlspecialchars($b['year']) . "</td>";
        echo "<td>
                <a href='index.php?action=view&id={$b['id']}' class='btn btn-info btn-sm'><i class='bi bi-eye'></i></a>
                <a href='index.php?action=edit&id={$b['id']}' class='btn btn-primary btn-sm'><i class='bi bi-pencil'></i></a>
                <a href='index.php?action=delete&id={$b['id']}' class='btn btn-danger btn-sm'><i class='bi bi-trash'></i></a>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table></div>";
}

function action_create(PDO $pdo) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title  = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $year   = trim($_POST['year'] ?? '');

        if ($title === '' || $author === '') {
            echo "<div class='alert alert-danger'>Titre et auteur sont obligatoires.</div>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO book (title, author, year) VALUES (:title, :author, :year)");
            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':year' => ($year === '' ? null : (int)$year)
            ]);
            header("Location: index.php?action=list");
            exit;
        }
    }

    echo '<h1>Ajouter un livre</h1>
          <form method="post" action="index.php?action=create">
            <div class="mb-3">
                <label class="form-label">Titre</label>
                <input type="text" name="title" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Auteur</label>
                <input type="text" name="author" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Année</label>
                <input type="number" name="year" class="form-control">
            </div>
            <button type="submit" class="btn btn-success">Enregistrer</button>
            <a href="index.php?action=list" class="btn btn-secondary">Annuler</a>
          </form>';
}

function action_view(PDO $pdo, ?int $id) {
    if (!$id) { echo "<div class='alert alert-warning'>ID manquant.</div>"; return; }

    $stmt = $pdo->prepare("SELECT * FROM book WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) { echo "<div class='alert alert-danger'>Livre introuvable.</div>"; return; }

    echo '<div class="card" style="max-width: 600px;">
            <div class="card-body">
                <h5 class="card-title">' . htmlspecialchars($book['title']) . '</h5>
                <p class="card-text"><strong>Auteur :</strong> ' . htmlspecialchars($book['author']) . '</p>
                <p class="card-text"><strong>Année :</strong> ' . htmlspecialchars($book['year']) . '</p>
                <a href="index.php?action=edit&id=' . $book['id'] . '" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Éditer</a>
                <a href="index.php?action=list" class="btn btn-secondary btn-sm">Retour à la liste</a>
            </div>
          </div>';
}

function action_edit(PDO $pdo, ?int $id) {
    if (!$id) { echo "<div class='alert alert-warning'>ID manquant.</div>"; return; }

    $stmt = $pdo->prepare("SELECT * FROM book WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) { echo "<div class='alert alert-danger'>Livre introuvable.</div>"; return; }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title  = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $year   = trim($_POST['year'] ?? '');

        if ($title === '' || $author === '') {
            echo "<div class='alert alert-danger'>Titre et auteur sont obligatoires.</div>";
        } else {
            $stmt = $pdo->prepare("UPDATE book SET title=:title, author=:author, year=:year WHERE id=:id");
            $stmt->execute([
                ':title'=>$title, ':author'=>$author,
                ':year'=>($year===''?null:(int)$year), ':id'=>$id
            ]);
            header("Location: index.php?action=view&id=$id");
            exit;
        }
    }

    echo '<h1>Modifier le livre</h1>
          <form method="post" action="index.php?action=edit&id=' . $book['id'] . '">
            <div class="mb-3">
                <label class="form-label">Titre</label>
                <input type="text" name="title" class="form-control" value="' . htmlspecialchars($book['title']) . '">
            </div>
            <div class="mb-3">
                <label class="form-label">Auteur</label>
                <input type="text" name="author" class="form-control" value="' . htmlspecialchars($book['author']) . '">
            </div>
            <div class="mb-3">
                <label class="form-label">Année</label>
                <input type="number" name="year" class="form-control" value="' . htmlspecialchars($book['year']) . '">
            </div>
            <button type="submit" class="btn btn-success">Enregistrer</button>
            <a href="index.php?action=list" class="btn btn-secondary">Annuler</a>
          </form>';
}

function action_delete(PDO $pdo, ?int $id) {
    if (!$id) { echo "<div class='alert alert-warning'>ID manquant.</div>"; return; }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
            $stmt = $pdo->prepare("DELETE FROM book WHERE id = :id");
            $stmt->execute([':id'=>$id]);
        }
        header("Location: index.php?action=list");
        exit;
    }

    echo '<div class="alert alert-danger">
            <h5>Supprimer le livre #'.$id.'</h5>
            <p>Es-tu sûr de vouloir supprimer ce livre ?</p>
            <form method="post" action="index.php?action=delete&id='.$id.'">
                <button type="submit" name="confirm" value="yes" class="btn btn-danger btn-sm">Oui, supprimer</button>
                <button type="submit" name="confirm" value="no" class="btn btn-secondary btn-sm">Annuler</button>
            </form>
          </div>';
}

// ===================================================
// ================== LAYOUT ========================
// ===================================================

function render_layout(string $content) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TP CRUD PHP/MySQL</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            body { padding: 20px; background-color: #f8f9fa; }
            h1 { margin-bottom: 20px; }
            nav { margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <nav class="mb-3">
                <a href="index.php?action=list" class="btn btn-outline-primary me-2"><i class="bi bi-list"></i> Liste</a>
                <a href="index.php?action=create" class="btn btn-outline-success"><i class="bi bi-plus"></i> Créer</a>
            </nav>
            <?= $content ?>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
?>
