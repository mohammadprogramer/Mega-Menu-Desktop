<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ramka_mega_cache_version' );
// Category metadata is retained to avoid destroying editorial content on accidental removal.
// Cached trees expire automatically within one day.
