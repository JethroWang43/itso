<header class="text-center">
    <h1>Welcome to My Website</h1>
</header>
<main>
    <section class="container py-4">
        <h2 class="mb-3">Good day, <?= esc($name ?? 'Guest') ?></h2>

        <?php
        // Robust fallbacks: if controller didn't provide counts, try session demo data.
        $session = \Config\Services::session();

        if (! isset($equipment_total)) {
            $items = $session->get('equipment_items') ?? $session->get('tbequipment') ?? [];
            if (is_array($items)) {
                $equipment_total = count($items);
                $equipment_available = 0; $equipment_borrowed = 0; $reserved = 0;
                foreach ($items as $it) {
                    $status = '';
                    if (is_array($it)) {
                        $status = strtolower($it['status'] ?? $it['state'] ?? '');
                    } else {
                        $status = strtolower((string)$it);
                    }
                    if (strpos($status, 'avail') !== false) $equipment_available++;
                    elseif (strpos($status, 'borrow') !== false) $equipment_borrowed++;
                    elseif (strpos($status, 'reserv') !== false) $reserved++;
                }
            } else {
                $equipment_total = 0; $equipment_available = 0; $equipment_borrowed = 0; $reserved = 0;
            }
        }

        if (! isset($pending_returns)) {
            $borrows = $session->get('borrows') ?? $session->get('borrowing_items') ?? [];
            $pending_returns = 0;
            if (is_array($borrows)) {
                foreach ($borrows as $b) {
                    if (empty($b['returned']) && empty($b['returned_at'])) $pending_returns++;
                }
            }
        }

        if (! isset($chart_data)) {
            $chart_data = [
                'labels' => ['Available','Borrowed','Reserved'],
                'values' => [($equipment_available ?? 0), ($equipment_borrowed ?? 0), ($reserved ?? 0)]
            ];
        }

        // Normalize and sort recent activity (controller-provided or session fallback)
        $recent_activity = $recent_activity ?? $session->get('recent_activity') ?? [];
        if (! is_array($recent_activity)) $recent_activity = [];
        $now = time();
        foreach ($recent_activity as &$act) {
            $act['title'] = $act['title'] ?? ($act['action'] ?? 'Activity');
            $act['subtitle'] = $act['subtitle'] ?? ($act['details'] ?? '');

            // Try to compute a unix timestamp for the activity from many possible fields
            $ts = 0;
            if (! empty($act['ts'])) {
                $ts = intval($act['ts']);
            } elseif (! empty($act['timestamp'])) {
                $ts = strtotime($act['timestamp']) ?: 0;
            } elseif (! empty($act['time'])) {
                $ts = strtotime($act['time']) ?: 0;
            } elseif (! empty($act['created_at'])) {
                $ts = strtotime($act['created_at']) ?: 0;
            } elseif (! empty($act['date'])) {
                $ts = strtotime($act['date']) ?: 0;
            }

            // If minutes/ago_minutes present, try to interpret intelligently:
            if (($ts === 0) && (isset($act['minutes']) || isset($act['ago_minutes']))) {
                $raw = isset($act['minutes']) ? $act['minutes'] : $act['ago_minutes'];
                // trim and remove non-digits
                $rawStr = is_string($raw) ? trim($raw) : (string) $raw;
                if ($rawStr !== '' && is_numeric($rawStr)) {
                    $num = intval($rawStr);
                    // Heuristics:
                    // - If number looks like a unix timestamp in seconds (>= 1e9), use it.
                    // - If number looks like milliseconds (> 1e11), convert to seconds.
                    // - Otherwise treat it as minutes-ago.
                    if ($num >= 1000000000) {
                        $ts = $num;
                    } elseif ($num >= 1000000000000) {
                        // milliseconds
                        $ts = intval($num / 1000);
                    } else {
                        // treat as minutes ago
                        $ts = $now - ($num * 60);
                    }
                }
            }

            $act['_ts'] = $ts;
            // expose a minutes field for display (compute from ts if missing)
            if (! isset($act['minutes']) || $act['minutes'] === '') {
                $act['minutes'] = $ts ? intval(($now - $ts) / 60) : 0;
            }
        }
        unset($act);

        // Sort by timestamp descending (newest first). Items without ts go to the end.
        usort($recent_activity, function($a, $b) {
            $ta = isset($a['_ts']) ? intval($a['_ts']) : 0;
            $tb = isset($b['_ts']) ? intval($b['_ts']) : 0;
            // descending
            return $tb <=> $ta;
        });
        // Keep only the top 6 most recent activities
        $recent_activity = array_slice($recent_activity, 0, 6);

        $month_delta = $month_delta ?? 12;
        $overdue_percent = $overdue_percent ?? 0;
        // Prepare upcoming reservations / claim list (session fallback)
        $upcoming_reservations = $upcoming_reservations ?? [];
        if (empty($upcoming_reservations)) {
            $reservations = $session->get('reservations') ?? $session->get('borrows') ?? [];
            if (is_array($reservations) && count($reservations)) {
                $now = time();
                $tmp = [];
                foreach ($reservations as $r) {
                    // detect reserved_for timestamps or scheduled claim times
                    $when = null;
                    if (! empty($r['reserved_for'])) $when = strtotime($r['reserved_for']);
                    elseif (! empty($r['reserved_at'])) $when = strtotime($r['reserved_at']);
                    elseif (! empty($r['claim_time'])) $when = strtotime($r['claim_time']);
                    // Only future (or soon) reservations
                    if ($when && $when >= ($now - 60*60*24)) { // show recent + upcoming (24h past tolerance)
                        $r['_when_ts'] = $when;
                        $tmp[] = $r;
                    }
                }
                usort($tmp, function($a,$b){ return ($a['_when_ts'] ?? 0) <=> ($b['_when_ts'] ?? 0); });
                $upcoming_reservations = array_slice($tmp, 0, 8);
            }
        }
        ?>

        

        <div class="card mb-4 p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="d-flex gap-3">
                <a class="btn btn-primary" href="<?= base_url('equipment/add') ?>">+ Add Equipment</a>
                <a class="btn btn-success" href="<?= base_url('borrowing/returns') ?>">Process Return</a>
                <a class="btn btn-warning text-white" href="<?= base_url('borrowing') ?>">New Borrowing</a>
                <a class="btn btn-secondary" href="<?= base_url('reports') ?>">Generate Report</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="card p-3">
                    <h5>Reservations to Claim</h5>
                    <p class="small text-muted mb-2">List of reservations with the person who reserved and the scheduled claim time. Use the Claim button to convert a reservation to an active borrow.</p>
                    <div class="mt-2">
                        <?php if (! empty($upcoming_reservations)): ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($upcoming_reservations as $res): ?>
                                    <?php
                                        $who = $res['reserved_by_name'] ?? $res['user_name'] ?? $res['reserved_by'] ?? $res['user_id'] ?? ($res['borrower_name'] ?? null);
                                        // fallback to session users map
                                        if (empty($who)) {
                                            $usersMap = $session->get('users') ?? $session->get('users_meta') ?? $session->get('user_extras') ?? [];
                                            $uid = $res['user_id'] ?? $res['reserved_by'] ?? null;
                                            if ($uid && is_array($usersMap) && isset($usersMap[$uid])) {
                                                $who = $usersMap[$uid]['name'] ?? $usersMap[$uid]['full_name'] ?? $usersMap[$uid]['display_name'] ?? $usersMap[$uid]['username'] ?? $uid;
                                            }
                                        }
                                        $item = $res['equipment_name'] ?? $res['name'] ?? $res['equipment_id'] ?? ($res['item'] ?? 'Item');
                                        $ts = $res['_when_ts'] ?? (isset($res['reserved_for']) ? strtotime($res['reserved_for']) : null);
                                        $resId = $res['id'] ?? $res['ireservation'] ?? $res['reservation_id'] ?? null;
                                    ?>
                                    <li class="mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?= esc($item) ?></strong>
                                                <div class="text-muted small">Claim by: <?= esc($who ?? 'Unknown') ?> — <?= esc($ts ? date('M j, Y', $ts) : ($res['reserved_for'] ?? $res['reserved_at'] ?? 'N/A')) ?></div>
                                            </div>
                                            <div>
                                                <?php if ($resId): ?>
                                                    <a class="btn btn-sm btn-primary" href="<?= base_url('reservations/borrow/'.$resId) ?>">Claim</a>
                                                <?php else: ?>
                                                    <a class="btn btn-sm btn-outline-secondary" href="#">Details</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="small text-muted">No reservations to claim.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card p-3">
                    <h5>Recent Activity</h5>
                    <div class="list-group mt-3">
                        <?php if (! empty($recent_activity)): foreach ($recent_activity as $act): ?>
                            <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?= esc($act['title']) ?></strong>
                                        <div class="small text-muted"><?= esc($act['subtitle']) ?></div>
                                    </div>
                                    <div class="small text-muted"><?= esc(isset($act['_ts']) && $act['_ts'] ? date('M j, Y', $act['_ts']) : ($act['date'] ?? $act['timestamp'] ?? 'N/A')) ?></div>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="p-3 text-muted">No recent activity.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card mt-3 p-3">
                    <h6>Upcoming Reservations / Claims</h6>
                    <div class="mt-2">
                        <?php if (! empty($upcoming_reservations)): ?>
                            <ul class="mb-0">
                                <?php foreach ($upcoming_reservations as $res): ?>
                                    <?php
                                        $who = $res['reserved_by_name'] ?? $res['user_name'] ?? $res['reserved_by'] ?? $res['user_id'] ?? ($res['borrower_name'] ?? null);
                                        // fallback to session users map
                                        if (empty($who)) {
                                            $usersMap = $session->get('users') ?? $session->get('users_meta') ?? $session->get('user_extras') ?? [];
                                            $uid = $res['user_id'] ?? $res['reserved_by'] ?? null;
                                            if ($uid && is_array($usersMap) && isset($usersMap[$uid])) {
                                                $who = $usersMap[$uid]['name'] ?? $usersMap[$uid]['full_name'] ?? $usersMap[$uid]['display_name'] ?? $usersMap[$uid]['username'] ?? $uid;
                                            }
                                        }
                                        $item = $res['equipment_name'] ?? $res['name'] ?? $res['equipment_id'] ?? ($res['item'] ?? 'Item');
                                        $ts = $res['_when_ts'] ?? (isset($res['reserved_for']) ? strtotime($res['reserved_for']) : null);
                                    ?>
                                    <li class="small">
                                        <strong><?= esc($item) ?></strong>
                                        <div class="text-muted small">Claim by: <?= esc($who ?? 'Unknown') ?> — <?= esc($ts ? date('M j, Y', $ts) : ($res['reserved_for'] ?? $res['reserved_at'] ?? 'N/A')) ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="small text-muted">No reservations to claim.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

