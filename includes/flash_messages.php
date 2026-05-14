<?php
declare(strict_types=1);

if (!empty($errors) && is_array($errors)): ?>
  <div class="flash-stack glass-card" style="padding:1rem;margin-bottom:1rem;border-radius:16px;">
    <?php foreach ($errors as $error): ?>
      <div class="toast error"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php if (!empty($msg)): ?>
  <div class="toast success"><?= htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($message)): ?>
  <div class="toast success"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="toast error"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
