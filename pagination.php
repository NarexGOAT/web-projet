<?php
class Pagination {
    private $items;
    private $perPage;
    private $totalItems;
    private $totalPages;
    private $currentPage;

    public function __construct(array $items, int $perPage = 10) {
        $this->items = $items;
        $this->perPage = $perPage;
        $this->totalItems = count($items);
        $this->totalPages = (int) ceil($this->totalItems / $this->perPage); // Calcule le nombre total de pages
        $this->setPageActuelle();
    }

    private function setPageActuelle() {
        if (isset($_GET['page'])) {
            $page = $_GET['page']; // Récupère le numéro de page dans l'URL
        } else {
            $page = 1;
        }

        // Si le nb est négatif ou 0, on passe sur la page 1
        if ($page < 1) {
            $page = 1;
        }
        // Si la page demandée est supérieure au nb total, on va à la dernière page[cite: 3]
        elseif ($page > $this->totalPages && $this->totalPages > 0) {
            $page = $this->totalPages;
        }

        $this->currentPage = $page;
    }

    public function itemsPage() {
        $debut = ($this->currentPage - 1) * $this->perPage;
        // array_slice extrait une portion du tableau sans modifier l'original[cite: 3]
        return array_slice($this->items, $debut, $this->perPage); 
    }

    public function getCurrentPage() {
        return $this->currentPage;
    }

    public function getTotalPages() {
        return $this->totalPages;
    }
}
?>