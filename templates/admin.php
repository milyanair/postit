<?php /** @var array $_ */ /** @var \OCP\IL10N $l */ ?>
<div id="postit-admin" class="section">
	<h2><?php p($l->t('Post It')); ?></h2>
	<p class="settings-hint"><?php p($l->t('Leave all groups unchecked to enable Post It for every user. Check groups to limit it to their members.')); ?></p>
	<?php foreach ($_['groups'] as $g): ?>
		<p><input type="checkbox" class="checkbox postit-group" id="postit-g-<?php p(md5($g['id'])); ?>" value="<?php p($g['id']); ?>" <?php if (in_array($g['id'], $_['selected'], true)) print_unescaped('checked'); ?>>
		<label for="postit-g-<?php p(md5($g['id'])); ?>"><?php p($g['name']); ?></label></p>
	<?php endforeach; ?>
	<button id="postit-save-groups" class="button primary"><?php p($l->t('Save')); ?></button>
	<span id="postit-admin-msg" class="msg"></span>
</div>
