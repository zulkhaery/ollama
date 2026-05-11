<?php
/**
 * Partial: satu bubble chat
 * @var array $msg  Chat message array
 */
?>
<div class="bubble <?= $msg['role'] === 'user' ? 'user' : 'ai' ?>">

    <?php if (($msg['type'] ?? '') === 'db'): ?>

        <pre><code class="language-sql"><?= htmlspecialchars($msg['sql'] ?? '') ?></code></pre>

        <?php if (!empty($msg['data'])): ?>
            <?php if (count($msg['data'][0]) > 1): ?>

                <table border="0" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin-top:10px;">
                    <tr>
                        <?php foreach (array_keys($msg['data'][0]) as $col): ?>
                            <th><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <?php foreach ($msg['data'] as $row): ?>
                        <tr>
                            <?php foreach ($row as $val): ?>
                                <td><?= htmlspecialchars($val ?? '-') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>

            <?php else: ?>

                <?php foreach ($msg['data'] as $row): ?>
                    <?php foreach ($row as $val): ?>
                        <?= htmlspecialchars($val ?? '-') ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>

            <?php endif; ?>
        <?php endif; ?>

    <?php elseif(($msg['type'] ?? '') !== 'debug'): ?>

        <div class="md-content"><?= htmlspecialchars($msg['content'] ?? '') ?></div>

    <?php endif; ?>

</div>
