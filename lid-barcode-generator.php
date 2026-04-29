<?php
/**
 * Plugin Name: LID Barcode Generator
 * Description: Generates printable barcode reference sheets for event session tracking. Reads session data from Pods and produces a one-click printable sheet for hostesses.
 * Version: 2.5.0
 * Author: Dagora
 * Requires PHP: 8.2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LID_BARCODE_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Check that composer dependencies are installed.
 */
function lid_barcode_check_autoloader(): bool
{
    if (file_exists(LID_BARCODE_PLUGIN_DIR . 'vendor/autoload.php')) {
        return true;
    }

    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>LID Barcode Generator:</strong> Dependencies not installed. ';
        echo 'Run <code>composer install</code> in the <code>lid-barcode-generator</code> plugin directory.';
        echo '</p></div>';
    });

    return false;
}

/**
 * Check that Pods is available.
 */
function lid_barcode_check_pods(): bool
{
    if (post_type_exists('session')) {
        return true;
    }

    add_action('admin_notices', function () {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>LID Barcode Generator:</strong> The "session" post type was not found. ';
        echo 'Make sure Pods is active and the Session pod is configured.';
        echo '</p></div>';
    });

    return false;
}

add_action('init', function () {
    if (!lid_barcode_check_autoloader()) {
        return;
    }

    require_once LID_BARCODE_PLUGIN_DIR . 'vendor/autoload.php';
    require_once LID_BARCODE_PLUGIN_DIR . 'includes/class-barcode-sheet.php';

    add_action('admin_menu', 'lid_barcode_register_menu');
    add_action('admin_post_lid_generate_sheet', 'lid_barcode_handle_generate');
}, 20); // Priority 20: run after Pods registers its post types

/**
 * Register the admin menu page under Tools.
 */
function lid_barcode_register_menu(): void
{
    add_management_page(
        'Barcode Sheet',
        'Barcode Sheet',
        'manage_options',
        'lid-barcode-sheet',
        'lid_barcode_admin_page'
    );
}

/**
 * Render the admin page with session summary and generate button.
 */
function lid_barcode_admin_page(): void
{
    if (!lid_barcode_check_pods()) {
        return;
    }

    $sheet = new LID_Barcode_Sheet();
    $sessions = $sheet->get_sessions();
    $total = array_sum(array_map('count', $sessions));
    $year = date('Y');

    ?>
    <div class="wrap">
        <h1>Barcode Sheet Generator</h1>
        <p>Generate a printable barcode reference sheet for event hostesses.</p>

        <?php if ($total === 0): ?>
            <div class="notice notice-warning">
                <p>No sessions found for <strong><?= esc_html($year) ?></strong>.
                Make sure sessions are published with a date in the current year.</p>
            </div>
        <?php else: ?>
            <div class="notice notice-info">
                <p>Found <strong><?= esc_html($total) ?></strong> session(s) across
                <strong><?= esc_html(count($sessions)) ?></strong> room(s) for
                <strong><?= esc_html($year) ?></strong>.</p>
            </div>

            <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>" target="_blank">
                <?php wp_nonce_field('lid_generate_sheet'); ?>
                <input type="hidden" name="action" value="lid_generate_sheet">
                <p>
                    <button type="submit" class="button button-primary button-hero">
                        Generate Barcode Sheet
                    </button>
                </p>
                <p class="description">Opens in a new tab. Print directly from there.</p>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Handle the sheet generation request.
 */
function lid_barcode_handle_generate(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized', 403);
    }

    check_admin_referer('lid_generate_sheet');

    if (!post_type_exists('session')) {
        wp_die('Session post type not found. Is Pods active?');
    }

    $sheet = new LID_Barcode_Sheet();
    $sheet->render_sheet();
    exit;
}
