<?php
script('intravox', 'intravox-main');
style('intravox', 'main');

// Add Open Graph meta tags if page data is available
if (isset($_['pageData']) && $_['pageData']) {
    $pageTitle = htmlspecialchars($_['pageData']['title']);
    $pageUrl = \OC::$server->getURLGenerator()->linkToRouteAbsolute('intravox.page.showByUniqueId', ['uniqueId' => $_['uniqueId']]);
    ?>
    <meta property="og:title" content="<?php p($pageTitle); ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="<?php p($pageUrl); ?>" />
    <meta property="og:site_name" content="IntraVox" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="<?php p($pageTitle); ?>" />
    <title><?php p($pageTitle); ?> - IntraVox</title>
    <?php
}
?>

<div id="app-intravox"
     data-unique-id="<?php p($_['uniqueId'] ?? ''); ?>"
     data-is-public="<?php p(($_['isPublicShare'] ?? false) ? 'true' : 'false'); ?>"
     data-token="<?php p($_['shareToken'] ?? ''); ?>"></div>
