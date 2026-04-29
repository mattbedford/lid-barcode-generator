<?php

class LID_Barcode_Sheet
{
    private const ENTRANCE_SLUG = 'entrance';

    private const VENUE_LABELS = [
        'red'     => 'Red Room',
        'blue'    => 'Blue Room',
        'purple'  => 'Purple Room',
        'green'   => 'Green Room',
        'plenary' => 'Plenary',
    ];

    private const VENUE_COLORS = [
        'red'     => '#c0392b',
        'blue'    => '#2980b9',
        'purple'  => '#8e44ad',
        'green'   => '#27ae60',
        'plenary' => '#34495e',
    ];

    private const ENTRANCE_WINDOWS = [
        ['start' => '07:00', 'end' => '12:00', 'label' => 'Morning arrivals'],
        ['start' => '12:01', 'end' => '14:00', 'label' => 'Afternoon arrivals'],
        ['start' => '14:01', 'end' => null,    'label' => 'Late arrivals'],
    ];

    /**
     * Query all published sessions for the current year, excluding breaks and title-only entries.
     * Returns sessions grouped by venue slug, sorted by room code then start time.
     *
     * @return array<string, array<int, array{title: string, start: string, end: ?string}>>
     */
    public function get_sessions(): array
    {
        $current_year = (int) date('Y');

        $query = new WP_Query([
            'post_type'      => 'session',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ]);

        $by_venue = [];

        foreach ($query->posts as $post) {
            if ($this->should_skip_session($post->ID, $current_year)) {
                continue;
            }

            $venue = get_post_meta($post->ID, 'venue', true);
            if (!$venue || !isset(self::VENUE_LABELS[$venue])) {
                continue;
            }

            $start = get_post_meta($post->ID, 'session_start', true);
            if (!$start) {
                continue;
            }

            $end = get_post_meta($post->ID, 'session_end', true);

            $by_venue[$venue][] = [
                'title' => $post->post_title,
                'start' => $this->normalize_time($start),
                'end'   => $end ? $this->normalize_time($end) : null,
            ];
        }

        foreach ($by_venue as &$sessions) {
            usort($sessions, fn($a, $b) => strcmp($a['start'], $b['start']));
        }
        unset($sessions);

        // Sort venues in a consistent display order
        $order = array_keys(self::VENUE_LABELS);
        uksort($by_venue, fn($a, $b) => array_search($a, $order) <=> array_search($b, $order));

        return $by_venue;
    }

    /**
     * Generate a CODE 128A barcode as an inline base64 PNG image tag.
     */
    public function generate_barcode(string $data): string
    {
        $barcode = new \Com\Tecnick\Barcode\Barcode();
        $bobj = $barcode->getBarcodeObj(
            'C128A',
            $data,
            -4,
            50,
            'black',
            [4, 10, 4, 10]
        )->setBackgroundColor('white');

        return '<img src="data:image/png;base64,'
            . base64_encode($bobj->getPngData())
            . '" alt="' . esc_attr($data) . '" />';
    }

    /**
     * Render the full printable barcode sheet as a standalone HTML page.
     */
    public function render_sheet(): void
    {
        $sessions = $this->get_sessions();
        $year = esc_html(date('Y'));
        $generated = esc_html(date('d/m/Y H:i'));

        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Sheet — LID <?= $year ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f0f1;
            color: #1d2327;
            padding: 20px;
            line-height: 1.4;
        }

        /* Controls bar */
        .controls {
            text-align: center;
            margin-bottom: 30px;
        }
        .controls button {
            background: #2271b1;
            color: #fff;
            border: none;
            padding: 12px 32px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 4px;
        }
        .controls button:hover {
            background: #135e96;
        }

        /* Sheet header */
        .sheet-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .sheet-header h1 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .sheet-header p {
            color: #646970;
            font-size: 13px;
        }

        /* Sections */
        .section {
            background: #fff;
            border-radius: 8px;
            padding: 28px 32px;
            margin-bottom: 24px;
            border-left: 5px solid var(--room-color, #50575e);
        }
        .section h2 {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--room-color, #50575e);
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        /* Entrance prominence */
        .entrance {
            border-left-width: 8px;
            --room-color: #d63638;
            padding: 36px 32px;
        }
        .entrance h2 {
            font-size: 28px;
            letter-spacing: 3px;
        }
        .entrance .time {
            font-size: 30px;
        }

        /* Barcode rows */
        .barcode-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 28px 0;
        }

        .time-col {
            flex: 1;
            min-width: 0;
        }
        .time {
            font-size: 26px;
            font-weight: 700;
            display: block;
            font-variant-numeric: tabular-nums;
        }
        .window-label {
            font-size: 13px;
            color: #787c82;
            display: block;
            margin-top: 2px;
        }
        .session-title {
            font-size: 13px;
            color: #787c82;
            display: block;
            margin-top: 4px;
            line-height: 1.35;
        }

        .barcode-col {
            flex-shrink: 0;
            text-align: right;
        }
        .barcode-col img {
            max-width: 300px;
            height: auto;
            display: block;
            margin-left: auto;
            padding: 6px;
            border: 1.5px solid #c0c4c8;
            border-radius: 4px;
            background: #fff;
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #fff;
                padding: 0;
                font-size: 12pt;
            }
            .sheet-header {
                margin-bottom: 20px;
            }
            .sheet-header h1 {
                font-size: 20pt;
            }
            .section {
                border-radius: 0;
                box-shadow: none;
                margin-bottom: 0;
                padding: 20px 24px;
            }
            .section.room {
                page-break-before: always;
            }
            .entrance {
                page-break-before: auto;
                page-break-after: always;
            }
            .barcode-row {
                page-break-inside: avoid;
                padding: 22px 0;
            }
            .barcode-col img {
                max-width: 260px;
            }
        }
    </style>
</head>
<body>

    <div class="controls no-print">
        <button onclick="window.print()">Print this sheet</button>
    </div>

    <div class="sheet-header">
        <h1>Lifestyle Innovation Day <?= $year ?></h1>
        <p>Barcode Reference Sheet &mdash; Generated <?= $generated ?></p>
    </div>

    <!-- ENTRANCE -->
    <section class="section entrance">
        <h2>Entrance</h2>
        <?php foreach (self::ENTRANCE_WINDOWS as $window): ?>
            <?php
            $barcode_data = self::ENTRANCE_SLUG . '-' . $window['start'];
            $time_display = $window['start'] . ($window['end'] ? ' &ndash; ' . $window['end'] : '+');
            ?>
            <div class="barcode-row">
                <div class="time-col">
                    <span class="time"><?= $time_display ?></span>
                    <span class="window-label"><?= esc_html($window['label']) ?></span>
                </div>
                <div class="barcode-col">
                    <?= $this->generate_barcode($barcode_data) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- ROOM SECTIONS -->
    <?php foreach ($sessions as $venue => $venue_sessions): ?>
        <section class="section room" style="--room-color: <?= self::VENUE_COLORS[$venue] ?>">
            <h2><?= esc_html(self::VENUE_LABELS[$venue]) ?></h2>
            <?php foreach ($venue_sessions as $session): ?>
                <?php $barcode_data = $venue . '-' . $session['start']; ?>
                <div class="barcode-row">
                    <div class="time-col">
                        <span class="time"><?= esc_html($session['start']) ?></span>
                        <span class="session-title"><?= esc_html($session['title']) ?></span>
                    </div>
                    <div class="barcode-col">
                        <?= $this->generate_barcode($barcode_data) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

    <?php if (empty($sessions)): ?>
        <section class="section">
            <h2>No room sessions found</h2>
            <p>No sessions with assigned rooms were found for <?= $year ?>.
            Check that sessions are published, have a date in the current year, and have a venue assigned.</p>
        </section>
    <?php endif; ?>

</body>
</html>
        <?php
    }

    /**
     * Determine whether a session post should be excluded from the sheet.
     */
    private function should_skip_session(int $post_id, int $current_year): bool
    {
        $date = get_post_meta($post_id, 'session_date', true);
        if (!$date) {
            return true;
        }

        $session_year = (int) date('Y', strtotime($date));
        if ($session_year !== $current_year) {
            return true;
        }

        if (get_post_meta($post_id, 'breaktime', true) === '1') {
            return true;
        }

        if (get_post_meta($post_id, 'just_a_title_section', true) === '1') {
            return true;
        }

        return false;
    }

    /**
     * Normalize a time value to HH:MM format.
     */
    private function normalize_time(string $time): string
    {
        $timestamp = strtotime($time);
        if ($timestamp === false) {
            return $time;
        }

        return date('H:i', $timestamp);
    }
}
