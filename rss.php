<?php
/**
 * rss.php — RSS 2.0 лента сайта
 */
header('Content-Type: application/rss+xml; charset=UTF-8');

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/all-articles-meta.php';
$siteUrl  = SITE_CANONICAL;
$siteName = 'ДомЭксперт';
$siteDesc = 'Практические советы по ремонту и обустройству дома';
$buildDate = date(DATE_RSS);

// Лента строится из общего реестра includes/all-articles-meta.php:
// свежие статьи сверху, по 50 последних — стандартный размер ленты.
$articles = domexpert_all_articles_expanded();
uasort($articles, static function (array $a, array $b): int {
  return domexpert_article_date_ts($b['date']) <=> domexpert_article_date_ts($a['date']);
});
$articles = array_slice($articles, 0, 50);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <title><?= htmlspecialchars($siteName, ENT_XML1, 'UTF-8') ?></title>
    <link><?= $siteUrl ?>/</link>
    <description><?= htmlspecialchars($siteDesc, ENT_XML1, 'UTF-8') ?></description>
    <language>ru</language>
    <lastBuildDate><?= $buildDate ?></lastBuildDate>
    <generator>ДомЭксперт CMS</generator>
    <image>
      <url><?= $siteUrl ?>/assets/img/og-default.jpg</url>
      <title><?= htmlspecialchars($siteName, ENT_XML1, 'UTF-8') ?></title>
      <link><?= $siteUrl ?>/</link>
    </image>
    <atom:link href="<?= $siteUrl ?>/rss.php" rel="self" type="application/rss+xml"/>

    <?php foreach ($articles as $a): ?>
    <item>
      <title><?= htmlspecialchars($a['title'], ENT_XML1, 'UTF-8') ?></title>
      <link><?= $siteUrl ?>/article/<?= urlencode($a['slug']) ?>/</link>
      <guid isPermaLink="true"><?= $siteUrl ?>/article/<?= urlencode($a['slug']) ?>/</guid>
      <description><?= htmlspecialchars($a['desc'], ENT_XML1, 'UTF-8') ?></description>
      <pubDate><?= domexpert_article_pubdate($a['date']) ?></pubDate>
      <category><?= htmlspecialchars($a['catLabel'], ENT_XML1, 'UTF-8') ?></category>
    </item>
    <?php endforeach; ?>

  </channel>
</rss>
