<?php
/** Add custom helper functions below */

function dump($data): void
{
    if($data) {
        $bt   = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = file($bt[0]['file']);
        $line = $file[$bt[0]['line'] - 1];

        preg_match('/dx\((.+)\)/', $line, $match);
        $label = $match[1] ?? 'data';

        echo '<style>
            .ddx {font-family: monospace; font-size:11px; line-height:1.4}
            .ddx details {margin-left:15px}
            .ddx summary {cursor:pointer; color:#22c55e}
            .ddx .key {color:#60a5fa}
            .ddx .value {color:#e5e7eb}
        </style>';

        echo '<div class="ddx">';
        echo "<b>$label</b>\n";
        renderDump($data);
        echo '</div>';
    }
}


function renderDump($data): void
{
    if (is_array($data)) {
        $keys    = array_keys($data);
        $lastKey = end($keys);

        foreach ($data as $key => $val) {
            $open = ($key === $lastKey) ? ' open' : '';
            echo '<details' . $open . '>';
            echo '<summary><span class="key">' . htmlspecialchars((string)$key) . '</span></summary>';
            renderDump($val);
            echo '</details>';
        }
    } else {
        echo '<div class="value">' . htmlspecialchars((string)$data) . '</div>';
    }
}