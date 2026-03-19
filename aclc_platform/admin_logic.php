<?php
// --- Post Updates & Deletes ---
if (isset($_POST['update_post'])) {
    $pdo->prepare("UPDATE posts SET title=?, content=?, type=?, event_date=? WHERE id=?")->execute([
        $_POST['title'],
        $_POST['content'],
        $_POST['type'],
        !empty($_POST['event_date']) ? $_POST['event_date'] : null,
        (int)$_POST['edit_id']
    ]);
    header("Location: admin.php?success=1");
    exit();
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $post = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
    $post->execute([$id]);
    if (($p = $post->fetch()) && !empty($p['image_path']) && file_exists($p['image_path'])) unlink($p['image_path']);
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
    header("Location: admin.php?deleted=1");
    exit();
}

if (isset($_POST['submit_post'])) {
    $date = !empty($_POST['scheduled_date']) ? date('Y-m-d H:i:s', strtotime($_POST['scheduled_date'])) : date('Y-m-d H:i:s');
    $img = null;
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === 0) {
        $path = 'assets/img/uploads/' . time() . '_' . basename($_FILES['post_image']['name']);
        if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'png', 'jpeg', 'gif', 'webp']) && move_uploaded_file($_FILES['post_image']['tmp_name'], $path)) $img = $path;
    }
    $pdo->prepare("INSERT INTO posts (title, content, type, image_path, created_at, event_date) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$_POST['title'], $_POST['content'], $_POST['type'], $img, $date, !empty($_POST['event_date']) ? $_POST['event_date'] : null]);
    header("Location: admin.php?success=1");
    exit();
}

// --- Urgent Bulletin (XML) Updates & Deletes ---
if (isset($_POST['update_xml']) && file_exists($xml = 'data/announcements.xml')) {
    $doc = new DOMDocument();
    $doc->load($xml);
    $xp = new DOMXPath($doc);
    foreach ($xp->query("//announcement[id='{$_POST['edit_xml_id']}']") as $n) {
        if ($c = $xp->query("category", $n)->item(0)) $c->nodeValue = htmlspecialchars($_POST['xml_category']);
        if ($t = $xp->query("title", $n)->item(0)) $t->nodeValue = htmlspecialchars($_POST['xml_title']);
        if ($d = $xp->query("description", $n)->item(0)) $d->nodeValue = htmlspecialchars($_POST['xml_description']);
    }
    $doc->save($xml);
    header("Location: admin.php?xml_success=1");
    exit();
}

if (isset($_GET['delete_xml_id']) && file_exists($xml = 'data/announcements.xml')) {
    $doc = new DOMDocument();
    $doc->load($xml);
    foreach ((new DOMXPath($doc))->query("//announcement[id='{$_GET['delete_xml_id']}']") as $n) $n->parentNode->removeChild($n);
    $doc->save($xml);
    header("Location: admin.php?xml_deleted=1");
    exit();
}

if (isset($_POST['submit_xml'])) {
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;
    $xml = 'data/announcements.xml';
    file_exists($xml) ? $doc->load($xml) : $doc->appendChild($doc->createElement('campus_updates'));
    $node = $doc->createElement('announcement');
    $node->append($doc->createElement('id', time()), $doc->createElement('category', htmlspecialchars($_POST['xml_category'])), $doc->createElement('title', htmlspecialchars($_POST['xml_title'])), $doc->createElement('date', date('Y-m-d')), $doc->createElement('description', htmlspecialchars($_POST['xml_description'])));
    $doc->documentElement->appendChild($node);
    $doc->save($xml);
    header("Location: admin.php?xml_success=1");
    exit();
}

// --- Comment & User Management ---
if (isset($_GET['delete_comment_id'])) {
    $pdo->prepare("DELETE FROM comments WHERE id = ? OR parent_id = ?")->execute([(int)$_GET['delete_comment_id'], (int)$_GET['delete_comment_id']]);
    header("Location: admin.php?deleted=1");
    exit();
}

if (isset($_GET['approve_user_id'])) {
    $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?")->execute([(int)$_GET['approve_user_id']]);
    header("Location: admin.php?success=1");
    exit();
}

if (isset($_GET['delete_user_id'])) {
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([(int)$_GET['delete_user_id']]);
    header("Location: admin.php?deleted=1");
    exit();
}

if (isset($_POST['submit_user'])) {
    try {
        $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, year_level, section, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'student', 'approved')")
            ->execute([trim($_POST['username']), trim($_POST['first_name']), trim($_POST['last_name']), trim($_POST['email']), trim($_POST['year_level']), trim($_POST['section']), password_hash($_POST['password'], PASSWORD_DEFAULT)]);
        header("Location: admin.php?success=1");
        exit();
    } catch (PDOException $e) {
        header("Location: admin.php?error=duplicate");
        exit();
    }
}

if (isset($_POST['update_user'])) {
    $id = (int)$_POST['edit_user_id'];
    try {
        if (!empty($_POST['edit_password'])) {
            $pdo->prepare("UPDATE users SET username=?, first_name=?, last_name=?, email=?, year_level=?, section=?, password_hash=? WHERE id=?")
                ->execute([trim($_POST['edit_username']), trim($_POST['edit_firstname']), trim($_POST['edit_lastname']), trim($_POST['edit_email']), trim($_POST['edit_year']), trim($_POST['edit_section']), password_hash($_POST['edit_password'], PASSWORD_DEFAULT), $id]);
        } else {
            $pdo->prepare("UPDATE users SET username=?, first_name=?, last_name=?, email=?, year_level=?, section=? WHERE id=?")
                ->execute([trim($_POST['edit_username']), trim($_POST['edit_firstname']), trim($_POST['edit_lastname']), trim($_POST['edit_email']), trim($_POST['edit_year']), trim($_POST['edit_section']), $id]);
        }
        header("Location: admin.php?success=1");
        exit();
    } catch (PDOException $e) {
        header("Location: admin.php?error=duplicate");
        exit();
    }
}
