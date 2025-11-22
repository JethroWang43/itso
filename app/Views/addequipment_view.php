<div class="py-4">
	<h2>Add Equipment</h2>

	<?php if (session()->getFlashdata('success')): ?>
		<div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
	<?php endif; ?>
	<?php if (session()->getFlashdata('error')): ?>
		<div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
	<?php endif; ?>

	<form method="post" action="<?= base_url('equipment/insert') ?>">

		<div class="mb-3">
			<label class="form-label">Equipment ID (optional)</label>
			<input class="form-control" name="equipment_id" placeholder="EQ-XXX or leave blank to auto-generate"
				value="<?= old('equipment_id') ?>" />
			<?php if (isset($validation) && $validation->hasError('equipment_id')): ?>
				<div class="text-danger small mt-1"><?= esc($validation->getError('equipment_id')) ?></div>
			<?php endif; ?>
		</div>

		<div class="mb-3">
			<label class="form-label">Name</label>
			<input class="form-control" name="name" value="<?= old('name') ?>" />
			<?php if (isset($validation) && $validation->hasError('name')): ?>
				<div class="text-danger small mt-1"><?= esc($validation->getError('name')) ?></div>
			<?php endif; ?>
		</div>

		<div class="mb-3">
			<label class="form-label">Description</label>
			<input class="form-control" name="description" value="<?= old('description') ?>" />
			<?php if (isset($validation) && $validation->hasError('description')): ?>
				<div class="text-danger small mt-1"><?= esc($validation->getError('description')) ?></div>
			<?php endif; ?>
		</div>

		<div class="mb-3">
			<label class="form-label">Category</label>
			<select class="form-select" name="category">
				<?php $old_category = old('category'); ?>
				<option value="laptops" <?= ($old_category == 'laptops' ? 'selected' : '') ?>>Laptops (with charger)
				</option>
				<option value="dlp" <?= ($old_category == 'dlp' ? 'selected' : '') ?>>DLP (with extension cord, VGA/HDMI
					cable, power cable)</option>
				<option value="hdmi_cable" <?= ($old_category == 'hdmi_cable' ? 'selected' : '') ?>>HDMI Cables</option>
				<option value="vga_cable" <?= ($old_category == 'vga_cable' ? 'selected' : '') ?>>VGA Cables</option>
				<option value="dlp_remote" <?= ($old_category == 'dlp_remote' ? 'selected' : '') ?>>DLP Remote Controls
				</option>
				<option value="keyboard_mouse" <?= ($old_category == 'keyboard_mouse' ? 'selected' : '') ?>>Keyboards &amp;
					Mouse (with lightning cable for Mac lab)</option>
				<option value="wacom" <?= ($old_category == 'wacom' ? 'selected' : '') ?>>Wacom Drawing Tablets (with pen)
				</option>
				<option value="speaker_sets" <?= ($old_category == 'speaker_sets' ? 'selected' : '') ?>>Speaker Sets
				</option>
				<option value="webcams" <?= ($old_category == 'webcams' ? 'selected' : '') ?>>Webcams</option>
				<option value="extension_cords" <?= ($old_category == 'extension_cords' ? 'selected' : '') ?>>Extension
					Cords</option>
				<option value="cable_crimping_tools" <?= ($old_category == 'cable_crimping_tools' ? 'selected' : '') ?>>
					Cable Crimping Tools</option>
				<option value="cable_testers" <?= ($old_category == 'cable_testers' ? 'selected' : '') ?>>Cable Testers
				</option>
				<option value="lab_room_keys" <?= ($old_category == 'lab_room_keys' ? 'selected' : '') ?>>Lab Room Keys
				</option>
				<option value="other" <?= ($old_category == 'other' ? 'selected' : '') ?>>Other</option>
			</select>
			<?php if (isset($validation) && $validation->hasError('category')): ?>
				<div class="text-danger small mt-1"><?= esc($validation->getError('category')) ?></div>
			<?php endif; ?>
		</div>

		<div class="mb-3">
			<label class="form-label">Status</label>
			<select class="form-select" name="status">
				<?php $old_status = old('status', 'Available'); ?>
				<option <?= ($old_status == 'Available' ? 'selected' : '') ?>>Available</option>
				<option <?= ($old_status == 'Borrowed' ? 'selected' : '') ?>>Borrowed</option>
				<option <?= ($old_status == 'Maintenance' ? 'selected' : '') ?>>Maintenance</option>
				<option <?= ($old_status == 'Reserved' ? 'selected' : '') ?>>Reserved</option>
			</select>
		</div>

		<div class="mb-3">
			<label class="form-label">Location</label>
			<input class="form-control" name="location" value="<?= old('location', 'ITSO') ?>" />
			<?php if (isset($validation) && $validation->hasError('location')): ?>
				<div class="text-danger small mt-1"><?= esc($validation->getError('location')) ?></div>
			<?php endif; ?>
		</div>

		<button class="btn btn-primary" type="submit">Add Equipment</button>
		<a href="<?= base_url('equipment') ?>" class="btn btn-secondary">Cancel</a>
	</form>
</div>
