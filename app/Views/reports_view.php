<div class="container-fluid pt-4">
    <div class="row">
        <div class="col">
            <h1 class="mb-4"><?= esc($title) ?></h1>

            <?= session()->getFlashdata('success') ?>
            <?= session()->getFlashdata('error') ?>

            <p>Summary of System Metrics:</p>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Equipment</h5>
                            <p class="card-text display-4"><?= $total_equipment ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Active Borrows</h5>
                            <p class="card-text display-4"><?= $active_borrows ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Active Reservations</h5>
                            <p class="card-text display-4"><?= $active_reservations ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-secondary">
                        <div class="card-body">
                            <h5 class="card-title">Total Users</h5>
                            <p class="card-text display-4"><?= $total_users ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="mt-4">Equipment Status Breakdown</h3>
            <ul class="list-group">
                <?php foreach ($equipment_status as $status => $count): ?>
                    <a href="<?= base_url('equipment?status=' . urlencode($status)) ?>"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <?= esc($status) ?>
                        <span class="badge bg-primary rounded-pill"><?= $count ?></span>
                    </a>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
