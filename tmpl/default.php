<?php defined('_JEXEC') or die;

/**
 * AIMod AI Content-Generator
 *
 * @package AIMod
 * @author Elias Ritter
 * @license GNU General Public license 2.0 or later
 *
 * @var object $return
 * @var object $module
 * @var object $params
 */

$content = $return->response ?? '';

if(!$params->get('allowhtml')) {
    $content = htmlentities($content);
}
if($params->get('keepbr')) {
    $content = nl2br($content);
}
?>

<div class="mod_aimodule aimodule-<?= $module->id ?>">
    <div class="aimodule__content">
        <?php if($content !== strip_tags($content)) : ?>
            <?= $content ?>
        <?php else : ?>
            <p><?= $content ?></p>
        <?php endif; ?>
    </div>
    <?php if($params->get('showdate')) : ?>
    <div class="aimodule__date">
        <small><?= date("Y/m/d", $return->written); ?></small>
    </div>
    <?php endif; ?>
</div>