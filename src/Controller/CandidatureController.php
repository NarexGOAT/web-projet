<?php

class CandidatureController
{
    private \PDO $pdo;
    private \Twig\Environment $twig;

    // Dossiers d'upload (relatifs à public_html/)
    private const DIR_CV = __DIR__ . '/../../public_html/uploads/cv/';
    private const DIR_LM = __DIR__ . '/../../public_html/uploads/lm/';
    private const MAX_SIZE = 5 * 1024 * 1024; // 5 Mo

    public function __construct(\PDO $pdo, \Twig\Environment $twig)
    {
        $this->pdo  = $pdo;
        $this->twig = $twig;
    }

    // ============== SFx20 — Formulaire postuler ==============
    public function form(int $idOffre): void
    {
        $offre = $this->fetchOffre($idOffre);
        if (!$offre) {
            http_response_code(404);
            echo 'Offre introuvable';
            return;
        }

        // déjà postulé ?
        $uid  = (int) ($_SESSION['user']['id'] ?? 0);
        $deja = $this->aDejaPostule($uid, $idOffre);

        echo $this->twig->render('postuler.html.twig', [
            'offre' => $offre,
            'deja'  => $deja,
        ]);
    }

    // ============== SFx20 — Soumission ==============
    public function submit(int $idOffre): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        $idUser = (int) $_SESSION['user']['id'];

        $offre = $this->fetchOffre($idOffre);
        if (!$offre) {
            http_response_code(404);
            echo 'Offre introuvable';
            return;
        }

        // déjà postulé ?
        if ($this->aDejaPostule($idUser, $idOffre)) {
            $_SESSION['flash_err'] = "Vous avez déjà postulé à cette offre.";
            header('Location: index.php?page=mes-candidatures');
            exit;
        }

        $erreurs    = [];
        $lettreText = trim($_POST['lettre_texte'] ?? '');

        // ----- CV (obligatoire) -----
        $cvName = null;
        if (empty($_FILES['cv']['name'])) {
            $erreurs[] = "Le CV est obligatoire.";
        } else {
            $res = $this->uploadPdf($_FILES['cv'], self::DIR_CV);
            if ($res['err']) $erreurs[] = "CV : " . $res['err'];
            else             $cvName    = $res['name'];
        }

        // ----- Lettre (PDF OU texte, au moins l'un des deux) -----
        $lmName = null;
        if (!empty($_FILES['lettre']['name'])) {
            $res = $this->uploadPdf($_FILES['lettre'], self::DIR_LM);
            if ($res['err']) $erreurs[] = "Lettre : " . $res['err'];
            else             $lmName    = $res['name'];
        }

        if (!$lmName && $lettreText === '') {
            $erreurs[] = "Veuillez fournir une lettre de motivation (PDF ou texte).";
        }

        // ----- En cas d'erreur, ré-afficher le form -----
        if ($erreurs) {
            // si on a déjà uploadé le CV mais qu'il y a une autre erreur, on le supprime
            if ($cvName) @unlink(self::DIR_CV . $cvName);
            if ($lmName) @unlink(self::DIR_LM . $lmName);

            echo $this->twig->render('postuler.html.twig', [
                'offre'   => $offre,
                'erreurs' => $erreurs,
                'old'     => [
                    'lettre_texte' => $lettreText,
                ],
            ]);
            return;
        }

        // ----- INSERT -----
        $sql = "
            INSERT INTO candidature (cv, lm, lm_texte, id_user, id_offre)
            VALUES (:cv, :lm, :lm_texte, :id_user, :id_offre)
        ";
        $this->pdo->prepare($sql)->execute([
            'cv'       => $cvName,
            'lm'       => $lmName,
            'lm_texte' => $lettreText !== '' ? $lettreText : null,
            'id_user'  => $idUser,
            'id_offre' => $idOffre,
        ]);

        $_SESSION['flash_ok'] = "Candidature envoyée avec succès !";
        header('Location: index.php?page=mes-candidatures');
        exit;
    }

    // ============== SFx21 / 22 — Détail d'une candidature ==============
    public function detail(int $idCandidature): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }
        $user = $_SESSION['user'];

        $sql = "
            SELECT c.*,
                   o.titre, o.duree_stage, o.competence, o.remuneration,
                   e.nom_entreprise, e.ville,
                   u.id_user AS etudiant_id, u.nom AS etudiant_nom,
                   u.prenom AS etudiant_prenom, u.email AS etudiant_email,
                   u.id_pilote
            FROM candidature c
            JOIN offre o ON o.id_offre = c.id_offre
            LEFT JOIN entreprise e ON e.id_entreprise = o.id_entreprise
            JOIN utilisateur u ON u.id_user = c.id_user
            WHERE c.id_candidature = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $idCandidature]);
        $c = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$c) {
            http_response_code(404);
            echo "Candidature introuvable";
            return;
        }

        // Contrôle d'accès :
        //  - l'étudiant propriétaire
        //  - le pilote (recruteur) qui encadre cet étudiant
        //  - l'admin
        $role  = $user['role'] ?? '';
        $uid   = (int) ($user['id'] ?? 0);
        $autorise =
            ($role === 'admin')
            || ($role === 'etudiant'  && $uid === (int) $c['etudiant_id'])
            || ($role === 'recruteur' && $uid === (int) $c['id_pilote']);

        if (!$autorise) {
            http_response_code(403);
            echo "⛔ Accès interdit à cette candidature.";
            return;
        }

        echo $this->twig->render('candidature-detail.html.twig', [
            'c' => $c,
        ]);
    }

    // ============== Helpers ==============
    private function fetchOffre(int $idOffre): ?array
    {
        $sql = '
            SELECT o.*, e.nom_entreprise, e.ville
            FROM offre o
            LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            WHERE o.id_offre = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $idOffre]);
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    private function aDejaPostule(int $idUser, int $idOffre): bool
    {
        if ($idUser <= 0) return false;
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM candidature WHERE id_user = :u AND id_offre = :o
        ");
        $stmt->execute(['u' => $idUser, 'o' => $idOffre]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Upload sécurisé d'un PDF. Retourne ['name'=>filename|null, 'err'=>string|null]
     */
    private function uploadPdf(array $file, string $destDir): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['name' => null, 'err' => "erreur d'upload (code {$file['error']})."];
        }
        if ($file['size'] > self::MAX_SIZE) {
            return ['name' => null, 'err' => "fichier trop volumineux (max 5 Mo)."];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return ['name' => null, 'err' => "format non supporté, le PDF est requis."];
        }

        // contrôle MIME (si possible)
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if ($mime !== 'application/pdf') {
                return ['name' => null, 'err' => "le fichier n'est pas un PDF valide."];
            }
        }

        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $safe = bin2hex(random_bytes(8)) . '_' . time() . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], $destDir . $safe)) {
            return ['name' => null, 'err' => "impossible d'enregistrer le fichier."];
        }
        return ['name' => $safe, 'err' => null];
    }
}
