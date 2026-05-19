<header class="site-header" style="display:flex;flex-direction:column;">
  <div>
<div class="header-title">
    <!--<?= htmlspecialchars($str_titleWebSite) ?>-->
    <a href="?page=<?= $pagesDuMenus[0] ?>" class="site-header__logo" aria-label="Accueil">
        <div class="decor-logo">
            <?php echo file_get_contents(DIR_IMG_DECO . 'logo.svg'); ?>
        </div>
    </a>
</div>


  <div style="display:flex; flex-direction: row;">

    <div class="site-header__controls">
      <nav class="site-nav" id="siteNav" aria-label="Menu principal">
        <?php
        require_once ROOT_PATH . 'src/view/view_menus.php';
        $menusView = new ViewMenu(APP_LANG, $page);
        $menuMain_view = $menusView->getViewMainMenu($menuMain);
        echo $menuMain_view;
        ?>
      </nav>
      <div class="lang-switcher" aria-label="Sélection de la langue">
        <?php foreach ($langs as $langue):
          $params = array_merge($_GET, ['lang' => $langue['code']]);
          $url = '?' . http_build_query($params);
          $active = APP_LANG === $langue['code'] ? ' class="lang--active"' : '';
          ?>
          <a href="<?= $url ?>" <?= $active ?>><?= strtoupper($langue['code']) ?></a>
        <?php endforeach; ?>
      </div>

      <button class="burger" id="burgerBtn" aria-controls="siteNav" aria-expanded="false" aria-label="Ouvrir le menu">
        <span class="burger__bar"></span>
        <span class="burger__bar"></span>
        <span class="burger__bar"></span>
      </button>

    </div>
  </div>
</header>