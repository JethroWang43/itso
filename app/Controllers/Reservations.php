<?php
namespace App\Controllers;

class Reservations extends BaseController
{
    protected function ensureReservations()
    {
        $session = session();
        if (!$session->has('reservations')) {
            $session->set('reservations', []);
            $session->set('reservations_next_id', 1);
        }
    }

    /**
     * Custom validation rule to check if a date is today or in the future.
     * Used for reserve_date and due_date fields.
     *
     * @param string $str The date string to validate.
     * @return bool True if the date is valid and today or later.
     */
    public function isTodayOrFuture(string $str): bool
    {
        if (!empty($str)) {
            try {
                $checkDate = new \DateTime($str);
                $today = new \DateTime(date('Y-m-d')); // Today at midnight
                // Compare only the date component
                return $checkDate >= $today;
            } catch (\Exception $e) {
                // Return false if date parsing fails, although valid_date should catch most cases
                return false;
            }
        }

        // If string is empty, another rule (required) should catch it.
        return true;
    }

    public function index()
    {
        $this->ensureReservations();
        $session = session();
        $reservations = $session->get('reservations');

        // Prefer DB `tbequipment` as source of truth for equipment labels
        // If DB is unavailable, fall back to session-stored demo items.
        $equipment = [];
        try {
            $em = new \App\Models\EquipmentModel();
            $dbAll = $em->findAll();
            foreach ($dbAll as $r) {
                $n = $em->normalize($r);
                $equipment[$n['id']] = $n;
            }
        } catch (\Throwable $e) {
            $equipment = $session->get('equipment_items') ?? [];
        }

        // Simple pagination for reservations list (controller-side)
        $perPage = 6;
        $page = (int) ($this->request->getGet('page') ?? 1);
        if ($page < 1)
            $page = 1;
        $all = is_array($reservations) ? array_values($reservations) : [];
        $totalFiltered = count($all);
        $pages = max(1, (int) ceil($totalFiltered / $perPage));
        if ($page > $pages)
            $page = $pages;
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($all, $offset, $perPage);

        $data = [
            'title' => 'Reservations',
            'reservations' => $paged,
            'equipment_items' => $equipment,
            'page' => $page,
            'perPage' => $perPage,
            'totalFiltered' => $totalFiltered,
        ];

        return view('include\\head_view', $data)
            . view('include\\nav_view')
            . view('reservations_list_view', $data)
            . view('include\\foot_view');
    }

    public function create($equipmentId = null)
    {
        $this->ensureReservations();
        $session = session();
        $equipment = $session->get('equipment_items') ?? [];
        $equipmentItem = null;
        if ($equipmentId !== null && isset($equipment[$equipmentId])) {
            $equipmentItem = $equipment[$equipmentId];
        } elseif ($equipmentId !== null) {
            try {
                $em = new \App\Models\EquipmentModel();
                $row = $em->find($equipmentId);
                if ($row)
                    $equipmentItem = $em->normalize($row);
            } catch (\Throwable $e) {
                $equipmentItem = null;
            }
        }

        // load users so the form can offer existing students for quick selection
        $usermodel = model('Users_model');
        $users = [];
        try {
            $users = $usermodel->findAll();
        } catch (\Throwable $e) {
            $users = [];
        }

        // build users_meta: prefer `StudentID` from DB row else fall back to session user_extras
        $user_extras = $session->get('user_extras') ?? [];
        $users_meta = [];
        foreach ($users as $u) {
            $sid = $u['StudentID'] ?? null;
            if (empty($sid) && isset($user_extras[$u['id']]['id_number'])) {
                $sid = $user_extras[$u['id']]['id_number'];
            }
            $users_meta[$u['id']] = [
                'id' => $u['id'],
                'name' => $u['fullname'] ?: $u['username'],
                'studentId' => $sid,
            ];
        }

        $data = [
            'title' => 'Reserve Equipment',
            'equipmentId' => $equipmentId,
            'equipment' => $equipmentItem ?? ($equipment[$equipmentId] ?? null),
            'users' => $users,
            'users_meta' => $users_meta,
            // Ensure validation service is available for the view to display errors
            'validation' => \Config\Services::validation(),
        ];

        return view('include\\head_view', $data)
            . view('include\\nav_view')
            . view('reserve_form_view', $data)
            . view('include\\foot_view');
    }

    public function submit()
    {
        $this->ensureReservations();
        $session = session();

        // ----------------------------
        // VALIDATION RULES
        // ----------------------------
        $rules = [
            'equipment_id' => 'required|integer',
            'name' => 'required|min_length[3]',
            'id_number' => 'required|numeric',
            'use_location' => 'required|min_length[2]',
            'reserve_date' => 'required|valid_date',
            'reserve_time' => 'required',
            'due_date' => 'permit_empty|valid_date'
        ];

        $messages = [
            'name' => [
                'required' => 'Please enter a name.',
                'min_length' => 'Name must be at least 3 characters.'
            ],
            'id_number' => [
                'required' => 'ID Number is required.',
                'numeric' => 'ID Number must be numbers only.'
            ],
            'use_location' => [
                'required' => 'Location is required.',
                'regex_match' => 'Location must follow a format like “Lab A - Room 101”.'
            ],

            'reserve_date' => [
                'required' => 'Pick a reservation date.'
            ],
            'reserve_time' => [
                'required' => 'Pick a reservation time.'
            ]
        ];

        // If validation fails → return with errors + old input
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // ----------------------------
        // PASSED VALIDATION → PROCESS
        // ----------------------------

        $equipmentId = (int) $this->request->getPost('equipment_id');
        $user_id = $this->request->getPost('user_id');
        $name = $this->request->getPost('name');
        $id_number = $this->request->getPost('id_number');
        $use_location = $this->request->getPost('use_location');
        $reserve_date = $this->request->getPost('reserve_date');
        $reserve_time = $this->request->getPost('reserve_time');
        $due_date = $this->request->getPost('due_date');

        $reservations = $session->get('reservations');
        $next = $session->get('reservations_next_id');

        $reserved_for = $reserve_date . ' ' . ($reserve_time ?: '00:00');

        $record = [
            'id' => $next,
            'equipment_id' => $equipmentId,
            'name' => $name,
            'id_number' => $id_number,
            'user_id' => $user_id ?: null,
            'use_location' => $use_location ?: null,
            'date_reserved' => date('Y-m-d H:i:s'),
            'reserved_for' => $reserved_for,
            'due_date' => $due_date ?: null,
        ];

        $reservations[$next] = $record;
        $session->set('reservations', $reservations);
        $session->set('reservations_next_id', $next + 1);

        // Update equipment status
        try {
            if (!empty($equipmentId)) {
                $em = new \App\Models\EquipmentModel();
                $update = ['status' => 'Reserved', 'last_updated' => date('Y-m-d H:i:s')];
                if (!empty($use_location))
                    $update['location'] = $use_location;
                $em->update($equipmentId, $update);
            }
        } catch (\Throwable $e) {
        }

        return redirect()->to('reservations');
    }


    public function cancel($id)
    {
        $this->ensureReservations();
        $session = session();
        $reservations = $session->get('reservations');
        $id = (int) $id;
        if (isset($reservations[$id])) {
            $equipmentId = $reservations[$id]['equipment_id'];
            unset($reservations[$id]);
            $session->set('reservations', $reservations);

            // Also try to update the equipment status back to 'Available' in DB.
            try {
                if (!empty($equipmentId)) {
                    $em = new \App\Models\EquipmentModel();
                    $em->update($equipmentId, ['status' => 'Available', 'last_updated' => date('Y-m-d H:i:s')]);
                }
            } catch (\Throwable $e) {
                // ignore DB errors
            }
        }

        return redirect()->to('reservations');
    }

    /**
     * Convert a reservation into a borrow record (claim and borrow).
     */
    public function borrow($id)
    {
        $this->ensureReservations();
        $session = session();

        $reservations = $session->get('reservations') ?? [];
        $id = (int) $id;
        if (!isset($reservations[$id])) {
            return redirect()->to('reservations');
        }

        $res = $reservations[$id];
        $equipmentId = $res['equipment_id'] ?? null;

        // Create a borrow record in session (mirror Borrowing::submit behavior)
        if (!$session->has('borrows')) {
            $session->set('borrows', []);
            $session->set('borrows_next_id', 1);
            $session->set('borrow_history', []);
        }

        $borrows = $session->get('borrows') ?? [];
        $next = $session->get('borrows_next_id');
        $year = date('Y');
        $ref = sprintf('BR-%s-%03d', $year, $next);

        $record = [
            'id' => $next,
            'ref' => $ref,
            'equipment_id' => $equipmentId,
            'user_id' => null,
            'borrower_name' => $res['name'] ?? null,
            'id_number' => $res['id_number'] ?? null,
            'borrower_email' => null,
            'date_borrowed' => date('Y-m-d'),
            // Prefer explicit due_date from reservation form; else derive from reserved_for (date part)
            'due_date' => !empty($res['due_date']) ? $res['due_date'] : (!empty($res['reserved_for']) ? date('Y-m-d', strtotime($res['reserved_for'])) : null),
        ];

        $borrows[$next] = $record;
        $session->set('borrows', $borrows);
        $session->set('borrows_next_id', $next + 1);

        // Update equipment status to Borrowed in DB
        try {
            if (!empty($equipmentId)) {
                $em = new \App\Models\EquipmentModel();
                $em->update($equipmentId, ['status' => 'Borrowed', 'last_updated' => date('Y-m-d H:i:s')]);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Remove the reservation (claimed)
        unset($reservations[$id]);
        $session->set('reservations', $reservations);

        session()->setFlashdata('success', 'Reservation claimed and marked as borrowed.');

        return redirect()->to('borrowing');
    }

    /**
     * Show a reschedule form for an existing reservation.
     */
    public function reschedule($id)
    {
        $this->ensureReservations();
        $session = session();
        $reservations = $session->get('reservations') ?? [];
        $id = (int) $id;
        if (!isset($reservations[$id])) {
            return redirect()->to('reservations');
        }

        $res = $reservations[$id];

        // Load equipment labels: prefer DB `tbequipment`, fall back to session items
        $equipment = [];
        try {
            $em = new \App\Models\EquipmentModel();
            $dbAll = $em->findAll();
            foreach ($dbAll as $r2) {
                $n = $em->normalize($r2);
                $equipment[$n['id']] = $n;
            }
        } catch (\Throwable $e) {
            $equipment = $session->get('equipment_items') ?? [];
        }

        $equipment_label = $res['equipment_id'] ?? '';
        if (!empty($equipment) && isset($equipment[$res['equipment_id']])) {
            $ei = $equipment[$res['equipment_id']];
            $equipment_label = ($ei['name'] ?? ($ei['equipment_id'] ?? $ei['id']));
        }

        // Pass validation service for reschedule form errors
        $validation = \Config\Services::validation();

        $data = [
            'title' => 'Reschedule Reservation',
            'reservation' => $res,
            'equipment_label' => $equipment_label,
            'validation' => $validation,
        ];

        return view('include\\head_view', $data)
            . view('include\\nav_view')
            . view('reschedule_form_view', $data)
            . view('include\\foot_view');
    }

    /**
     * Handle reschedule form submit
     */
    public function rescheduleSubmit()
    {
        $this->ensureReservations();
        $session = session();

        // VALIDATION RULES
        $rules = [
            'reserve_date' => 'required|valid_date|isTodayOrFuture',
            'reserve_time' => 'required|validTime'
        ];

        $messages = [
            'reserve_date' => [
                'required' => 'Please choose a reservation date.',
                'valid_date' => 'Invalid date format.',
                'isTodayOrFuture' => 'Reservation date cannot be in the past.'
            ],
            'reserve_time' => [
                'required' => 'Please choose a reservation time.',
                'validTime' => 'Time must be in HH:MM 24-hour format.'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $id = (int) $this->request->getPost('id');
        $reserve_date = $this->request->getPost('reserve_date');
        $reserve_time = $this->request->getPost('reserve_time');

        $reservations = $session->get('reservations') ?? [];

        if (!isset($reservations[$id])) {
            session()->setFlashdata('error', 'Reservation not found.');
            return redirect()->to('reservations');
        }

        $reserved_for = $reserve_date . ' ' . ($reserve_time ?? '00:00');

        $reservations[$id]['reserved_for'] = $reserved_for;
        $reservations[$id]['date_reserved'] = date('Y-m-d H:i:s');

        $session->set('reservations', $reservations);

        session()->setFlashdata('success', 'Reservation rescheduled.');

        return redirect()->to('reservations');
    }

}
