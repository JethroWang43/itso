<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Equipment Management</h2>
        <a class="btn btn-primary" href="<?= base_url('equipment/add') ?>">+ Add Equipment</a>
    </div>

    

    <div class="card">
        <div class="card-body p-0">
            <div class="p-3">
                <form method="get" class="row g-2">
                    <div class="col-md-6">
                        <input type="search" name="q" class="form-control" placeholder="Search by ID, name, description or category" value="<?= esc($q ?? '') ?>" />
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="all">Any status</option>
                            <option value="available" <?= isset($status) && $status==='available' ? 'selected' : '' ?>>Available</option>
                            <option value="borrowed" <?= isset($status) && $status==='borrowed' ? 'selected' : '' ?>>Borrowed</option>
                            <option value="reserved" <?= isset($status) && $status==='reserved' ? 'selected' : '' ?>>Reserved</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="all">All categories</option>
                            <option value="laptops" <?= isset($category) && $category==='laptops' ? 'selected' : '' ?>>Laptops</option>
                            <option value="dlp" <?= isset($category) && $category==='dlp' ? 'selected' : '' ?>>DLP</option>
                            <option value="hdmi_cable" <?= isset($category) && $category==='hdmi_cable' ? 'selected' : '' ?>>HDMI Cables</option>
                            <option value="vga_cable" <?= isset($category) && $category==='vga_cable' ? 'selected' : '' ?>>VGA Cables</option>
                            <option value="dlp_remote" <?= isset($category) && $category==='dlp_remote' ? 'selected' : '' ?>>DLP Remotes</option>
                            <option value="keyboard_mouse" <?= isset($category) && $category==='keyboard_mouse' ? 'selected' : '' ?>>Keyboard & Mouse</option>
                            <option value="wacom" <?= isset($category) && $category==='wacom' ? 'selected' : '' ?>>Wacom Tablets</option>
                            <option value="speaker_sets" <?= isset($category) && $category==='speaker_sets' ? 'selected' : '' ?>>Speaker Sets</option>
                            <option value="webcams" <?= isset($category) && $category==='webcams' ? 'selected' : '' ?>>Webcams</option>
                            <option value="extension_cords" <?= isset($category) && $category==='extension_cords' ? 'selected' : '' ?>>Extension Cords</option>
                            <option value="cable_crimping_tools" <?= isset($category) && $category==='cable_crimping_tools' ? 'selected' : '' ?>>Cable Crimping Tools</option>
                            <option value="cable_testers" <?= isset($category) && $category==='cable_testers' ? 'selected' : '' ?>>Cable Testers</option>
                            <option value="lab_room_keys" <?= isset($category) && $category==='lab_room_keys' ? 'selected' : '' ?>>Lab Room Keys</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Equipment ID</th>
                        <th>Equipment Name</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="6" class="text-center">No equipment found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><input type="checkbox" /></td>
                                <td><?= esc($item['equipment_id']) ?></td>
                                <td>
                                    <strong><?= esc($item['name']) ?></strong>
                                    <div class="text-muted small"><?= esc($item['description']) ?></div>
                                </td>
                                <td><?= esc($item['category']) ?></td>
                                <td>
                                    <?php $st = strtolower($item['status'] ?? ''); ?>
                                    <?php if ($st === 'available'): ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php elseif ($st === 'borrowed'): ?>
                                        <span class="badge bg-warning text-dark">Borrowed</span>
                                    <?php elseif ($st === 'maintenance' || $st === 'reserved'): ?>
                                        <span class="badge bg-danger">Reserved</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc(ucfirst($item['status'] ?? 'Unknown')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('equipment/view/'.$item['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="<?= base_url('equipment/edit/'.$item['id']) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <a href="<?= base_url('equipment/delete/'.$item['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this item?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php
            // Define the maximum number of page links to show
            $max_links = 5;

            // Set current page, defaulting to 1
            $current_page = $page ?? 1;

            // Calculate the total number of pages
            $total_items = ($totalFiltered ?? $total) ?? 0;
            $per_page_count = $perPage ?? 6;
            $pages = max(1, (int) ceil($total_items / $per_page_count));

            // --------------------------------------------------------
            // LOGIC TO DETERMINE THE START AND END PAGE NUMBERS
            // --------------------------------------------------------

            // 1. Determine the ideal starting point (current page minus half the max links, rounded down)
            $p_start = max(1, $current_page - floor($max_links / 2));

            // 2. Determine the ending point, capped by the total number of pages
            $p_end = min($pages, $p_start + $max_links - 1);

            // 3. Re-adjust the start point if the end point was capped by $pages (This ensures exactly $max_links are shown if possible)
            $p_start = max(1, $p_end - $max_links + 1);

            // --------------------------------------------------------
            // Build base query preserving filters
            $baseQs = [];
            if (! empty($q)) $baseQs['q'] = $q;
            if (! empty($category) && $category !== 'all') $baseQs['category'] = $category;
            if (! empty($status) && $status !== 'all') $baseQs['status'] = $status;
            ?>

            <!-- Previous Button -->
            <?php if ($current_page > 1):
                $qs = $baseQs; $qs['page'] = $current_page - 1;
                $link = base_url('equipment') . '?' . http_build_query($qs);
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

            <!-- Page Links (Max 5 shown) -->
            <?php for ($p = $p_start; $p <= $p_end; $p++):
                $qs = $baseQs; $qs['page'] = $p;
                $link = base_url('equipment') . '?' . http_build_query($qs);
            ?>
                <li class="page-item <?= $p == $current_page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $link ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>

            <!-- Next Button -->
            <?php if ($current_page < $pages):
                $qs = $baseQs; $qs['page'] = $current_page + 1;
                $link = base_url('equipment') . '?' . http_build_query($qs);
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
</div>
