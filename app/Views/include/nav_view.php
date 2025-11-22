<nav class="bg-light border-end vh-100" style="width:260px;">
    <div class="p-3">
        <a class="d-block mb-3 fs-4 text-decoration-none" href="<?= base_url('main') ?>">TW32</a>

        <ul class="nav nav-pills flex-column mb-3">
            <li class="nav-item mb-1">
                <a class="nav-link" href="<?= base_url('main') ?>">Dashboard</a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link" href="<?= base_url('equipment') ?>">Equipment</a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link" href="<?= base_url('borrowing') ?>">Borrowing</a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link" href="<?= base_url('returns') ?>">Returns</a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link" href="<?= base_url('reservations') ?>">Reservations</a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link" href="<?= base_url('users') ?>">Users</a>
            </li>
            <li class="nav-item mb-1">
                <a href="<?= base_url('reports') ?>"
                    class="nav-link <?= (url_is('reports*') ? 'active' : '') ?>">Reports</a>
            </li>
        </ul>

        <div class="mt-4 small text-muted">Logged in as: Demo User</div>
    </div>
</nav>

<div class="flex-fill p-3" role="main">
