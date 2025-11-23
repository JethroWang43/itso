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

    // Available items specific variables (ensure defaults exist for robust logic)
    $availPage = isset($_GET['avail_page']) ? (int) $_GET['avail_page'] : 1;
    $availPerPage = 6; // Set default items per page for available list
    
    // Common pagination limit
    $max_links = 5;
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
                        <ul class="pagination pagination-sm justify-content-center">
                            <?php
                            $baseQs = $_GET;
                            $current_page = $availPage;
                            $pages = $availPages;

                            // LOGIC FOR 5-PAGE WINDOW (Available Items)
                            $p_start = max(1, $current_page - floor($max_links / 2));
                            $p_end = min($pages, $p_start + $max_links - 1);
                            $p_start = max(1, $p_end - $max_links + 1);
                            ?>

                            <!-- Previous Button (Available Items) -->
                            <?php if ($current_page > 1):
                                $qs = $baseQs;
                                $qs['avail_page'] = $current_page - 1;
                                $link = base_url('borrowing') . '?' . http_build_query($qs);
                                ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $link ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&laquo;</span>
                                </li>
                            <?php endif; ?>

                            <!-- Page Links (Available Items) -->
                            <?php for ($p = $p_start; $p <= $p_end; $p++):
                                $qs = $baseQs;
                                $qs['avail_page'] = $p;
                                $link = base_url('borrowing') . '?' . http_build_query($qs);
                                ?>
                                <li class="page-item <?= $p == $current_page ? 'active' : '' ?>"><a class="page-link"
                                        href="<?= $link ?>"><?= $p ?></a></li>
                            <?php endfor; ?>

                            <!-- Next Button (Available Items) -->
                            <?php if ($current_page < $pages):
                                $qs = $baseQs;
                                $qs['avail_page'] = $current_page + 1;
                                $link = base_url('borrowing') . '?' . http_build_query($qs);
                                ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $link ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&raquo;</span>
                                </li>
                            <?php endif; ?>

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
                    <ul class="pagination justify-content-center">
                        <?php
                        // Borrowed Items Pagination Logic
                        $current_page = $page ?? 1;
                        $total_items = $totalFiltered ?? count($borrows ?? []);
                        $per_page_count = $perPage ?? 6;
                        $pages = max(1, (int) ceil($total_items / $per_page_count));

                        $baseQs = [];
                        if (!empty($q))
                            $baseQs['q'] = $q;
                        if (!empty($status) && $status !== 'all')
                            $baseQs['status'] = $status;

                        // LOGIC FOR 5-PAGE WINDOW (Borrowed Items)
                        $p_start = max(1, $current_page - floor($max_links / 2));
                        $p_end = min($pages, $p_start + $max_links - 1);
                        $p_start = max(1, $p_end - $max_links + 1);
                        ?>

                        <!-- Previous Button (Borrowed Items) -->
                        <?php if ($current_page > 1):
                            $qs = $baseQs;
                            $qs['page'] = $current_page - 1;
                            $link = base_url('borrowing') . '?' . http_build_query($qs);
                            ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $link ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-hidden="true">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Page Links (Borrowed Items) -->
                        <?php for ($p = $p_start; $p <= $p_end; $p++):
                            $qs = $baseQs;
                            $qs['page'] = $p;
                            $link = base_url('borrowing') . '?' . http_build_query($qs);
                            ?>
                            <li class="page-item <?= $p == $current_page ? 'active' : '' ?>"><a class="page-link"
                                    href="<?= $link ?>"><?= $p ?></a></li>
                        <?php endfor; ?>

                        <!-- Next Button (Borrowed Items) -->
                        <?php if ($current_page < $pages):
                            $qs = $baseQs;
                            $qs['page'] = $current_page + 1;
                            $link = base_url('borrowing') . '?' . http_build_query($qs);
                            ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $link ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-hidden="true">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

</div>
