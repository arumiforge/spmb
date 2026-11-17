<?php
/**
 * Flowbite pagination (Config\Pager 'default_full'). Query string filters are kept.
 *
 * @var CodeIgniter\Pager\PagerRenderer $pager
 */
$pager->setSurroundCount(1);
$link = 'flex h-10 min-w-10 items-center justify-center border border-gray-300 bg-white px-3 leading-tight text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white';
?>
<nav aria-label="Navigasi halaman">
    <ul class="inline-flex -space-x-px text-sm">
        <?php if ($pager->hasPrevious()) : ?>
            <li><a href="<?= $pager->getFirst() ?>" class="<?= $link ?> rounded-s-lg">Awal</a></li>
            <li><a href="<?= $pager->getPrevious() ?>" class="<?= $link ?>" aria-label="Sebelumnya">‹</a></li>
        <?php endif ?>
        <?php $links = $pager->links(); $lastIndex = count($links) - 1; ?>
        <?php foreach ($links as $i => $link_) : ?>
            <li>
                <a href="<?= $link_['uri'] ?>" <?= $link_['active'] ? 'aria-current="page"' : '' ?>
                   class="<?= $link_['active'] ? 'z-10 flex h-10 min-w-10 items-center justify-center border border-primary-300 bg-primary-50 px-3 text-primary-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white' : $link ?> <?= ! $pager->hasPrevious() && $i === 0 ? 'rounded-s-lg' : '' ?> <?= ! $pager->hasNext() && $i === $lastIndex ? 'rounded-e-lg' : '' ?>">
                    <?= $link_['title'] ?>
                </a>
            </li>
        <?php endforeach ?>
        <?php if ($pager->hasNext()) : ?>
            <li><a href="<?= $pager->getNext() ?>" class="<?= $link ?>" aria-label="Berikutnya">›</a></li>
            <li><a href="<?= $pager->getLast() ?>" class="<?= $link ?> rounded-e-lg">Akhir</a></li>
        <?php endif ?>
    </ul>
</nav>
