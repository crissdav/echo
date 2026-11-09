<?php $gid = 'eg' . bin2hex(random_bytes(3)); ?>
<svg class="logo-mark" viewBox="0 0 48 48" aria-hidden="true">
  <defs>
    <linearGradient id="<?= $gid ?>" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#22D3EE"/><stop offset=".5" stop-color="#6366F1"/><stop offset="1" stop-color="#A855F7"/>
    </linearGradient>
  </defs>
  <g fill="none" stroke="url(#<?= $gid ?>)" stroke-linecap="round">
    <rect x="3" y="3" width="42" height="42" rx="12.5" stroke-width="4"/>
    <circle cx="23.2" cy="25.4" r="14.2" stroke-width="2.6" stroke-dasharray="70 19" transform="rotate(-40 23.2 25.4)"/>
    <circle cx="24.4" cy="24.6" r="10.4" stroke-width="2.6" stroke-dasharray="52 13.5" transform="rotate(130 24.4 24.6)"/>
    <circle cx="23.6" cy="25.2" r="6.6" stroke-width="2.6" stroke-dasharray="34 7.5" transform="rotate(-10 23.6 25.2)"/>
    <circle cx="24" cy="25" r="2.9" stroke-width="2.4"/>
  </g>
  <circle cx="36.4" cy="11.6" r="2.7" fill="url(#<?= $gid ?>)"/>
</svg>
