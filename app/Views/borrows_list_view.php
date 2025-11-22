<div class="py-4">
    <h2>Borrowed Items</h2>
    <?php
    // Ensure view variables have sensible defaults so this view is robust
    $q = $q ?? '';
    $status = $status ?? 'all';
    $borrows = $borrows ?? [];
    $equipment_items = $equipment_items ?? [];
    $perPage = $perPage ?? 6;
    $page = $page ?? 1;
    $totalFiltered = $totalFiltered ?? count($borrows);
    ?>

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card p-3">
                <div class="h5">Active</div>
                <div class="display-6"><?= esc($stats['active'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="h5">Overdue</div>
                <div class="display-6 text-danger"><?= esc($stats['overdue'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="h5">Due Soon</div>
                <div class="display-6 text-warning"><?= esc($stats['pending'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="h5">History</div>
                <div class="display-6"><?= esc($stats['totalHistory'] ?? 0) ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <form method="get" class="row g-2">
                    <div class="col-md-4">
                        <input type="search" name="q" class="form-control"
                            placeholder="Search by ref, equipment, borrower or ID" value="<?= esc($q ?? '') ?>" />
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="all">Any</option>
                            <option value="active" <?= isset($status) && $status === 'active' ? 'selected' : '' ?>>Active
                                (not due soon)</option>
                            <option value="due_soon" <?= isset($status) && $status === 'due_soon' ? 'selected' : '' ?>>Due
                                Soon (≤7 days)</option>
                            <option value="overdue" <?= isset($status) && $status === 'overdue' ? 'selected' : '' ?>>
                                Overdue</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="search" name="avail_q" class="form-control"
                            placeholder="Search available equipment"
                            value="<?= esc(isset($_GET['avail_q']) ? $_GET['avail_q'] : '') ?>" />
                    </div>
                    <div class="col-md-2">
                        <select name="avail_category" class="form-select">
                            <option value="all">All Categories</option>
                            <?php
                            $avail_categories = [];
                            foreach ($equipment_items as $c_ei) {
                                if (!empty($c_ei['category']))
                                    $avail_categories[$c_ei['category']] = true;
                            }
                            foreach ($avail_categories as $cat => $_):
                                ?>
                                <option value="<?= esc($cat) ?>" <?= isset($_GET['avail_category']) && $_GET['avail_category'] === $cat ? 'selected' : '' ?>><?= esc($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>
            <?php if (!empty($equipment_items)): ?>
                <?php
                $avail_q = isset($_GET['avail_q']) ? trim((string) $_GET['avail_q']) : '';
                $avail_category = isset($_GET['avail_category']) ? trim((string) $_GET['avail_category']) : 'all';
                $avail_categories = [];
                foreach ($equipment_items as $c_ei) {
                    if (!empty($c_ei['category']))
                        $avail_categories[$c_ei['category']] = true;
                }

                // Build filtered available items to compute counts and support pagination later
                $availableItems = [];
                $totalAvailable = 0;
                foreach ($equipment_items as $c_ei) {
                    if (isset($c_ei['status']) && strtolower($c_ei['status']) !== 'available')
                        continue;
                    $totalAvailable++;
                    $hay = strtolower(($c_ei['name'] ?? '') . ' ' . ($c_ei['equipment_id'] ?? '') . ' ' . ($c_ei['description'] ?? ''));
                    if ($avail_q !== '' && stripos($hay, strtolower($avail_q)) === false)
                        continue;
                    if ($avail_category !== '' && $avail_category !== 'all' && strtolower(($c_ei['category'] ?? '')) !== strtolower($avail_category))
                        continue;
                    $availableItems[] = $c_ei;
                }
                // Pagination for available items (separate param: avail_page)
                $availPerPage = 6;
                $availPage = isset($_GET['avail_page']) ? (int) $_GET['avail_page'] : 1;
                if ($availPage < 1)
                    $availPage = 1;
                $availTotalFiltered = count($availableItems);
                $availPages = max(1, (int) ceil($availTotalFiltered / $availPerPage));
                if ($availPage > $availPages)
                    $availPage = $availPages;
                $availOffset = ($availPage - 1) * $availPerPage;
                $availablePageItems = array_slice($availableItems, $availOffset, $availPerPage);
                $avail_shown = count($availablePageItems);
                ?>

                <div class="mb-3">
                    <h5>Available Equipment</h5>
                    <div class="small text-muted">Showing <?= $avail_shown ?> of <?= $totalAvailable ?> available items
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Equipment ID</th>
                            <th>Equipment</th>
                            <th>Category</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($availablePageItems as $ei): ?>
                            <tr>
                                <td><?= esc($ei['equipment_id'] ?? $ei['id']) ?></td>
                                <td><strong><?= esc($ei['name'] ?? '') ?></strong>
                                    <div class="text-muted small"><?= esc($ei['description'] ?? '') ?></div>
                                </td>
                                <td><?= esc($ei['category'] ?? '') ?></td>
                                <td>
                                    <a href="<?= base_url('borrowing/create/' . $ei['id']) ?>"
                                        class="btn btn-sm btn-outline-success">Borrow</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($availPages > 1): ?>
                    <nav class="mt-2">
                        <ul class="pagination pagination-sm">
                            <?php
                            $baseQs = $_GET;
                            for ($p = 1; $p <= $availPages; $p++):
                                $qs = $baseQs;
                                $qs['avail_page'] = $p;
                                $link = base_url('borrowing') . '?' . http_build_query($qs);
                                ?>
                                <li class="page-item <?= $p == $availPage ? 'active' : '' ?>"><a class="page-link"
                                        href="<?= $link ?>"><?= $p ?></a></li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                <?php // close available-items wrapper if block ?>
            <?php endif; ?>

            <?php if (empty($borrows)): ?>
                <div class="p-4">
                    <p class="lead">No active borrows.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ref</th>
                            <th>Equipment</th>
                            <th>Location</th>
                            <th>Borrower</th>
                            <th>Borrowed</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrows as $b): ?>
                            <tr>
                                <td><?= $b['id'] ?></td>
                                <td><?= esc($b['ref'] ?? '') ?></td>
                                <td>
                                    <?php
                                    $equipLabel = esc($b['equipment_id']);
                                    $equipLocation = '';
                                    if (!empty($equipment_items) && isset($equipment_items[$b['equipment_id']])) {
                                        $ei = $equipment_items[$b['equipment_id']];
                                        $equipLabel = esc(($ei['name'] ?? $ei['equipment_id'] ?? $ei['id']) . ' (' . ($ei['equipment_id'] ?? $ei['id']) . ')');
                                        $equipLocation = esc($ei['location'] ?? '');
                                    }
                                    ?>
                                    <?= $equipLabel ?>
                                </td>
                                <td><?= $equipLocation ?></td>
                                <td><?= esc($b['borrower_name'] ?: $b['user_id']) ?>
                                    <div class="text-muted small"><?= esc($b['id_number'] ?? '') ?></div>
                                </td>
                                <td><?= esc($b['date_borrowed']) ?></td>
                                <td><?= esc($b['due_date'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <nav class="mt-3">
                    <ul class="pagination">
                        <?php
                        $baseQs = [];
                        if (!empty($q))
                            $baseQs['q'] = $q;
                        if (!empty($status) && $status !== 'all')
                            $baseQs['status'] = $status;
                        $pages = max(1, (int) ceil(($totalFiltered ?? count($borrows ?? [])) / ($perPage ?? 6)));
                        $cur = $page ?? 1;
                        for ($p = 1; $p <= $pages; $p++):
                            $qs = $baseQs;
                            $qs['page'] = $p;
                            $link = base_url('borrowing') . '?' . http_build_query($qs);
                            ?>
                            <li class="page-item <?= $p == $cur ? 'active' : '' ?>"><a class="page-link"
                                    href="<?= $link ?>"><?= $p ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

</div>
