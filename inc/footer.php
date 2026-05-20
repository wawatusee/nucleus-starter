<?php
/*
 * Footer — données de contact chargées depuis json/articles/contact-coordonnees.json
 * Le menu principal est ré-instancié sans page active (currentPage = '')
 * Le RS_menu vient de $menuRS, disponible depuis config.php
 */

// Coordonnées de contact
$contactJson = file_get_contents(DIR_JSON . 'articles/contact-coordonnees.json');
$contactData = json_decode($contactJson);
$contactBlocs = $contactData->content ?? [];

// Menu footer — pas de lien actif
$menusViewFooter  = new ViewMenu(APP_LANG, '');
$menuFooter_view  = $menusViewFooter->getViewMainMenu($menuMain);
?>

<footer class="site-footer">

  <div class="site-footer__grid">

    <!-- Bloc contact -->
    <nav class="site-footer__bloc" aria-label="Coordonnées">
      <?php foreach ($contactBlocs as $bloc) : ?>

        <?php if ($bloc->type === 'title') : ?>
          <h2 class="site-footer__title">
            <?= htmlspecialchars($bloc->data->{APP_LANG} ?? $bloc->data->fr) ?>
          </h2>

        <?php elseif ($bloc->type === 'text') : ?>
          <p class="site-footer__text">
            <?= htmlspecialchars($bloc->data->{APP_LANG} ?? $bloc->data->fr) ?>
          </p>

        <?php elseif ($bloc->type === 'link') : ?>
          <a class="site-footer__link"
             href="<?= htmlspecialchars($bloc->url) ?>">
            <?= htmlspecialchars($bloc->data->{APP_LANG} ?? $bloc->data->fr) ?>
          </a>

        <?php endif; ?>

      <?php endforeach; ?>
    </nav>

    <!-- Bloc menu -->
    <nav class="site-footer__bloc" aria-label="Menu">
      <h2 class="site-footer__title">Menu</h2>
      <?= $menuFooter_view ?>
    </nav>

  </div>

  <!-- Réseaux sociaux -->
  <!--<nav class="site-footer__rs" aria-label="Réseaux sociaux">
    <?php foreach ($menuRS as $item) :
      $href  = htmlspecialchars($item->page);
      $label = htmlspecialchars($item->titre);
    ?>
      <a class="rs-link" href="<?= $href ?>"
         title="<?= $label ?>"
         target="_blank"
         rel="noopener noreferrer">
        <div class="rs-icon rs-icon--<?= $label ?>" aria-hidden="true"></div>
        <span class="sr-only"><?= $label ?></span>
      </a>
    <?php endforeach; ?>
  </nav>-->
  <nav class="site-footer__rs" aria-label="Réseaux sociaux">
    <?php foreach ($menuRS as $item) :
        $href  = htmlspecialchars($item->page);
        $label = htmlspecialchars($item->titre);
        $svgPath = DIR_IMG_DECO . 'rs/' . $label . '.svg';
    ?>
        <a class="rs-link rs-link--<?= $label ?>" href="<?= $href ?>"
           title="<?= $label ?>"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="<?= $label ?>">
            <?php if (file_exists($svgPath)) : ?>
                <?php echo file_get_contents($svgPath); ?>
            <?php else : ?>
                <span class="sr-only"><?= $label ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>

  <!-- Logo -->
<div class="site-footer__logo">
    <div class="decor-logo">
        <?php echo file_get_contents(DIR_IMG_DECO . 'logo.svg'); ?>
    </div>
</div>

</footer>