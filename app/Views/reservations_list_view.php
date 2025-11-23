<div class="py-4">
    <h2>Reservations</h2>

    <div class="card mb-3">
        <div class="card-body">
            <?php $active = is_array($reservations) ? count($reservations) : 0; ?>
            <div class="h5">Active Reservations</div>
            <div class="display-6"><?= esc($active) ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (!empty($equipment_items)): ?>
                <?php
                $avail_q = isset($_GET['avail_q']) ? trim((string) $_GET['avail_q']) : '';
                $avail_category = isset($_GET['avail_category']) ? trim((string) $_GET['avail_category']) : 'all';
                $avail_categories = [];
                foreach ($equipment_items as $c_ei) {
                    if (!empty($c_ei['category']))
                        $avail_categories[$c_ei['category']] = true;
                }

                // Build filtered available items so we can show counts and support pagination later
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
                    <form method="get" class="row g-2 mt-2">
                        <div class="col-md-5">
                            <input type="search" name="avail_q" class="form-control"
                                placeholder="Search available equipment" value="<?= esc($avail_q) ?>" />
                        </div>
                        <div class="col-md-4">
                            <select name="avail_category" class="form-select">
                                <option value="all">All Categories</option>
                                <?php foreach ($avail_categories as $cat => $_): ?>
                                    <option value="<?= esc($cat) ?>" <?= $avail_category === $cat ? 'selected' : '' ?>>
                                        <?= esc($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-sm btn-outline-primary w-100">Filter Available</button>
                        </div>
                    </form>
                    <div class="small text-muted mt-2">Showing <?= $avail_shown ?> of <?= $totalAvailable ?> available items
                    </div>
                </div>

                <div class="table-responsive">
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
                                        <a href="<?= base_url('reservations/create/' . $ei['id']) ?>"
                                            class="btn btn-sm btn-outline-primary">Reserve</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($availPages > 1): ?>
                    <nav class="mt-2">
                        <ul class="pagination pagination-sm">
                            <?php
                            // AVAILABLE EQUIPMENT PAGINATION LOGIC (Max 5 links)
                            $baseQs = $_GET;
                            $cur = $availPage;
                            $maxPagesToShow = 5;

                            // Calculate start and end page for the sliding window
                            $startPage = max(1, $cur - floor($maxPagesToShow / 2));
                            $endPage = min($availPages, $startPage + $maxPagesToShow - 1);

                            // Adjust startPage if we hit the end bound
                            if (($endPage - $startPage + 1) < $maxPagesToShow) {
                                $startPage = max(1, $endPage - $maxPagesToShow + 1);
                            }

                            // Previous button
                            if ($cur > 1) {
                                $qs = $baseQs;
                                $qs['avail_page'] = $cur - 1;
                                $link = base_url('reservations') . '?' . http_build_query($qs);
                                echo '<li class="page-item"><a class="page-link" href="' . $link . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
                            }

                            // Loop through the calculated page range
                            for ($p = $startPage; $p <= $endPage; $p++):
                                $qs = $baseQs;
                                $qs['avail_page'] = $p;
                                $link = base_url('reservations') . '?' . http_build_query($qs);
                                ?>
                                <li class="page-item <?= $p == $cur ? 'active' : '' ?>"><a class="page-link"
                                        href="<?= $link ?>"><?= $p ?></a></li>
                            <?php endfor; ?>

                            <?php
                            // Next button
                            if ($cur < $availPages) {
                                $qs = $baseQs;
                                $qs['avail_page'] = $cur + 1;
                                $link = base_url('reservations') . '?' . http_build_query($qs);
                                echo '<li class="page-item"><a class="page-link" href="' . $link . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
                            }
                            ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            // Active Reservations Pagination Variables
            $perPage = 6;
            $totalFiltered = is_array($reservations) ? count($reservations) : 0;
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $pages = max(1, (int) ceil($totalFiltered / $perPage));
            if ($page < 1)
                $page = 1;
            if ($page > $pages)
                $page = $pages;
            $offset = ($page - 1) * $perPage;
            $pagedReservations = array_slice($reservations ?? [], $offset, $perPage);
            ?>


            <?php if (empty($reservations)): ?>
                <div class="p-4">
                    <p class="lead">No active reservations.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Equipment</th>
                                <th>Name</th>
                                <th>ID</th>
                                <th>Date Reserved</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagedReservations as $r): // Use paged results ?>
                                <tr>
                                    <td><?= $r['id'] ?></td>
                                    <td>
                                        <?php
                                        $equipLabel = esc($r['equipment_id']);
                                        if (!empty($equipment_items) && isset($equipment_items[$r['equipment_id']])) {
                                            $ei = $equipment_items[$r['equipment_id']];
                                            $equipLabel = esc(($ei['name'] ?? $ei['equipment_id'] ?? $ei['id']) . ' (' . ($ei['equipment_id'] ?? $ei['id']) . ')');
                                        }
                                        ?>
                                        <?= $equipLabel ?>
                                    </td>
                                    <td><?= esc($r['name']) ?></td>
                                    <td><?= esc($r['id_number']) ?></td>
                                    <td>
                                        <?= esc($r['reserved_for'] ?? $r['date_reserved']) ?>
                                    </td>
                                    <td><?= esc($r['use_location'] ?? '') ?></td>
                                    <td>
                                        <a href="<?= base_url('reservations/borrow/' . $r['id']) ?>"
                                            class="btn btn-sm btn-primary me-1"
                                            onclick="return confirm('Claim and mark this reservation as borrowed?')">Borrow</a>
                                        <a href="<?= base_url('reservations/reschedule/' . $r['id']) ?>"
                                            class="btn btn-sm btn-outline-secondary me-1">Reschedule</a>
                                        <a href="<?= base_url('reservations/cancel/' . $r['id']) ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Cancel this reservation?')">Cancel</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Active Reservations SLIDING PAGINATION (Max 5 links) -->
                <?php if ($pages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination">
                            <?php
                            // ACTIVE RESERVATIONS PAGINATION LOGIC (Max 5 links)
                            $baseQs = [];
                            // Retain query parameters for filtering available items
                            if ($avail_q !== '')
                                $baseQs['avail_q'] = $avail_q;
                            if ($avail_category !== 'all')
                                $baseQs['avail_category'] = $avail_category;

                            $cur = $page;
                            $maxPagesToShow = 5;

                            // Calculate start and end page for the sliding window
                            $startPage = max(1, $cur - floor($maxPagesToShow / 2));
                            $endPage = min($pages, $startPage + $maxPagesToShow - 1);

                            // Adjust startPage if we hit the end bound
                            if (($endPage - $startPage + 1) < $maxPagesToShow) {
                                $startPage = max(1, $endPage - $maxPagesToShow + 1);
                            }

                            // Previous button
                            if ($cur > 1) {
                                $qs = $baseQs;
                                $qs['page'] = $cur - 1;
                                $link = base_url('reservations') . '?' . http_build_query($qs);
                                echo '<li class="page-item"><a class="page-link" href="' . $link . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
                            }

                            // Loop through the calculated page range
                            for ($p = $startPage; $p <= $endPage; $p++):
                                $qs = $baseQs;
                                $qs['page'] = $p;
                                $link = base_url('reservations') . '?' . http_build_query($qs);
                                ?>
                                <li class="page-item <?= $p == $cur ? 'active' : '' ?>"><a class="page-link"
                                        href="<?= $link ?>"><?= $p ?></a></li>
                            <?php endfor; ?>

                            <?php
                            // Next button
                            if ($cur < $pages) {
                                $qs = $baseQs;
                                $qs['page'] = $cur + 1;
                                $link = base_url('reservations') . '?' . http_build_query($qs);
                                echo '<li class="page-item"><a class="page-link" href="' . $link . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
                            }
                            ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
