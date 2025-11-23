<div class="py-4">
    <h2>Reschedule Reservation</h2>

    <?php if (session()->has('errors')): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach (session('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php
            $r = $reservation;

            // Extract date + time safely
            $raw = $r['reserved_for'] ?? '';
            $dt = strtotime($raw);

            $theDate = old('reserve_date', $dt ? date('Y-m-d', $dt) : date('Y-m-d'));
            $theTime = old('reserve_time', $dt ? date('H:i', $dt) : date('H:i'));
            ?>

            <form method="post" action="<?= base_url('reservations/rescheduleSubmit') ?>">
                <input type="hidden" name="id" value="<?= esc($r['id']) ?>" />

                <div class="mb-3">
                    <label class="form-label">Equipment</label>
                    <div class="form-control-plaintext">
                        <?= esc($equipment_label ?? ($r['equipment_id'] ?? '')) ?>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Reserve Date</label>
                        <input type="date" name="reserve_date" class="form-control" value="<?= esc($theDate) ?>" />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Reserve Time</label>
                        <input type="time" name="reserve_time" class="form-control" value="<?= esc($theTime) ?>" />
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="<?= base_url('reservations') ?>" class="btn btn-secondary me-2">
                        Cancel
                    </a>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
