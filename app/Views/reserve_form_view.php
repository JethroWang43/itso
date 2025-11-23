<div class="py-4">

    <h2>Reserve Equipment</h2>

    <div class="card p-3 mb-3">
        <h5><?= $equipment['name'] ?? 'Selected item' ?>
            <small class="text-muted"><?= $equipment['equipment_id'] ?? '' ?></small>
        </h5>
    </div>

    <!-- VALIDATION ERRORS -->
    <?php if (session()->has('errors')): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach (session('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('reservations/submit') ?>">
        <input type="hidden" name="equipment_id" value="<?= esc($equipmentId) ?>" />

        <div class="mb-3">
            <label class="form-label">Choose Student (optional)</label>
            <select name="user_id" id="reserve_user_select" class="form-select">
                <option value="">-- Select existing student --</option>
                <?php if (!empty($users)):
                    foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= old('user_id') == $u['id'] ? 'selected' : '' ?>>
                            <?= esc($u['fullname'] ?: $u['username']) ?>
                        </option>
                    <?php endforeach; endif; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" id="reserve_name" value="<?= old('name') ?>" list="reserve_students"
                autocomplete="off" />
            <datalist id="reserve_students">
                <?php if (!empty($users)):
                    foreach ($users as $u): ?>
                        <option value="<?= esc($u['fullname'] ?: $u['username']) ?>"></option>
                    <?php endforeach; endif; ?>
            </datalist>
        </div>

        <div class="mb-3">
            <label class="form-label">ID Number</label>
            <input class="form-control" name="id_number" id="reserve_id_number" value="<?= old('id_number') ?>"
                placeholder="202311220" />
        </div>

        <div class="mb-3">
            <label class="form-label">Bring To (Location / Room)</label>
            <input class="form-control" name="use_location" id="reserve_use_location" value="<?= old('use_location') ?>"
                placeholder="e.g. Lab A - Room 101" />
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Reservation Date</label>
                <input class="form-control" type="date" name="reserve_date"
                    value="<?= old('reserve_date', date('Y-m-d')) ?>" />
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Reservation Time</label>
                <input class="form-control" type="time" name="reserve_time"
                    value="<?= old('reserve_time', date('H:i')) ?>" />
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Due Date (return by)</label>
            <input class="form-control" type="date" name="due_date"
                value="<?= old('due_date', date('Y-m-d', strtotime('+7 days'))) ?>" />
            <div class="form-text">Optional — date the equipment is expected to be returned.</div>
        </div>

        <button class="btn btn-primary" type="submit">Reserve</button>
        <a class="btn btn-secondary" href="<?= base_url('equipment') ?>">Cancel</a>
    </form>
</div>

<script>
    const usersMeta = <?= json_encode($users_meta ?? []) ?>;

    function findUserIdByName(name) {
        name = (name || '').toLowerCase().trim();
        for (const k in usersMeta) {
            if ((usersMeta[k].name || '').toLowerCase() === name)
                return usersMeta[k].id;
        }
        return null;
    }

    document.getElementById('reserve_user_select')?.addEventListener('change', function () {
        const uid = this.value;
        if (!uid) return;
        const meta = usersMeta[uid];
        if (meta) {
            document.getElementById('reserve_name').value = meta.name || '';
            document.getElementById('reserve_id_number').value = meta.studentId || '';
        }
    });

    document.getElementById('reserve_name')?.addEventListener('input', function () {
        const val = this.value;
        const uid = findUserIdByName(val);
        if (uid) {
            const meta = usersMeta[uid] || {};
            document.getElementById('reserve_id_number').value = meta.studentId || '';
            const sel = document.getElementById('reserve_user_select');
            if (sel) sel.value = uid;
        }
    });
</script>
