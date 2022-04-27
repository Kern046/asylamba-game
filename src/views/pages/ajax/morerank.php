<?php

use App\Modules\Atlas\Model\PlayerRanking;

$container = $this->getContainer();
$appRoot = $container->getParameter('app_root');
$mediaPath = $container->getParameter('media');
$request = $this->getContainer()->get('app.request');
$session = $this->getContainer()->get(\App\Classes\Library\Session\SessionWrapper::class);
$playerRankingManager = $this->getContainer()->get(\App\Modules\Atlas\Manager\PlayerRankingManager::class);

$direction = $request->query->get('dir');
$current = $request->query->get('current');
$type = $request->query->get('type');

if (false !== $direction && false !== $current && false !== $type) {
    if (in_array($direction, ['next', 'prev'])) {
        if (in_array($type, ['general', 'resources', 'xp', 'fight', 'armies', 'butcher', 'trader'])) {
            // var
            $fty = ('xp' == $type)
                ? 'experience'
                : $type;

            $bot = ('next' == $direction)
                ? (($current - PlayerRanking::PAGE > 1) ? $current - PlayerRanking::PAGE : 1)
                : $current + 1;

            $size = (1 == $bot)
                ? $current - 1
                : PlayerRanking::PAGE;

            $S_PRM1 = $playerRankingManager->getCurrentSession();
            $playerRankingManager->newSession();
            $playerRankingManager->loadLastContext([], [$fty.'Position', 'ASC'], [$bot - 1, $size]);

            if ('next' == $direction && $bot > 1) {
                echo '<a class="more-item" href="'.$appRoot.'ajax/a-morerank/dir-next/type-'.$type.'/current-'.$bot.'" data-dir="top">';
                echo 'afficher les joueurs précédents';
                echo '</a>';
            }

            for ($i = 0; $i < $playerRankingManager->size(); ++$i) {
                echo $playerRankingManager->get($i)->commonRender($session->get('playerId'), $type, $appRoot, $mediaPath);
            }

            if ('prev' == $direction && PlayerRanking::PAGE == $playerRankingManager->size()) {
                echo '<a class="more-item" href="'.$appRoot.'ajax/a-morerank/dir-prev/type-'.$type.'/current-'.($current + PlayerRanking::PAGE).'">';
                echo 'afficher les joueurs suivants';
                echo '</a>';
            }

            $playerRankingManager->changeSession($S_PRM1);
        }
    }
}
