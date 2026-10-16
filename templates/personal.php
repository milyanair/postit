<?php /** @var array $_ */ /** @var \OCP\IL10N $l */ $p = $_['prefs']; ?>
<div id="postit-personal" class="section">
	<h2><?php p($l->t('Post It')); ?></h2>
	<?php if (!$_['allowed']): ?>
		<p class="settings-hint"><?php p($l->t('Post It is not enabled for your account.')); ?></p>
	<?php else: ?>
	<p class="settings-hint"><?php p($l->t('Defaults for new notes. Existing notes keep their own size and color.')); ?></p>
	<p><label for="postit-w"><?php p($l->t('Default size')); ?></label>
		<input type="number" id="postit-w" min="160" max="1600" step="10" value="<?php p($p['w']); ?>" style="width:90px"> ×
		<input type="number" id="postit-h" min="120" max="1200" step="10" value="<?php p($p['h']); ?>" style="width:90px"> <?php p($l->t('px')); ?></p>
	<p><label><?php p($l->t('Default color')); ?></label></p>
	<p id="postit-colors">
		<?php foreach ($p['palette'] as $c): ?>
			<button type="button" class="postit-swatch<?php if ($c === $p['color']) p(' postit-on'); ?>" data-color="<?php p($c); ?>" style="background:<?php p($c); ?>" aria-label="<?php p($c); ?>"></button>
		<?php endforeach; ?>
	</p>
	<p><label for="postit-font"><?php p($l->t('Default font size')); ?></label>
		<input type="number" id="postit-font" min="10" max="32" value="<?php p($p['fontSize']); ?>" style="width:70px"> <?php p($l->t('px')); ?></p>
	<p><input type="checkbox" class="checkbox" id="postit-pinned" <?php if ($p['pinned']) print_unescaped('checked'); ?>>
		<label for="postit-pinned"><?php p($l->t('New notes start pinned')); ?></label></p>
	<p><button type="button" class="button" id="postit-reset-launcher"><?php p($l->t('Reset launcher position')); ?></button>
		<span class="settings-hint"><?php p($l->t('Tip: hold the right mouse button (or long-press) on the launcher to unlock it, then drag it.')); ?></span></p>
	<span id="postit-personal-msg" class="msg"></span>
	<?php endif; ?>
</div>
