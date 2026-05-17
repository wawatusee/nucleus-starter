<?php
/**
 * ViewMenu
 * Produit le HTML d'un menu depuis un tableau d'objets issu de MenusModel.
 * Les items sont attendus avec les propriétés : page, titre (objet multilingue)
 */
class ViewMenu
{
    private string $lang;
    private string $currentPage;

    public function __construct(string $lang, string $currentPage)
    {
        $this->lang = $lang;
        $this->currentPage = $currentPage;
    }

    /**
     * @param array $menuArray  Tableau d'objets issu de MenusModel::getMenu()
     * @return string HTML du menu
     */
    public function getViewMainMenu(array $menuArray): string
    {
        $html = '';

        foreach ($menuArray as $item) {
            $label = $item->titre->{$this->lang} ?? $item->titre->fr ?? $item->page;
            $active = ($item->page === $this->currentPage) ? ' nav__link--active' : '';
            $href = '?page=' . $item->page . '&lang=' . $this->lang;

            $html .= '<a class="nav__link' . $active . '" href="' . $href . '">'
                . htmlspecialchars($label)
                . '</a>';
        }

        return $html;
    }
}