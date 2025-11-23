<div class="py-4">
    <h2><?= esc($reportTitle ?? 'Equipment Reports') ?></h2>
    <?php
    // Ensure view variables have sensible defaults
    $reportType = $reportType ?? 'active_equipment';
    $reportData = $reportData ?? [];
    $equipment_items = $equipment_items ?? [];
    $q = $q ?? '';
    $page = $page ?? 1;
    $perPage = $perPage ?? 10;
    $totalFiltered = $totalFiltered ?? count($reportData);

    $report_options = [
        'active_equipment' => 'Active Equipment List',
        'unusable_equipment' => 'Unusable Equipment Report',
        'user_borrowing_history' => 'User Borrowing History',
    ];

    // Card stats are replaced with report summary
    $activeCount = 0;
    $unusableCount = 0;
    try {
        foreach ($equipment_items as $item) {
            $status = strtolower($item['status'] ?? 'available');
            if ($status !== 'unusable') {
                $activeCount++;
            } else {
                $unusableCount++;
            }
        }
    } catch (\Throwable $e) {
        // Safe exit if data is corrupted
    }
    $historyCount = count(session('borrow_history') ?? []) + count(session('borrows') ?? []);
    ?>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card p-3">
                <div class="h5">Active Equipment</div>
                <div class="display-6"><?= esc($activeCount) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <div class="h5">Unusable Equipment</div>
                <div class="display-6 text-danger"><?= esc($unusableCount) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <div class="h5">Total Borrow History</div>
                <div class="display-6"><?= esc($historyCount) ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="reportType" class="form-label">Select Report</label>
                        <select name="type" id="reportType" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($report_options as $key => $label): ?>
                                <option value="<?= esc($key) ?>" <?= $reportType === $key ? 'selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="searchQuery" class="form-label">Search Filter</label>
                        <input type="search" name="q" id="searchQuery" class="form-control"
                            placeholder="Search in current report..." value="<?= esc($q) ?>" />
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Apply Filter</button>
                    </div>
                </form>
            </div>

            <?php if (empty($reportData)): ?>
                <div class="p-4 text-center">
                    <p class="lead">No records found for the selected report and filters.</p>
                </div>
            <?php else: ?>
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <h5>Report Results</h5>
                    <div class="small text-muted">Showing <?= count($reportData) ?> of <?= $totalFiltered ?> total records
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <?php if ($reportType === 'active_equipment' || $reportType === 'unusable_equipment'): ?>
                                <tr>
                                    <th>Equipment ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                </tr>
                            <?php elseif ($reportType === 'user_borrowing_history'): ?>
                                <tr>
                                    <th>Ref</th>
                                    <th>Borrower</th>
                                    <th>Equipment</th>
                                    <th>Borrowed</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            <?php endif; ?>
                        </thead>
                        <tbody>
                            <?php if ($reportType === 'active_equipment' || $reportType === 'unusable_equipment'): ?>
                                <?php foreach ($reportData as $item): ?>
                                    <tr class="<?= strtolower($item['status'] ?? '') === 'unusable' ? 'table-danger' : '' ?>">
                                        <td><?= esc($item['equipment_id'] ?? $item['id']) ?></td>
                                        <td><strong><?= esc($item['name'] ?? '') ?></strong>
                                            <div class="text-muted small"><?= esc($item['description'] ?? '') ?></div>
                                        </td>
                                        <td><?= esc($item['category'] ?? '') ?></td>
                                        <td><?= esc($item['location'] ?? '') ?></td>
                                        <td>
                                            <span
                                                class="badge bg-<?= strtolower($item['status'] ?? '') === 'available' ? 'success' : (strtolower($item['status'] ?? '') === 'unusable' ? 'danger' : 'warning') ?>">
                                                <?= esc($item['status'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td><?= esc($item['date_added'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php elseif ($reportType === 'user_borrowing_history'): ?>
                                <?php foreach ($reportData as $record):
                                    $equip = $equipment_items[$record['equipment_id']] ?? [];
                                    $isReturned = !isset($record['due_date']); // Simplified check: if it has no due_date, assume it's a history record
                                    $isOverdue = false;
                                    $statusLabel = 'Returned';
                                    $statusClass = 'success';

                                    if (!$isReturned) {
                                        $statusLabel = 'Active';
                                        $statusClass = 'primary';
                                        if (!empty($record['due_date'])) {
                                            try {
                                                $now = new \DateTime();
                                                $due = new \DateTime($record['due_date']);
                                                $diff = (int) $now->diff($due)->format('%r%a');
                                                if ($diff < 0) {
                                                    $isOverdue = true;
                                                    $statusLabel = 'Overdue';
                                                    $statusClass = 'danger';
                                                } elseif ($diff <= 7) {
                                                    $statusLabel = 'Due Soon';
                                                    $statusClass = 'warning';
                                                }
                                            } catch (\Exception $e) { /* ignore */
                                            }
                                        }
                                    }
                                    ?>
                                    <tr class="<?= $isOverdue ? 'table-danger' : ($isReturned ? 'table-light' : '') ?>">
                                        <td><?= esc($record['ref'] ?? 'N/A') ?></td>
                                        <td><strong><?= esc($record['borrower_name'] ?: $record['user_id']) ?></strong>
                                            <div class="text-muted small"><?= esc($record['id_number'] ?? '') ?></div>
                                        </td>
                                        <td><?= esc($equip['name'] ?? 'Unknown Equipment') ?></td>
                                        <td><?= esc($record['date_borrowed']) ?></td>
                                        <td><?= esc($record['due_date'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= esc($statusLabel) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                // Pagination logic
                $pages = max(1, (int) ceil(($totalFiltered ?? count($reportData)) / ($perPage ?? 10)));
                $cur = $page ?? 1;
                ?>
                <?php if ($pages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination pagination-sm">
                            <?php
                            $baseQs = $_GET; // Use current GET params
                            for ($p = 1; $p <= $pages; $p++):
                                $qs = $baseQs;
                                $qs['page'] = $p;
                                $link = base_url('reports') . '?' . http_build_query($qs);
                                ?>
                                <li class="page-item <?= $p == $cur ? 'active' : '' ?>"><a class="page-link"
                                        href="<?= $link ?>"><?= $p ?></a></li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
